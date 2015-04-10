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
        'taoDeliverySchedule/calendar/tooltips/eventTooltip',
        'handlebars',
        'i18n',
        'ui/feedback',
        'taoDeliverySchedule/lib/qtip/jquery.qtip'
    ],
    function (_, $, eventTooltip, Handlebars, __, feedback) {
        'use stirct';
        return function (options) {
            var that = this;
            
            eventTooltip.apply(this, arguments);
                 
            this.init = function () {
                that.set({
                    'content.title' : __('Create a new delivery'),
                    'style.width' : 400,
                    'position.adjust.resize' : true
                });
            };
            
            this.show = function (options) {
                this.callback('beforeShow');
                var tplOptions = {
                    start : options.start.format('ddd, MMMM D, H:mm'),
                    end : options.end ? options.end.format('ddd, MMMM D, H:mm') : false
                };
                
                that.tooltip.set({
                    'position.target' : [options.e.pageX, options.e.pageY]
                });
                
                $.ajax({
                    url : options.action.url,
                    type : 'POST',
                    data : {
                        classUri : options.classUri,
                        id : options.id
                    },
                    success : function (response) {
                        var tpl = Handlebars.compile(response),
                            $form;
                        that.tooltip.set({
                            'content.text' : tpl(tplOptions)
                        });
                        that.tooltip.show();
                        
                        $form = that.tooltip.elements.content.find('form');
                        $form.find('#label').focus();
                        
                        $form.find('[name="start"]').val(options.start.format('YYYY-MM-DD HH:mm'));
                        $form.find('[name="end"]').val(options.end.format('YYYY-MM-DD HH:mm'));
                        
                        that.tooltip.elements.content.find('.js-create-event').on('click', function () {
                            that.submit($form);
                        });
                        
                        that.callback('afterShow');
                    }
                });
            };
            
            this.submit = function ($form) {
                that.callback('beforeSubmit');
                $.ajax({
                    url     : $form.attr('action'),
                    type    : $form.attr('method'),
                    data    : $form.serialize(),
                    success : function(data) {
                        feedback().success(__('Delivery saved'));
                        that.callback('afterSubmit');
                    },
                    error   : function(xhr, err) {
                        feedback().warning('Something went wrong');
                    }
                });
            };
            
            this.init();
        };
    }
);


