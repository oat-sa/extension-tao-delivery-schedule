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
 * Controller provides Rest API for managing deliveries.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class CalendarApi extends \tao_actions_SaSModule
{
    private $tz;
    private $action;
    
    public function __construct()
    {
        parent::__construct();
        $tzName = $this->getRequestParameter('timeZone') === null ? 
                \common_session_SessionManager::getSession()->getTimeZone()
                : $this->getRequestParameter('timeZone');
        
        $this->tz = new \DateTimeZone($tzName);
        $this->assemblyService = \taoDelivery_models_classes_DeliveryAssemblyService::singleton();
        $this->scheduleService = DeliveryScheduleService::singleton();
        
        switch ($this->getRequestMethod()) {
            case "GET":
                $this->action = 'get';
                break;
            case "PUT":
                $this->action = 'update';
                break;
            case "POST":
                $this->action = 'create';
                break;
            case "DELETE":
                $this->sendData(array('message' => 'Method is not implemented'), 501);
                exit();
            default :
                $this->sendData(array('message' => 'Not found'), 404);
                exit();
        }
    }
    
    public function __call($name, $arguments) 
    {
        $this->sendData(array('message' => 'Not found'), 404);
        exit();
    }
    
    public function index() {
        $action = $this->action;
        if(is_callable(array($this, $action))){
            $this->$action();
        } else {
            $this->sendData(array('message' => 'Not found'), 404);
        }
    }
    
    /**
     * Function returns list of deliveries in JSON format. 
     * If <b>$_GET['uri']</b> parameter is given then will be returned appropriate record.
     * If <b>$_GET['full']</b> parameter is given (not empty) then for each delivery will be fetched extended data (e.g. groups, number of executions etc.).
     */
    protected function get()
    {
        $requestParams = $this->getRequestParameters();
        $from = isset($requestParams['start']) ? (integer) $requestParams['start'] : null;
        $to = isset($requestParams['end']) ? (integer) $requestParams['end'] : null;
        
        $result = array();
        $startProp = new \core_kernel_classes_Property(TAO_DELIVERY_START_PROP);
        $endProp = new \core_kernel_classes_Property(TAO_DELIVERY_END_PROP);
        
        $assemblies = array();
        
        if (isset($requestParams['uri'])) {
            $assemblies[] = $this->getCurrentInstance();
        } else {
            $assemblies = $this->scheduleService->getAssemblies($from, $to);
        }
        
        $colorGenerator = new ColorGenerator();
        $colorGenerator->setMaxColorValue(80);
        $colorGenerator->setMinColorValue(20);
        
        foreach ($assemblies as $delivery) {
            $deliveryProps = $delivery->getPropertiesValues(array(
                $startProp,
                $endProp,
                new \core_kernel_classes_Property(DeliveryScheduleService::TAO_DELIVERY_RRULE_PROP)
            ));
            
            $start = (string) current($deliveryProps[TAO_DELIVERY_START_PROP]);
            $end = (string) current($deliveryProps[TAO_DELIVERY_END_PROP]);
            if (!$start || !$end) {
                if (isset($requestParams['uri'])) {
                    $this->sendData(array('message' => __('Delivery has no start and end date.')), 400, array(), true);    
                } else {
                    continue;
                }
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
            
            if (isset($requestParams['full'])) {
                $rawResult = $rawResult + $this->getFullDeliveryData($delivery);
            }
            
            $result[] = $rawResult;
        }
        
        if (empty($result) && isset($requestParams['uri'])) {
            $this->sendData(array('message' => 'Not found'), 404);    
        } else {
            $this->sendData(isset($requestParams['uri']) ? current($result) : $result);
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
    protected function create()
    {
        $params = $this->scheduleService->mapDeliveryProperties($this->getRequestParameters());
        if (empty($params['classUri'])) {
            $params['classUri'] = CLASS_COMPILEDDELIVERY;
        }
        if ($this->scheduleService->validate($this->scheduleService->getEvaluatedParams($params))) {
            $report = $this->scheduleService->create($params);
            if ($report->getType() == \common_report_Report::TYPE_SUCCESS) {
                $data = $report->getdata();

                $result = array(
                    'message' => $report->getMessage(),
                    'id' => \tao_helpers_Uri::encode($data->getUri()),
                    'uri' => $data->getUri(),
                );
                $this->sendData($result);
            } else {
                $this->sendData(
                    array(
                        'message' => $report->getMessage(), 
                        'errors' => $report->getErrors()
                    ), 
                400);
            }
        } else {
            $this->sendData(
                array(
                    'message' => __('Saving error'), 
                    'errors' => $this->scheduleService->mapDeliveryProperties(
                        $this->scheduleService->getErrors($params), 
                        true
                    )
                ), 
                400
            );
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
    protected function update()
    {
        parse_str(file_get_contents("php://input"), $data);
        $params = $this->scheduleService->mapDeliveryProperties($data);

        if(empty($params['classUri'])){
            throw new \tao_models_classes_MissingRequestParameterException("classUri");
        }
        if(empty($params['uri'])){
            throw new \tao_models_classes_MissingRequestParameterException("uri");
        }
        
        //$clazz =  new \core_kernel_classes_Class(\tao_helpers_Uri::decode($params['classUri']));
        $delivery =  new \core_kernel_classes_Class(\tao_helpers_Uri::decode($params['uri']));
        
        $evaluatedParams = $this->scheduleService->getEvaluatedParams($params);
        
        if ($this->scheduleService->validate($evaluatedParams)) {
            $this->scheduleService->save($delivery, $evaluatedParams);
            $this->sendData(array('message'=>__('Delivery saved')));
        } else {
            $this->sendData(
                array(
                    'message' => __('Saving error'), 
                    'errors' =>  $this->scheduleService->mapDeliveryProperties(
                        $this->scheduleService->getErrors($params), 
                        true
                    ),
                    'errorType' => 'warning'
                ), 
                400
            );
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
     * Get test uri assigned to delivery. 
     * If no test assigned to the delivery then delivery uri parameter will be returned.
     * @param \core_kernel_classes_Resource $delivery Delivery instance
     * @return string assigned to the delivery test uri.
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
    
    /**
     * Function converts $data array to json format and sends it to the client.
     * Usage example:
     * <pre>
     *   $this->sendData(
     *       array(...),
     *       200,
     *       array(
     *           "Content-Range: items 1/10",
     *       )
     *   );
     * </pre>
     * @param array $data 
     * @param int $status HTTP status code.
     * @param array $headers http headers array.
     * @param boolean $terminate whether application should be terminated after data was sended.
     */
    private function sendData(array $data, $status = 200, array $headers = array(), $terminate = false)
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->getStatusCodeMessage($status);
        header($status_header);
        header('Content-Type: application/json');
        
        foreach($headers as $header){
            header($header);
        }
        echo json_encode($data);
        
        if ($terminate) {
            exit();
        }
    }
    
    /**
     * Funcion return HTTP status code message.
     * @param int $status status code.
     * @return string code message.
     */
    private function getStatusCodeMessage($status)
    {
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
}
