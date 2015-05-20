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

/**
 * Controller provides basic functionality fot Rest API.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class ApiBaseController extends \tao_actions_SaSModule
{
    protected $action;
    public function __call($name, $arguments) 
    {
        $this->sendData(array('message' => 'Method is not implemented'), 501, array(), true);
    }
    
    public function index() {
        $action = $this->action;
        if(is_callable(array($this, $action))){
            $this->$action();
        } else {
            $this->sendData(array('message' => 'Method is not implemented'), 501, array(), true);
        }
    }
    
    /**
     * Function converts $data array to json format and sends it to the client.
     * Usage example:
     * <pre>
     *   $this->sendData(
     *       array(...),
     *       200,
     *       array(
     *           "Content-Range: items 1/10",
     *       )
     *   );
     * </pre>
     * @param array $data 
     * @param int $status HTTP status code.
     * @param array $headers http headers array.
     * @param boolean $terminate whether application should be terminated after data was sended.
     */
    protected function sendData(array $data, $status = 200, array $headers = array(), $terminate = false)
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->getStatusCodeMessage($status);
        header($status_header);
        header('Content-Type: application/json');
        
        foreach($headers as $header){
            header($header);
        }
        echo json_encode($data);
        
        if ($terminate) {
            exit();
        }
    }
    
    /**
     * Funcion return HTTP status code message.
     * @param int $status status code.
     * @return string code message.
     */
    protected function getStatusCodeMessage($status)
    {
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
}
