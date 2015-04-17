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
         * @property {object}         options.callback List of tooltip callbacks
         * @property {function}       options.callback.beforeHide 
         * @property {function}       options.callback.afterHide
         * @property {function}       options.callback.beforeShow
         * @property {function}       options.callback.aftersShow
         */
        return function (options) {
            var that = this;
        
            this.init = function () {
                this.tooltip = $('<div/>').qtip(
                    {
                        prerender : true,
                        content : {
                            text : ' ',
                            title : ' '
                        },
                        position : {
                            my : 'bottom center',
                            at : 'top center',
                            //target : 'mouse',
                            viewport : options.$container
                        },
                        show : false,
                        hide : false,
                        style : {
                            width : 280,
                            classes : 'qtip-light qtip-shadow'
                        }
                    }
                ).qtip('api');
        
                this.tooltip.elements.content.on('click', '.js-close', function () {
                    that.hide();
                });
            };
        
            this.callback = function (name, e) {
                if (options.callback && _.isFunction(options.callback[name])) {
                    var args = Array.prototype.slice.call(arguments, 1);
                    options.callback[name].apply(this, args);
                }
            };
        
            this.hide = function () {
                if (!this.tooltip.elements.tooltip.is(':visible')) {
                    return;
                }
                this.callback('beforeHide');
                this.tooltip.hide();
                this.callback('afterHide');
            };
            
            this.set = function (options) {
                this.tooltip.set(options);
            };
            
            this.init();
        };
    }
);


