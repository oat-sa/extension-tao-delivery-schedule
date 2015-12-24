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

use oat\taoDeliverySchedule\model\DeliveryScheduleService;
use oat\taoDeliverySchedule\model\DeliveryTestTakersService;
use oat\taoDeliverySchedule\controller\ApiBaseController;
use oat\taoGroups\models\GroupsService;
/**
 * Controller provides Rest API for managing (assign, exclude etc.) delivery test takers.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class TestTakersApi extends ApiBaseController
{
    public function __construct()
    {
        parent::__construct();
 
        $this->scheduleService = DeliveryScheduleService::singleton();
        $this->testTakersService = DeliveryTestTakersService::singleton();
        
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
                $this->sendData(array('message' => 'Method is not implemented'), 501);
                exit();
            default :
                $this->sendData(array('message' => 'Not found'), 404);
                exit();
        }
    }
    
    protected function get()
    {
        $requestParams = $this->getRequestParameters();
        
        $delivery = $this->getCurrentInstance();
        
        $deliveryUsers = $this->testTakersService->getDeliveryTestTakers($delivery);
        $result = array(
            'ttexcluded' => array(),
            'ttassigned' => array()
        );
        
        $excludedUsersUri = array_map(function ($val) {return $val['uri'];}, $deliveryUsers['ttexcluded']);
            
        if (isset($requestParams['groups'])) {
            $groupsService = GroupsService::singleton();
            foreach ($requestParams['groups'] as $group) {
                $users = $groupsService->getUsers(\tao_helpers_Uri::decode($group));
                foreach ($users as $user) {
                    if (!in_array($user->getUri(), $excludedUsersUri)) {
                        $result['ttassigned'][] = $this->testTakersService->getTestTakerData($user);
                    } else {
                        $result['ttexcluded'][] = $this->testTakersService->getTestTakerData($user);
                    }
                }
            }
        }
        
        $this->sendData($result);
    }
}
