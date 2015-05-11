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
namespace oat\taoDeliverySchedule\controller;

use oat\taoDeliverySchedule\helper\ColorGenerator;
use oat\taoDeliverySchedule\model\DeliveryScheduleService;
use oat\taoDeliverySchedule\model\DeliveryFactory;
/**
 * Controller provides Rest API for getting deliveries.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class CalendarApi extends \tao_actions_SaSModule
{
    private $tz;
    
    public function __construct()
    {
        parent::__construct();
        $this->tz = new \DateTimeZone(\common_session_SessionManager::getSession()->getTimeZone());
        $this->service = \taoDelivery_models_classes_DeliveryAssemblyService::singleton();
    }
    
    public function index(){
        switch ($this->getRequestMethod()) {
            case "GET":
                $this->get();
                break;
            case "PUT":
                $this->update();
                break;
            case "POST":
                $this->create();
                break;
        }
    }
    
    public function get()
    {
        $params = $this->getRequestParameters();
        $from = isset($params['start']) ? $params['start'] : null;
        $to = isset($params['end']) ? $params['start'] : null;
        
        $result = array();
        $startProp = new \core_kernel_classes_Property(TAO_DELIVERY_START_PROP);
        $endProp = new \core_kernel_classes_Property(TAO_DELIVERY_END_PROP);
        
        $assemblies = array();
        
        if (isset($params['uri'])) {
            $assemblies[] = $this->getCurrentInstance();
        } else {
            $assemblies = $this->service->getAllAssemblies();
        }
        
        $colorGenerator = new ColorGenerator();
        $colorGenerator->setMaxColorValue(80);
        $colorGenerator->setMinColorValue(20);
        
        // TO DO get filtered deliveries list based on $from and $to params.
        foreach ($assemblies as $delivery) {
            $deliveryProps = $delivery->getPropertiesValues(array(
                $startProp,
                $endProp,
                new \core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP)
            ));
            
            $start = (string) current($deliveryProps[TAO_DELIVERY_START_PROP]);
            $end = (string) current($deliveryProps[TAO_DELIVERY_END_PROP]);
            if (!$start || !$end) {
                continue;
            }
            //getDeliverySettings
            $classUri = key($delivery->getTypes());
            
            $rawResult = array(
                'label' => $delivery->getLabel(),
                'title' => $delivery->getLabel(),
                'id' => \tao_helpers_Uri::encode($delivery->getUri()),
                'uri' => $delivery->getUri(),
                'classId' => \tao_helpers_Uri::encode($classUri),
                'classUri' => $classUri,
                'start' => $this->formatDate($start),
                'end' => $this->formatDate($end),
                'color' => $colorGenerator->getColor($this->getTestUri($delivery)),
                'recurrence' => (string) current($deliveryProps[DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP])
            );
            
            if (isset($params['full'])) {
                $rawResult = $rawResult + $this->getFullDeliveryData($delivery);
            }
            
            $result[] = $rawResult;
        }
        
        header('Content-type: application/json');
        isset($params['uri']) ? $result = current($result) : $result;
        echo json_encode($result);
    }
    
    /**
     * Save a delivery instance.
     * Note: <b>start</b> and <b>start</b> parameters must be in UTC timezone.
     * 
     * @access public
     * @author Aleh Hutnikau <hutnikau@1pt.com>
     * @return void
     */
    public function create()
    {
        $params = DeliveryScheduleService::singleton()->mapDeliveryProperties($this->getRequestParameters());
        if (DeliveryScheduleService::singleton()->validate($params)) {
            $test = new \core_kernel_classes_Resource($params['test']);
            $deliveryClass = new \core_kernel_classes_Class($params['classUri']);
            $report = DeliveryFactory::create($deliveryClass, $test, $params);
            $data = $report->getdata();
            
            $result = array(
                'message' => $report->getMessage(),
                'id' => \tao_helpers_Uri::encode($data->getUri()),
                'uri' => $data->getUri(),
            );

            $this->returnJson($result);
        } else {
            //TO DO send errors 
            header('HTTP/1.1 400');
            echo json_encode(array('message'=>__('Saving error')));
        }
    }
    
    /**
     * Save a delivery instance.
     * Note: <b>start</b> and <b>start</b> parameters must be in UTC timezone.
     * 
     * @access public
     * @author Aleh Hutnikau <hutnikau@1pt.com>
     * @return void
     */
    public function update()
    {
        parse_str(file_get_contents("php://input"), $data);
        $params = DeliveryScheduleService::singleton()->mapDeliveryProperties($data);
        
        if(empty($params['classUri'])){
            throw new \tao_models_classes_MissingRequestParameterException("classUri");
        }
        if(empty($params['uri'])){
            throw new \tao_models_classes_MissingRequestParameterException("uri");
        }
        
        //$clazz =  new \core_kernel_classes_Class(\tao_helpers_Uri::decode($params['classUri']));
        $delivery =  new \core_kernel_classes_Class(\tao_helpers_Uri::decode($params['uri']));
        
        $evaluatedParams = DeliveryScheduleService::singleton()->getEvaluatedParams($params);
        
        if (DeliveryScheduleService::singleton()->validate($evaluatedParams)) {
            DeliveryScheduleService::singleton()->save($delivery, $evaluatedParams);

            header('Content-type: application/json');
            echo json_encode(array('message'=>__('Delivery saved')));
            
        } else {
            //TO DO send errors 
            header('HTTP/1.1 400');
            echo json_encode(array('message'=>__('Saving error')));
        }
    }
    
    /**
     * Function returns extended delivery data (e.g. groups, number of executions etc.)
     * @param \core_kernel_classes_Resource $delivery
     */
    private function getFullDeliveryData(\core_kernel_classes_Resource $delivery)
    {
        $result = array();
        if (\taoDelivery_models_classes_execution_ServiceProxy::singleton()->implementsMonitoring()) {
            $execs = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getExecutionsByDelivery($delivery);
            $result['executions'] = count($execs);
        }
        //pubished
        $result['published'] = \taoDelivery_models_classes_DeliveryAssemblyService::singleton()->getCompilationDate($delivery);

        //groups
        $groupsProperty = new \core_kernel_classes_Property(PROPERTY_GROUP_DELVIERY);
        $domainCollection = $groupsProperty->getDomain();
        if (!$domainCollection->isEmpty()) {
            $domain = $domainCollection->get(0);
            $groups = array_keys(
                $domain->searchInstances(array(
                    $groupsProperty->getUri() => $delivery
                ), 
                array('recursive' => true, 'like' => false))
            );
            $result['groups'] = \tao_helpers_Uri::encodeArray($groups);
        }

        // excluded test takers
        $excludedSubjProperty = new \core_kernel_classes_Property(TAO_DELIVERY_EXCLUDEDSUBJECTS_PROP);
        $excluded = $delivery->getPropertyValues($excludedSubjProperty);
        $result['ttexcluded'] = $excluded;

        // assigned test takers
        $users = \taoDelivery_models_classes_AssignmentService::singleton()->getAssignedUsers($delivery);
        $assigned = array_values(array_diff(array_unique($users), $excluded));
        $result['ttassigned'] = $assigned;

        //Max. number of executions
        $deliveryMaxexecProperty = new \core_kernel_classes_Property(TAO_DELIVERY_MAXEXEC_PROP);
        $result['maxexec'] = (string) $delivery->getOnePropertyValue($deliveryMaxexecProperty);

        //Result server
        $resultServerProp = new \core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP);
        $result['resultserver'] = $delivery->getOnePropertyValue($resultServerProp)->getUri();
        $result['resultserver'] = \tao_helpers_Uri::encode($result['resultserver']);
        
        return $result;
    }


    /**
     * format date from Unix to ISO 8601 format
     */
    private function formatDate($date) {
        $datetime = \DateTime::createFromFormat('U', $date, $this->tz);
        if ($datetime) {
            $datetime->setTimezone($this->tz);
            return $datetime->format(\DateTime::ISO8601);
        }
    }
    
    /**
     * Get test uri assigned to delivery
     */
    private function getTestUri(\core_kernel_classes_Resource $delivery) {
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
