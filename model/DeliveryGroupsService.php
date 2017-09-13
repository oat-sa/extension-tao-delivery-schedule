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

use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\GroupAssignment;

/**
 * Delivery groups service
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class DeliveryGroupsService extends ConfigurableService
{
    const CONFIG_ID = 'taoDeliverySchedule/DeliveryGroupsService';

    /**
     * Assign deliveries to groups
     *
     * @param \core_kernel_classes_Resource $delivery Delivery or RepeatedDelivery instance
     * @param array $values List of groups (uri)
     * @return boolean
     */
    public function saveGroups(\core_kernel_classes_Resource $delivery, $values)
    {
        $property = new \core_kernel_classes_Property(GroupAssignment::GROUP_DELIVERY);

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
     * Get groups to which delivery is assigned.
     * @param \core_kernel_classes_Resource $delivery
     * @return \core_kernel_classes_Resource[]
     */
    public function getGroups(\core_kernel_classes_Resource $delivery)
    {
        $result = array();
        $groupsProperty = new \core_kernel_classes_Property(GroupAssignment::GROUP_DELIVERY);
        $domainCollection = $groupsProperty->getDomain();
        if (!$domainCollection->isEmpty()) {
            $domain = $domainCollection->get(0);
            $result = $domain->searchInstances(array(
                $groupsProperty->getUri() => $delivery
            ),
            array('recursive' => false, 'like' => false));
        }
        return $result;
    }
}
