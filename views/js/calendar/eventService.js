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
        'ui/feedback',
        'layout/actions',
        'moment',
        'layout/loading-bar',
        'taoDeliverySchedule/lib/rrule/rrule.amd'
    ],
    function (_, $, feedback, actionManager, moment, loadingBar) {
        'use stirct';
        var instance = null;
        
        function EventService(calendar) {
            var that = this,
                $treeElt = $('#tree-manage_delivery_schedule'),
                $calendar = $('.js-delivery-calendar'),
                tree = $.tree.reference($treeElt);
            
            if (instance !== null) {
                throw new Error("Cannot instantiate more than one EventService, use EventService.getInstance()");
            }
            
            this.classAttrPrefix = 'fc_event_id_';
            
            /**
             * Create new event
             * @param {object} options
             * @property {string} options.url Url address to creaet new event
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
                                
                                    feedback().info(response.message);
                                    
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
                        feedback().warning(message, {encodeHtml : false});
                        if(typeof options.error === 'function') {
                            options.error();
                        }
                        loadingBar.stop();
                    }
                });
            };
            
            /**
             * Save event
             * @param {object} options
             * @property {string} options.url Url address to creaet new event
             * @property {string} options.data Event data. Example:
             *      <pre>
             *      {
             *         classUri: "http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery",
             *         start: "2015-04-17 00:00"
             *         end: "2015-04-18 00:00",
             *         label: "Delivery label",
             *         uri: "http_2_sample_1_first_0_rdf_3_i14301245512201554",
             *         id: "http://sample/first.rdf#i14301245512201554"
             *      }
             *      </pre>
             * @returns {undefined}
             */
            this.saveEvent = function (fcEvent, callback) {
                loadingBar.start();
                
                var data = {
                    label      : fcEvent.label === undefined ? fcEvent.title : fcEvent.label,
                    classUri   : fcEvent.classUri,
                    id         : fcEvent.id,
                    uri        : fcEvent.uri,
                    start      : fcEvent.start.clone().add(fcEvent.start._tzm, 'm').format('YYYY-MM-DD HH:mm'),
                    end        : fcEvent.end.clone().add(fcEvent.end._tzm, 'm').format('YYYY-MM-DD HH:mm'),
                    recurrence : ''
                };
                
                if (fcEvent.resultserver) {
                    data.resultserver = fcEvent.resultserver;
                }
                
                if (fcEvent.recurrence) {
                    var rruleOptions = RRule.parseString(fcEvent.recurrence),
                        rrule;
                    rruleOptions.dtstart = fcEvent.start.clone().toDate();
                    rrule = new RRule(rruleOptions);
                    data.recurrence = rrule.toString();
                }
                
                if (fcEvent.groups && _.isArray(fcEvent.groups)) {
                    data.groups = fcEvent.groups;
                }
                if (fcEvent.maxexec !== undefined) {
                    data.maxexec = fcEvent.maxexec;
                }
                
                $.ajax({
                    url     : '/taoDeliverySchedule/CalendarApi',
                    type    : 'PUT',
                    data    : data,
                    global : false,
                    dataType : 'json',
                    success : function (response) {
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
                            
                            feedback().info(response.message);
                            loadingBar.stop();
                        });
                    },
                    error   : function (xhr, err) {
                        loadingBar.stop();
                        var message = that.getRequestErrorMessage(xhr);
                        feedback().warning(message, {encodeHtml : false});
                    }
                });
            };
            
            /**
             * Delete selected on the tree event
             * @returns {undefined}
             */
            this.deleteEvent = function (eventId) {
                actionManager.exec(
                    'delivery-delete', 
                    _.extend(
                        actionManager._resourceContext, 
                        {action : actionManager.getBy('delivery-delete')}
                    )
                );
            };
            
            /**
             * Function parse response and returns error message
             * @param {object} xhr jqXHR object.
             * @returns {xhr.responseText|responseJSON.message|String}
             */
            this.getRequestErrorMessage = function (xhr) {
                var message = '';
                try {
                    var responseJSON = $.parseJSON(xhr.responseText);
                    if (responseJSON.errors) {
                        $.each(responseJSON.errors, function (key, val) {
                            message += key + ': ' + val + "<br>";
                        });
                    } else if (responseJSON.message) {
                        message = responseJSON.message;
                    } else {
                        message = xhr.responseText;
                    }
                } catch (e) {
                    message = xhr.responseText;
                }
                return message;
            };
            
            /**
             * Edit selected on the tree event (show edit form)
             * @returns {undefined}
             */
            this.editEvent = function (eventId) {
                actionManager.exec(
                    'delivery-edit', 
                    _.extend(
                        actionManager._resourceContext, 
                        {action : actionManager.getBy('delivery-edit')}
                    )
                );
            };
            
            /**
             * Load event by Id from the server
             * 
             * @param {string} eventId
             * @param {function} callback
             * @returns {undefined}
             */
            this.loadEvent = function (eventId, callback) {
                $.ajax({
                    url : '/taoDeliverySchedule/CalendarApi?uri=' + eventId,
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
                        rrule = RRule.fromString(event.recurrence);

                    var recurringEventIds = [];
                    
                    $.each(rrule.all(), function (rEventKey, rEventDate) {
                        var startMoment = moment(rEventDate),
                            endMoment = moment(rEventDate).add(diff, 'ms'),
                            rEvent = _.cloneDeep(event);

                        rEvent.start = startMoment.utc().format('YYYY-MM-DDTHH:mm:ssZZ');
                        if (rEvent.start === event.start) {
                            return;
                        }
                        
                        rEvent.end = endMoment.utc().format('YYYY-MM-DDTHH:mm:ssZZ');
                        rEvent.id = event.id + rEventKey;
                        rEvent.subEvent = true;
                        rEvent.subEventNum = rEventKey;
                        rEvent.parentEventId = event.id;
                        rEvent.className = ['sub-event'];
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
        };
        
        EventService.getInstance = function () {
            // Gets an instance of the singleton.
            if (instance === null) {
                instance = new EventService();
            }
            return instance;
        };
        
        return EventService.getInstance();
    }
);