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

use oat\taoGroups\models\GroupsService;
use oat\oatbox\user\User;

/**
 * Class AssignmentService
 *
 * Service to manage the assignment of users to deliveries
 *
 * @access public
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\taoDeliverySchedule\model
 */
class AssignmentService extends \taoDelivery_models_classes_AssignmentService
{
    const CONFIG_ID = 'taoDelivery/assignment';

    public static function singleton()
    {
        return ServiceManager::getServiceManager()->get(self::CONFIG_ID);
    }

    public function getAvailableDeliveries(User $user)
    {
        $deliveryUris = array();
        $repeatedDeliveryService = $this->getServiceManager()->get('taoDeliverySchedule/RepeatedDeliveryService');
        //check for guest access
        if($this->isDeliveryGuestUser($user)){
            $deliveryUris = $this->getGuestAccessDeliveries();
        } else {
            // check if realy available
            foreach (GroupsService::singleton()->getGroups($user) as $group) {
                $deliveries = $group->getPropertyValues(
                    new \core_kernel_classes_Property(PROPERTY_GROUP_DELVIERY)
                );
                foreach ($deliveries as $deliveryUri) {
                    $candidate = new \core_kernel_classes_Resource($deliveryUri);
                    if (!$this->isUserExcluded($candidate, $user) && $candidate->exists()) {
                        if ($repeatedDeliveryService->isRepeated($candidate)) {
                            $candidate = $repeatedDeliveryService->getParentDelivery($candidate);
                        }
                        $deliveryUris[] = $candidate->getUri();
                    }
                }
            }
        }
        return array_unique($deliveryUris);
    }


    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @param bool $checkRepeated Whether check repeated deliveries if main (not repeated) delivery given
     * @return bool
     */
    public function isUserAssigned(\core_kernel_classes_Resource $delivery, User $user, $checkRepeated = true){
        $returnValue = false;
        $isGuestUser = $this->isDeliveryGuestUser($user);
        $isGuestAccessibleDelivery = \taoDelivery_models_classes_DeliveryServerService::singleton()->hasDeliveryGuestAccess($delivery);

        //check for guest access mode
        if( $isGuestUser && $isGuestAccessibleDelivery ){
            $returnValue = true;
        } else {
            $repeatedDeliveryService = $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
            $currentRepeatedDelivery = $repeatedDeliveryService->getCurrentRepeatedDelivery($delivery);
            if ($currentRepeatedDelivery && $checkRepeated) {
                $delivery = $currentRepeatedDelivery;
            }
            $userGroups = GroupsService::singleton()->getGroups($user);
            $deliveryGroups = GroupsService::singleton()->getRootClass()->searchInstances(array(
                PROPERTY_GROUP_DELVIERY => $delivery->getUri()
            ), array(
                'like'=>false, 'recursive' => true
            ));

            $returnValue = count(array_intersect($userGroups, $deliveryGroups)) > 0 && !$this->isUserExcluded($delivery, $user);
        }

        return $returnValue;
    }

}