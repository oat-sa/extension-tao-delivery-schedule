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
        'taoDeliverySchedule/calendar/modals/modal',
        'tpl!/taoDeliverySchedule/main/editDeliveryForm?noext', //load template from controller action. (noext extension may be useful here)
        'i18n',
        'generis.tree.select',
        'moment',
        'taoDeliverySchedule/calendar/eventService',
        'taoDeliverySchedule/lib/jquery.serialize-object.min',
        'taoDeliverySchedule/lib/qtip/jquery.qtip',
        'jqueryui'
    ],
    function (_, $, modal, formTpl, __, GenerisTreeSelectClass, moment, eventService) {
        'use stirct';
        return function () {
            var that = this;
            
            modal.apply(this, arguments);
            
            function pad(value) {
                return value < 10 ? '0' + value : value;
            }
            function createOffset(val) {
                var sign = (offset > 0) ? "-" : "+";
                var offset = Math.abs(val);
                var hours = pad(Math.floor(offset / 60));
                var minutes = pad(offset % 60);
                return sign + hours + ":" + minutes;
            }    
            
            /**
             * Initialization edit modal
             * @returns {undefined}
             */
            this.init = function () {
                that.set({
                    'position.my'     : 'top center', 
                    'position.at'     : 'top center',
                    'position.target' : $(window),
                    'style.width'     : 900,
                    'style.classes'   : 'edit-delivery-modal dialogue qtip-light qtip-shadow'
                });
                that.modal.elements.content.on('click', '.js-close', function () {
                    that.hide();
                });
            };
            
            /**
             * Show edit delivery modal
             * @param {object} options
             * @param {string} options.uri Delivery uri
             */
            this.show = function (options) {
                this.callback('beforeShow');
                $.ajax({
                    url : '/taoDeliverySchedule/CalendarApi?full=1',
                    type : 'GET',
                    data : {
                        uri : options.uri
                    },
                    success : function (response) {
                        var color = response.color || 'transparent';
                        
                        response.publishedFromatted = moment(response.published * 1000).format("YYYY-MM-DD HH:mm");
                        response.executionsMessage = that.getExecutionsMessage(response.executions);
                        response.ttexcludedMessage = response.ttexcluded.length ? __('%d test-taker(s) are excluded', String(response.ttexcluded.length)) : '';
                        response.ttassignedMessage = response.ttassigned.length ? __('Delivery is assigned to %d test-takers', String(response.ttassigned.length)) : '';
                        
                        //response.rrule = 'FREQ=weekly;INTERVAL=2;BYDAY=MO,WE,TH;COUNT=5'; //TODO REMOVE
                        
                        that.modal.set({
                            'content.text'   : formTpl(response),
                            'content.title'  : response.title
                        });
                        
                        that.modal.elements.titlebar.css({'border-bottom' : '2px solid ' + color});
                        that.modal.show();
                        
                        that.initDatepickers();
                        that.initGroupTree(response);
                        that.initForm(response);
                    }
                });
            };
            
            /**
             * Initialize form (bind validation on submit event etc.)
             * @returns {undefined}
             */
            this.initForm = function (data) {
                that.$form = that.modal.elements.content.find('.edit-delivery-form');
                
                if (data.resultserver) {
                    that.$form.find('select[name="resultserver"] option[value="' + data.resultserver + '"]').attr('selected', 'selected');
                }
                
                that.$form.on('submit', function () {
                    if (that.validate()) {
                        var data = that.getFormData(),
                        fcEvent = eventService.getEventById(data.id);
                        
                        _.assign(fcEvent, data);
                        
                        fcEvent.start = $.fullCalendar.moment.parseZone(data.start);
                        fcEvent.end = $.fullCalendar.moment.parseZone(data.end);
                        
                        //console.log(data);
                        //return false;
                        
                        eventService.saveEvent(fcEvent, function () {
                            that.hide();
                        });
                    }
                    
                    return false;
                });
                
                that.parseRrule();
                
                $('.js-repeat-toggle')
                    .prop('checked', data.rrule)
                    .on('change', function () {
                        $('.repeat-event-table').toggle($(this).is(':checked'));
                    })
                    .trigger('change');
            
                $('[name="rrule[freq]"]')
                    .on('change', function () {
                        $('.js-byday-row').toggle($(this).val() === 'weekly');
                    })
                    .trigger('change');
            };
            
            /**
             * Initialize test taker groups tress
             * @param {object} options
             * @param {array} options.groups List of group ids assigned to the delivery
             * @returns {undefined}
             */
            this.initGroupTree = function (options) {
                that.groupTree = new GenerisTreeSelectClass('.js-groups', '/tao/GenerisTree/getData', {
                    checkedNodes: options.groups,
                    serverParameters: {
                        openNodes: ["http:\/\/www.tao.lu\/Ontologies\/TAOGroup.rdf#Group"],
                        rootNode: "http:\/\/www.tao.lu\/Ontologies\/TAOGroup.rdf#Group"
                    },
                    paginate: 10
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
                        response(timeList);
                    },
                    minLength: 0,
                    appendTo : '.edit-delivery-form'
                }).focus(function(event, ui){     
                    $(this).autocomplete("search");
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
                var timezone = $('.js-delivery-time-zone').val(),
                    startVal = $('.js-delivery-start-date').val() 
                               + ' ' + $('.js-delivery-start-time').val() 
                               + ' ' + createOffset(timezone),
                    endVal =   $('.js-delivery-end-date').val() 
                               + ' ' + $('.js-delivery-end-time').val()
                               + ' ' + createOffset(timezone),
                    startMoment = moment(startVal),
                    endMoment = moment(endVal);
            
                startMoment.parseZone();
                endMoment.parseZone();
                
                $('.js-delivery-start').val(startMoment.format('YYYY-MM-DDTHH:mm:ssZZ'));
                $('.js-delivery-end').val(endMoment.format('YYYY-MM-DDTHH:mm:ssZZ'));
            };
            
            /**
             * Parse recurence rule params and store processed value in hidden input
             * that will be sent to the server.
             * @returns {string} recurrence rule
             */
            this.updateRrule = function () {
                var value = '',
                    data = that.$form.serializeObject(),
                    days = [];
                
                if ($('.js-repeat-toggle').is(':checked')) {
                    value += 'FREQ=' + data.rrule.freq;
                    value += ';INTERVAL=' + data.rrule.interval;
                    
                    if (data.rrule.freq === 'weekly') {
                        $.each(data.rrule.byday, function (key, val) {
                            if (val === '1') {
                                days.push(key);
                            }
                        });
                        value += ';BYDAY=' + (days.join(','));
                    }
                    
                    value += ';COUNT=' + data.rrule.count;
                } else {
                    value = '0';
                }
                $('[name="rrule[value]"]').val(value);
                
                return value;
            };
            
            /**
             * Function parse rrule and populate inputs by appropriate values.
             * @returns {undefined}
             */
            this.parseRrule = function () {
                var rule = $('[name="rrule[value]"]').val(),
                    days,
                    values = rule.split(';');
            
                $.each(values, function (key, value) {
                    value = value.split('=');
                    if (value[0].toLowerCase() !== 'byday') {
                        $('[name="rrule[' + value[0].toLowerCase() + ']"]').val(value[1]);
                    } else {
                        days = value[1].split(',');
                        $.each(days, function (dayNum, day) {
                            $('[name="rrule[byday][' + day + ']"]').prop('checked', true);
                        });
                    }
                });
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
                    'js-label' : {
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
                    }           
                },
                formIsValid = true,
                $elements;
                
                that.updateDatetime();
                
                for (var selector in rules) {
                    that.$form.find(selector).removeClass('error');
                    if ($(selector).data('qtip')) {
                        $(selector).qtip('disable', true);
                    }
                }
                
                for (var selector in rules) {
                    $elements = that.$form.find(selector);
                    if ($elements.length) {
                        $elements.each(function () {
                            var valid = rules[selector].validate.apply(this);
                            if (!valid) {
                                if (!$(this).data('qtip')) {
                                    $(this).qtip({
                                        content: {
                                            text: rules[selector].message
                                        },
                                        position: {
                                            target: 'mouse', // Track the mouse as the positioning target
                                            adjust: { x: 5, y: 5 } // Offset it slightly from under the mouse
                                        }
                                    });
                                } else {
                                    $(this).qtip('content.text', rules[selector].message);
                                    $(this).qtip('disable', false);
                                }

                                $(this).addClass('error');
                            }
                            formIsValid = formIsValid && valid;
                        });
                    }
                }
                
                return formIsValid;
            };
            
            /**
             * Convert form data to JS object
             * @returns {object} form data
             */
            this.getFormData = function () {
                var data = {};
                that.updateDatetime();
                that.updateRrule();
                data = that.$form.serializeObject();
                data.groups = that.groupTree.getChecked();
                return data;
            };
            
            this.init();
        };
    }
);


