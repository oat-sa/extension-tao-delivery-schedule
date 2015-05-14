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
         * Calendar modal constructor.
         * 
         * @constructor
         * @property {object}         options Modal options.
         */
        return function (options) {
            var that = this,
                defaultOptions = {
                    prerender : true,
                    content : {
                        text : ' ',
                        title : ' '
                    },
                    position: {
                        my : 'center', at: 'center',
                        target : $(window),
                        adjust : {
                            scroll : false
                        }
                    },
                    show : {
                        ready : false,
                        modal : {
                            on : true,
                            blur : true,
                            escape : true
                        }
                    },
                    hide : false,
                    style : {
                        width : 800,
                        classes : 'dialogue qtip-light qtip-shadow'
                    }
                };
            
            this.init = function () {
                $.fn.qtip.modal_zindex = 8800;
                options = _.merge(defaultOptions, options);
                this.modal = $('<div />').qtip(options).qtip('api');
            };
        
            this.hide = function () {
                if (!this.modal.elements.tooltip.is(':visible')) {
                    return;
                }
                this.modal.hide();
            };
            
            this.set = function (options) {
                this.modal.set(options);
            };
            
            this.init();
        };
    }
);


