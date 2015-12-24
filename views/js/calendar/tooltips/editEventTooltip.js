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
define([
    'lodash',
    'jquery',
    'taoDeliverySchedule/calendar/tooltips/eventTooltip',
    'tpl!taoDeliverySchedule/calendar/tooltips/eventTooltip',
    'taoDeliverySchedule/calendar/mediator',
    'taoDeliverySchedule/lib/qtip/jquery.qtip'
], function (_, $, eventTooltip, tooltipTpl, mediator) {
    'use strict';

    return function () {
        var that = this;

        eventTooltip.apply(this, arguments);

        /**
         * Init event tooltip.
         * @returns {undefined}
         */
        this.init = function () {
            that.set({
                'events.hide': function () {mediator.fire('hide.editEventTooltip'); },
                'position.adjust.resize' : true
            });

            that.tooltip.elements.content.on('click', '.js-edit-event', function (e) {
                e.preventDefault();
                mediator.fire('edit.editEventTooltip');
            });

            that.tooltip.elements.content.on('click', '.js-delete-event', function (e) {
                e.preventDefault();
                mediator.fire('delete.editEventTooltip', that.getId());
            });

            that.tooltip.elements.content.on('click', '.js-go-to-parent-event', function (e) {
                e.preventDefault();
                mediator.fire('to-parent.editEventTooltip', that.getId());
            });
        };

        /**
         * Show tooltip
         * @param {object} fullcalendar event
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
                'content.title' : '<b>' + _.escape(fcEvent.title) + '</b>'
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
});


