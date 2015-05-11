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
            
            this.idAttrPrefix = 'fc_event_id_';
            
            /**
             * Create new event
             * @param {object} options
             * @property {string} options.url Url address to creaet new event
             * @property {string} options.data Event data. Example:
         *          <pre>
         *          {
         *             classUri: "http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery",
         *             end: "2015-04-18 00:00",
         *             label: "sdfdsf",
         *             simpleWizard_sent: "1",
         *             start: "2015-04-17 00:00",
         *             test: "http://sample/first.rdf#i1429018012670729"
         *          }
         *          </pre>
             * @returns {undefined}
             */
            this.createEvent = function (options) {
                $.ajax({
                    url     : '/taoDeliverySchedule/CalendarApi',
                    type    : 'POST',
                    data    : options.data,
                    success : function (response) {
                        feedback().info(response.message);
                        if (response.uri) {
                            $treeElt.trigger('addnode.taotree', [{
                                'uri'       : response.uri,
                                'parent'    : options.data.classUri,
                                'label'     : options.data.label,
                                'cssClass'  : 'node-instance'
                            }]);
                        }
                    },
                    error   : function (xhr, err) {
                        feedback().warning('Something went wrong');
                    }
                });
            };
            
            /**
             * Save
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
                    label : fcEvent.label ? fcEvent.label : fcEvent.title,
                    classUri : fcEvent.classUri,
                    id : fcEvent.id,
                    uri : fcEvent.uri,
                    start : fcEvent.start.clone().add(fcEvent.start._tzm, 'm').format('YYYY-MM-DD HH:mm'),
                    end : fcEvent.end.clone().add(fcEvent.end._tzm, 'm').format('YYYY-MM-DD HH:mm'),
                    recurrence : ''
                };
                
                if (fcEvent.resultserver) {
                    data.resultserver = fcEvent.resultserver;
                }
                
                if (fcEvent.recurrence) {
                    var rruleOptions = RRule.parseString(fcEvent.recurrence),
                        rrule;
                    rruleOptions.dtstart = fcEvent.start.clone().add(fcEvent.start._tzm, 'm').toDate();
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
                        feedback().warning('Something went wrong');
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
             * Select event on the tree by Id
             * @param {string} eventId
             * @param {string} classId
             * @returns {undefined}
             */
            this.selectEvent = function (eventId, classId) {
                if ($('#' + eventId).length == 0) {
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
                    success : function (data) {
                        if (typeof callback === 'function') {
                            callback(data);
                        }
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
             * Get event jQuery DOM element by id 
             * 
             * @param {string} id Event id
             * @returns {jQuery element}
             */
            this.getEventElement = function (id) {
                return $('#' + that.idAttrPrefix + id);
            };
            
            this.getRecurringEvents = function (event) {
                var events = [];
            
                if (event.recurrence) {
                    var diff = moment(event.end).diff(moment(event.start)),
                        rrule = RRule.fromString(event.recurrence);

                    event.recurringEventIds = [];
                    
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
                        rEvent.parentEventId = event.id;
                        rEvent.className = ['sub-event'];
                        //rEvent.editable = false;
                        
                        event.recurringEventIds.push(rEvent.id);
                        events.push(rEvent);
                    });
                    
                }
                
                return events;
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