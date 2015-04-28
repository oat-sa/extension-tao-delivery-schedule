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
            \tao_helpers_Uri::encode(RDFS_LABEL) => 'label', 
            \tao_helpers_Uri::encode(TAO_DELIVERY_START_PROP) => 'start', 
            \tao_helpers_Uri::encode(TAO_DELIVERY_END_PROP) => 'end'
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
}
