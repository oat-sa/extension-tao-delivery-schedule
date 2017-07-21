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

use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliverySchedule\helper\ColorGenerator;
use oat\taoDeliverySchedule\model\DeliveryScheduleService;
use oat\taoDeliverySchedule\model\DeliveryTestTakersService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliverySchedule\model\RepeatedDeliveryService;

/**
 * Controller provides Rest API for managing deliveries.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class CalendarApi extends ApiBaseController
{
    private $tz;
    private $requestParams;
    private $scheduleService;

    public function __construct()
    {
        parent::__construct();
        $tzName = $this->getRequestParameter('timeZone') === null ? 
                \common_session_SessionManager::getSession()->getTimeZone()
                : $this->getRequestParameter('timeZone');
        
        $this->tz = new \DateTimeZone($tzName);
        $this->scheduleService = DeliveryScheduleService::singleton();
        $this->service = DeliveryAssemblyService::singleton();

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
                $this->action = 'deleteDelivery';
                break;
            default :
                $this->sendData(array('message' => 'Not found'), 404);
                exit();
        }
    }
    
    /**
     * Function returns list of deliveries in JSON format. 
     * If <b>$_GET['uri']</b> parameter is given then will be returned data of certain delivery.
     * If <b>$_GET['full']</b> parameter is given (not empty) then for each delivery will be fetched extended data (e.g. groups, number of executions etc.).
     * <b>$_GET['start']</b> filter deliveries which begin after given timestamp
     * <b>$_GET['end']</b> filter deliveries which finish before given timestamp
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
                'color' => $colorGenerator->getColor($this->scheduleService->getTestUri($delivery)),
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
        $params = $this->getRequestParams();
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
     * Note: <b>start</b> and <b>end</b> parameters must be in UTC timezone.
     * 
     * @access public
     * @author Aleh Hutnikau <hutnikau@1pt.com>
     * @return void
     */
    protected function update()
    {
        $params = $this->getRequestParams();
        $delivery = $this->getDelivery();

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
     * Delete delivery instance.
     *
     * @access public
     * @author Aleh Hutnikau <hutnikau@1pt.com>
     * @return void
     */
    protected function deleteDelivery()
    {
        $delivery = $this->getDelivery();
        $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID)->deleteDeliveries($delivery);
        $result = $this->delete();
        $this->sendData($result);
    }

    /**
     * Function returns extended delivery data (e.g. groups, number of executions etc.)
     * @param \core_kernel_classes_Resource $delivery
     * @return void
     */
    private function getFullDeliveryData(\core_kernel_classes_Resource $delivery)
    {
        $result = array();
        if (ServiceProxy::singleton()->implementsMonitoring()) {
            $execs = ServiceProxy::singleton()->getExecutionsByDelivery($delivery);
            $result['executions'] = count($execs);
        }

        $result['published'] = $this->service->getCompilationDate($delivery);

        //groups
        $groups = array_keys($this->getServiceManager()->get('taoDeliverySchedule/DeliveryGroupsService')->getGroups($delivery));
        $result['groups'] = \tao_helpers_Uri::encodeArray($groups);

        //Test takers
        $result = $result + DeliveryTestTakersService::singleton()->getDeliveryTestTakers($delivery);
        
        //Max. number of executions
        $deliveryMaxexecProperty = new \core_kernel_classes_Property(TAO_DELIVERY_MAXEXEC_PROP);
        $result['maxexec'] = (string) $delivery->getOnePropertyValue($deliveryMaxexecProperty);

        //Result server
        $resultServerProp = new \core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP);
        $result['resultserver'] = $delivery->getOnePropertyValue($resultServerProp)->getUri();
        $result['resultserver'] = \tao_helpers_Uri::encode($result['resultserver']);

        $result['repeatedDeliveries'] = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID)->getRepeatedDeliveriesData($delivery);

        return $result;
    }


    /**
     * format date from Unix to ISO 8601 format
     * @param string $date
     * @return string
     */
    private function formatDate($date) {
        $datetime = \DateTime::createFromFormat('U', $date);
        if ($datetime) {
            $datetime->setTimezone($this->tz);
            return $datetime->format(\DateTime::ISO8601);
        }
    }
    
    /**
     * Function returns parameters from the request body 
     * and changes array keys in accordance with RDF properties.
     * @see {@link DeliveryScheduleService::mapDeliveryProperties()}
     * @return array
     */
    private function getRequestParams() {
        if ($this->requestParams === null) {
            parse_str(file_get_contents("php://input"), $data);
            $data = array_merge($data, $this->getRequestParameters());
            $this->requestParams = $this->scheduleService->mapDeliveryProperties($data);
        }
        return $this->requestParams;
    }

    /**
     * Get delivery by request params.

     * @throws \tao_models_classes_MissingRequestParameterException
     * @returns \core_kernel_classes_Class Delivery instance.
     */
    private function getDelivery() {
        $params = $this->getRequestParams();

        if (empty($params['uri'])) {
            throw new \tao_models_classes_MissingRequestParameterException("uri or parentDeliveryUri");
        }

        $delivery = new \core_kernel_classes_Class(\tao_helpers_Uri::decode($params['uri']));
        return $delivery;
    }
}