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
use oat\taoDeliverySchedule\form\WizardForm;
use oat\taoDeliverySchedule\model\DeliveryFactory;

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
        $this->setView('Main/index.tpl');
    }
    
    /**
     * Create new delivery
     * 
     * @access public
     * @author Aleh Hutnikau <hutnikau@1pt.com>
     * @return void 
     */
    public function wizard()
    {
        try {
            $formContainer = new WizardForm(array('class' => $this->getCurrentClass()));
            $myForm = $formContainer->getForm();
            
            if ($myForm->isValid() && $myForm->isSubmited()) {
                $label = $myForm->getValue('label');
                $start = $myForm->getValue('start');
                $end = $myForm->getValue('end');
                $test = new \core_kernel_classes_Resource($myForm->getValue('test'));
                $deliveryClass = new \core_kernel_classes_Class($myForm->getValue('classUri'));
                $report = DeliveryFactory::create($deliveryClass, $test, array(
                        RDFS_LABEL => $label, 
                        TAO_DELIVERY_START_PROP => $start, 
                        TAO_DELIVERY_END_PROP => $end
                    )
                );
                $data = $report->getdata();
                $result = array(
                    'message' => $report->getMessage(),
                    'id' => \tao_helpers_Uri::encode($data->getUri()),
                    'uri' => $data->getUri(),
                );
                
                $this->returnJson($result);
            } else {
                $this->setData('myForm', $myForm->render());
                $this->setView('tooltips/createEventTooltip.tpl');
            }
    
        } catch (taoSimpleDelivery_actions_form_NoTestsException $e) {
            $this->setView('tooltips/createEventTooltip.tpl');
        }
    }
    
}
