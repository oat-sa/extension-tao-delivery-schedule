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

return array(
    'name' => 'taoDeliverySchedule',
    'label' => 'Delivery schedule',
    'description' => 'An extension is used to visualize the delivery schedule in the form of a calendar.',
    'license' => 'GPL-2.0',
    'version' => '2.3.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'tao' => '>=15.4.0',
        'taoDeliveryRdf' => '>=3.12.0'
    ),
    // for compatibility
    'dependencies' => array('tao', 'taoDelivery'),
    'managementRole' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryManagerRole',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryManagerRole', array('ext'=>'taoDeliverySchedule'))
    ),
    'models' => array(),
    'install' => array(
        'rdf' => array(
            dirname(__FILE__). '/scripts/install/deliverySchedule.rdf'
        ),
        'php' => array(
            dirname(__FILE__) . '/scripts/install/registerServices.php',
        )
    ),
    // not supported 'uninstall' => array(),
    'update' => 'oat\\taoDeliverySchedule\\scripts\\update\\Updater',
    'autoload' => array(
        'psr-4' => array(
            'oat\\taoDeliverySchedule\\' => dirname(__FILE__).DIRECTORY_SEPARATOR
        )
    ),
    'routes' => array(
        '/taoDeliverySchedule' => 'oat\\taoDeliverySchedule\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => dirname(__FILE__).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,
	    
        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL.'taoDeliverySchedule/',
    ),
    'extra' => array(
        'structures' => dirname(__FILE__).DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    )
);
