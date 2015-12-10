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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliverySchedule\scripts\update;

use oat\oatbox\service\ConfigurableService;
use tao_helpers_data_GenerisAdapterRdf;
use common_Logger;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDeliverySchedule\model\RepeatedDeliveryService;
use oat\taoDeliverySchedule\model\DeliveryGroupsService;
use oat\taoDeliverySchedule\model\AssignmentService;
use oat\oatbox\service\ServiceNotFoundException;

/**
 * 
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class Updater extends \common_ext_ExtensionUpdater {
    
    /**
     * 
     * @param string $initialVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion) {
        
        $currentVersion = $initialVersion;

        if ($currentVersion == '0.1') {
            $file = dirname(__FILE__).DIRECTORY_SEPARATOR.'model_0_1_1.rdf';

            $adapter = new tao_helpers_data_GenerisAdapterRdf();

            if ($adapter->import($file)) {
                $currentVersion = '0.1.1';
            } else{
                common_Logger::w('Import failed for '.$file);
            }
        }

        if ($currentVersion == '0.1.1') {
            OntologyUpdater::syncModels();
            $currentVersion = '0.1.2';
        }

        if ($currentVersion === '0.1.2') {

            try {
                $this->getServiceManager()->get(RepeatedDeliveryService::CONFIG_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new RepeatedDeliveryService();
                $service->setServiceManager($this->getServiceManager());

                $this->getServiceManager()->register(RepeatedDeliveryService::CONFIG_ID, $service);
            }

            try {
                $this->getServiceManager()->get(DeliveryGroupsService::CONFIG_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new DeliveryGroupsService();
                $service->setServiceManager($this->getServiceManager());

                $this->getServiceManager()->register(DeliveryGroupsService::CONFIG_ID, $service);
            }
            $currentVersion = '0.1.3';
        }

        if ($currentVersion === '0.1.3') {

            $assignmentService = new AssignmentService();
            $assignmentService->setServiceManager($this->getServiceManager());
            $this->getServiceManager()->register(AssignmentService::CONFIG_ID, $assignmentService);

            // removed, class no longer exists
            
            // $currentDeliveryServerServiceConfig = $this->getServiceManager()->get(\taoDelivery_models_classes_DeliveryServerService::CONFIG_ID);
            // if ($currentDeliveryServerServiceConfig instanceof ConfigurableService) {
            //     $currentDeliveryServerServiceConfig = $currentDeliveryServerServiceConfig->getOptions();
            // }
            // $deliveryServerService = new DeliveryServerService($currentDeliveryServerServiceConfig);
            // $deliveryServerService->setServiceManager($this->getServiceManager());
            // $this->getServiceManager()->register(DeliveryServerService::CONFIG_ID, $deliveryServerService);

            $currentVersion = '0.1.4';
        }

        if ($currentVersion === '0.1.4') {

            // prevent missing class error
            $currentService = $this->safeLoadService(\taoDelivery_models_classes_DeliveryServerService::CONFIG_ID);
            if (class_exists('\\oat\\taoDeliverySchedule\\model\\DeliveryServerService', false)
                && $currentService instanceof \oat\taoDeliverySchedule\model\DeliveryServerService) {
                    
                $service = new \taoDelivery_models_classes_DeliveryServerService($currentService->getOptions());
                $this->getServiceManager()->register(\taoDelivery_models_classes_DeliveryServerService::CONFIG_ID, $service);
            }
            $this->setVersion('1.0.0');
            $currentVersion = null;
        }

        return $currentVersion;
    }
}
