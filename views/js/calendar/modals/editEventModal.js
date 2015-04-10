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
        'taoDeliverySchedule/calendar/modals/modal',
        'handlebars',
        'i18n',
        'taoDeliverySchedule/lib/qtip/jquery.qtip'
    ],
    function (_, $, modal, Handlebars, __) {
        'use stirct';
        return function ($container) {
            var that = this;
            
            modal.apply(this, arguments);
            
            this.init = function () {
                that.set({
                    'style.width' : 900,
                    'content.title' : 'This form is not work properly yet!'
                });
            };
            
            this.show = function (options) {
                this.callback('beforeShow');
                $.ajax({
                    url : options.action.url,
                    type : 'POST',
                    data : {
                        classUri : options.classUri,
                        id : options.id,
                        uri : options.uri
                    },
                    success : function (response) {
                        that.modal.set({'content.text' : response});
                        that.modal.show();
                    }
                });
            };
            
            this.init();
        };
    }
);


