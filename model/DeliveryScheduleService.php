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

/**
 * Delivery schedule service
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class DeliveryScheduleService extends \tao_models_classes_Service
{
    const TAO_DELIVERY_RRULE_PROP = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#RecurrenceRule';

    /**
     * Change array keys in accordance with RDF properties.
     * Example:
     * <pre>
     * DeliveryScheduleService::singleton()->mapDeliveryProperties(
     *     array(
     *         'start' => '2015-04-13 00:00',
     *         'end' => '2015-04-14 00:00'
     *     )
     * );
     * </pre>
     * returns:
     * <pre>
     * array(
     *     'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart' => '2015-04-13 00:00',
     *     'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodEnd' => '2015-04-14 00:00'
     * )
     * </pre>
     * @param array $data 
     * @param boolean $reverse
     */
    public function mapDeliveryProperties($data, $reverse = false)
    {
        $map = array(
            RDFS_LABEL => 'label', 
            TAO_DELIVERY_START_PROP => 'start', 
            TAO_DELIVERY_END_PROP => 'end',
            TAO_DELIVERY_MAXEXEC_PROP => 'maxexec',
            TAO_DELIVERY_RESULTSERVER_PROP => 'resultserver',
            self::TAO_DELIVERY_RRULE_PROP => 'recurrence'
        );
        
        foreach ($data as $key => $val) {
            if ($reverse) {
                $newIndex = isset($map[$key]) ? $map[$key] : false;
            } else {
                $newIndex = array_search($key, $map);
            }
            if ($newIndex !== false) {
                unset($data[$key]);
                $data[$newIndex] = $val;
            }
        }
        
        return $data;
    }
    
    /**
     * Evaluate delivery params.
     * 
     * Example:
     * <pre>
     * DeliveryScheduleService::singleton()->getEvaluatedParams(
     *     array(
     *         'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart' => '2015-04-13 00:00',
     *         ...
     *     )
     * );
     * </pre>
     * returns:
     * <pre>
     * array(
     *     'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart' => '1428897600',
     *     ...
     * )
     * </pre>
     * 
     * @param array $params Array of delivery parameters (uri=>value)
     * @return array evaluated params
     */
    public function getEvaluatedParams($params)
    {
        $tz = new \DateTimeZone('UTC');
        if (isset($params[TAO_DELIVERY_START_PROP])) {
            $dt = new \DateTime($params[TAO_DELIVERY_START_PROP], $tz);
            $params[TAO_DELIVERY_START_PROP] = (string) $dt->getTimestamp();
        }
        if (isset($params[TAO_DELIVERY_END_PROP])) {
            $dt = new \DateTime($params[TAO_DELIVERY_END_PROP], $tz);
            $params[TAO_DELIVERY_END_PROP] = (string) $dt->getTimestamp();
        }
        if (isset($params[TAO_DELIVERY_RESULTSERVER_PROP])) {
            $params[TAO_DELIVERY_RESULTSERVER_PROP] = \tao_helpers_Uri::decode($params[TAO_DELIVERY_RESULTSERVER_PROP]);
        }
        unset($params['uri']);
        unset($params['classUri']);
        return $params;
    }
    
    /**
     * Validate delivery parameters.
     * 
     * @param array $params Array of delivery parameters (uri=>value)
     * @return boolean Whether the parameters are valid.
     */
    public function validate($params)
    {
        $valid = true;
        if ($params[TAO_DELIVERY_START_PROP] >= $params[TAO_DELIVERY_END_PROP]) {
            $valid = false;
        }
        return $valid;
    }
    
    /**
     * Save the delivery.
     * 
     * @param \core_kernel_classes_Class $delivery
     * @param array $params Array of delivery parameters (uri=>value)
     * @return \core_kernel_classes_Class $delivery instance
     */
    public function save($delivery, $params)
    {
        $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($delivery);
        $delivery = $binder->bind($params);
        
        if (isset($params['groups'])) {
            $groups = array_map(array('\tao_helpers_Uri' , 'decode'), $params['groups']);
            $this->saveGroups($delivery, $groups);
        }
        
        return $delivery;
    }
    
    /**
     * Function generates array of time zones
     * 
     * @return array Example:
     *         <pre>
     *         array(
     *           array('label' => 'Antarctica/McMurdo', 'value' => -720),
     *           ...
     *           array('label' => 'Pacific/Kiritimati', 'value' => 840)
     *         )
     *         </pre>
     */
    public function getTimeZones()
    {
        $results = array();
        $now = new \DateTime("now", new \DateTimeZone('UTC'));
        foreach (\DateTimeZone::listIdentifiers() as $key) {
            $timezone = new \DateTimeZone($key);
            
            $offset = ($timezone->getOffset($now) / 60);
            if ($offset == 0) {
                $offset = '0000';
            }
            $results[] = array(
                'label' => $key,
                'value' => $offset
            );
        }
        return array_values($results);
    }
    
    /**
     * Save delivery groups.
     * 
     * @param \core_kernel_classes_Class $resource Delivery instance
     * @param array $values List of grups (uri)
     * @return boolean 
     */
    private function saveGroups($resource, $values)
    {
        $property = new \core_kernel_classes_Property(PROPERTY_GROUP_DELVIERY);

        $currentValues = array();
        foreach ($property->getDomain() as $domain) {
            $instances = $domain->searchInstances(array(
                $property->getUri() => $resource
            ), array('recursive' => true, 'like' => false));
            $currentValues = array_merge($currentValues, array_keys($instances));
        }

        $toAdd = array_diff($values, $currentValues);
        $toRemove = array_diff($currentValues, $values);

        $success = true;
        foreach ($toAdd as $uri) {
            $subject = new \core_kernel_classes_Resource($uri);
            $success = $success && $subject->setPropertyValue($property, $resource);
        }

        foreach ($toRemove as $uri) {
            $subject = new \core_kernel_classes_Resource($uri);
            $success = $success && $subject->removePropertyValue($property, $resource);
        }

        return $success;
    }
    
    /**
     * Get all deliveries in time range.
     * @param integer $form Timestamp
     * @param integer $to Timestamp
     */
    public function getAssemblies($from, $to)
    {
        $assemblies = \taoDelivery_models_classes_DeliveryAssemblyService::singleton()->getAllAssemblies();
        
        $startProp = new \core_kernel_classes_Property(TAO_DELIVERY_START_PROP);
        $endProp = new \core_kernel_classes_Property(TAO_DELIVERY_END_PROP);
        
        $result = array();
        $timeZone = new \DateTimeZone('UTC');
        
        $filterStartDate = \DateTime::createFromFormat('U', $from, $timeZone);
        $filterEndDate = \DateTime::createFromFormat('U', $to, $timeZone);
        
        foreach ($assemblies as $delivery) {
            $deliveryProps = $delivery->getPropertiesValues(array(
                $startProp,
                $endProp,
                new \core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP)
            ));
            
            $deliveryStartTs = (integer) current($deliveryProps[TAO_DELIVERY_START_PROP])->literal;
            $deliveryEndTs = (integer) current($deliveryProps[TAO_DELIVERY_END_PROP])->literal;
            
            if ((!$deliveryStartTs || !$deliveryEndTs)) {
                continue;
            }
            
            $rrule = (string) current($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP]);
            
            if (empty($rrule)) {
                if (($deliveryStartTs < $from && $deliveryEndTs < $from) || ($deliveryStartTs > $to && $deliveryEndTs > $to)) {
                    continue;
                }
                $result[] = $delivery;
            } else {
                $rule = new \Recurr\Rule($rrule);
                $transformer = new \Recurr\Transformer\ArrayTransformer();
                $rEvents = $transformer->transform($rule)->startsBetween($filterStartDate, $filterEndDate);
                
                if(count($rEvents) !== 0) {
                    $result[] = $delivery;
                }
            }
        }
        return $result;
    }
    
}
