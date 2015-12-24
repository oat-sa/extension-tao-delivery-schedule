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

use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\AssignmentService;

/**
 * Delivery test takers service
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class DeliveryTestTakersService extends \tao_models_classes_Service
{
    
    /**
     * Get test takers assigned to the delivery.
     * @param \core_kernel_classes_Resource $delivery
     * @return array list of assigned and excluded test takers.
     * Example:
     * <pre>
     * array(
     *   'ttassigned' => array(
     *     array(
     *       'uri' => 'http://sample/first.rdf#i14298035456775118',
     *       'label' => 'test taker 1',
     *       'firstname' => 'test',    
     *       'lastname' => 'taker'    
     *     ),
     *     ...   
     *   )
     *   'ttexcluded' => array(
     *     array(
     *       'uri' => 'http://sample/first.rdf#i14303206619279661',
     *       'label' => 'test taker 2',
     *       'firstname' => 'test',    
     *       'lastname' => 'taker'    
     *     ),
     *     ...   
     *   )
     * )
     * </pre>
     */
    public function getDeliveryTestTakers(\core_kernel_classes_Resource $delivery)
    {
        $result = array(
            'ttexcluded'=>array(),
            'ttassigned'=>array(),
        );
        // excluded test takers
        $excludedSubjProperty = new \core_kernel_classes_Property(TAO_DELIVERY_EXCLUDEDSUBJECTS_PROP);
        $excluded = $delivery->getPropertyValues($excludedSubjProperty);

        foreach ($excluded as $testTaker) {
            $result['ttexcluded'][] = $this->getTestTakerData(new \core_kernel_classes_Resource($testTaker));
        }

        // assigned test takers
        $users = ServiceManager::getServiceManager()->get(AssignmentService::CONFIG_ID)->getAssignedUsers($delivery->getUri());
        $assigned = array_values(array_diff(array_unique($users), $excluded));
        
        foreach ($assigned as $testTaker) {
            $result['ttassigned'][] = $this->getTestTakerData(new \core_kernel_classes_Resource($testTaker));
        }
        
        return $result;
    }
    
    /**
     * Get test taker properties
     * @param \core_kernel_classes_Resource $testTaker
     * @return array
     */
    public function getTestTakerData(\core_kernel_classes_Resource $testTaker)
    {
        $result = array();
        $properties = array(
            RDFS_LABEL,
            PROPERTY_USER_FIRSTNAME,
            PROPERTY_USER_LASTNAME,
        );
        $values = $testTaker->getPropertiesValues($properties);
        
        foreach($values as $key => $value) {
            $result[$key] = (string) current($value);
        }
        $result['uri'] = $testTaker->getUri();
        return $this->mapDeliveryProperties($result, true);
    }
    
    /**
     * Change array keys in accordance with RDF properties.
     * Example:
     * <pre>
     * DeliveryTestTakersService::singleton()->mapDeliveryProperties(
     *     array(
     *         'label' => 'test taker 2',
     *         'firstname' => 'test',    
     *         'lastname' => 'taker'    
     *     )
     * );
     * </pre>
     * returns:
     * <pre>
     * array(
     *     'http://www.w3.org/2000/01/rdf-schema#label' => 'test taker 2',
     *     'http://www.tao.lu/Ontologies/generis.rdf#userFirstName' => 'test',    
     *     'http://www.tao.lu/Ontologies/generis.rdf#userLastName' => 'taker'    
     * )
     * </pre>
     * @param array $data 
     * @param boolean $reverse
     * @return array
     */
    public function mapDeliveryProperties($data, $reverse = false)
    {
        $map = array(
            RDFS_LABEL => 'label', 
            PROPERTY_USER_LASTNAME => 'lastname', 
            PROPERTY_USER_FIRSTNAME => 'firstname',
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
     * Save excluded test takers
     * @param \core_kernel_classes_Resource $delivery Delivery instance
     * @param array $excluded List of excluded testakers (uri)
     * @return boolean
     */
    public function saveExcludedTestTakers(\core_kernel_classes_Resource $delivery, $excluded) {
        $success = $delivery->editPropertyValues(
            new \core_kernel_classes_Property(TAO_DELIVERY_EXCLUDEDSUBJECTS_PROP),
            $excluded
        );

        return $success;
    }
}
