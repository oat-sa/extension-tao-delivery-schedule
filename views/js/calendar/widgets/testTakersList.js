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

/**
 * Dual listbox widget for managing delivery test takers.
 * @param {type} _ lodash
 * @param {type} $ jQuery
 * @param {type} Handlebars
 * @param {type} __ i18n
 * @param {type} feedback
 * @param {type} testTakersService {@link /taoDeliverySchedule/calendar/testTakersService.js}
 * @returns {Function} Constructor
 * Usage example:
 * <pre>
 * testTakersList = new TestTakersList({
 *   container : $('.js-tt-list'),
 *   data : {ttexcluded : response.ttexcluded, ttassigned : response.ttassigned},
 *   deliveryId : response.id
 * });
 * </pre>
 */
define(
    [
        'lodash',
        'jquery',
        'handlebars',
        'i18n',
        'ui/feedback',
        'taoDeliverySchedule/calendar/testTakersService'
    ],
    function (_, $, Handlebars, __, feedback, testTakersService) {
        'use stirct';
        return function (options) {
            var that = this,
                $container = options.container,
                $assignedList = $container.find('.js-assigned-tt-list'),
                $excludedList = $container.find('.js-excluded-tt-list'),
                assignedSummaryTpl,
                excludedSummaryTpl;
            
            /**
             * Init widget
             * @returns {undefined}
             */
            this.init = function () {
                $container.on('keyup', '.tt-list__filter', function () {
                    that.applyFilter($(this));
                });
                $container.on('click', '.js-reset-filter', function (e) {
                    e.preventDefault();
                    var $input = $(this).closest('td').find('.tt-list__filter');
                    $input.val('');
                    that.applyFilter($input);
                });
                
                $container.find('.tt-select').on('change', function () {
                    that.moveOptions($(this).find('option:selected'));
                });
                
                assignedSummaryTpl = Handlebars.compile($('.js-assigned-summary-wrap').html());
                excludedSummaryTpl = Handlebars.compile($('.js-excluded-summary-wrap').html());
                $('.js-assigned-summary-wrap').empty();
                $('.js-excluded-summary-wrap').empty();
                
                if (options.data) {
                    that.render(options.data);
                }
            };
            
            /**
             * Move options to another selectbox.
             * @param {jQueryElement} $options option elements to move.
             * @returns {jQueryElement} moved option elements
             */
            this.moveOptions = function ($options) {
                $options.each(function () {
                    var $option = $(this);
                    if ($.contains($assignedList[0], $option[0])) {
                        $excludedList.append($option);
                    } else {
                        $assignedList.append($option);
                    }
                });
                that.unselectOptions();
                $('.tt-list__filter').each(function () {
                    that.applyFilter($(this));
                });
                return $options;
            };
            
            /**
             * Unselect selected rows in both selectboxes
             * @returns {undefined}
             */
            this.unselectOptions = function () {
                $('.tt-select option').each(function () {
                    $(this)[0].selected = false;
                });
            };
            
            /**
             * Render both lists according to the given data.
             * @param {object} data test-akers data
             * @property {array} data.ttassigned array of assigned test-takers
             * @property {object} data.ttassigned[].uri 
             * @property {object} data.ttassigned[].label 
             * @property {array} data.ttexcluded array of excluded test-takers
             * @property {object} data.ttexcluded[].uri 
             * @property {object} data.ttexcluded[].label 
             * @returns {undefined}
             */
            this.render = function (data) {
                $assignedList.empty();
                $excludedList.empty();
                $.each(data.ttassigned, function (key, val) {
                    $assignedList.append(
                        $('<option value="' + val.uri + '">' + val.label + '</option>')
                    );
                });
                $.each(data.ttexcluded, function (key, val) {
                    $excludedList.append(
                        $('<option value="' + val.uri + '">' + val.label + '</option>')
                    );
                });
                
                that.updateSummary();
            };
            
            /**
             * Load testakers assigned to the given groups and update both lists according to the given data.
             * @param {array} groups array of groups ids
             * @returns {undefined}
             */
            this.updateGroups = function (groups) {
                testTakersService.loadTestTakers(
                    {groups : groups, uri : options.deliveryId}
                ).done(
                    function (data) {
                        that.render(data);
                    }
                );
            };
            
            /**
             * Update messages over the lists.
             * @returns {undefined}
             */
            this.updateSummary = function () {
                var data = {
                    ttassigned : {
                        total : $assignedList.find('option').length,
                        filtered : $assignedList.find('option.filtered').length
                    },
                    ttexcluded : {
                        total : $excludedList.find('option').length,
                        filtered : $excludedList.find('option.filtered').length
                    }
                };
                
                data.ttexcluded.message = data.ttexcluded.total ? __('%d test-taker(s) are excluded', String(data.ttexcluded.total)) : '&nbsp;';
                data.ttassigned.message = __('Delivery is assigned to %d test-takers', String(data.ttassigned.total));
                
                $('.js-assigned-summary-wrap').html(assignedSummaryTpl(data.ttassigned));
                $('.js-excluded-summary-wrap').html(excludedSummaryTpl(data.ttexcluded));
            };
            
            /**
             * Apply filters to the lists.
             * @param {jQueryElement} $input filter filed
             * @returns {undefined}
             */
            this.applyFilter = function ($input) {
                var $container = $input.closest('td'),
                    $select = $container.find('.tt-select'),
                    str = $input.val(),
                    filtered = 0;
                
                $select.find('span > option').unwrap();
                $select.find('option').removeClass('filtered').each(function (key, option) {
                    var $option = $(option);
                    if ($option.text().indexOf(str) < 0) {
                        //IE cannot hide option by usual .hide() function
                        $option.addClass('filtered').wrap("<span>").parent().hide();
                        filtered++;
                    }
                });
                
                that.updateSummary();
            };
            
            this.init();
        };
    }
);


