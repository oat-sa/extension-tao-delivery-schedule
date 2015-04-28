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
            throw new \common_exception_BadRequest("Only get allowed");
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
                $endProp
            ));
            
            $start = (string) current($deliveryProps[TAO_DELIVERY_START_PROP]);
            $end = (string) current($deliveryProps[TAO_DELIVERY_END_PROP]);
            if (!$start || !$end) {
                continue;
            }
            //getDeliverySettings
            $classUri = key($delivery->getTypes());
            
            $result[] = array(
                'title' => $delivery->getLabel(),
                'id' => \tao_helpers_Uri::encode($delivery->getUri()),
                'uri' => $delivery->getUri(),
                'classId' => \tao_helpers_Uri::encode($classUri),
                'classUri' => $classUri,
                'start' => $this->formatDate($start),
                'end' => $this->formatDate($end),
                'color' => $colorGenerator->getColor($this->getTestUri($delivery))
            );
        }
        header('Content-type: application/json');
        isset($params['uri']) ? $result = current($result) : $result;
        echo json_encode($result);
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
     * Get color by delivery test uri
     */
    private function getTestUri(\core_kernel_classes_Resource $delivery) {
        $result;
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
