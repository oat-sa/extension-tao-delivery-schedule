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
        'taoDeliverySchedule/calendar/tooltips/eventTooltip',
        'tpl!/taoDeliverySchedule/views/templates/tooltips/eventTooltip',
        'layout/actions',
        'layout/actions/binder',
        'taoDeliverySchedule/calendar/eventService',
        'taoDeliverySchedule/lib/qtip/jquery.qtip'
    ],
    function (_, $, eventTooltip, tooltipTpl, actionManager, binder, eventService) {
        'use stirct';
        return function (options) {
            var that = this;
            
            eventTooltip.apply(this, arguments);
            
            /**
             * Init event tooltip
             * @returns {undefined}
             */
            this.init = function () {
                that.tooltip.elements.content.on('click', '.js-edit-event', function (e) {
                    e.preventDefault();
                    eventService.editEvent(that.getId());
                });
                
                that.tooltip.elements.content.on('click', '.js-delete-event', function (e) {
                    e.preventDefault();
                    eventService.deleteEvent(that.getId());
                });
                
                that.tooltip.elements.content.on('click', '.js-go-to-parent-event', function (e) {
                    e.preventDefault();
                    var fcEvent = eventService.getEventById(that.getId());
                    if (fcEvent.parentEventId) {
                        //eventService.selectEvent(fcEvent.parentEventId);
                        $('.' + eventService.classAttrPrefix + fcEvent.parentEventId).trigger('click');
                    }
                });
            };
            
            /**
             * Show tooltip
             * @returns {undefined}
             */
            this.show = function (fcEvent) {
                var tplOptions = {
                    start : fcEvent.start.format('ddd, MMMM D, H:mm'),
                    end : fcEvent.end ? fcEvent.end.format('ddd, MMMM D, H:mm') : false,
                    color : fcEvent.color || 'transparent',
                    fcEvent : fcEvent
                };
                
                that.tooltip.set({
                    'content.text' : tooltipTpl(tplOptions),
                    'position.adjust.resize' : true,
                    'content.title' : '<b>' + fcEvent.title + '</b>'
                });
                
                that.tooltip.elements.titlebar.css({'border-bottom' : '2px solid ' + tplOptions.color});
                
                that.tooltip.show();
            };
            
            /**
             * Get event id
             * @returns {string}
             */
            this.getId = function () {
                return that.tooltip.elements.tooltip.find('input[name="id"]').val();
            };
            
            /**
             * Get event class uri
             * @returns {string}
             */
            this.getClassUri = function () {
                return that.tooltip.elements.tooltip.find('input[name="classUri"]').val();
            };
            
            this.init();
        };
    }
);


