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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */
namespace oat\taoDeliverySchedule\model;

use core_kernel_classes_Property;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\model\AssignmentFactory;
use oat\oatbox\user\User;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
/**
 * Service to manage the assignment of users to deliveries
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class RepetionAssignmentFactory extends AssignmentFactory
{
    private $startTime;
    
    private $endTime;
    
    public function __construct(\core_kernel_classes_Resource $delivery, User $user, $startTime, $endTime, $startable)
    {
        parent::__construct($delivery, $user, $startable);
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }
    
    public function getStartTime()
    {
        return $this->startTime;
    }
    
    protected function getDescription()
    {
        $propMaxExec = $this->delivery->getOnePropertyValue(new core_kernel_classes_Property(DeliveryContainerService::MAX_EXEC_PROP));
        $maxExecs = is_null($propMaxExec) ? 0 : $propMaxExec->literal;
        
        $user = \common_session_SessionManager::getSession()->getUser();
        $countExecs = count(ServiceProxy::singleton()->getUserExecutions($this->delivery, $user->getIdentifier()));
        
        return $this->buildDescriptionFromData($this->startTime, $this->endTime, $countExecs, $maxExecs);
    }

    public function __equals(AssignmentFactory $factory)
    {
        return ($this->getDeliveryId() == $factory->getDeliveryId() && $this->getStartTime() == $factory->getStartTime());
    }
}