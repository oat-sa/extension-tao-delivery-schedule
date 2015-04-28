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
 */
class EditDeliveryForm extends \taoDelivery_actions_form_Delivery
{
    /**
     * Validate form elements. 
     * Use set $this->getForm()->setValues($params) before validate to populate form values.
     */
    public function validate()
    {
        $returnValue = (bool) true;
        
        foreach($this->form->getElements() as $element){
            if(!$element->validate()){
                $returnValue = false;
            }
        }
        $this->form->valid = $returnValue;
        
        return (bool) $returnValue;
    }
    
    public function setValues($values) 
    {
        $this->form->setValues($values);
    }
    
    public function getValues() 
    {
        return $this->form->getValues();
    }

}
