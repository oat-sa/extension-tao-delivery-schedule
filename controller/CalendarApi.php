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

/**
 * Controller provides Rest API for getting deliveries.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class CalendarApi extends \tao_actions_CommonModule
{
    
    public function __construct()
    {
        parent::__construct();
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
        
        // TO DO get filtered deliveries list based on $from and $to params.
        foreach ($this->service->getAllAssemblies() as $delivery) {
            $deliveryProps = $delivery->getPropertiesValues(array(
                $startProp,
                $endProp
            ));
            
            $start = (string) current($deliveryProps[TAO_DELIVERY_START_PROP]);
            $end = (string) current($deliveryProps[TAO_DELIVERY_END_PROP]);
            if (!$start || !$end) {
                continue;
            }
            
            $classUri = key($delivery->getTypes());
            
            $result[] = array(
                'title' => $delivery->getLabel(),
                'id' => \tao_helpers_Uri::encode($delivery->getUri()),
                'uri' => $delivery->getUri(),
                'classId' => \tao_helpers_Uri::encode($classUri),
                'classUri' => $classUri,
                'start' => $this->formatDate($start),
                'end' => $this->formatDate($end),
            );
        }
        
        header('Content-type: application/json');
        echo json_encode($result);
    }
    
    /**
     * format date from Unix to ISO 8601 format
     */
    private function formatDate($date) {
        $datetime = \DateTime::createFromFormat('U', $date);
        if ($datetime) {
            return $datetime->format(\DateTime::ISO8601);
        }
    }
}
