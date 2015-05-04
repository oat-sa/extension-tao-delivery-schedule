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
 * @access public
 * @package taoDeliverySchedule
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class EditDeliveryForm extends \tao_actions_form_Instance
{
    protected function initForm()
    {
        parent::initForm();
    }
    
    protected function initElements()
    {
        parent::initElements();
        $labelElt = $this->form->getElement(\tao_helpers_Uri::encode(RDFS_LABEL));
        if ($labelElt !== null) {
            $labelElt->addAttribute('noLabel', true);
            $labelElt->addAttribute('class', 'full-width js-label');
            $labelElt->addAttribute('value', '{{label}}');
            $labelElt->setName('label');
            $labelElt->addValidators(array(
                \tao_helpers_form_FormFactory::getValidator('NotEmpty')
            ));
            $this->form->addElement($labelElt);
        }
        
        $maxExecElt = $this->form->getElement(\tao_helpers_Uri::encode(TAO_DELIVERY_MAXEXEC_PROP));
        if ($maxExecElt !== null) {
            $maxExecElt->addValidators(array(
                \tao_helpers_form_FormFactory::getValidator('Integer', array(
                    'min' => 1
                ))
            ));
            $maxExecElt->addAttribute('value', '{{maxexec}}');
            $maxExecElt->addAttribute('noLabel', true);
            $maxExecElt->setName('maxexec');
            $maxExecElt->addAttribute('class', 'full-width js-maxexec');
            $this->form->addElement($maxExecElt);
        }
        
        $resultServerElt = $this->form->getElement(\tao_helpers_Uri::encode(TAO_DELIVERY_RESULTSERVER_PROP));
        if ($resultServerElt !== null) {
            $resultServerElt->addAttribute('noLabel', true);
            $resultServerElt->addAttribute('class', 'full-width');
            $resultServerElt->setName('resultserver');
            $resultServerElt->addValidators(array(
                \tao_helpers_form_FormFactory::getValidator('NotEmpty')
            ));
            $this->form->addElement($resultServerElt);
        }
    }
}