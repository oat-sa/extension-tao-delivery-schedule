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
        'tpl!/taoDeliverySchedule/views/templates/editDeliveryForm',
        'i18n',
        'generis.tree.select',
        'moment',
        'taoDeliverySchedule/calendar/eventService',
        'taoDeliverySchedule/lib/qtip/jquery.qtip',
        'jqueryui'
    ],
    function (_, $, modal, formTpl, __, GenerisTreeSelectClass, moment, eventService) {
        'use stirct';
        return function () {
            var that = this;
            
            modal.apply(this, arguments);
            
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
            this.initForm = function () {
                that.$form = that.modal.elements.content.find('.edit-delivery-form');
                that.$form.on('submit', function () {
                    if (that.validate()) {
                        var data = that.getFormData(),
                        fcEvent = eventService.getEventById(data.id);
                        
                        fcEvent.title = data.label;
                        fcEvent.groups = data.groups;
                        fcEvent.maxexec = data.maxexec;
                        fcEvent.start = moment(data.start);
                        fcEvent.end = moment(data.end);
                        
                        eventService.saveEvent(fcEvent, function () {
                            that.hide();
                        });
                    }
                    
                    return false;
                });
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
            
            this.updateDatetime = function () {
                var startVal = $('.js-delivery-start-date').val() + ' ' + $('.js-delivery-start-time').val();
                var endVal = $('.js-delivery-end-date').val() + ' ' + $('.js-delivery-end-time').val();
                $('.js-delivery-start').val(startVal);
                $('.js-delivery-end').val(endVal);
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
                    '[name="label"]' : {
                        validate : function () {
                            return $(this).val().length !== 0;
                        },
                        message : __('This field is required')
                    },
                    '[name="maxexec"]' : {
                        validate : function () {
                            return /^\d*$/.test($(this).val());
                        },
                        message : __('Value must be a number')
                    }           
                },
                formIsValid = true;
                
                that.updateDatetime();
                
                for (var selector in rules) {
                    $(selector).removeClass('error');
                    if ($(selector).data('qtip')) {
                        $(selector).qtip('disable', true);
                    }
                }
                
                for (var selector in rules) {
                    if ($(selector).length) {
                        $(selector).each(function () {
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
                                    $(selector).qtip('disable', false);
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
                that.$form.serializeArray().map(function(x){data[x.name] = x.value;});
                data.groups = that.groupTree.getChecked();
                
                return data;
            };
            
            this.init();
        };
    }
);


