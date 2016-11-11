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

/*global window,RRule,define*/
define([
'lodash',
'jquery',
'helpers',
'taoDeliverySchedule/calendar/modals/modal',
'text!editDeliveryForm',
'i18n',
'generis.tree.select',
'moment',
'handlebars',
'taoDeliverySchedule/calendar/eventService',
'taoDeliverySchedule/calendar/widgets/testTakersList',
'taoDeliverySchedule/calendar/mediator',
'taoDeliverySchedule/lib/moment-timezone-with-data.min',
'taoDeliverySchedule/lib/rrule/rrule.amd',
'taoDeliverySchedule/lib/jquery.serialize-object.min',
'taoDeliverySchedule/lib/qtip/jquery.qtip',
'jqueryui'], function (_, $, helpers, modal, formTpl, __, GenerisTreeSelectClass, moment, Handlebars, eventService, TestTakersList, mediator) {
    'use strict';
    return function () {
        var that = this,
            initialData,
            testTakersList;

        modal.apply(this, arguments);

        function pad(value) {
            return value < 10 ? '0' + value : value;
        }

        function createOffset(val) {
            var offset = Math.abs(val),
                sign = (offset < 0) ? "-" : "+",
                hours = pad(Math.floor(offset / 60)),
                minutes = pad(offset % 60);

            return sign + hours + ":" + minutes;
        }

        /**
         * Initialization edit modal
         * @returns {undefined}
         */
        this.init = function () {
            formTpl = Handlebars.compile(formTpl);

            that.set({
                'position.my'     : 'top center',
                'position.at'     : 'top center',
                'position.target' : $(window),
                'style.width'     : 900,
                'style.classes'   : 'edit-delivery-modal dialogue qtip-light qtip-shadow'
            });
            that.modal.elements.content.on('click', '.js-close', function (e) {
                e.preventDefault();
                that.hide();
            });
        };

        /**
         * Show edit delivery modal
         * @param {object} fcEvent fullcalendar event object
         */
        this.show = function (fcEvent) {
            var eventId = fcEvent.subEvent ? fcEvent.parentEventId : fcEvent.id;
            $.ajax({
                url : helpers._url('index', 'CalendarApi', 'taoDeliverySchedule', {full : '1'}),
                type : 'GET',
                data : {
                    uri : eventId
                },
                success : function (response) {
                    var color = response.color || 'transparent',
                        formContent = that.getFormContent(fcEvent, response),
                        testTakersListData = {},
                        groupTreeOptions = {},
                        repeatedDeliveries = response.repeatedDeliveries || [];

                    that.modal.set({
                        'content.text'  : formContent,
                        'content.title' : _.escape(response.title)
                    });

                    that.modal.show();
                    that.modal.elements.titlebar.css({'border-bottom' : '2px solid ' + color});

                    if (fcEvent.subEvent && repeatedDeliveries[fcEvent.subEventNum]) {
                        testTakersListData = {
                            ttexcluded : repeatedDeliveries[fcEvent.subEventNum].ttexcluded,
                            ttassigned : repeatedDeliveries[fcEvent.subEventNum].ttassigned
                        };
                        groupTreeOptions.groups = repeatedDeliveries[fcEvent.subEventNum].groups;
                    } else {
                        testTakersListData = {ttexcluded : response.ttexcluded, ttassigned : response.ttassigned};
                        groupTreeOptions.groups = response.groups;
                    }

                    testTakersList = new TestTakersList({
                        container : $('.js-tt-list'),
                        data : testTakersListData,
                        deliveryId : response.id
                    });

                    that.initDatepickers();
                    that.initGroupTree(groupTreeOptions);
                    that.initForm(response);
                }
            });
        };

        /**
         * Get edit form HTML markup.
         * @param {object} fcEvent - fullcalendar event instance
         * @param {object} data - delivery data loaded from server
         * parent event data should be given).
         * @returns {string} Form HTML markup
         */
        this.getFormContent = function(fcEvent, data) {
            var formContent;

            data = _.cloneDeep(data);
            data.publishedFromatted = moment(data.published * 1000).format("YYYY-MM-DD HH:mm");
            data.executionsMessage = that.getExecutionsMessage(data.executions);
            data.id = fcEvent.id;
            data.uri = fcEvent.uri;

            if (fcEvent.subEvent) {
                data.subEvent = true;
                data.numberOfRepetition = fcEvent.subEventNum;
            }

            formContent = formTpl(data);

            return formContent;
        }

        /**
         * Hide edit form. If any data on the form was changed then confirmation window will be shown.
         * @returns {undefined}
         */
        this.hide = function () {
            if (!that.isShown()) {
                return;
            }
            if (!that.modified()) {
                that.modal.hide();
            } else {
                var message = '<div><p>' + __('Do you want to save your changes?') + '</p>' +
                    '<button class="btn-success small js-confirm-save">' + __('Save') + '</button> ' +
                    '<button class="btn-info small js-confirm-cancel">' + __('Cancel') + '</button> ' +
                    '<button class="btn-warning small js-confirm-discard">' + __('Discard') + '</button></div>';

                $('<div />').qtip({
                    content: {
                        text: $(message),
                        title: __("The delivery has been modified.")
                    },
                    position: {
                        my: 'center',
                        at: 'center',
                        target: $(window),
                        adjust: {
                            y: -200
                        }
                    },
                    show: {
                        ready: true,
                        modal: {
                            on: false,
                            blur: false
                        }
                    },
                    hide: false,
                    style: 'dialogue qtip-light qtip-shadow',
                    events: {
                        render: function (event, api) {
                            $('.js-confirm-cancel', api.elements.content).click(function (e) {
                                api.hide(e);
                            });
                            $('.js-confirm-discard', api.elements.content).click(function (e) {
                                api.hide(e);
                                that.modal.hide();
                            });
                            $('.js-confirm-save', api.elements.content).click(function (e) {
                                api.hide(e);
                                that.$form.submit();
                            });
                        },
                        hide: function (event, api) { api.destroy(); }
                    }
                });
            }
        };

        this.modified = function () {
            return !_.isEqual(initialData, that.getFormData());
        };

        /**
         * Initialize form (bind validation on submit event etc.)
         * @param {object} data - delivery data
         * @returns {undefined}
         */
        this.initForm = function (data) {
            that.$form = that.modal.elements.content.find('.edit-delivery-form');

            if (data.resultserver) {
                that.$form.find('select[name="resultserver"] option[value="' + data.resultserver + '"]').attr('selected', 'selected');
            }

            that.$form.on('submit', function (e) {
                e.preventDefault();

                if (that.validate()) {
                    var formData = that.getFormData(),
                        fcEvent = eventService.getEventById(formData.id);
                    _.assign(fcEvent, formData);

                    if (!fcEvent.subEvent) {
                        fcEvent.start = moment(formData.start);
                        fcEvent.end = moment(formData.end);
                    }

                    eventService.saveEvent(fcEvent, function () {
                        initialData = that.getFormData();
                        that.hide();
                    });
                }
            });

            that.parseRrule(data);

            $('.js-repeat-toggle')
                .prop('checked', !!data.recurrence)
                .on('change', function () {
                    $('.repeat-event-table').toggle($(this).is(':checked'));
                    that.updateRruleValue();
                })
                .trigger('change');

            $('[name="rrule[freq]"]')
                .on('change', function () {
                    $('.js-byday-row').toggle($(this).val() == RRule.WEEKLY);
                })
                .trigger('change');

            $('[name^="rrule["]').on('change', function () {
                that.updateRruleValue();
            });

            that.$form.on('change', 'input, select', function () {
                that.validate();
            });

            that.$form.on('click', '[type="submit"]', function () {
                that.$form.submit();
            });

            initialData = that.getFormData();
        };

        /**
         * Initialize test taker groups tress
         * @param {object} options
         * @param {array} options.groups - List of group ids assigned to the delivery
         * @returns {undefined}
         */
        this.initGroupTree = function (options) {
            that.groupTree = new GenerisTreeSelectClass('.js-groups', helpers._url('getData', 'GenerisTree','tao'), {
                checkedNodes: options.groups,
                serverParameters: {
                    openNodes: ["http:\/\/www.tao.lu\/Ontologies\/TAOGroup.rdf#Group"],
                    rootNode: "http:\/\/www.tao.lu\/Ontologies\/TAOGroup.rdf#Group"
                },
                paginate: 10,
                onChangeCallback : function () {
                    testTakersList.updateGroups(that.groupTree.getChecked());
                }
            });
        };

        /**
         * Initialize datepickers and timepickers.
         * @returns {undefined}
         */
        this.initDatepickers = function () {
            var start = moment($('.js-delivery-start').val()).parseZone(),
                end = moment($('.js-delivery-end').val()).parseZone(),
                timeList = [];

            for (var i = 0; i < 24; i++) {
                var hour = (i < 10) ? ('0' + i) : i; 
                timeList.push(hour + ':00', hour + ':30');
            }

            $('.js-delivery-start-date, .js-delivery-end-date').datepicker({
                dateFormat : "yy-mm-dd",
                beforeShow: function (textbox, instance) {
                    instance.dpDiv.addClass('edit-delivery-form__datepicker');
                }
            });
            $('.js-delivery-start-date').datepicker('setDate', start.format('YYYY-MM-DD'));
            $('.js-delivery-end-date').datepicker('setDate', end.format('YYYY-MM-DD'));

            $('.js-delivery-start-time, .js-delivery-end-time').autocomplete({
                source : function (request, response) {
                    response(_.filter(timeList, function (val) {
                        return val.indexOf(request.term) === 0;
                    }));
                },
                minLength: 0,
                close: function(event, ui) {
                    $(this).trigger('change');
                },
                appendTo : '.edit-delivery-form'
            }).click(function(event, ui){     
                $(this).autocomplete('search', '');
            });

            $('.js-delivery-start-time, .js-delivery-end-time').on('blur', function (e) {
                $(this).val($(this).val().substr(0,5));
            });

            $('.js-delivery-start-time').val(start.format('HH:mm'));
            $('.js-delivery-end-time').val(end.format('HH:mm'));
        };

        /**
         * Parse date, time and timezone inputs and set time value in UTC timezone to hiddent inputs 
         * that will be sent to the server.
         * @returns {undefined}
         */
        this.updateDatetime = function () {
            var startMoment = that.getStartMoment(),
                endMoment = that.getEndMoment();

            $('.js-delivery-start').val(startMoment.format('YYYY-MM-DDTHH:mm:ssZZ'));
            $('.js-delivery-end').val(endMoment.format('YYYY-MM-DDTHH:mm:ssZZ'));
        };

        /**
         * Parse recurence rule params and store processed value in hidden input
         * that will be sent to the server.
         * @param {object} formData Form data. If not given then data will be fetched from form automatically.
         * @see {@link http://tools.ietf.org/html/rfc2445}
         * @returns {string} recurrence rule
         */
        this.updateRruleValue = function (formData) {
            var value = '',
                data = formData || that.$form.serializeObject(),
                rrule,
                rruleData = _.clone(data.rrule);

            if (!rruleData) {
                return;
            }

            rruleData.dtstart = that.getStartMoment().clone().utc().toDate();

            if ($('.js-repeat-toggle').is(':checked')) {
                var rrule = new RRule(rruleData);
                value = rrule.toString();
                $('.js-rrule-summary').text(RRule.fromString(rrule.toString()).toText());
            }

            $('[name="recurrence"]').val(value);

            return value;
        };

        /**
         * Function parse rrule value obtained from the server and populate inputs by appropriate values.
         * @see {@link http://tools.ietf.org/html/rfc2445}
         * @returns {undefined}
         */
        this.parseRrule = function () {
            var $rruleInput = $('[name="recurrence"]'),
                rule,
                rrule,
                startMoment;

            if ($rruleInput.length === 0 || !$('[name="recurrence"]').val()) {
                return;
            }

            rule = $('[name="recurrence"]').val().toUpperCase();
            startMoment = that.getStartMoment();

            rule += ';DTSTART=' + startMoment.clone().utc().format('YYYYMMDDTHHmmss') + 'Z';
            rrule = RRule.fromString(rule);
            $.each(rrule.options, function (key, value) {
                if (!rrule.origOptions[key]) {
                    return;
                }
                var $input = $('[name^="rrule[' + key.toLowerCase() + ']"]');
                if ($input && $input.length) {
                    if (_.isArray(value)) {
                        $.each(value, function (i, num) {
                            $input.eq(num).prop('checked', true);
                        });
                    } else {
                        $input.val(value);
                    }
                }
            });
            this.updateRruleValue();
        };

        /**
         * Return message about the number of attempts. 
         * @param {integer} executions Number of attempts
         * @returns {string}
         */
        this.getExecutionsMessage = function (executions) {
            var message = __('No information available');
            if(executions >= 0) {
                if(executions == 0) {
                    message =__('No attempt has been started yet.');
                } else if(executions == 1) {
                    message = __('There is currently 1 attempt');
                } else {
                    message = __('There are currently %s attempts', String(executions));
                }
            } 
            return message;
        };

        /**
         * Validate form and mark/unmark error inputs.
         * @returns {boolean} Whether the form is valid.
         */
        this.validate = function () {
            var rules = {
                '.js-delivery-end-time, .js-delivery-start-time' : {
                    validate : function () {
                        return /^\d\d:\d\d$/.test($(this).val());
                    },
                    message : __('The format of time is invalid')
                },
                '.js-delivery-end-date, .js-delivery-start-date' : {
                    validate : function () {
                        return /^\d\d\d\d-\d\d-\d\d$/.test($(this).val());
                    },
                    message : __('The format of date is invalid')
                },
                '.js-label' : {
                    validate : function () {
                        return $(this).val().length !== 0;
                    },
                    message : __('This field is required')
                },
                '.js-maxexec' : {
                    validate : function () {
                        return /^\d*$/.test($(this).val());
                    },
                    message : __('Value must be a number')
                },   
                '[name="resultserver"]' : {
                    validate : function () {
                        return $.trim($(this).val()).length !== 0;
                    },
                    message : __('This field is required')
                },   
                '[name="rrule[count]"]' : {
                    validate : function () {
                        return /^\d*$/.test($(this).val()) || !$('.js-repeat-toggle').is(':checked');
                    },
                    message : __('Value must be a number')
                }           
            },
            formIsValid = true,
            qtipApi,
            $elements;

            that.updateDatetime();

            for (var selector in rules) {
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

        /**
         * Get moment of start time
         * @returns {object} Moment instance
         */
        this.getStartMoment = function () {
            var startVal = $('.js-delivery-start-date').val() + ' ' + $('.js-delivery-start-time').val(),
                startMoment = moment.tz(startVal, this.getCurrentTZName());
            return startMoment;
        };

        /**
         * Get moment of end time
         * @returns {object} Moment instance
         */
        this.getEndMoment = function () {
            var endVal =   $('.js-delivery-end-date').val() + ' ' + $('.js-delivery-end-time').val(),
                endMoment = moment.tz(endVal, this.getCurrentTZName());
            return endMoment;
        };

        /**
         * Get time zone name from the select box on the form.
         * @returns {String} Example 'Europe/Luxembourg'
         */
        this.getCurrentTZName = function () {
            var timeZone = $.trim($('.js-delivery-time-zone').find('option:selected').text());
            if (timeZone === '') {
                timeZone = this.tz || 'UTC';
            }
            return timeZone;
        };

        /**
         * Convert form data to JS object
         * @returns {object} form data
         */
        this.getFormData = function () {
            var data = {};
            that.updateDatetime();
            that.updateRruleValue();
            data = that.$form.serializeObject();
            data.ttexcluded = [];
            $('.js-excluded-tt-list option').each(
                function () {
                    data.ttexcluded.push(this.value);
                }
            );
            data.groups = that.groupTree.getChecked();
            return data;
        };

        this.init();
    };
});