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
                            scroll : false,
                            method : 'shift'
                        },
                        viewport : $(document)
                    },
                    show : false,
                    hide : false,
                    style : {
                        width : 280,
                        classes : 'qtip-light qtip-shadow'
                    }
                };
            
            
            this.init = function () {
                if (typeof options === 'object') {
                    options = _.merge(defaultOptions, options);
                } else  {
                    options = defaultOptions;
                }
                this.tooltip = $('<div/>').qtip(options).qtip('api');
                this.tooltip.elements.content.on('click', '.js-close', function () {
                    that.hide();
                });
            };
        
            /**
             * Hide tooltip
             * @returns {undefined}
             */
            this.hide = function () {
                if (!this.isShown()) {
                    return;
                }
                this.tooltip.hide();
            };
            
            /**
             * Whether tooltip is shown.
             * @returns {boolean}
             */
            this.isShown = function () {
                return this.tooltip.elements.tooltip.is(':visible');
            };
            
            /**
             * Set dialogue (qtip2) options.
             * @see http://qtip2.com/api#api-methods.set
             * @param {type} options
             * @returns {undefined}
             */
            this.set = function (options) {
                this.tooltip.set(options);
            };
            
            this.init();
        };
    }
);


