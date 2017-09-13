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

use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DeliveryContainerService;

/**
 * Service to manage repeated deliveries
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class RepeatedDeliveryService extends ConfigurableService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#RepeatedDelivery';
    const PROPERTY_REPETITION_OF = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#RepetitionOf';
    const PROPERTY_NUMBER_OF_REPETITION = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#NumberOfRepetition';
    
    const CONFIG_ID = 'taoDeliverySchedule/RepeatedDeliveryService';

    /**
     * Get repeated delivery by parent delivery instance and number of repetition.
     * @param \core_kernel_classes_Resource $delivery
     * @param integer $numberOfRepetition
     * @param boolean $createNew If repeated delivery is not exists then new instance will be created.
     * @return \core_kernel_classes_Resource|false
     */
    public function getDelivery(\core_kernel_classes_Resource $delivery, $numberOfRepetition, $createNew = false)
    {
        $rrule = $delivery->getOnePropertyValue(
            new \core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP)
        );
        if ($rrule === null) {
            throw new \InvalidArgumentException('Delivery has no recurrence rule');
        } else {
            $rrule = (string)$rrule;
        }

        $rule = new \Recurr\Rule($rrule);
        $transformer = new \Recurr\Transformer\ArrayTransformer();
        $rEvents = $transformer->transform($rule);
        if (!isset($rEvents[$numberOfRepetition])) {
            throw new \InvalidArgumentException('Delivery has no recurrence number ' . $numberOfRepetition);
        }

        $repeatedDeliveryClass = new \core_kernel_classes_Class(self::CLASS_URI);

        $resources = $repeatedDeliveryClass->searchInstances(array(
            self::PROPERTY_REPETITION_OF => $delivery->getUri(),
            self::PROPERTY_NUMBER_OF_REPETITION => $numberOfRepetition,
        ), array(
            'like' => false
        ));

        if (empty($resources) && $createNew) {
            $repeatedDeliveryClass = new \core_kernel_classes_Class(self::CLASS_URI);
            $repeatedDeliveryProperties = array(
                self::PROPERTY_REPETITION_OF => $delivery->getUri(),
                self::PROPERTY_NUMBER_OF_REPETITION => $numberOfRepetition,
            );
            $repeatedDelivery = $repeatedDeliveryClass->createInstanceWithProperties($repeatedDeliveryProperties);
        } else {
            $repeatedDelivery = current($resources);
        }

        return $repeatedDelivery;
    }

    /**
     * Get parent delivery by repeated delivery.
     * @param \core_kernel_classes_Resource $delivery
     * @return \core_kernel_classes_Resource
     */
    public function getParentDelivery(\core_kernel_classes_Resource $delivery)
    {
        $uri = $delivery->getOnePropertyValue(new \core_kernel_classes_Property(self::PROPERTY_REPETITION_OF))->getUri();
        return new \core_kernel_classes_Resource($uri);
    }

    /**
     * Get repeated deliveries data
     * @param \core_kernel_classes_Resource $delivery
     * @return array
     */
    public function getRepeatedDeliveriesData(\core_kernel_classes_Resource $delivery)
    {
        $result = array();

        $repeatedDeliveryClass = new \core_kernel_classes_Class(self::CLASS_URI);

        $repeatedDeliveries = $repeatedDeliveryClass->searchInstances(array(
            self::PROPERTY_REPETITION_OF => $delivery->getUri()
        ), array(
            'like' => false
        ));

        foreach ($repeatedDeliveries as $repeatedDelivery) {
            $repetitionNumber = (string)$repeatedDelivery->getUniquePropertyValue(
                new \core_kernel_classes_Property(self::PROPERTY_NUMBER_OF_REPETITION)
            );
            $result[$repetitionNumber] = array();

            //groups
            $groups = array_keys($this->getServiceManager()->get('taoDeliverySchedule/DeliveryGroupsService')->getGroups($repeatedDelivery));
            $result[$repetitionNumber]['groups'] = \tao_helpers_Uri::encodeArray($groups);

            //Test takers
            $result[$repetitionNumber] = array_merge(
                $result[$repetitionNumber],
                DeliveryTestTakersService::singleton()->getDeliveryTestTakers($repeatedDelivery)
            );
        }

        return $result;
    }

    /**
     * Delete repeated deliveries
     * @param \core_kernel_classes_Resource $delivery
     * @return array
     */
    public function deleteDeliveries(\core_kernel_classes_Resource $delivery)
    {
        $repeatedDeliveryClass = new \core_kernel_classes_Class(self::CLASS_URI);

        $repeatedDeliveries = $repeatedDeliveryClass->searchInstances(array(
            self::PROPERTY_REPETITION_OF => $delivery->getUri()
        ), array(
            'like' => false
        ));
        foreach ($repeatedDeliveries as $repeatedDelivery) {
            $repeatedDelivery->delete();
        }
    }

    /**
     * Get available in current time repeated delivery by parent delivery
     * @param \core_kernel_classes_Resource $delivery
     * @return \core_kernel_classes_Resource|null
     */
    public function getCurrentRepeatedDelivery(\core_kernel_classes_Resource $delivery)
    {
        $deliveryProps = $delivery->getPropertiesValues(array(
            new \core_kernel_classes_Property(DeliveryContainerService::START_PROP),
            new \core_kernel_classes_Property(DeliveryContainerService::END_PROP),
            new \core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP),
        ));

        $propStartExec = (string) current($deliveryProps[DeliveryContainerService::START_PROP]);
        $propEndExec = (string) current($deliveryProps[DeliveryContainerService::END_PROP]);
        $rrule = (string) current($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP]);

        if ($rrule) {
            $startDate = date_create('@'.$propStartExec);
            $endDate = date_create('@'.$propEndExec);
            $diff = date_diff($startDate, $endDate);

            $rule = new \Recurr\Rule($rrule);
            $transformer = new \Recurr\Transformer\ArrayTransformer();
            $rEvents = $transformer->transform($rule)->startsBefore(date_create(), true);
            foreach ($rEvents as $numberOfRepetition => $rEvent) {
                $rEventStartDate = $rEvent->getStart();
                $rEventEndDate = clone $rEvent->getStart();
                $rEventEndDate->add($diff);

                $repeatedDelivery = $this->getDelivery($delivery, $numberOfRepetition);

                if ($repeatedDelivery && $this->areWeInRange($rEventStartDate, $rEventEndDate)) {
                    return $repeatedDelivery;
                }
            }
        } else {
            return null;
        }
    }

    /**
     * Get list of events.
     * @param \core_kernel_classes_Resource $delivery - main delivery instance
     * @return \Recurr\RecurrenceCollection
     */
    public function getRecurrenceCollection(\core_kernel_classes_Resource $delivery)
    {
        $deliveryProps = $delivery->getPropertiesValues(array(
            new \core_kernel_classes_Property(DeliveryContainerService::START_PROP),
            new \core_kernel_classes_Property(DeliveryContainerService::END_PROP),
            new \core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP),
        ));

        $propStartExec = current($deliveryProps[DeliveryContainerService::START_PROP]);
        $propEndExec = current($deliveryProps[DeliveryContainerService::END_PROP]);
        $rrule = !empty($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP])
            ? current($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP])->literal
            : false;

        if (!empty($rrule)) {
            $startDate = date_create('@'.$propStartExec->literal);
            $endDate = date_create('@'.$propEndExec->literal);
            $diff = date_diff($startDate, $endDate);
            
            $rule = new \Recurr\Rule((string) $rrule);
            $transformer = new \Recurr\Transformer\ArrayTransformer();
            $rEvents = $transformer->transform($rule);
            
            unset($rEvents[0]); //the first recurrence has the same time as the main delivery
    
            foreach ($rEvents as $rEvent) {
                $end = clone($rEvent->getStart());
                $end->add($diff);
                $rEvent->setEnd($end);
            }
        } else {
            $rEvents = array();
        }
        
        return $rEvents;
    }

    /**
     * Whether delivery is repetition of main delivery
     * @param \core_kernel_classes_Resource $delivery
     * @return bool
     */
    public function isRepeated(\core_kernel_classes_Resource $delivery)
    {
        return $delivery->isInstanceOf(new \core_kernel_classes_Class(self::CLASS_URI));
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