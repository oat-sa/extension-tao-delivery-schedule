<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDeliverySchedule\model;

use oat\oatbox\user\User;
use oat\taoFrontOffice\model\interfaces\DeliveryExecution;
use oat\oatbox\service\ServiceManager;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use core_kernel_classes_Class;
use taoDelivery_models_classes_execution_ServiceProxy;
use common_Logger;

/**
 * Service to manage the execution of deliveries
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class DeliveryServerService extends \taoDelivery_models_classes_DeliveryServerService
{
    const CONFIG_ID = 'taoDelivery/deliveryServer';

    private $deliverySettings = [];
    private $deliveryProperties = [];

    public function getDeliverySettings(core_kernel_classes_Resource $delivery, User $user = null)
    {
        $user = null;
        if (isset($this->deliverySettings[$delivery->getUri()])) {
            return $this->deliverySettings[$delivery->getUri()];
        }

        $repeatedDeliveryService =  $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        $assignmentService =  $this->getServiceManager()->get(AssignmentService::CONFIG_ID);

        if ($repeatedDeliveryService->isRepeated($delivery)) {
            $repeatedDelivery = $delivery;
            $delivery = $repeatedDeliveryService->getParentDelivery($delivery);
        }

        $settings = $this->getDeliveryProperties($delivery);

        if ($settings[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP]) {
            $settings['TAO_DELIVERY_REPETITIONS'] = [];
            $rEvents = $repeatedDeliveryService->getRecurrenceCollection($delivery);

            foreach ($rEvents as $numberOfRepetition => $rEvent) {
                $deliveryRepetitionSettings = [
                    TAO_DELIVERY_START_PROP => $rEvent->getStart()->getTimestamp(),
                    TAO_DELIVERY_END_PROP => $rEvent->getEnd()->getTimestamp(),
                    RepeatedDeliveryService::PROPERTY_NUMBER_OF_REPETITION => $numberOfRepetition,
                ];

                $deliveryRepetition = $repeatedDeliveryService->getDelivery($delivery, $numberOfRepetition);

                if ($deliveryRepetition) {
                    //if user is not assigned to the repeated delivery or excluded
                    if ($user !== null && !$assignmentService->isUserAssigned($deliveryRepetition, $user)) {
                        continue;
                    }
                    $deliveryRepetitionSettings[RepeatedDeliveryService::CLASS_URI] = $deliveryRepetition->getUri();
                } else {
                    //if user is not assigned to the main delivery or excluded
                    if ($user !== null && !$assignmentService->isUserAssigned($delivery, $user, false)) {
                        continue;
                    }
                }

                $settings['TAO_DELIVERY_REPETITIONS'][$numberOfRepetition] = $deliveryRepetitionSettings;
            }
        }

        $this->deliverySettings[isset($repeatedDelivery) ? $repeatedDelivery->getUri() : $delivery->getUri()] = $settings;

        return $settings;
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     */
    public function isDeliveryExecutionAllowed(core_kernel_classes_Resource $delivery, User $user)
    {
        $userUri = $user->getIdentifier();
        if (is_null($delivery)) {
            common_Logger::w("Attempt to start the compiled delivery ".$delivery->getUri(). " related to no delivery");
            return false;
        }

        //first check the user is assigned
        $serviceManager = ServiceManager::getServiceManager();
        if(!$serviceManager->get(AssignmentService::CONFIG_ID)->isUserAssigned($delivery, $user)){
            common_Logger::w("User ".$userUri." attempts to start the compiled delivery ".$delivery->getUri(). " he was not assigned to.");
            return false;
        }

        $properties = $this->getDeliveryProperties($delivery);

        //check Tokens
        $usedTokens = count(taoDelivery_models_classes_execution_ServiceProxy::singleton()->getUserExecutions($delivery, $userUri));

        if (($properties[TAO_DELIVERY_MAXEXEC_PROP] !=0 ) and ($usedTokens >= $properties[TAO_DELIVERY_MAXEXEC_PROP])) {
            common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). "without tokens");
            return false;
        }

        //check time

        $startDate  = date_create('@'.$properties[TAO_DELIVERY_START_PROP]);
        $endDate = date_create('@'.$properties[TAO_DELIVERY_END_PROP]);

        if (!$this->areWeInRange($startDate, $endDate)) {
            common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). " at the wrong date");
            return false;
        }

        return true;
    }

    /**
     * Get delivery property values
     * @param core_kernel_classes_Resource $delivery
     * @return array
     */
    private function getDeliveryProperties(core_kernel_classes_Resource $delivery)
    {
        if (isset($this->deliveryProperties[$delivery->getUri()])) {
            return $this->deliveryProperties[$delivery->getUri()];
        }

        $properties = [];

        $repeatedDeliveryService =  $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);

        if ($repeatedDeliveryService->isRepeated($delivery)) {
            $repeatedDelivery = $delivery;
            $numberOfRepetitionProp = new core_kernel_classes_Property(RepeatedDeliveryService::PROPERTY_NUMBER_OF_REPETITION);
            $numberOfRepetition = (string) $repeatedDelivery->getOnePropertyValue($numberOfRepetitionProp);
            $delivery = $repeatedDeliveryService->getParentDelivery($delivery);
        }

        $deliveryProps = $delivery->getPropertiesValues(array(
            new core_kernel_classes_Property(TAO_DELIVERY_MAXEXEC_PROP),
            new core_kernel_classes_Property(TAO_DELIVERY_START_PROP),
            new core_kernel_classes_Property(TAO_DELIVERY_END_PROP),
            new core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP),
        ));

        $propMaxExec = current($deliveryProps[TAO_DELIVERY_MAXEXEC_PROP]);
        $propStartExec = current($deliveryProps[TAO_DELIVERY_START_PROP]);
        $propEndExec = current($deliveryProps[TAO_DELIVERY_END_PROP]);
        $rrule = isset($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP]) ? current($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP]) : false;

        if (isset($repeatedDelivery)) {
            $rEvents = $repeatedDeliveryService->getRecurrenceCollection($delivery);
            if (isset($rEvents[$numberOfRepetition])) {
                $properties[TAO_DELIVERY_START_PROP] = $rEvents[$numberOfRepetition]->getStart()->getTimestamp();
                $properties[TAO_DELIVERY_END_PROP] = $rEvents[$numberOfRepetition]->getEnd()->getTimestamp();
                $properties[RepeatedDeliveryService::PROPERTY_NUMBER_OF_REPETITION] = $numberOfRepetition;
            }
        } else {
            $properties[TAO_DELIVERY_START_PROP] = (!(is_object($propStartExec)) or ($propStartExec=="")) ? null : $propStartExec->literal;
            $properties[TAO_DELIVERY_END_PROP] = (!(is_object($propEndExec)) or ($propEndExec=="")) ? null : $propEndExec->literal;
            $properties[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP] = $rrule;
        }

        $properties[TAO_DELIVERY_MAXEXEC_PROP] = (!(is_object($propMaxExec)) or ($propMaxExec=="")) ? 0 : $propMaxExec->literal;
        $properties[CLASS_COMPILEDDELIVERY] = $delivery;

        $this->deliveryProperties[isset($repeatedDelivery) ? $repeatedDelivery->getUri() : $delivery->getUri()] = $properties;

        return $properties;
    }


    /**
     * Check if the date are in range
     * @param type $startDate
     * @param type $endDate
     * @return boolean true if in range
     */
    private function areWeInRange($startDate, $endDate){
        return (empty($startDate) || date_create() >= $startDate)
            && (empty($endDate) || date_create() <= $endDate);
    }
}