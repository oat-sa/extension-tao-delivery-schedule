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

namespace oat\taoDeliverySchedule\form;

/**
 * 
 * @access public
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package tao
 */
class WizardForm extends \taoDelivery_actions_form_WizardForm
{

    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('simpleWizard');
        /*$controlsElt = \tao_helpers_form_FormFactory::getElement('create', 'Free');
        $controlsElt->setValue(
            '<hr>
            <p class="event-tooltip__controls">
                <a href="#" class="js-close">' + __('Cancel') + '</a>
                <a href="#" class="js-create-event">' + __('Create') + ' &raquo;</a>
            </p>'
        );
        $this->form->setActions(array($controlsElt), 'bottom');*/
    }

   /**
    * Initialize create delivery form elements
    *
    * @access public
    * @author Aleh Hutnikau <hutnikau@1pt.com>
    */
    public function initElements()
    {
        /*$class = $this->data['class'];
        if(!$class instanceof core_kernel_classes_Class) {
            throw new common_Exception('missing class in simple delivery creation form');
        }
        
        $classUriElt = tao_helpers_form_FormFactory::getElement('classUri', 'Hidden');
        $classUriElt->setValue($class->getUri());
        $this->form->addElement($classUriElt);
        
        //create the element to select the import format

        $formatElt = tao_helpers_form_FormFactory::getElement('test', 'Combobox');
        $formatElt->setDescription(__('Select the test you want to publish to the test-takers'));
        $testClass = new core_kernel_classes_Class(TAO_TEST_CLASS);
        $options = array();
        foreach ($testClass->getInstances(true) as $test) {
            $options[$test->getUri()] = $test->getLabel();
        } 
        
        if (empty($options)) {
            throw new taoDelivery_actions_form_NoTestsException();
        }
        $formatElt->setOptions($options);
        $formatElt->addValidator(tao_helpers_form_FormFactory::getValidator('NotEmpty'));
        $this->form->addElement($formatElt);*/
        $labelElt = \tao_helpers_form_FormFactory::getElement('label', 'Textbox');
        $labelElt->setDescription(__('Label'));
        $this->form->addElement($labelElt);
        
        $startElt = \tao_helpers_form_FormFactory::getElement('start', 'Hidden');
        $this->form->addElement($startElt);
        
        $endElt = \tao_helpers_form_FormFactory::getElement('end', 'Hidden');
        $this->form->addElement($endElt);
        
        parent::initElements();
    }
}