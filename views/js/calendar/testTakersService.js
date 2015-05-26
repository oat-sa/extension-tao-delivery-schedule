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
define(
    [
        'lodash',
        'jquery',
        'ui/feedback',
        'layout/loading-bar'
    ],
    function (_, $, feedback, loadingBar) {
        'use strict';
        var instance = null;
        
        function TestTakersService() {
            var that = this;
            
            if (instance !== null) {
                throw new Error("Cannot instantiate more than one TestTakersService, use TestTakersService.getInstance()");
            }
            
            this.loadTestTakers = function (options) {
                var deferred = $.Deferred();
                
                $.ajax({
                    url : '/taoDeliverySchedule/TesttakersApi',
                    type : 'GET',
                    data : options,
                    success : function (response) {
                        deferred.resolve(response);
                    },
                    error : function (xhr, err) {
                        deferred.rejext(xhr);
                    }
                });
                
                return deferred.promise();
            };
        };
        
        TestTakersService.getInstance = function () {
            // Gets an instance of the singleton.
            if (instance === null) {
                instance = new TestTakersService();
            }
            return instance;
        };
        
        return TestTakersService.getInstance();
    }
);