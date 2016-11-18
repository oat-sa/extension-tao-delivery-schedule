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
    'helpers',
    'taoDeliverySchedule/calendar/tooltips/eventTooltip',
    'handlebars',
    'i18n',
    'taoDeliverySchedule/calendar/mediator',
    'moment',
    'taoDeliverySchedule/lib/qtip/jquery.qtip'
], function (_, $, helpers, eventTooltip, Handlebars, __, mediator, moment) {
    'use strict';
    return function () {
        var that = this;

        eventTooltip.apply(this, arguments);

        this.validateRules = {
            '[name="label"]' : {
                validate : function () {
                    return $(this).val().length !== 0;
                },
                message : __('This field is required')
            }
        };

        /**
         * Init create event tooltip
         * @returns {undefined}
         */
        this.init = function () {
            that.set({
                'content.title' : __('Create a new delivery'),
                'style.width' : 400,
                'position.adjust.resize' : true,
                'events.hide': function () {mediator.fire('hide.createEventTooltip'); }
            });
        };

        /**
         * Load form template and show tooltip. 
         * 
         * @param {object} options
         * @property {object} options.start momentjs object
         * @property {object} options.end momentjs object
         * @property {string} options.end classUri
         * @property {string} options.end id
         * @returns {undefined}
         */
        this.show = function (options) {
            var start = moment.tz(options.start.clone().format('YYYY-MM-DD HH:mm'), options.timeZoneName),
                end = moment.tz(options.end.clone().format('YYYY-MM-DD HH:mm'), options.timeZoneName),
                startUTCStr = start.utc().format('YYYY-MM-DD HH:mm'),
                endUTCStr = end.utc().format('YYYY-MM-DD HH:mm'),
                tplOptions = {
                    start : options.start.format('ddd, MMMM D, H:mm'),
                    end : options.end ? options.end.format('ddd, MMMM D, H:mm') : false
                };

            $.ajax({
                url : helpers._url('createDeliveryForm', 'Main', 'taoDeliverySchedule'),
                type : 'GET',
                data : {
                    classUri : options.classUri,
                    id : options.id
                },
                success : function (response) {
                    var tpl = Handlebars.compile(response),
                        $form;

                    that.tooltip.set({
                        'content.text' : tpl(tplOptions),
                        'position.target' : options.target || options.e.target
                    });
                    that.tooltip.show();

                    $form = that.getForm();
                    $form.find('#label').focus();
                    $form.find('[name="start"]').val(startUTCStr);
                    $form.find('[name="end"]').val(endUTCStr);

                    that.$form = $form;

                    that.$form.on('keyup', 'input, select', function() {
                        that.validate(that.validateRules);
                    });

                    that.$form.find('[name="test"]').on('change', function () {
                        var text = $(this).find('option[value="' + $(this).val() + '"]').text(),
                            $label = $form.find('#label');
                        $label.val(text).focus();
                    }).trigger('change');

                    that.tooltip.elements.content.find('.js-create-event').on('click', function (e) {
                        e.preventDefault();
                        $form.submit();
                    });

                    $form.on('submit', function (e) {
                        e.preventDefault();
                        that.submit($(this), e);
                    });
                }
            });
        };

        /**
         * Fire <b>submit.createEventTooltip</b> event.
         * @param {object} e event
         * @fires createEventTooltip#submit.createEventTooltip
         * @returns {undefined}
         */
        this.submit = function (e) {
            if (this.validate(this.validateRules)) {
                mediator.fire('submit.createEventTooltip', that.getFormData());
            }
        };

        /**
         * Get form jQueryElement.
         * @returns {jQuery} form element
         */
        this.getForm = function () {
            return that.tooltip.elements.content.find('form');
        };

        /**
         * Convert form data to JS object
         * @returns {object} form data
         */
        this.getFormData = function () {
            var data = {},
                $form = that.getForm();
            $form.serializeArray().map(function (x) {data[x.name] = x.value; });
            return data;
        };

        this.init();
    };
});


