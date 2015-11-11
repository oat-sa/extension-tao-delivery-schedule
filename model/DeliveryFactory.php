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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliverySchedule\model;

/**
 * Services to create deliveries
 *
 * @access public
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class DeliveryFactory extends \taoDelivery_models_classes_SimpleDeliveryFactory
{
    /**
     * Creates a new delivery
     * 
     * @param core_kernel_classes_Class $deliveryClass
     * @param core_kernel_classes_Resource $test
     * @param array $properties Array of properties of delivery
     * @return common_report_Report
     */
    public static function create(\core_kernel_classes_Class $deliveryClass, \core_kernel_classes_Resource $test, $properties = array()) 
    {
        \common_Logger::i('Creating delivery with ' . $test->getLabel() . ' under ' . $deliveryClass->getLabel());
        
        $storage = new \taoDelivery_models_classes_TrackedStorage();
        
        $testCompilerClass = \taoTests_models_classes_TestsService::singleton()->getCompilerClass($test);
        $compiler = new $testCompilerClass($test, $storage);
        
        $report = $compiler->compile();
        if ($report->getType() == \common_report_Report::TYPE_SUCCESS) {
            //$tz = new \DateTimeZone(\common_session_SessionManager::getSession()->getTimeZone());
            $tz = new \DateTimeZone('UTC');
            
            if (!empty($properties[TAO_DELIVERY_START_PROP])) {
                $dt = new \DateTime($properties[TAO_DELIVERY_START_PROP], $tz);
                $properties[TAO_DELIVERY_START_PROP] = (string) $dt->getTimestamp();
            }
            
            if (!empty($properties[TAO_DELIVERY_END_PROP])) {
                $dt = new \DateTime($properties[TAO_DELIVERY_END_PROP], $tz);
                $properties[TAO_DELIVERY_END_PROP] = (string) $dt->getTimestamp();
            }
            
            $serviceCall = $report->getData();
            $properties[PROPERTY_COMPILEDDELIVERY_DIRECTORY] = $storage->getSpawnedDirectoryIds();
            $compilationInstance = \taoDelivery_models_classes_DeliveryAssemblyService::singleton()->createAssemblyFromServiceCall($deliveryClass, $serviceCall, $properties);
            $report->setData($compilationInstance);
        }
        return $report;
    }
}
