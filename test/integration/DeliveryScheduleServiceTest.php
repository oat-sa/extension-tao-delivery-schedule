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

use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\generis\model\OntologyRdfs;
use oat\taoDeliverySchedule\model\DeliveryScheduleService;
use oat\tao\test\TaoPhpUnitTestRunner;

include_once dirname(__FILE__) . '/../../includes/raw_start.php';

/**
 * Delivery schedule service
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class DeliveryScheduleServiceTest extends TaoPhpUnitTestRunner
{

    public function setUp(): void
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

        $label = OntologyRdfs::RDFS_LABEL;
        $start = DeliveryContainerService::PROPERTY_START;
        $end = DeliveryContainerService::PROPERTY_END;

        $properties = $service->mapDeliveryProperties($data);
        $this->assertTrue(isset($properties[$label]) && $properties[$label] === $data['label']);
        $this->assertTrue(isset($properties[$start]) && $properties[$start] === $data['start']);
        $this->assertTrue(isset($properties[$end]) && $properties[$end] === $data['end']);
        $this->assertTrue($service->mapDeliveryProperties($properties) === $properties);

        $reverceProperties = $service->mapDeliveryProperties($properties, true);

        sort($reverceProperties);
        sort($data);

        $this->assertTrue($reverceProperties === $data);
    }

    public function testGetEvaluatedParams()
    {
        $service = DeliveryScheduleService::singleton();

        $params = array(
            DeliveryContainerService::PROPERTY_START => '2015-05-05T00:00:00+0000',
            DeliveryContainerService::PROPERTY_END => '2015-05-05T05:00:00+0000',
            DeliveryContainerService::PROPERTY_RESULT_SERVER => 'http_2_www_0_tao_0_lu_1_Ontologies_1_TAOResultServer_0_rdf_3_void'
        );

        $eveluatedParams = $service->getEvaluatedParams($params);

        $this->assertEquals(1430784000, $eveluatedParams[DeliveryContainerService::PROPERTY_START]);
        $this->assertEquals(1430802000, $eveluatedParams[DeliveryContainerService::PROPERTY_END]);
        $this->assertEquals('http://www.tao.lu/Ontologies/TAOResultServer.rdf#void', $eveluatedParams[DeliveryContainerService::PROPERTY_RESULT_SERVER]);
    }

    public function testGetErrors()
    {
        $service = DeliveryScheduleService::singleton();

        $start = new \DateTime();
        $end = new \DateTime();
        $end->modify('+1 day');

        //all fields are valid
        $params = array(
            DeliveryContainerService::PROPERTY_START => $start->getTimestamp(),
            DeliveryContainerService::PROPERTY_END => $end->getTimestamp(),
            OntologyRdfs::RDFS_LABEL => 'Delivery name',
            DeliveryContainerService::PROPERTY_MAX_EXEC => '3'
        );
        $this->assertTrue(empty($service->getErrors($params)));

        //empty end date
        $params[DeliveryContainerService::PROPERTY_END] = '';
        $errors = $service->getErrors($params);
        $this->assertTrue(isset($errors[DeliveryContainerService::PROPERTY_END]));

        //all fields are invalid
        $params[DeliveryContainerService::PROPERTY_MAX_EXEC] = 'str';
        $params[OntologyRdfs::RDFS_LABEL] = '';
        $end->modify('-2 day');
        $params[DeliveryContainerService::PROPERTY_END] = $end->getTimestamp();

        $errors = $service->getErrors($params);

        $this->assertTrue(isset($errors[DeliveryContainerService::PROPERTY_MAX_EXEC]));
        $this->assertTrue(isset($errors[OntologyRdfs::RDFS_LABEL]));
        $this->assertTrue(isset($errors[DeliveryContainerService::PROPERTY_START]));
    }

    /*public function testCreate()
    {
        $service = DeliveryScheduleService::singleton();
        $testsService = \taoTests_models_classes_TestsService::singleton();

        $tests = $testsService->getRootclass();
        $testInstanceLabel = 'Test instance';
        $testInstance = $testsService->createInstance($tests, $testInstanceLabel);

        $params = array(
            'label' => 'Delivery Name',
            'start' => '2015-05-18 00:00',
            'end' => '2015-05-19 00:00',
            'classUri' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery',
            'test' => $tests->getUri()
        );

        $report = $service->create($service->mapDeliveryProperties($params));
        var_dump($report);
        $this->assertTrue($report->getType() == \common_report_Report::TYPE_SUCCESS);

        $testInstance->delete();
    }*/

}
