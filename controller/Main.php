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

use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliverySchedule\model\DeliveryScheduleService;
use oat\taoDeliverySchedule\form\WizardForm;
use oat\taoDeliverySchedule\form\EditDeliveryForm;

/**
 * Controller to managed assembled deliveries
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class Main extends \tao_actions_SaSModule
{

    /**
     * Index action. 
     *
     * @access public
     * @author Aleh Hutnikau <hutnikau@1pt.com>
     * @return void
     */
    public function index()
    {
        $this->setData('time-zone-name', \common_session_SessionManager::getSession()->getTimeZone());
        
        $this->setView('Main/index.tpl');
    }
    
    /**
     * Return create event tooltip markup
     */
    public function createDeliveryForm()
    {
        $formContainer = new WizardForm(array('class' => $this->getCurrentClass()));
        $myForm = $formContainer->getForm();
        $this->setData('myForm', $myForm->render());
        $this->setView('tooltips/createEventTooltip.tpl');
    }
    
    /**
     * Return edit delivery form markup
     */
    public function editDeliveryForm() 
    {
        $clazz = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_ID);
        
        $formContainer = new EditDeliveryForm($clazz);
        $myForm = $formContainer->getForm();
        
        $this->setData('form', $myForm);
        $this->setData('userTimeZone', \common_session_SessionManager::getSession()->getTimeZone());
        $this->setData('timeZones', DeliveryScheduleService::singleton()->getTimeZones());
        $this->setView('editDeliveryForm.tpl');
    }
    
    public function timeZoneList() 
    {
        $this->setData('timeZones', DeliveryScheduleService::singleton()->getTimeZones());
        $this->setView('timeZoneList.tpl');
    }
}
