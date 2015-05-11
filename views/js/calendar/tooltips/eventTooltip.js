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
        'taoDeliverySchedule/lib/qtip/jquery.qtip'
    ],
    function (_, $) {
        'use stirct';
        /**
         * Calendar tooltop constructor.
         * 
         * @constructor
         * @property {object}         options Tooltop options.
         * @property {jQueryElement}  options.$container Determines the HTML element which the tooltip is appended to
         */
        return function (options) {
            var that = this,
                defaultOptions = {
                    prerender : true,
                    content : {
                        text : ' ',
                        title : ' '
                    },
                    position : {
                        my : 'bottom center',
                        at : 'top center',
                        adjust: {
                            scroll: false,
                            method: 'shift'
                        }
                    },
                    show : false,
                    hide : false,
                    style : {
                        width : 280,
                        classes : 'qtip-light qtip-shadow'
                    }
                };
            
            
            this.init = function () {
                options = _.merge(defaultOptions, options);
                this.tooltip = $('<div/>').qtip(options).qtip('api');
                this.tooltip.elements.content.on('click', '.js-close', function () {
                    that.hide();
                });
            };
        
            /**
             * Hide tooltip
             * @fires eventTooltip#hide:eventTooltip.hide
             * @returns {undefined}
             */
            this.hide = function () {
                if (!this.tooltip.elements.tooltip.is(':visible')) {
                    return;
                }
                this.tooltip.hide();
                
            };
            
            this.set = function (options) {
                this.tooltip.set(options);
            };
            
            this.init();
        };
    }
);


