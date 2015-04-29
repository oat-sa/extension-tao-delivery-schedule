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
        'taoDeliverySchedule/lib/qtip/jquery.qtip',
        'jqueryui'
    ],
    function (_, $, modal, formTpl, __, GenerisTreeSelectClass, moment) {
        'use stirct';
        return function () {
            var that = this;
            
            modal.apply(this, arguments);
            
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
            
            this.show = function (options) {
                this.callback('beforeShow');
                $.ajax({
                    url : '/taoDeliverySchedule/CalendarApi?full=1',
                    type : 'GET',
                    data : {
                        uri : options.uri
                    },
                    success : function (response) {
                        console.log(response);
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
                    }
                });
            };
            
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
            
            this.initDatepickers = function () {
                var start = moment($('.js-delivery-start').val()),
                    end = moment($('.js-delivery-end').val()),
                    timeList = [];
            
                start.parseZone();
                end.parseZone();
                
                for (var i = 0; i < 24; i++) {
                    var hour = (i < 10) ? ('0' + i) : i; 
                    timeList.push(hour + ':00');
                    timeList.push(hour + ':30');
                }
                
                $('.js-delivery-start-date, .js-delivery-end-date').datepicker({
                    dateFormat : "yy-mm-dd",
                    beforeShow: function (textbox, instance) {
                        $('#ui-datepicker-div').addClass('edit-delivery-form__datepicker');
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
                
                $('.js-delivery-start-time').val(start.format('HH:mm'));
                $('.js-delivery-end-time').val(end.format('HH:mm'));
            };
            
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
            
            this.init();
        };
    }
);


