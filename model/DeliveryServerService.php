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

    public function getDeliverySettings(core_kernel_classes_Resource $delivery)
    {
        if (isset($this->deliverySettings[$delivery->getUri()])) {
            return $this->deliverySettings[$delivery->getUri()];
        }

        $repeatedDeliveryService =  $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);

        if ($this->isRepeated($delivery)) {
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

        $settings[TAO_DELIVERY_MAXEXEC_PROP] = (!(is_object($propMaxExec)) or ($propMaxExec=="")) ? 0 : $propMaxExec->literal;
        $settings[TAO_DELIVERY_START_PROP] = (!(is_object($propStartExec)) or ($propStartExec=="")) ? null : $propStartExec->literal;
        $settings[TAO_DELIVERY_END_PROP] = (!(is_object($propEndExec)) or ($propEndExec=="")) ? null : $propEndExec->literal;
        $settings[CLASS_COMPILEDDELIVERY] = $delivery;

        if ($rrule) {
            $startDate = date_create('@'.$settings[TAO_DELIVERY_START_PROP]);
            $endDate = date_create('@'.$settings[TAO_DELIVERY_END_PROP]);
            $diff = date_diff($startDate, $endDate);

            $rule = new \Recurr\Rule((string) $rrule);
            $transformer = new \Recurr\Transformer\ArrayTransformer();
            $rEvents = $transformer->transform($rule)->startsBefore(date_create(), true);

            if (isset($repeatedDelivery) && isset($rEvents[$numberOfRepetition])) {
                $rEvent = $rEvents[$numberOfRepetition];
                $rEventStartDate = $rEvent->getStart();

                $rEventEndDate = clone $rEvent->getStart();
                $rEventEndDate->add($diff);

                $settings[TAO_DELIVERY_START_PROP] = $rEventStartDate->getTimestamp();
                $settings[TAO_DELIVERY_END_PROP] = $rEventEndDate->getTimestamp();
            } else {
                foreach ($rEvents as $numberOfRepetition => $rEvent) {
                    $rEventStartDate = $rEvent->getStart();
                    $rEventEndDate = clone $rEvent->getStart();

                    $rEventEndDate->add($diff);

                    $repeatedDeliveryExists = ($repeatedDeliveryService->getDelivery($delivery, $numberOfRepetition) !== false);
                    if ($this->areWeInRange($rEventStartDate, $rEventEndDate) && !$repeatedDeliveryExists) {
                        $settings[TAO_DELIVERY_START_PROP] = $rEventStartDate->getTimestamp();
                        $settings[TAO_DELIVERY_END_PROP] = $rEventEndDate->getTimestamp();
                    }
                }
            }
        }

        $this->deliverySettings[isset($repeatedDelivery) ? $repeatedDelivery->getUri() : $delivery->getUri()] = $settings;

        return $settings;
    }

    public function isDeliveryExecutionAllowed(core_kernel_classes_Resource $delivery, User $user)
    {
        $userUri = $user->getIdentifier();
        if (is_null($delivery)) {
            common_Logger::w("Attempt to start the compiled delivery ".$delivery->getUri(). " related to no delivery");
            return false;
        }

        //first check the user is assigned
        $serviceManager = ServiceManager::getServiceManager();
        if(!$serviceManager->get('taoDelivery/assignment')->isUserAssigned($delivery, $user)){
            common_Logger::w("User ".$userUri." attempts to start the compiled delivery ".$delivery->getUri(). " he was not assigned to.");
            return false;
        }

        $settings = $this->getDeliverySettings($delivery);

        //check Tokens
        $usedTokens = count(taoDelivery_models_classes_execution_ServiceProxy::singleton()->getUserExecutions($delivery, $userUri));

        if (($settings[TAO_DELIVERY_MAXEXEC_PROP] !=0 ) and ($usedTokens >= $settings[TAO_DELIVERY_MAXEXEC_PROP])) {
            common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). "without tokens");
            return false;
        }

        //check time
        $currentRepeatedDelivery = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID)->getCurrentRepeatedDelivery($delivery);
        if ($currentRepeatedDelivery) {
            $repeatedDeliverySettings = $this->getDeliverySettings($currentRepeatedDelivery);
            $startDate  = date_create('@'.$repeatedDeliverySettings[TAO_DELIVERY_START_PROP]);
            $endDate = date_create('@'.$repeatedDeliverySettings[TAO_DELIVERY_END_PROP]);
        } else {
            $startDate  = date_create('@'.$settings[TAO_DELIVERY_START_PROP]);
            $endDate = date_create('@'.$settings[TAO_DELIVERY_END_PROP]);
        }

        if (!$this->areWeInRange($startDate, $endDate)) {
            common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). " at the wrong date");
            return false;
        }

        return true;
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

    /**
     * Whether delivery is repetition of main delivery
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     */
    private function isRepeated(core_kernel_classes_Resource $delivery)
    {
        return $delivery->isInstanceOf(new core_kernel_classes_Class(RepeatedDeliveryService::CLASS_URI));
    }
}