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
        'i18n',
        'taoDeliverySchedule/calendar/eventService',
        'ui/feedback',
        'context',
        'taoDeliverySchedule/lib/fullcalendar/fullcalendar.amd'
    ],
    function (_, $, __, eventService, feedback, context) {
        'use strict';

        /**
         * Function retuns height of calendar container
         * 
         * @param {jQueryElement} $contentBlock
         * @returns {integer} calendar height
         */
        function getCalendarHeight($contentBlock) {
            var height;
            $contentBlock = $contentBlock ? $contentBlock : $('.content-block');
            height = $('body > footer').offset().top - $contentBlock.offset().top;
            return height - parseInt($contentBlock.css('padding-top')) - parseInt($contentBlock.css('padding-bottom'));
        }
        
        /**
         * Calendar constructor.
         * 
         * @constructor
         * @property {object}        options Calendar options.
         * @property {jQueryElement} options.$container Calendar container.
         * @property {Date}          options.defaultDate Calendar The initial date displayed when the calendar first loads.
         */
        return function (options) {
            if (!(options.$container instanceof $) || !$.contains(document, options.$container[0])) {
                throw new TypeError("Calendar requires $container option that should be jQuery element.");
            }
            var defaultOptions,
            that = this;
            this.calendarLoading = $.Deferred();
            
            this.init = function () {
                defaultOptions = {
                    lang: context.base_lang,
                    defaultDate : new Date(),
                    editable : true,
                    selectable : true,
                    selectHelper : false,
                    unselectAuto : false,
                    height : getCalendarHeight(),
                    eventLimit : false, // allow "more" link when too many events
                    select : _.noop,
                    //timezone : options.$container.data('time-zone-name'),
                    timeFormat: 'H:mm',
                    axisFormat: 'HH:mm',
                    loading : function (loading) {
                        if (loading) {
                            that.calendarLoading = $.Deferred();
                        } else {
                            that.calendarLoading.resolve();
                        }
                    },
                    eventRender : function (fcEvent, $element) {
                        /*if (fcEvent.end.diff(fcEvent.start, 'hours') >= 24) {
                            fcEvent.allDay = true;
                        }*/
                        $element.addClass(eventService.classAttrPrefix + fcEvent.id);
                        if (fcEvent.recurringEventIds) {
                            $element.append('<span class="recurring-count">1</span>');
                        }
                        if (fcEvent.subEvent && fcEvent.subEventNum) {
                            $element.append('<span class="recurring-count">' + (fcEvent.subEventNum + 1) + '</span>');
                        }
                    },
                    eventDrop : function (fcEvent, e, revertFunc) {
                        if (fcEvent.subEvent) {
                            revertFunc();
                            feedback().warning(__("Sub delivery cannot be changed."));
                        } else {
                            eventService.saveEvent(fcEvent, _.noop, function () {revertFunc();});
                        }
                    },
                    eventResize : function (fcEvent, e, revertFunc) {
                        if (fcEvent.subEvent) {
                            revertFunc();
                            feedback().warning(__("Sub delivery cannot be changed."));
                        } else {
                            eventService.saveEvent(fcEvent, _.noop, function () {revertFunc();});
                        }
                    },
                    events : function(start, end, timezone, callback) {
                        $.ajax({
                            url: '/taoDeliverySchedule/CalendarApi?timeZone=' + eventService.getCurrentTZName(),
                            dataType: 'json',
                            data: {
                                start: start.unix(),
                                end: end.unix()
                            },
                            success: function(response) {
                                var events = [];

                                that.exec('removeEvents');
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
                };
                
                options = _.assign(defaultOptions, options);
                options.$container.fullCalendar(options);
            };

            
            this.exec = function () {
                return options.$container.fullCalendar.apply(options.$container, arguments);
            };
            
            /**
             * Add event to the calendar.
             * @param {object} eventData Event properties
             * @param {Date} eventData.start Event begin date
             * @param {Date} eventData.end Event end date
             * @returns {undefined}
             */
            this.addEvent = function (eventData) {
                that.exec('renderEvent', eventData, true); // stick? = true
            };
            
            /**
             * Load event by id and render it on the calendar. 
             * If event already loaded and represented on the calendar then the Deferred object will be resolved immediately.
             * @param {string} eventId
             * @returns {Deferred} 
             */
            this.getEvent = function (eventId) {
                var deferred = $.Deferred(),
                    fcEvent = that.exec('clientEvents', eventId);
                
                if (fcEvent.length === 0) {
                    fcEvent = eventService.loadEvent(eventId, function (eventData) {
                        that.exec('renderEvent', eventData);
                        var fcEvent = that.exec('clientEvents', eventId);
                        deferred.resolve(fcEvent[0]);
                    });
                } else {
                    deferred.resolve(fcEvent[0]);
                }
                return deferred.promise();
            };
            
            /**
             * Moves the calendar to an event. 
             * If calendar has a scrollbar then it will be scrolled to start of event.
             * Deferred object will be resolved after all event will be loaded.
             * @param {object} fcEvent Calendar event
             * @returns {Deferred} 
             */
            this.goToEvent = function (eventId) {
                var deferred = $.Deferred();
                that.getEvent(eventId).done(function(fcEvent) {
                    if (!eventService.getEventElement(fcEvent.id).length) {
                        that.exec('gotoDate', fcEvent.start);
                    }
                    that.calendarLoading.done(function () {
                        var $eventElement = eventService.getEventElement(fcEvent.id),
                            $scroller = options.$container.find('.fc-scroller'),
                            pos;

                        if ($scroller.length) {
                            pos = $eventElement.offset().top - $scroller.offset().top + $scroller.scrollTop();
                            $scroller.scrollTop(pos);
                        }
                        
                        deferred.resolve(fcEvent);
                    });
                });
                
                return deferred.promise();
            };
            
            if (context.base_lang !== 'en') {
                require(['taoDeliverySchedule/lib/fullcalendar/lang/' + context.base_lang], function () {
                    that.init();
                }, function (err) {
                    that.init();
                });
            } else {
                that.init();
            }
        };
    }
);