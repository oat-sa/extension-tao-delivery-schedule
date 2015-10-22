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
define([
    'lodash',
    'jquery',
    'i18n',
    'handlebars',
    'layout/actions/binder',
    'taoDeliverySchedule/calendar/calendar',
    'taoDeliverySchedule/calendar/eventService',
    'taoDeliverySchedule/calendar/tooltips/editEventTooltip',
    'taoDeliverySchedule/calendar/tooltips/createEventTooltip',
    'taoDeliverySchedule/calendar/modals/editEventModal',
    'layout/actions',
    'ui/feedback',
    'text!timeZoneList',
    'taoDeliverySchedule/calendar/mediator',
    'taoDeliverySchedule/lib/rrule/rrule.amd',
    'css!/taoDeliverySchedule/views/css/taodeliveryschedule'
],
function (
    _,
    $,
    __,
    Handlebars,
    binder,
    Calendar,
    eventService,
    EditEventTooltip,
    CreateEventTooltip,
    EditEventModal,
    actionManager,
    feedback,
    timeZoneListTpl,
    mediator
) {
    'use strict';

    function DeliverySchedule() {
        timeZoneListTpl = Handlebars.compile(timeZoneListTpl);
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

        this.start = function () {
            that.initTree();
            eventService.setTZ(timeZone);
            
            calendar = new Calendar({
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
                                timeZone : $tzSelect.val(),
                                timeZoneName : eventService.getCurrentTZName()
                            },
                            {action : actionManager.getBy('delivery-new')}
                        )
                    );
                },
                eventClick : function (fcEvent, e) {
                    createEventTooltip.hide();
                    that.selectEvent(fcEvent.id, true, e);
                },
                eventResizeStart : function (fcEvent, e) {
                    that.hideTooltips();
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
                viewDestroy : function () {
                    that.hideTooltips();
                }
            });

            that.initTzSelect();
            that.initTooltips();

            //bind events
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
                that.showEditEventModal(context);
            });
            //Instance selected on the tree
            binder.register('delivery-select', function (treeInstance) {
                var fcEvent = eventService.getEventById(treeInstance.uri),
                    subEventSelected;

                //tooltip already shown.
                if (editEventTooltip.isShown() && editEventTooltip.getId() === treeInstance.uri) {
                    return;
                }

                subEventSelected = editEventTooltip.isShown() && _.contains(fcEvent.recurringEventIds, editEventTooltip.getId());

                //If selection of instance on the tree was triggered by click on the subdelivery on the calendar
                //and parent delivery already selected on the tree then do nothing.
                if (!subEventSelected) {
                    //select event on the calendar
                    that.selectEvent(treeInstance.uri);
                }
            });
            binder.register('class-select', function (treeInstance) {
                that.hideTooltips();
            });
        };

        /**
         * Initialize tooltips.
         * @returns {undefined}
         */
        this.initTooltips = function () {
            $.fn.qtip.zindex = 9000;

            //set up edit delivery tooltip
            editEventTooltip = new EditEventTooltip();
            mediator.on('edit.editEventTooltip', function () {
                actionManager.exec(
                    'delivery-edit',
                    _.extend(
                        actionManager._resourceContext,
                        {action : actionManager.getBy('delivery-edit')}
                    )
                );
            });
            mediator.on('hide.editEventTooltip', function () {
                eventService.highlightEvent(false);
            });
            mediator.on('delete.editEventTooltip', function (eventId) {
                eventService.deleteEvent(eventId);
            });
            mediator.on('to-parent.editEventTooltip', function (eventId) {
                var fcEvent = eventService.getEventById(eventId);
                if (fcEvent.parentEventId) {
                    that.selectEvent(fcEvent.parentEventId, true);
                }
            });

            //set up create delivery tooltip
            createEventTooltip = new CreateEventTooltip();
            mediator.on('submit.createEventTooltip', function (data) {
                eventService.createEvent({
                    data : data,
                    success : that.hideTooltips
                });
            });
            mediator.on('hide.createEventTooltip', function (data) {
                calendar.exec('unselect');
            });

            editEventModal = new EditEventModal();
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
                    var fcEvent = eventService.getEventById(data.id),
                        eventsToBeRemoved;
                    that.hideTooltips();
                    if (fcEvent) {
                        eventsToBeRemoved = [fcEvent.id];
                        if (fcEvent.recurringEventIds && fcEvent.recurringEventIds.length) {
                            eventsToBeRemoved = eventsToBeRemoved.concat(fcEvent.recurringEventIds);
                        }
                        calendar.exec('removeEvents', function (eventToRemove) {
                            return eventsToBeRemoved.indexOf(eventToRemove.id) !== -1;
                        });
                    }
                }
            );

            $($treeElt).children('ul').on('click', function () {
                editEventTooltip.tooltip.elements.tooltip.find('input[name="id"]').val('');
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

            if (typeof e === 'undefined' || e.isTrigger) {
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
                    'position.adjust.y' : e.offsetY || (e.pageY - $eventElement.offset().top),
                    'position.adjust.x' : e.offsetX || (e.pageX - $eventElement.offset().left),
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
        this.showEditEventModal = function (context) {
            var fcEventId = editEventTooltip.isShown() ? editEventTooltip.getId() : context.uri,
                fcEvent = eventService.getEventById(fcEventId);
            this.hideTooltips();
            editEventModal.show(fcEvent);
        };

        /**
         * Select event and show edit tooltip.
         * @param {string} eventId
         * @param {boolean} selectTreeNode - whether tree node should be selected too.
         * @param {object} e - if triggered by clicking on the event
         * then the tooltip coordinates will be the same as click coordinates.
         * @returns {undefined}
         */
        this.selectEvent = function (eventId, selectTreeNode, e) {
            calendar.goToEvent(eventId).done(function (fcEvent) {
                that.showEditEventTooltip(fcEvent, e);
                if (selectTreeNode) {
                    that.selectTreeNode(fcEvent.id);
                }
            });
        };

        /**
         * Select node on the tree
         * @param {string} eventId
         * @returns {undefined}
         */
        this.selectTreeNode = function (eventId) {
            tree.deselect_branch(tree.selected);
            calendar.getEvent(eventId).done(function (fcEvent) {
                if (fcEvent.subEvent) {
                    that.selectTreeNode(fcEvent.parentEventId);
                    return;
                }
                //if node under the 'more' button
                if ($('#tree-manage_delivery_schedule #' + fcEvent.id).length === 0) {
                    tree.select_branch($('#' + fcEvent.classId + ' .more'));
                    //after the `more` element has been deleted.
                    $treeElt.one('delete.taotree', function (e, elt) {
                        if ($(elt).hasClass('more')) {
                            tree.select_branch($('#' + eventId));
                        }
                    });
                }

                if (fcEvent.classId) {
                    tree.open_branch('#' + fcEvent.classId, false, function () {
                        tree.select_branch($('#' + fcEvent.id));
                    });
                } else {
                    tree.select_branch($('#' + fcEvent.id));
                }
            });
        };
    }

    return new DeliverySchedule();
});