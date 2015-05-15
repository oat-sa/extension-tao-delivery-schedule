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
/*global define*/
define(
    [
        'lodash',
        'jquery',
        'i18n',
        'layout/actions/binder',
        'taoDeliverySchedule/calendar/calendar',
        'taoDeliverySchedule/calendar/eventService',
        'taoDeliverySchedule/calendar/tooltips/editEventTooltip',
        'taoDeliverySchedule/calendar/tooltips/createEventTooltip',
        'taoDeliverySchedule/calendar/modals/editEventModal',
        'layout/actions',
        'uri',
        'ui/feedback',
        'tpl!/taoDeliverySchedule/main/timeZoneList?noext',
        'taoDeliverySchedule/lib/rrule/rrule.amd',
        'css!/taoDeliverySchedule/views/css/taodeliveryschedule'
    ],
    function (
        _,
        $,
        __,
        binder,
        Calendar,
        eventService,
        EditEventTooltip,
        CreateEventTooltip,
        EditEventModal,
        actionManager,
        uri,
        feedback,
        timeZoneListTpl
    ) {
        'use strict';

        function DeliverySchedule() {
            var calendar,
                that = this,
                editEventTooltip,
                createEventTooltip,
                editEventModal,
                tree,
                $treeElt = $('#tree-manage_delivery_schedule'),
                $calendarContainer = $('.js-delivery-calendar'),
                timeZone = $('.js-delivery-calendar').data('time-zone-name') || 'UTC',
                $tzSelect = $(timeZoneListTpl());
            
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
                                        target : e.target,
                                        timeZone : $tzSelect.val()
                                    },
                                    {action : actionManager.getBy('delivery-new')}
                                )
                            );
                        },
                        eventRender : function (fcEvent, $element) {
                            $element.addClass(eventService.classAttrPrefix + fcEvent.id);
                            if (fcEvent.recurringEventIds) {
                                $element.append('<span class="recurring-count">1</span>');
                            }
                            if (fcEvent.subEvent && fcEvent.subEventNum) {
                                $element.append('<span class="recurring-count">' + (fcEvent.subEventNum + 1) + '</span>');
                            }
                        },
                        eventClick : function (fcEvent, e) {
                            createEventTooltip.hide();
                            
                            if (fcEvent.subEvent) {
                                that.selectTreeNode(fcEvent.parentEventId, fcEvent.classId);
                                that.goToEvent(fcEvent, function () {
                                    that.showEditEventTooltip(fcEvent, e);
                                });
                            } else {
                                that.selectEvent(fcEvent.id, fcEvent.classId, e);
                            }
                        },
                        eventResizeStart : function (fcEvent, e) {
                            that.hideTooltips();
                        },
                        eventDrop : function (fcEvent, e, revertFunc) {
                            if (fcEvent.subEvent) {
                                revertFunc();
                                feedback().warning(__("Sub delivery cannot be changed."));
                            } else {
                                eventService.saveEvent(fcEvent);
                            }
                        },
                        eventResize : function (fcEvent, e, revertFunc) {
                            if (fcEvent.subEvent) {
                                revertFunc();
                                feedback().warning(__("Sub delivery cannot be changed."));
                            } else {
                                eventService.saveEvent(fcEvent);
                            }
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
                        viewDestroy : function () {
                            that.hideTooltips();
                        },
                        events : function(start, end, timezone, callback) {
                            $.ajax({
                                url: '/taoDeliverySchedule/CalendarApi?timeZone=' + timeZone,
                                dataType: 'json',
                                data: {
                                    start: start.unix(),
                                    end: end.unix()
                                },
                                success: function(response) {
                                    var events = [];
                                        
                                    calendar.exec('removeEvents');
                                    $.each(response, function (key, event) {
                                        var recurringEvents = eventService.getRecurringEvents(event);
                                        events.push(event);
                                        $.each(recurringEvents, function (rEventKey, rEventVal) {
                                            events.push(rEventVal);
                                        });
                                    });
                                    
                                    callback(events);
                                }
                            });
                        }
                    }
                );
                
                that.initTzSelect();
                
                $.fn.qtip.zindex = 9000;
                
                /* Edit event tooltip */
                editEventTooltip = new EditEventTooltip({
                    position : {
                        viewport : $(document)
                    },
                    events : {
                        hide : function () {
                            eventService.highlightEvent(false);
                        }
                    }
                });
                editEventTooltip.tooltip.elements.tooltip.on('go-to-parent-event', function (e, data) {
                    that.selectEvent(data.fcEvent.parentEventId);
                });
                /* END edit event tooltip */

                /* Create event tooltip */
                createEventTooltip = new CreateEventTooltip({
                    position : {
                        viewport : $(document)
                    },
                    events : {
                        hide : function () {
                            calendar.exec('unselect');
                        }
                    }
                });
                /* END create event tooltip */

                /* Edit event modal */
                editEventModal = new EditEventModal();
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
                    if (treeInstance.uri === editEventTooltip.getId() && editEventTooltip.isShown()) {
                        return;
                    }
                    
                    that.calendarLoading.done(function () {
                        var fcEvent = calendar.exec('clientEvents', treeInstance.uri);
                        if (fcEvent.length) {
                            that.goToEvent(fcEvent[0], that.showEditEventTooltip);
                        } else {
                            eventService.loadEvent(
                                treeInstance.uri,
                                function (eventData) {
                                    calendar.exec('renderEvent', eventData);
                                    var fcEvent = calendar.exec('clientEvents', treeInstance.uri);
                                    if (fcEvent.length) {
                                        that.goToEvent(fcEvent[0], that.showEditEventTooltip);
                                    }
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
             * Initialize time zone selectbox.
             * @returns {undefined}
             */
            this.initTzSelect = function () {
                $tzSelect.find('option:contains(' + timeZone + ')').attr('selected', 'selected');
                
                $('.fc-toolbar .fc-right').prepend($tzSelect);
                
                $tzSelect.on('change', function () {
                    that.hideTooltips();
                    timeZone = eventService.getCurrentTZName();
                    calendar.exec('refetchEvents');
                });
            };

            /**
             * Initialize tree and bind events.
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
                        var fcEvent = eventService.getEventById(data.id);
                        if (fcEvent) {
                            var eventsToBeRemoved = [fcEvent.id];
                            if (fcEvent.recurringEventIds && fcEvent.recurringEventIds.length) {
                                eventsToBeRemoved = eventsToBeRemoved.concat(fcEvent.recurringEventIds);
                            }
                            
                            calendar.exec('removeEvents', function (eventToRemove) {
                                return eventsToBeRemoved.indexOf(eventToRemove.id) !== -1;
                            });
                        }
                    }
                );
            };

            /**
             * Moves the calendar to an event. 
             * If calendar has a scrollbar then it will be scrolled to start of event.
             * Callback function will be invoked after all event will be loaded.
             * @param {object} fcEvent Calendar event
             * @param {funcrion} callback
             * @returns {undefined}
             */
            this.goToEvent = function (fcEvent, callback) {
                //if the event is not presented on the calendar then move the calendar to appropriate date. 
                if (!eventService.getEventElement(fcEvent.id).length) {
                    calendar.exec('gotoDate', fcEvent.start);
                }
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
                var $eventElement = eventService.getEventElement(fcEvent.id),
                    $moreLinks;
            
                calendar.exec('unselect');

                if (!$eventElement.length) {
                    return;
                }
                
                eventService.highlightEvent(fcEvent);
                
                if (e === undefined || e.isTrigger) {
                    if (!$eventElement.is(':visible')) {
                        $moreLinks = $eventElement.closest('.fc-content-skeleton').find('a.fc-more');
                        $eventElement = $moreLinks.eq(0);
                    }
                    editEventTooltip.set({
                        'position.target' : $eventElement
                    });
                    editEventTooltip.set({
                        'position.adjust.y' : 4,
                        'position.adjust.x' : 0,
                        'position.my' : 'bottom center',
                        'position.at' : 'top center'
                    });
                } else {
                    editEventTooltip.set({
                        'position.adjust.y' : e.offsetY,
                        'position.adjust.x' : e.offsetX,
                        'position.target' : e.currentTarget,
                        'position.my' : 'bottom center',
                        'position.at' : 'top left'
                    });
                }
                
                editEventTooltip.show(fcEvent);
            };
            
            /**
             * Show create delivery tooltip.
             * @param {object} context Action context (uri, classUri, id, start, end etc.).
             * @see {@link /tao/views/js/layout/actions.js} for further information.
             * @returns {undefined}
             */
            this.showCreateEventTooltip = function (context) {
                createEventTooltip.show(context);
            };
            
            /**
             * Show delivery edit form in modal window.
             * @param {object} context Action context (uri, classUri, id, start, end etc.).
             * @see {@link /tao/views/js/layout/actions.js} for further information.
             * @returns {undefined}
             */
            this.showEditForm = function (context) {
                this.hideTooltips();
                editEventModal.show(context);
            };
            
            /**
             * Select event and show edit tooltip.
             * @param {string} eventId
             * @param {string} classId
             * @param {object} e If triggered by clicking on the event 
             * then the tooltip coordinates will be the same as click coordinates.
             * @returns {undefined}
             */
            this.selectEvent = function (eventId, classId, e) {
                //select event on the tree
                that.selectTreeNode(eventId, classId);
                
                //select event on the calendar
                that.calendarLoading.done(function () {
                    var fcEvent = calendar.exec('clientEvents', eventId);
                    if (fcEvent.length) {
                        that.goToEvent(fcEvent[0], that.showEditEventTooltip(fcEvent[0], e));
                    } else {
                        eventService.loadEvent(
                            eventId,
                            function (eventData) {
                                calendar.exec('renderEvent', eventData);
                                var fcEvent = calendar.exec('clientEvents', eventId);
                                if (fcEvent.length) {
                                    that.goToEvent(fcEvent[0], that.showEditEventTooltip(fcEvent[0], e));
                                }
                            }
                        );
                    }
                });
            };
            
            /**
             * Select node on the tree
             * @param {string} eventId
             * @param {string} classId
             * @returns {undefined}
             */
            this.selectTreeNode = function (eventId, classId) {
                //if node under the 'more' button
                if ($('#tree-manage_delivery_schedule #' + eventId).length == 0) {
                    tree.select_branch($('#' + classId + ' .more'));
                    //after the `more` element has been deleted.
                    $treeElt.one('delete.taotree', function (e, elt) {
                        if ($(elt).hasClass('more')) {
                            tree.select_branch($('#' + eventId));
                        }
                    });
                }
                
                if (classId) {
                    tree.open_branch('#' + classId, false, function () {
                        tree.select_branch($('#' + eventId));
                    });
                } else {
                    tree.select_branch($('#' + eventId));
                }
            };
        }

        return new DeliverySchedule();
    }
);
