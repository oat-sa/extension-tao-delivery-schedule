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

use oat\taoGroups\models\GroupsService;
use oat\oatbox\user\User;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoDeliveryRdf\model\AssignmentFactory;
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
     * Return array of available deliveries.
     * If user has assigned repeated delivery {@see RepeatedDeliveryService::CLASS_URI}
     * then the parent delivery of this delivery will be returned.
     * @param User $user
     * @return array
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
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
        
        usort($assignments, function ($a, $b) {
            return $a->getStartTime() - $b->getStartTime();
        });
        $final = array();
        foreach ($assignments as $factory) {
            $final[] = $factory->toAssignment();
        }
        return $final;
    }
    
    public function isDeliveryExecutionAllowed($deliveryIdentifier, User $user)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryIdentifier);
        return $this->verifyUserAssigned($delivery, $user)
            && $this->verifyTimeRecursiv($delivery)
            && $this->verifyToken($delivery, $user);
    }

    /**
     * Transform a repetition into an assignment
     * 
     * @param \core_kernel_classes_Resource $deliveryRepetition
     */
    protected function transform(\core_kernel_classes_Resource $deliveryRepetition, User $user)
    {
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $delivery = $repeatedDeliveryService->getParentDelivery($deliveryRepetition);
        $repetitionId = (string)$deliveryRepetition->getUniquePropertyValue(
            new \core_kernel_classes_Property($repeatedDeliveryService::PROPERTY_NUMBER_OF_REPETITION)
        );
        $collection =  $repeatedDeliveryService->getRecurrenceCollection($delivery);
        
        $tokenLeft = $this->verifyToken($delivery, $user);
        $rEvent = $collection[$repetitionId];
        return $this->buildAssignment($delivery, $repetitionId, $rEvent, $tokenLeft);
        
    }
    
    protected function getRepeatedAssignments(\core_kernel_classes_Resource $delivery, User $user)
    {
        $tokenLeft = $this->verifyToken($delivery, $user);
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $rEvents = $repeatedDeliveryService->getRecurrenceCollection($delivery);
        
        $assignments = array();
        foreach ($rEvents as $repetitionId => $rEvent) {
            $assignments[] = $this->buildAssignment($delivery, $repetitionId, $rEvent, $tokenLeft);
        }
        return $assignments;
        
    }
    
    protected function buildAssignment(\core_kernel_classes_Resource $delivery, $repetitionId, Recurrence $rec, $tokenLeft) {
        
        $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
        
        $start = $rec->getStart()->getTimestamp();
        $end = $rec->getEnd()->getTimestamp();
        $user = \common_session_SessionManager::getSession()->getUser();
        
        $startable = $tokenLeft && $this->areWeInRange($rec->getStart(), $rec->getEnd());
        
        return new RepetionAssignmentFactory($delivery, $user, $start, $end, $startable);
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @param bool $checkRepeated Whether check repeated deliveries if main (not repeated) delivery given
     * @return bool
     */
    public function isUserAssigned(\core_kernel_classes_Resource $delivery, User $user, $checkRepeated = true)
    {
        $returnValue = false;
        $isGuestUser = $this->isDeliveryGuestUser($user);
        $isGuestAccessibleDelivery = $this->hasDeliveryGuestAccess($delivery);

        //check for guest access mode
        if( $isGuestUser && $isGuestAccessibleDelivery ){
            $returnValue = true;
        } else {
            $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
            $currentRepeatedDelivery = $repeatedDeliveryService->getCurrentRepeatedDelivery($delivery);
            if ($currentRepeatedDelivery && $checkRepeated) {
                $delivery = $currentRepeatedDelivery;
            }
            $userGroups = GroupsService::singleton()->getGroups($user);
            $deliveryGroups = GroupsService::singleton()->getRootClass()->searchInstances(array(
                PROPERTY_GROUP_DELVIERY => $delivery->getUri()
            ), array(
                'like'=>false, 'recursive' => true
            ));

            $returnValue = count(array_intersect($userGroups, $deliveryGroups)) > 0 && !$this->isUserExcluded($delivery, $user);
        }

        return $returnValue;
    }
    
    protected function verifyTimeRecursiv(\core_kernel_classes_Resource $delivery)
    {
        $valid = parent::verifyTime($delivery);
        
        // if parent is not valid, check for recurring
        if (!$valid) {
            $props = $delivery->getPropertiesValues(array(
                TAO_DELIVERY_START_PROP,
                TAO_DELIVERY_END_PROP,
                DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP
            ));
            if (empty($props[TAO_DELIVERY_START_PROP])
                || empty($props[TAO_DELIVERY_END_PROP])
                || empty($props[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP])
            ) {
                // not a recuring delivery
                return false;
            }

            $startDate  =    date_create('@'.(string)current($props[TAO_DELIVERY_START_PROP]));
            $endDate    =    date_create('@'.(string)current($props[TAO_DELIVERY_END_PROP]));
            
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