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
    'taoDeliverySchedule/lib/qtip/jquery.qtip'
], function (_, $) {
    'use strict';
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
            } else {
                options = defaultOptions;
            }
            this.tooltip = $('<div/>').qtip(options).qtip('api');
            this.tooltip.elements.content.on('click', '.js-close', function (e) {
                e.preventDefault();
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

        /**
         * Validate tooltip form by given rules. Error fields will me marked by <i>error</i> class and tooltip with error message.
         * @param {object} rules - Rules for calidate form.
         * Example:
         * <pre>
         * {
         *   '.js-delivery-end-date, .js-delivery-start-date' : { //field selector
         *     validate : function () { //valudate function. Must return boolean value (whether field is valid). <i>this<i> valiable inside function refers to field element.
         *       return /^\d\d\d\d-\d\d-\d\d$/.test($(this).val());
         *     },
         *     message : __('The format of date is invalid') //error message
         *   },
         *   '.js-label' : {
         *     validate : function () {
         *       return $(this).val().length !== 0;
         *     },
         *     message : __('This field is required')
         *   }
         * }
         * </pre>
         * @returns {Boolean} whether form is valid
         */
        this.validate = function (rules) {
            var formIsValid = true,
                qtipApi,
                selector,
                $elements;

            for (selector in rules) {
                $elements = that.$form.find(selector);
                $elements.each(function () {
                    var valid = rules[selector].validate.apply(this);
                    qtipApi = $(this).qtip('api');
                    if (!qtipApi) {
                        qtipApi = $(this).qtip({
                            content: {
                                text: rules[selector].message
                            },
                            position: {
                                target: 'mouse', // Track the mouse as the positioning target
                                adjust: { x: 5, y: 5 } // Offset it slightly from under the mouse
                            }
                        }).qtip('api');
                    }

                    $elements.toggleClass('error', !valid);
                    qtipApi.toggle(!valid).disable(valid);

                    formIsValid = formIsValid && valid;
                });
            }

            return formIsValid;
        };

        this.init();
    };
});