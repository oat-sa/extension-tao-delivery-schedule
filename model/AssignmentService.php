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

use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoGroups\models\GroupsService;
use oat\oatbox\user\User;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\GroupAssignment;
use Recurr\Recurrence;
/**
 * Class AssignmentService
 *
 * Service to manage the assignment of users to deliveries
 *
 * @access public
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\taoDeliverySchedule\model
 */
class AssignmentService extends GroupAssignment
{
    public static function singleton()
    {
        return ServiceManager::getServiceManager()->get(self::CONFIG_ID);
    }

    /**
     * Return array of assigments to available deliveries.
     * Takes into account repeated delivery {@see RepeatedDeliveryService::CLASS_URI}
     * 
     * @see \oat\taoDeliveryRdf\model\GroupAssignment::getAssignments()
     */
    public function getAssignments(User $user)
    {
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $assignments = array();
        foreach (parent::getAssignmentFactories($user) as $factory) {
            $delivery = new \core_kernel_classes_Resource($factory->getDeliveryId());
            if ($repeatedDeliveryService->isRepeated($delivery)) {
                $assignments[] = $this->transform($delivery, $user);
            } else {
                $assignments[] = $factory;
                foreach($this->getRepeatedAssignments($delivery, $user) as $repeat) {
                    $assignments[] = $repeat;
                }
            }
        }
        
        // will all be repeat assignments
        usort($assignments, function ($a, $b) {
            return $a->getStartTime() - $b->getStartTime();
        });
        
        $assignments = \tao_helpers_Array::array_unique($assignments);
        //$assignments = array_unique($assignments);
        $final = array();
        foreach ($assignments as $factory) {
            $final[] = $factory->toAssignment();
        }
        return $final;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoDeliveryRdf\model\GroupAssignment::isDeliveryExecutionAllowed()
     */
    public function isDeliveryExecutionAllowed($deliveryIdentifier, User $user)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryIdentifier);
        return $this->verifyUserAssignedRecursiv($delivery, $user)
            && $this->verifyTimeRecursiv($delivery)
            && $this->verifyToken($delivery, $user);
    }

    /**
     * Transform a repetition into an assignment
     * group assignment and exlusions already verified
     * 
     * @param \core_kernel_classes_Resource $deliveryRepetition
     * @param User $user
     * @return \oat\taoDeliverySchedule\model\RepetionAssignmentFactory
     */
    protected function transform(\core_kernel_classes_Resource $deliveryRepetition, User $user)
    {
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $delivery = $repeatedDeliveryService->getParentDelivery($deliveryRepetition);
        $repetitionId = (string)$deliveryRepetition->getUniquePropertyValue(
            new \core_kernel_classes_Property($repeatedDeliveryService::PROPERTY_NUMBER_OF_REPETITION)
        );
        $collection =  $repeatedDeliveryService->getRecurrenceCollection($delivery);
        
        $rEvent = $collection[$repetitionId];
        return $this->buildAssignment($delivery, $user, $repetitionId, $rEvent);
        
    }
    
    /**
     * Add the repetitions to the assignment
     * group assignment already verified, exclusions not yet
     * 
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @return multitype:\oat\taoDeliverySchedule\model\RepetionAssignmentFactory
     */
    protected function getRepeatedAssignments(\core_kernel_classes_Resource $delivery, User $user)
    {
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $rEvents = $repeatedDeliveryService->getRecurrenceCollection($delivery);
        
        $assignments = array();
        foreach ($rEvents as $repetitionId => $rEvent) {
            $repeatedDelivery = $repeatedDeliveryService->getDelivery($delivery, $repetitionId);
            // no repeated Delivery found means no custom rules
            if ($repeatedDelivery == false || !$this->isUserExcluded($repeatedDelivery, $user)) {
                $assignments[] = $this->buildAssignment($delivery, $user, $repetitionId, $rEvent);
            }
        }
        return $assignments;
        
    }
    
    /**
     * Build an Assigmnet factory from the data provided
     * 
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @param string $repetitionId
     * @param Recurrence $rec
     * @return \oat\taoDeliverySchedule\model\RepetionAssignmentFactory
     */
    protected function buildAssignment(\core_kernel_classes_Resource $delivery, User $user, $repetitionId, Recurrence $rec) {
        
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $start = $rec->getStart()->getTimestamp();
        $end = $rec->getEnd()->getTimestamp();
        
        $tokenLeft = $this->verifyToken($delivery, $user);
        $startable = $tokenLeft && $this->areWeInRange($rec->getStart(), $rec->getEnd());
        
        return new RepetionAssignmentFactory($delivery, $user, $start, $end, $startable);
    }

    /**
     * // assigned if:
     * (assigned to parent || assigned to recursion) && !exluded from recursion && !exluded from parent
     * 
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @param bool $checkRepeated Whether check repeated deliveries if main (not repeated) delivery given
     * @return bool
     */
    public function verifyUserAssignedRecursiv(\core_kernel_classes_Resource $delivery, User $user)
    {
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        $currentRepetition = $repeatedDeliveryService->getCurrentRepeatedDelivery($delivery);
        if (is_null($currentRepetition)) {
            $assigned = parent::verifyUserAssigned($delivery, $user); 
        } else {
            $userGroups = GroupsService::singleton()->getGroups($user);
            $deliveryGroups = GroupsService::singleton()->getRootClass()->searchInstances(array(
                GroupAssignment::PROPERTY_GROUP_DELIVERY => array($currentRepetition->getUri(), $delivery->getUri())
            ), array(
                'like'=>false, 'recursive' => true, 'chaining' => 'or'
            ));
                
            $assigned = count(array_intersect($userGroups, $deliveryGroups)) > 0
                && !$this->isUserExcluded($delivery, $user)
                && !$this->isUserExcluded($currentRepetition, $user);
        }
        return $assigned;
    }
    
    protected function verifyTimeRecursiv(\core_kernel_classes_Resource $delivery)
    {
        $valid = parent::verifyTime($delivery);
        
        // if parent is not valid, check for recurring
        if (!$valid) {
            $props = $delivery->getPropertiesValues(array(
                DeliveryContainerService::START_PROP,
                DeliveryContainerService::END_PROP,
                DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP
            ));
            if (empty($props[DeliveryContainerService::START_PROP])
                || empty($props[DeliveryContainerService::END_PROP])
                || empty($props[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP])
            ) {
                // not a recuring delivery
                return false;
            }

            $startDate  =    date_create('@'.(string)current($props[DeliveryContainerService::START_PROP]));
            $endDate    =    date_create('@'.(string)current($props[DeliveryContainerService::END_PROP]));
            
            $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
            $rEvents = $repeatedDeliveryService->getRecurrenceCollection($delivery)
                ->startsBefore(date_create(), true)
                ->endsAfter(date_create(), true);
            
            if (count($rEvents) > 0) {
                $event = $rEvents->first();
                $startDate = $event->getStart();
                $endDate = $event->getEnd();
                $valid = $this->areWeInRange($startDate, $endDate);
            }
            
        }
        return $valid;
    }
}