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

use oat\taoDeliverySchedule\model\DeliveryScheduleService;
use oat\tao\test\TaoPhpUnitTestRunner;

include_once dirname(__FILE__) . '/../includes/raw_start.php';

/**
 * Delivery schedule service
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class DeliveryScheduleServiceTest extends TaoPhpUnitTestRunner
{
    
    public function setUp()
    {
        TaoPhpUnitTestRunner::initTest();
    }
    
    public function testMapDeliveryProperties()
    {
        $service = DeliveryScheduleService::singleton();
        
        $data = array(
            'label' => 'Delivery label',
            'classUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery',
            'id' => 'http_2_sample_1_first_0_rdf_3_i14302039015368604',
            'uri' => 'http://sample/first.rdf#i14302039015368604',
            'start' => '2015-04-13 00:00',
            'end' => '2015-04-14 00:00',
        );
        
        $labelEncoded = \tao_helpers_Uri::encode(RDFS_LABEL);
        $startEncoded = \tao_helpers_Uri::encode(TAO_DELIVERY_START_PROP);
        $endEncoded = \tao_helpers_Uri::encode(TAO_DELIVERY_END_PROP);
        
        $properties = $service->mapDeliveryProperties($data);
        $this->assertTrue(isset($properties[$labelEncoded]) && $properties[$labelEncoded] === 'Delivery label');
        $this->assertTrue(isset($properties[$startEncoded]) && $properties[$startEncoded] === '2015-04-13 00:00');
        $this->assertTrue(isset($properties[$endEncoded]) && $properties[$endEncoded] === '2015-04-14 00:00');
        
        $reverceProperties = $service->mapDeliveryProperties($properties, true);
        
        sort($reverceProperties);
        sort($data);
        
        $this->assertTrue($reverceProperties === $data);
    }
}
