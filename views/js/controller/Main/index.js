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
        'layout/actions/binder',
        'taoDeliverySchedule/calendar/calendar',
        'taoDeliverySchedule/calendar/eventService',
        'taoDeliverySchedule/calendar/tooltips/editEventTooltip',
        'taoDeliverySchedule/calendar/tooltips/createEventTooltip',
        'taoDeliverySchedule/calendar/modals/editEventModal',
        'layout/actions',
        'ui/feedback',
        'uri',
        'css!/taoDeliverySchedule/views/css/taodeliveryschedule'
    ],
    function (
        _, 
        $, 
        binder, 
        Calendar, 
        eventService, 
        EditEventTooltip, 
        CreateEventTooltip, 
        EditEventModal, 
        actionManager,
        feedback,
        uri
    ) {
        'use strict';

        function DeliverySchedule() {
            var calendar,
                that = this,
                tree,
                editEventTooltip,
                createEventTooltip,
                editEventModal,
                $treeElt = $('#tree-manage_delivery_schedule'),
                $calendarContainer = $('.js-delivery-calendar');

            this.calendarLoading = $.Deferred();

            this.start = function () {
                that.initTree();
                
                calendar = new Calendar(
                    {
                        $container : $calendarContainer,
                        select : function (start, end, e) {
                            editEventTooltip.hide();
                            actionManager.exec(
                                'delivery-new', 
                                _.extend(
                                    actionManager._resourceContext, 
                                    {
                                        start : start,
                                        end : end,
                                        e : e,
                                        target : e.target
                                    },
                                    {action : actionManager.getBy('delivery-new')}
                                )
                            );
                        },
                        eventRender : function (fcEvent, $element) {
                            $element.attr('id', eventService.idAttrPrefix + fcEvent.id);
                        },
                        eventClick : function (fcEvent, e) {
                            createEventTooltip.hide();
                            eventService.selectEvent(fcEvent.id, fcEvent.classId);
                        },
                        eventResizeStart : function (fcEvent, e) {
                            that.hideTooltips();
                        },
                        /*eventDragStart : function (fcEvent, e) {
                            //that.hideTooltips();
                            console.log(fcEvent.end.format('YYYY-MM-DD HH:mm'))
                        },
                        eventDragStop : function (fcEvent, e) {
                            console.log(fcEvent.end.format('YYYY-MM-DD HH:mm'))
                        },*/
                        eventDrop : function (fcEvent, e) {
                            eventService.saveEvent(fcEvent);
                        },
                        eventResize : function (fcEvent, e) {
                            eventService.saveEvent(fcEvent);
                        },
                        viewRender : function () {
                            $('.fc-scroller').on('scroll', function (e) {
                                if (editEventTooltip.tooltip.elements.tooltip.is(':visible')) {
                                    editEventTooltip.tooltip.reposition(e);
                                }
                                if (createEventTooltip.tooltip.elements.tooltip.is(':visible')) {
                                    createEventTooltip.tooltip.reposition(e);
                                }
                            });
                        },
                        viewDisplay : function () {
                            that.hideTooltips();
                        },
                        loading : function (loading) {
                            if (loading) {
                                that.calendarLoading = $.Deferred();
                                that.calendarLoading.promise();
                            } else {
                                that.calendarLoading.resolve();
                            }
                        },
                        eventAfterAllRender : function () {
                            that.hideTooltips();
                        },
                        events : '/taoDeliverySchedule/CalendarApi'
                    }
                );
        
                /* Edit event tooltip */
                editEventTooltip = new EditEventTooltip({
                    $container : $calendarContainer
                });
                /* END edit event tooltip */
                
                /* Create event tooltip */
                createEventTooltip = new CreateEventTooltip({
                    $container : $calendarContainer,
                    callback : {
                        afterHide : function () {
                            calendar.exec('unselect');
                        }
                    }
                });
                /* END create event tooltip */
                
                /* Edit event modal */
                editEventModal = new EditEventModal({
                    callback : {
                        afterHide : function () {
                            calendar.exec('unselect');
                        }
                    }
                });
                /* END edit event modal */
                
                binder.register('schedule_month_mode', function () {
                    that.hideTooltips();
                    calendar.exec('changeView', 'month');
                });
                binder.register('schedule_week_mode', function () {
                    that.hideTooltips();
                    calendar.exec('changeView', 'agendaWeek');
                });
                binder.register('schedule_day_mode', function () {
                    that.hideTooltips();
                    calendar.exec('changeView', 'agendaDay');
                });
                binder.register('delivery-new', function (context) {
                    that.showCreateEventTooltip(context);
                });
                binder.register('delivery-edit', function (context) {
                    that.showEditForm(context);
                });
                binder.register('delivery-select', function (treeInstance) {
                    that.calendarLoading.done(function () {
                        var fcEvent = calendar.exec('clientEvents', treeInstance.uri);
                        if (fcEvent.length) {
                            that.goToEvent(
                                fcEvent[0], 
                                function (fcEvent) {
                                    that.showEditEventTooltip(fcEvent);
                                }
                            );
                        }
                    });
                });
                binder.register('class-select', function (treeInstance) {
                    that.hideTooltips();
                });
            };
            
            /**
             * Initialize tree and bind events
             * @returns {undefined}
             */
            this.initTree = function () {
                tree = $.tree.reference($treeElt);

                $($treeElt).on(
                    'refresh.taotree', 
                    function () {
                        calendar.exec('refetchEvents');
                    }
                );
        
                $($treeElt).on(
                    'removenode.taotree', 
                    function (e, data) {
                        calendar.exec('removeEvents', [data.id]);
                    }
                );
        
                $($treeElt).on(
                    'addnode.taotree', 
                    function (e, data) {
                        eventService.loadEvent(
                            uri.encode(data.uri), 
                            function (eventData) {
                                calendar.exec('renderEvent', eventData);
                            }
                        );
                    }
                );
            };
            
            /**
             * Moves the calendar to an event. 
             * If calendar has a scrollbar then it will be scrolled to start of event.
             * Callback function will be inwoked after all event will be loaded.
             * @param {object} fcEvent Calendar event
             * @param {funcrion} callback
             * @returns {undefined}
             */
            this.goToEvent = function (fcEvent, callback) {
                calendar.exec('gotoDate', fcEvent.start);
                that.calendarLoading.done(function () {
                    var $eventElement = eventService.getEventElement(fcEvent.id),
                        $scroller = $calendarContainer.find('.fc-scroller'),
                        pos;
                    if ($scroller.length) {
                        pos = $eventElement.offset().top - $scroller.offset().top + $scroller.scrollTop();
                        $scroller.scrollTop(pos);
                    }
                    if (_.isFunction(callback)) {
                        callback(fcEvent);
                    }
                });
            };
            
            /**
             * Hide all tooltips on calendar
             * @returns {undefined}
             */
            this.hideTooltips = function () {
                editEventTooltip.hide();
                createEventTooltip.hide();
            };
            
            /**
             * Show event tooltip
             * @param {object} fcEvent Calendar event
             * @param {Event} e jQuery event
             * @returns {undefined}
             */
            this.showEditEventTooltip = function (fcEvent, e) {
                var $eventElement = eventService.getEventElement(fcEvent.id);
                calendar.exec('unselect');
                
                if (!$eventElement.length) {
                    return;
                }
                    
                editEventTooltip.set({
                    'content.title' : '<b>' + fcEvent.title + '</b>'
                });
                if (e === undefined || e.isTrigger) {
                    if (!$eventElement.is(':visible')) {
                       var $moreLinks = $eventElement.closest('.fc-content-skeleton').find('a.fc-more');
                       $eventElement = $moreLinks.eq(0);
                    }
                    editEventTooltip.set({
                        'position.target' : $eventElement,
                        'position.adjust.y' : 7
                    });
                } else {
                    editEventTooltip.set({
                        'position.target' : [e.pageX, e.pageY],
                        'position.adjust.y' : 0
                    });
                }
                
                editEventTooltip.show({
                    start : fcEvent.start.format('ddd, MMMM D, H:mm'),
                    end : fcEvent.end ? fcEvent.end.format('ddd, MMMM D, H:mm') : false,
                    id : fcEvent.id,
                    uri : fcEvent.uri,
                    color : fcEvent.color
                });
            };
            
            this.showCreateEventTooltip = function (context) {
                createEventTooltip.show(context);
            };
            
            this.showEditForm = function (context) {
                this.hideTooltips();
                editEventModal.show(context);
            };
        }
        
        return new DeliverySchedule();
    }
);
