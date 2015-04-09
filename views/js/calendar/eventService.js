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
        'jquery'
    ],
    function (_, $) {
        'use stirct';
        return function EventService(calendar) {
            var that = this;
            
            this.idAttrPrefix = 'fc_event_id_';
            
            this.addEvent = function (eventData) {
                
            };
            
            this.deleteEvent = function (eventId) {
                
            };
            
            /**
             * Get event jQuery DOM element by id 
             * @param {string} id Event id
             * @returns {jQuery element}
             */
            this.getEventElement = function (id) {
                return $('#' + that.idAttrPrefix + id);
            };
        };
    }
);