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
                'color' => $this->getColor($delivery)
            );
        }
        header('Content-type: application/json');
        count($result) === 1 ? $result = current($result) : $result;
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
    private function getColor(\core_kernel_classes_Resource $delivery) {
        //TO DO https://github.com/davidmerfield/randomColor/blob/master/randomColor.js
        $color = '0x';
        $runtimeResource = $delivery->getUniquePropertyValue(new \core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_RUNTIME));
        $actualParams = $runtimeResource->getPropertyValuesCollection(new \core_kernel_classes_Property(PROPERTY_CALLOFSERVICES_ACTUALPARAMETERIN));
        foreach ($actualParams as $actualParam) {
            $test = $actualParam->getUniquePropertyValue(new \core_kernel_classes_Property(PROPERTY_ACTUALPARAMETER_CONSTANTVALUE));
            if (get_class($test) === "core_kernel_classes_Resource") {
                $color .= substr(md5($test->getUri()), 0, 6);
                break;
            }
        }
        
        if ($color === null) {
            $color .= substr(md5($delivery->getUri()), 0, 6);
        }
        return $this->genColorCodeFromText($test->getUri());
        $rgb = array(hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));
        if (array_sum($rgb) > 450) {
            $rgb = array_map(function ($val) {
                return $val / 16 * 2; 
            }, $rgb);
        }
        
        return $this->rgb2hex($rgb);
    }
    
    private function genColorCodeFromText($text,$min_brightness=0,$spec=10)
    {
            // Check inputs
            if(!is_int($min_brightness)) throw new Exception("$min_brightness is not an integer");
            if(!is_int($spec)) throw new Exception("$spec is not an integer");
            if($spec < 2 or $spec > 10) throw new Exception("$spec is out of range");
            if($min_brightness < 0 or $min_brightness > 255) throw new Exception("$min_brightness is out of range");


            $hash = md5($text);  //Gen hash of text
            $colors = array();
            for($i=0;$i<3;$i++)
                    $colors[$i] = max(array(round(((hexdec(substr($hash,$spec*$i,$spec)))/hexdec(str_pad('',$spec,'F')))*255),$min_brightness)); //convert hash into 3 decimal values between 0 and 255

            if($min_brightness > 0)  //only check brightness requirements if min_brightness is about 100
                    while( array_sum($colors)/3 < $min_brightness )  //loop until brightness is above or equal to min_brightness
                            for($i=0;$i<3;$i++)
                                    $colors[$i] += 10;	//increase each color by 10

            $output = '';

            for($i=0;$i<3;$i++)
                    $output .= str_pad(dechex($colors[$i]),2,0,STR_PAD_LEFT);  //convert each color to hex and append to output

            return '#'.$output;
    }
    
    
    private function rgb2hex($rgb) {
       $hex = "#";
       $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

       return $hex; // returns the hex value including the number sign (#)
    }
}
