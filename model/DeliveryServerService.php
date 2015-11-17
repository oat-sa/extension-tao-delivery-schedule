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

use alroniks\dtms\DateTime;
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

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param User|null $user If given then only available for user delivery repetitions will be represented in the setting
     * and <i>TAO_DELIVERY_TAKABLE</i> property (for main and repeated deliveries) will be included in settings.
     * @return array
     * Example:
     * <pre>
     * array (
     *   'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart' => '1447632000',
     *   'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodEnd' => '1447718400',
     *   'http://www.tao.lu/Ontologies/TAODelivery.rdf#RecurrenceRule' => 'FREQ=DAILY;INTERVAL=1;COUNT=5;DTSTART=20151116T000000Z',
     *   'http://www.tao.lu/Ontologies/TAODelivery.rdf#Maxexec' => 0,
     *   'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery' => core_kernel_classes_Resource::__set_state(array(
     *       'uriResource' => 'http://sample/first.rdf#i1447748714582769',
     *       'label' => NULL,
     *       'comment' => '',
     *       'debug' => '',
     *   )),
     *   'TAO_DELIVERY_REPETITIONS' => array (
     *       1 => array (
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart' => 1447718400,
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodEnd' => 1447804800,
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#NumberOfRepetition' => 1,
     *           'TAO_DELIVERY_TAKABLE' => true,
     *       ),
     *       3 => array (
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart' => 1447804800,
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodEnd' => 1447891200,
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#NumberOfRepetition' => 2,
     *           'http://www.tao.lu/Ontologies/TAODelivery.rdf#RepeatedDelivery' => 'http://sample/first.rdf#i1447748775331191',
     *           'TAO_DELIVERY_TAKABLE' => false,
     *       ),
     *   ),
     *   'TAO_DELIVERY_USED_TOKENS' => 0,
     *   'TAO_DELIVERY_TAKABLE' => false,
     * ),
     * </pre>
     *
     * Notice that <i>TAO_DELIVERY_REPETITIONS</i> array indexed by delivery repetition number.
     *
     * Uri of repeated delivery (RepeatedDeliveryService::CLASS_URI) will be represented in the
     * <i>TAO_DELIVERY_REPETITIONS</i> properties in case if repetition has it own instance.
     *
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function getDeliverySettings(core_kernel_classes_Resource $delivery, User $user = null)
    {
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
                    $deliveryRepetitionSettings[RepeatedDeliveryService::CLASS_URI] = $deliveryRepetition->getUri();
                }

                if ($user === null) {
                    $settings['TAO_DELIVERY_REPETITIONS'][$numberOfRepetition] = $deliveryRepetitionSettings;
                    continue;
                }

                if ($deliveryRepetition) {
                    //if user is not assigned or excluded from the repeated delivery
                    if (!$assignmentService->isUserAssigned($deliveryRepetition, $user)) {
                        continue;
                    }
                    $deliveryRepetitionSettings['TAO_DELIVERY_TAKABLE'] = $this->isDeliveryExecutionAllowed($deliveryRepetition, $user);
                } else {
                    //if user is not assigned or excluded from the main delivery
                    if (!$assignmentService->isUserAssigned($delivery, $user, false)) {
                        continue;
                    }
                    $deliveryRepetitionSettings['TAO_DELIVERY_TAKABLE'] =
                        $this->checkTokens($delivery, $user) && $this->areWeInRange(
                            $deliveryRepetitionSettings[TAO_DELIVERY_START_PROP],
                            $deliveryRepetitionSettings[TAO_DELIVERY_END_PROP]
                        );
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
        $allowed = true;

        //first check the user is assigned
        $allowed = $allowed && $this->isUserAssigned($delivery, $user);

        //check Tokens
        $allowed = $allowed && $this->checkTokens($delivery, $user);

        //check time
        $allowed = $allowed && $this->checkTime($delivery);

        return $allowed;
    }

    /**
     * Check if current date included in delivery execution time range.
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     */
    private function checkTime(core_kernel_classes_Resource $delivery)
    {
        $result = true;
        $properties = $this->getDeliveryProperties($delivery);
        $startDate  = date_create('@'.$properties[TAO_DELIVERY_START_PROP]);
        $endDate = date_create('@'.$properties[TAO_DELIVERY_END_PROP]);

        if (!$this->areWeInRange($startDate, $endDate)) {
            common_Logger::d("Attempt to start the compiled delivery " . $delivery->getUri(). " at the wrong date");
            $result = false;
        }
        return $result;
    }

    /**
     * Check if user has attempts to execute delivery.
     * @param core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     */
    private function checkTokens(core_kernel_classes_Resource $delivery, User $user)
    {
        $result = true;
        $properties = $this->getDeliveryProperties($delivery);
        $userUri = $user->getIdentifier();
        $usedTokens = count(taoDelivery_models_classes_execution_ServiceProxy::singleton()->getUserExecutions($delivery, $userUri));
        if (($properties[TAO_DELIVERY_MAXEXEC_PROP] != 0 ) && ($usedTokens >= $properties[TAO_DELIVERY_MAXEXEC_PROP])) {
            common_Logger::d("Attempt to start the compiled delivery " . $delivery->getUri() . " without tokens");
            $result = false;
        }
        return $result;
    }

    /**
     * Check if user assigned to delivery
     * @param core_kernel_classes_Resource $delivery
     * @param User $user
     * @return boolean
     */
    private function isUserAssigned(core_kernel_classes_Resource $delivery, User $user)
    {
        $allowed = $this->getServiceManager()->get(AssignmentService::CONFIG_ID)->isUserAssigned($delivery, $user);
        if (!$allowed) {
            common_Logger::w("User " . $user->getIdentifier() . " attempts to start the compiled delivery " . $delivery->getUri() . " he was not assigned to.");
        }
        return $allowed;
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
        $rrule = current($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP]);

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
            $properties[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP] = (!(is_object($rrule)) or ($rrule=="")) ? null : $rrule->literal;
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
    private function areWeInRange($startDate, $endDate)
    {
        if ($startDate instanceof \DateTime) {
            $startDate = $startDate->getTimestamp();
        }
        if ($endDate instanceof \DateTime) {
            $endDate = $endDate->getTimestamp();
        }
        return time() >= $startDate && time() <= $endDate;
    }
}