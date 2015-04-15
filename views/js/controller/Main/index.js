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
        'css!/taoDeliverySchedule/views/css/taodeliveryschedule'
    ],
    function (
        _, 
        $, 
        binder, 
        Calendar, 
        EventService, 
        EditEventTooltip, 
        CreateEventTooltip, 
        EditEventModal, 
        actionManager,
        feedback
    ) {
        'use strict';

        function DeliverySchedule() {
            var calendar,
                that = this,
                tree,
                eventService,
                editEventTooltip,
                createEventTooltip,
                editEventModal,
                $calendarContainer = $('.js-delivery-calendar');

            this.calendarLoading = $.Deferred();

            this.start = function () {
                tree = $.tree.reference($('#tree-manage_delivery_schedule'));
                
                /*$('#tree-manage_delivery_schedule').on('ready.taotree', function () {
                    console.log(calendar)
                    calendar.exec('refetchEvents');
                });*/
                
                window.calendar = calendar = new Calendar( //TO REMOVE window.calendar
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
                                        e : e
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
                            tree.open_branch('#' + fcEvent.classId, false, function () {
                                tree.select_branch($('#' + fcEvent.id));
                            });
                        },
                        eventResizeStart : function () {
                            that.hideTooltips();
                        },
                        eventDragStart : function () {
                            that.hideTooltips();
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
                        events : '/taoDeliverySchedule/CalendarApi'
                    }
                );
        
                eventService = new EventService(calendar);
                
                /* Edit event tooltip */
                editEventTooltip = new EditEventTooltip({
                    $container : $calendarContainer,
                    callback : {
                        afterHide : function () {
                            calendar.exec('unselect');
                        }
                    }
                });
                editEventTooltip.tooltip.elements.content.on('click', '.js-edit-event', function (e) {
                    e.preventDefault();
                    actionManager.exec(
                        'delivery-edit', 
                        _.extend(
                            actionManager._resourceContext, 
                            {action : actionManager.getBy('delivery-edit')}
                        )
                    );
                });
                editEventTooltip.tooltip.elements.content.on('click', '.js-delete-event', function (e) {
                    e.preventDefault();
                    actionManager.exec(
                        'delivery-delete', 
                        _.extend(
                            actionManager._resourceContext, 
                            {action : actionManager.getBy('delivery-delete')}
                        )
                    );
                });
                /* END edit event tooltip */
                
                /* Create event tooltip */
                createEventTooltip = new CreateEventTooltip({
                    $container : $calendarContainer,
                    callback : {
                        afterHide : function () {
                            calendar.exec('unselect');
                        },
                        afterSubmit : function (data) {
                            that.hideTooltips();
                            feedback().info(data.message);
                            $('#tree-manage_delivery_schedule').trigger(
                                'refresh.taotree', 
                                [{
                                    selectNode : data.id
                                }]
                            );
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
                binder.register('new_event', function (context) {
                    that.showCreateEventTooltip(context);
                });
                binder.register('edit_event', function (context) {
                    that.showEditForm(context);
                });
                binder.register('select_event', function (treeInstance) {
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
                binder.register('select_group', function (treeInstance) {
                    that.hideTooltips();
                });
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
                    uri : fcEvent.uri
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
