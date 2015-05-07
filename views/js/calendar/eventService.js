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
        'uri',
        'layout/actions'
    ],
    function (_, $, feedback, uri, actionManager) {
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
            
            this.getEventById = function () {
                //c.fullCalendar( 'clientEvents', id);
            };
            
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
                    url     : options.url,
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
                var data = {
                    label : fcEvent.label ? fcEvent.label : fcEvent.title,
                    classUri : fcEvent.classUri,
                    id : fcEvent.id,
                    uri : fcEvent.uri,
                    resultserver : fcEvent.resultserver,
                    start : fcEvent.start.clone().add(fcEvent.start._tzm, 'm').format('YYYY-MM-DD HH:mm'),
                    end : fcEvent.end.clone().add(fcEvent.end._tzm, 'm').format('YYYY-MM-DD HH:mm'),
                    recurrence : fcEvent.recurrence
                };
                
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
                        feedback().info(response.message);
                        that.loadEvent(fcEvent.id, function (eventData) {
                            $calendar.fullCalendar('removeEvents', fcEvent.id);
                            $calendar.fullCalendar('renderEvent', eventData);
                            if (typeof callback === 'function') {
                                callback(eventData);
                            }
                        });
                    },
                    error   : function (xhr, err) {
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