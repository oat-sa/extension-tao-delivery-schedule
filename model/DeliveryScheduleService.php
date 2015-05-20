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
     * @param array $params array of delivery parameters (uri=>value)
     * @return boolean Whether the parameters are valid.
     */
    public function validate($params)
    {
        return empty($this->getErrors($params));
    }
    
    /**
     * Function returns list of errors in the delivery data.
     * @param array $data delivery data (uri=>value) 
     * (evaluate {@link self::getEvaluatedParams()} raw data before)
     */
    public function getErrors($data)
    {
        $data = $this->mapDeliveryProperties($data);
        
        $errors = array();
        $notEmptyValidator = new \tao_helpers_form_validators_NotEmpty();
        $numericValidator = new \tao_helpers_form_validators_Numeric();
        
        if (!$notEmptyValidator->evaluate($data[TAO_DELIVERY_START_PROP])) {
            $errors[TAO_DELIVERY_START_PROP] = $notEmptyValidator->getMessage();
        }
        if (!$notEmptyValidator->evaluate($data[TAO_DELIVERY_END_PROP])) {
            $errors[TAO_DELIVERY_END_PROP] = $notEmptyValidator->getMessage();
        }
        if ($data[TAO_DELIVERY_END_PROP] < $data[TAO_DELIVERY_START_PROP]) {
            $errors[TAO_DELIVERY_START_PROP] = __('start date must be before end date');
        }
        if (!$notEmptyValidator->evaluate($data[RDFS_LABEL])) {
            $errors[RDFS_LABEL] = $notEmptyValidator->getMessage();
        }
        if (isset($data[TAO_DELIVERY_MAXEXEC_PROP]) && !$numericValidator->evaluate($data[TAO_DELIVERY_MAXEXEC_PROP])) {
            $errors[TAO_DELIVERY_MAXEXEC_PROP] = $numericValidator->getMessage();
        }
        
        return $errors;
    }
    
    /**
     * Save the delivery.
     * 
     * @param \core_kernel_classes_Class $delivery
     * @param array $params Array of delivery parameters (uri=>value)
     * @return \core_kernel_classes_Class $delivery instance
     */
    public function save(\core_kernel_classes_Class $delivery, array $params)
    {
        $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($delivery);
        $delivery = $binder->bind($params);
        
        if (isset($params['groups'])) {
            $groups = array_map(array('\tao_helpers_Uri' , 'decode'), $params['groups']);
            $this->saveGroups($delivery, $groups);
        }
        
        if (isset($params['ttexcluded'])) {
            $ttexcluded = is_array($params['ttexcluded']) ? $params['ttexcluded'] : array();
            $this->saveExcludedTestTakers($delivery, $ttexcluded);
        }
        return $delivery;
    }
    
    /**
     * Create delivery.
     * 
     * @param array $params Array of delivery parameters (uri=>value)
     * Example: 
     * <pre>
     * array(
     *   'test' => 'http://sample/first.rdf#i1429716287341629', //test uri (required)
     *   'start' => '2015-04-27 00:00', //start date in 'Y-m-d H:i' format (required)
     *   'end' => '2015-04-27 00:00', //start date in 'Y-m-d H:i' format (required)
     *   'label' => 'Delivery Label', //start date in 'Y-m-d H:i' format (required)
     *   'classUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery',
     * )
     * </pre>
     * @return report
     */
    public function create(array $params)
    {
        $test = new \core_kernel_classes_Resource($params['test']);
        $deliveryClass = new \core_kernel_classes_Class($params['classUri']);
        
        $report = DeliveryFactory::create($deliveryClass, $test, array(
            TAO_DELIVERY_START_PROP => $params[TAO_DELIVERY_START_PROP],
            TAO_DELIVERY_END_PROP => $params[TAO_DELIVERY_END_PROP],
            RDFS_LABEL => $params[RDFS_LABEL]
        ));
        
        return $report;
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
     * @param \core_kernel_classes_Class $delivery Delivery instance
     * @param array $values List of grups (uri)
     * @return boolean 
     */
    private function saveGroups(\core_kernel_classes_Class $delivery, $values)
    {
        $property = new \core_kernel_classes_Property(PROPERTY_GROUP_DELVIERY);

        $currentValues = array();
        foreach ($property->getDomain() as $domain) {
            $instances = $domain->searchInstances(array(
                $property->getUri() => $delivery
            ), array('recursive' => true, 'like' => false));
            $currentValues = array_merge($currentValues, array_keys($instances));
        }

        $toAdd = array_diff($values, $currentValues);
        $toRemove = array_diff($currentValues, $values);

        $success = true;
        foreach ($toAdd as $uri) {
            $subject = new \core_kernel_classes_Resource($uri);
            $success = $success && $subject->setPropertyValue($property, $delivery);
        }

        foreach ($toRemove as $uri) {
            $subject = new \core_kernel_classes_Resource($uri);
            $success = $success && $subject->removePropertyValue($property, $delivery);
        }

        return $success;
    }
    
    /**
     * Save excluded testakers
     * @param \core_kernel_classes_Class $delivery Delivery instance
     * @param array $excluded List of excluded testakers (uri)
     * @return boolean 
     */
    public function saveExcludedTestTakers(\core_kernel_classes_Class $delivery, $excluded) {
        $success = $delivery->editPropertyValues(
            new \core_kernel_classes_Property(TAO_DELIVERY_EXCLUDEDSUBJECTS_PROP), 
            $excluded
        );
        
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
            
            if(empty($deliveryProps[TAO_DELIVERY_START_PROP]) || empty($deliveryProps[TAO_DELIVERY_END_PROP])) {
                continue;
            }
            
            $deliveryStartTs = (integer) current($deliveryProps[TAO_DELIVERY_START_PROP])->literal;
            $deliveryEndTs = (integer) current($deliveryProps[TAO_DELIVERY_END_PROP])->literal;
            
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
    
    /**
     * Get delivery settings specific for schedule extension.
     * @param \core_kernel_classes_Resource $delivery
     * @return array list of settings (key => value)
     * Some of values depends on the user language (e.g. <i>rruleSummary</i>)
     * Example:
     * <pre>
     * array(
     *   'http://www.tao.lu/Ontologies/TAODelivery.rdf#RecurrenceRule' => 'FREQ=WEEKLY;INTERVAL=1;COUNT=5;DTSTART=20150507T010000Z',
     *   'rruleSummary' => 'weekly for 5 times',
     * )
     * </pre>
     */
    public function getDeliverySettings(\core_kernel_classes_Resource $delivery)
    {
        $settings = array();
        
        $deliveryProps = $delivery->getPropertiesValues(array(
            new \core_kernel_classes_Property(self::TAO_DELIVERY_RRULE_PROP),
        ));
        $rrule = (string) current($deliveryProps[self::TAO_DELIVERY_RRULE_PROP]);

        if ($rrule) {
            $rule = new \Recurr\Rule($rrule, new \DateTime());
            
            try {
                $userLang = substr(\common_session_SessionManager::getSession()->getInterfaceLanguage(), 0, 2);
                $rruleTranslator = new \Recurr\Transformer\Translator($userLang);
            } catch (\InvalidArgumentException $e) {
                //fallback to english
                $rruleTranslator = new \Recurr\Transformer\Translator('en');
            }
            $textTransformer = new \Recurr\Transformer\TextTransformer($rruleTranslator);
            $settings[self::TAO_DELIVERY_RRULE_PROP] = $rrule;
            $settings['rruleSummary'] = $textTransformer->transform($rule);
            
            $transformer = new \Recurr\Transformer\ArrayTransformer();
            $recurrenceCollection  = $transformer->transform($rule);
            
            if (count($recurrenceCollection)) {
                $firstRecurranse = $recurrenceCollection->first();
                $lastRecurranse = $recurrenceCollection->last();
                $settings['from'] = $firstRecurranse->getStart()->getTimestamp();
                $settings['until'] = $lastRecurranse->getEnd()->getTimestamp();
            }
        }
        
        return $settings;
    }
    
    /**
     * Get test uri assigned to delivery. 
     * If no test assigned to the delivery then delivery uri parameter will be returned.
     * @param \core_kernel_classes_Resource $delivery Delivery instance
     * @return string assigned to the delivery test uri.
     */
    public function getTestUri(\core_kernel_classes_Resource $delivery) {
        $runtimeResource = $delivery->getUniquePropertyValue(new \core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_RUNTIME));
        $actualParams = $runtimeResource->getPropertyValuesCollection(new \core_kernel_classes_Property(PROPERTY_CALLOFSERVICES_ACTUALPARAMETERIN));
        foreach ($actualParams as $actualParam) {
            $test = $actualParam->getUniquePropertyValue(new \core_kernel_classes_Property(PROPERTY_ACTUALPARAMETER_CONSTANTVALUE));
            if (get_class($test) === "core_kernel_classes_Resource") {
                $result = $test->getUri();
                break;
            }
        }
        if ($result === null) {
            $result = $delivery->getUri();
        }
        return $result;
    }
}
