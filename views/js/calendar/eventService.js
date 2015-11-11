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
    'i18n',
    'ui/feedback',
    'layout/actions',
    'moment',
    'layout/loading-bar',
    'taoDeliverySchedule/lib/rrule/rrule.amd'
], function (_, $, __, feedback, actionManager, moment, loadingBar) {
    'use strict';
    var instance = null;

    function EventService(calendar) {
        var that = this,
            $treeElt = $('#tree-manage_delivery_schedule'),
            $calendar = $('.js-delivery-calendar'),
            tree = $.tree.reference($treeElt);

        if (instance !== null) {
            throw new Error("Cannot instantiate more than one EventService, use EventService.getInstance()");
        }

        this.tz = 'UTC';
        this.classAttrPrefix = 'fc_event_id_';

        /**
         * Create new event
         * @param {object} options
         * @property {string} options.url Url address to create new event
         * @property {string} options.data Event data. Example:
         *      <pre>
         *      {
         *         classUri: "http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery",
         *         end: "2015-04-18 00:00",
         *         label: "sdfdsf",
         *         simpleWizard_sent: "1",
         *         start: "2015-04-17 00:00",
         *         test: "http://sample/first.rdf#i1429018012670729"
         *      }
         *      </pre>
         * @returns {undefined}
         */
        this.createEvent = function (options) {
            loadingBar.start();
            $.ajax({
                url : '/taoDeliverySchedule/CalendarApi',
                type : 'POST',
                data : options.data,
                global : false,
                dataType : 'json',
                success : function (response) {
                    if (response.uri) {
                        that.loadEvent(
                            response.id,
                            function (eventData) {
                                $calendar.fullCalendar('renderEvent', eventData);

                                $treeElt.trigger('addnode.taotree', [{
                                    'uri'       : response.uri,
                                    'parent'    : options.data.classUri,
                                    'label'     : options.data.label,
                                    'cssClass'  : 'node-instance'
                                }]);

                                feedback().success(response.message);

                                if(typeof options.success === 'function') {
                                    options.success();
                                }

                                loadingBar.stop();
                            }
                        );
                    }
                },
                error : function (xhr, err) {
                    var message = that.getRequestErrorMessage(xhr);
                    feedback().error(message, {encodeHtml : false});
                    if(typeof options.error === 'function') {
                        options.error();
                    }
                    loadingBar.stop();
                }
            });
        };

        /**
         * Save event
         * @param {object} fcEvent
         * @param {function} callback success callback
         * @param {function} errorCallback error callback
         * @returns {undefined}
         */
        this.saveEvent = function (fcEvent, callback, errorCallback) {
            loadingBar.start();

            var data = {
                    label      : fcEvent.label === undefined ? fcEvent.title : fcEvent.label,
                    classUri   : fcEvent.classUri,
                    id         : fcEvent.subEvent ? fcEvent.parentEventId : fcEvent.id,
                    uri        : fcEvent.uri,
                    start      : fcEvent.start.clone().utc().format('YYYY-MM-DD HH:mm'),
                    end        : fcEvent.end.clone().utc().format('YYYY-MM-DD HH:mm'),
                    recurrence : ''
                },
                self = this;

            if (fcEvent.ttexcluded) {
                data.ttexcluded = fcEvent.ttexcluded.length ? fcEvent.ttexcluded : ''; //jquery does not send empty arrays via ajax.
            }

            if (fcEvent.resultserver) {
                data.resultserver = fcEvent.resultserver;
            }

            if (fcEvent.recurrence && !fcEvent.subEvent) {
                var rruleOptions = RRule.parseString(fcEvent.recurrence),
                    rrule;

                if (fcEvent.start._ambigZone) {
                    rruleOptions.dtstart = fcEvent.start.clone().add(-that.getCurrentTZOffset(), 'm').toDate();
                } else {
                    rruleOptions.dtstart = fcEvent.start.clone().toDate();
                }

                rrule = new RRule(rruleOptions);
                data.recurrence = rrule.toString();
            }

            if (fcEvent.subEvent) {
                data.repeatedDelivery = true;
                data.numberOfRepetition = fcEvent.numberOfRepetition;
            }

            if (fcEvent.groups && _.isArray(fcEvent.groups)) {
                data.groups = fcEvent.groups.length ? fcEvent.groups : ['']; //jquery does not send empty arrays via ajax.
            }
            if (fcEvent.maxexec !== undefined) {
                data.maxexec = fcEvent.maxexec;
            }

            $.ajax({
                url : '/taoDeliverySchedule/CalendarApi',
                type : 'PUT',
                data : data,
                global : false,
                dataType : 'json',
                success : function (response) {
                    fcEvent = fcEvent.subEvent ? self.getEventById(fcEvent.parentEventId) : fcEvent;
                    that.loadEvent(fcEvent.id, function (eventData) {
                        var eventsToBeAdded = that.getRecurringEvents(eventData),
                            eventsToBeRemoved = fcEvent.recurringEventIds || [];

                        eventsToBeRemoved.push(fcEvent.id);
                        eventsToBeAdded.push(eventData);
                        $calendar.fullCalendar('removeEvents', function (eventToRemove) {
                            return eventsToBeRemoved.indexOf(eventToRemove.id) !== -1;
                        });

                        $calendar.fullCalendar('addEventSource', eventsToBeAdded);

                        if (typeof callback === 'function') {
                            callback(eventData);
                        }

                        feedback().success(response.message);
                        loadingBar.stop();
                    });
                },
                error : function (xhr, err) {
                    loadingBar.stop();
                    var message = that.getRequestErrorMessage(xhr);
                    feedback().warning(message, {encodeHtml : false});
                    if (typeof errorCallback === 'function') {
                        errorCallback();
                    }
                }
            });
        };

        /**
         * Delete delivery selected on the tree
         * @returns {undefined}
         */
        this.deleteEvent = function (eventId, callback, errorCallback) {
            var self = this,
                fcEvent = self.getEventById(eventId),
                data;

            if (confirm(__("Please confirm deletion"))) {
                loadingBar.start();
                fcEvent = fcEvent.subEvent ? self.getEventById(fcEvent.parentEventId) : fcEvent;

                data = {
                    classUri   : fcEvent.classUri,
                    id         : fcEvent.uri,
                    uri        : fcEvent.uri
                };
                $.ajax({
                    url : '/taoDeliverySchedule/CalendarApi?' + $.param(data),
                    type : 'DELETE',
                    global : false,
                    dataType : 'json',
                    success : function (response) {
                        tree.remove($('#' + fcEvent.id));
                        loadingBar.stop();
                        if (typeof callback === 'function') {
                            response.id = fcEvent.id;
                            callback(response);
                        }
                    },
                    error : function (xhr, err) {
                        loadingBar.stop();
                        if (typeof errorCallback === 'function') {
                            errorCallback();
                        }
                    }
                });
                tree.deselect_branch(tree.selected);
            }
        };

        /**
         * Function parse response and returns error message
         * @param {object} xhr jqXHR object.
         * @returns {string} error message
         */
        this.getRequestErrorMessage = function (xhr) {
            var message = '';
            try {
                var responseJSON = $.parseJSON(xhr.responseText);
                if (responseJSON.message) {
                    message = responseJSON.message;
                } else if (responseJSON.errors && !_.isEmpty(responseJSON.errors)) {
                    $.each(responseJSON.errors, function (key, val) {
                        message += key + ': ' + val + "<br>";
                    });
                } else {
                    message = xhr.responseText;
                }

            } catch (e) {
                message = xhr.responseText;
            }

            return message;
        };

        /**
         * Load event by Id from the server
         * 
         * @param {string} eventId
         * @param {function} callback
         * @returns {undefined}
         */
        this.loadEvent = function (eventId, callback) {
            var timeZone = that.getCurrentTZName();

            $.ajax({
                url : '/taoDeliverySchedule/CalendarApi?uri=' + eventId + '&timeZone=' + timeZone,
                type : 'GET',
                global : false,
                success : function (data) {
                    if (typeof callback === 'function') {
                        callback(data);
                    }
                },
                error : function (xhr) {
                    loadingBar.stop();
                    var message = that.getRequestErrorMessage(xhr);
                    feedback().error(message, {encodeHtml : false});
                }
            });
        };

        /**
         * Get FullCalendar event object
         * @param {string} id Event id. If paraneter is not given all event will be returned.
         * @returns {object|array} FullCalendar event (@see http://fullcalendar.io/docs/event_data/Event_Object) or array of events
         */
        this.getEventById = function (id) {
            var events = $calendar.fullCalendar('clientEvents', id);
            if (events.length === 1) {
                return events[0];
            }
            return events;
        };

        /**
         * Get event jQuery DOM elements by id 
         * 
         * @param {string} id Event id
         * @returns {jQuery element}
         */
        this.getEventElement = function (id) {
            return $('.' + that.classAttrPrefix + id);
        };

        /**
         * Function create recurring events if for initial event specified recurrence rule
         * @param {object} event Fullcalendar event.
         * @returns {Array} array of recurring fullcalendar events.
         */
        this.getRecurringEvents = function (event) {
            var events = [];
            if (event.recurrence) {
                var diff = moment(event.end).diff(moment(event.start)),
                    rrule = RRule.fromString(event.recurrence),
                    zone = moment(event.start).parseZone().zone();

                var recurringEventIds = [];

                $.each(rrule.all(), function (rEventKey, rEventDate) {
                    var startMoment = moment(rEventDate),
                        endMoment = moment(rEventDate).add(diff, 'ms'),
                        rEvent = _.cloneDeep(event);

                    rEvent.start = startMoment.zone(zone).format('YYYY-MM-DDTHH:mm:ssZZ');
                    if (rEvent.start === event.start) {
                        return;
                    }

                    rEvent.end = endMoment.zone(zone).format('YYYY-MM-DDTHH:mm:ssZZ');
                    rEvent.id = event.id + rEventKey;
                    rEvent.subEvent = true;
                    rEvent.subEventNum = rEventKey;
                    rEvent.parentEventId = event.id;
                    rEvent.className = ['sub-event'];
                    rEvent.durationEditable = false;
                    //rEvent.editable = false;
                    recurringEventIds.push(rEvent.id);
                    events.push(rEvent);
                });
                event.className = ['recurring-event'];
                event.recurringEventIds = recurringEventIds;
            }

            return events;
        };

        /**
         * Function highlights event element (or elements for long events) by adding <b>fc-selected</b> class
         * @param {object} fcEvent Fullcalendar event object.
         * @param {boolean} deselectedOther Whether other event must be deselected. True by default.
         * @returns {undefined}
         */
        this.highlightEvent = function (fcEvent, deselectedOther) {
            if (deselectedOther === undefined) {
               deselectedOther = true; 
            }
            if (deselectedOther) {
                $('.fc-event.fc-selected').removeClass('fc-selected');
            }
            if (fcEvent.id) {
                $('.' + that.classAttrPrefix + fcEvent.id).addClass('fc-selected');
            }
        };

        /**
         * Get time zone name from the select box on the calendar toolbar.
         * @returns {String} Example 'Europe/Luxembourg'
         */
        this.getCurrentTZName = function () {
            var timeZone = $.trim($('.js-time-zone-list').find('option:selected').text());
            if (timeZone === '') {
                timeZone = this.tz || 'UTC';
            }
            return timeZone;
        };

        /**
         * Set current time zone
         * 
         * @param {string} value - Time zone name (e.g. 'Europe/Luxembourg')
         */
        this.setTZ = function (value) {
            this.tz = value;
            $('.js-time-zone-list').find('option:contains(' + value + ')')
                .attr('selected', 'selected')
                .trigger('change');
        };

        /**
         * Get time zone offset in minutes from the select box on the calendar toolbar.
         * @returns {Number} Example 120
         */
        this.getCurrentTZOffset = function () {
            var offset = parseInt($('.js-time-zone-list').val(), 10);
            if (isNaN(offset)) {
                offset = 0;
            }
            return offset;
        };
    }

    EventService.getInstance = function () {
        // Gets an instance of the singleton.
        if (instance === null) {
            instance = new EventService();
        }
        return instance;
    };

    return EventService.getInstance();
});