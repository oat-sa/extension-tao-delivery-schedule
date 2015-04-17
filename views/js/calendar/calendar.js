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
        'taoDeliverySchedule/lib/fullcalendar/fullcalendar.amd'
    ],
    function (_, $) {
        'use stirct';
        
        /**
         * Function retuns height of calendar container
         * 
         * @param {jQueryElement} $contentBlock
         * @returns {integer} calendar height
         */
        function getCalendarHeight($contentBlock) {
            var height;
            $contentBlock = $contentBlock ? $contentBlock : $('.content-block');
            height = $('body > footer').offset().top - $contentBlock.offset().top;
            return height - parseInt($contentBlock.css('padding-top')) - parseInt($contentBlock.css('padding-bottom'));
        }
        
        /**
         * Calendar constructor.
         * 
         * @constructor
         * @property {object}        options Calendar options.
         * @property {jQueryElement} options.$container Calendar container.
         * @property {Date}          options.defaultDate Calendar The initial date displayed when the calendar first loads.
         */
        return function (options) {
            if (!(options.$container instanceof $) || !$.contains(document, options.$container[0])) {
                throw new TypeError("Calendar requires $container option that should be jQuery element.");
            }
            var defaultOptions,
            that = this;
    
            this.init = function () {
                defaultOptions = {
                    defaultDate : new Date(),
                    editable : true,
                    selectable : true,
                    selectHelper : false,
                    unselectAuto : false,
                    height : getCalendarHeight(),
                    eventLimit : false, // allow "more" link when too many events
                    select : _.noop
                };
                options = _.assign(defaultOptions, options);
                options.$container.fullCalendar(options);
            };

            
            this.exec = function () {
                return options.$container.fullCalendar.apply(options.$container, arguments);
            };
            
            /**
             * Add event to the calendar.
             * @param {object} eventData Event properties
             * @param {Date} eventData.start Event begin date
             * @param {Date} eventData.end Event end date
             * @returns {undefined}
             */
            this.addEvent = function (eventData) {
                that.exec('renderEvent', eventData, true); // stick? = true
            };
            
            this.init();
        };
    }
);