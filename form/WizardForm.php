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

use oat\taoDeliveryRdf\view\form\WizardForm as DeliveryWizard;
/**
 * 
 * @access public
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package tao
 */
class WizardForm extends DeliveryWizard
{

    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('simpleWizard');
    }

   /**
    * Initialize create delivery form elements
    *
    * @access public
    * @author Aleh Hutnikau <hutnikau@1pt.com>
    */
    public function initElements()
    {
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