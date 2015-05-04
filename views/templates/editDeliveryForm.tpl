<?php  
$timeZones = get_data('timeZones');
$userTimeZone = get_data('userTimeZone');
?>
<span class="event-tooltip__close js-close icon icon-result-nok"></span>
<div>
    <div>
        <table id="history-list"></table>
        <div id="history-list-pager"></div>
    </div>
</div>
<div>
    <form method="post" class="edit-delivery-form" >
        <input name="id" type="hidden" value="{{id}}">
        <input name="uri" type="hidden" value="{{uri}}">
        <table border="0" cellpadding="0" cellspacing="0" class="edit-delivery-form__table">
            <tr>
                <td><label class="form_desc">{{__ 'Published on'}}</label></td>
                <td>{{publishedFromatted}}</td>
            </tr>
            <tr>
                <td><label class="form_desc">{{__ 'Attempts'}}</label></td>
                <td>
                    {{executionsMessage}}
                </td>
            </tr>
            <tr>
                <td>
                    <label class="form_desc">{{__ 'Label'}} *</label>
                </td>
                <td>
                    <?= get_data('form')->getElement('label')->render() ?>
                </td>
            </tr>
            <tr class="edit-delivery-form_time-row">
                <td>
                    <label class="form_desc">{{__ 'Duration'}}</label>
                </td>
                <td>
                    <input class="js-delivery-start-date" type="text">
                    <input class="js-delivery-start-time" type="text">
                    <span>{{__ 'to'}}</span>
                    <input class="js-delivery-end-date" type="text">
                    <input class="js-delivery-end-time" type="text">
                    <span>{{__ 'timezone'}}</span>
                    <select class="js-delivery-time-zone" name="timezone">
                        <?php foreach($timeZones as $timeZone): ?>
                        <option <?= ($userTimeZone == $timeZone['label'])? 'selected' : '' ?> value="<?= $timeZone['value'] ?>">
                            <?= $timeZone['label'] ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                    
                    <input name="start" class="js-delivery-start" type="hidden" value="{{start}}">
                    <input name="end" class="js-delivery-end" type="hidden" value="{{end}}">
                </td>
            </tr>
            <tr>
                <td>
                    <label class="form_desc">{{__ 'Repeat'}}</label>
                </td>
                <td>
                    <input type="checkbox" class="js-repeat-toggle">
                </td>
            </tr>
            <tr>
                <td>
                    
                </td>
                <td>
                    <div class="repeat-event-table">
                        <table>
                            <tr>
                                <td>
                                    <label class="form_desc">{{__ 'Repeats'}}</label>
                                </td>
                                <td>
                                    <select name="rrule[freq]">
                                        <option value="daily" title="Daily">Daily</option>
                                        <option value="weekly" title="Weekly">Weekly</option>
                                        <option value="monthly" title="Monthly">Monthly</option>
                                        <option value="yearly" title="Yearly">Yearly</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="form_desc">{{__ 'Repeat every'}}</label>
                                </td>
                                <td>
                                    <select name="rrule[interval]">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                        <option value="16">16</option>
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="form_desc">{{__ 'Repeat on'}}</label>
                                </td>
                                <td>
                                    <div>
                                        <span>
                                            <label title="Monday">
                                                <input name="rrule[byday][MO]" value="1" type="checkbox" title="{{__ 'Monday'}}">
                                                Mo
                                            </label>
                                        </span>
                                        <span>
                                            <label title="Tuesday">
                                                <input name="rrule[byday][TU]" value="1" type="checkbox" title="{{__ 'Tuesday'}}">
                                                Tu
                                            </label>
                                        </span>
                                        <span>
                                            <label title="Wednesday">
                                                <input name="rrule[byday][WE]" value="1" type="checkbox" title="{{__ 'Wednesday'}}">
                                                We
                                            </label>
                                        </span>
                                        <span>
                                            <label title="Thursday">
                                                <input name="rrule[byday][TH]" value="1" type="checkbox" title="{{__ 'Thursday'}}">
                                                Th
                                            </label>
                                        </span>
                                        <span>
                                            <label title="Friday">
                                                <input name="rrule[byday][FR]" value="1" type="checkbox" title="{{__ 'Friday'}}">
                                                Fr
                                            </label>
                                        </span>
                                        <span>
                                            <label title="Saturday">
                                                <input name="rrule[byday][SA]" value="1" type="checkbox" title="{{__ 'Saturday'}}">
                                                Sa
                                            </label>
                                        </span>
                                        <span>
                                            <label title="Sunday">
                                                <input name="rrule[byday][SU]" value="1" type="checkbox" title="{{__ 'Sunday'}}">
                                                Su
                                            </label>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <label class="form_desc">{{__ 'Max. number of executions (default: unlimited)'}}</label>
                </td>
                <td>
                    <?= get_data('form')->getElement('maxexec')->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label class="form_desc">{{__ 'Result Server'}}</label>
                </td>
                <td>
                    <?= get_data('form')->getElement('resultserver')->render() ?>
                </td>
            </tr>
        </table>
                
        <table border="0" cellpadding="0" cellspacing="0" class="edit-delivery-form__table edit-delivery-form__test-takers-table">
            <tr>
                <td>
                    <h3>{{__ 'Assigned to'}}</h3>
                    <div class="js-groups">
                    </div>
                </td>
                <td>
                    <h3>{{__ 'Test-takers'}}</h3>
                    {{#if ttassignedMessage}}
                        {{ttassignedMessage}}.
                    {{/if}}
                    
                    {{#if ttexcludedMessage}}
                    <div class="feedback-info small">
                        <span class="icon-info"></span>
                        {{ttexcludedMessage}}.
                    </div>
                    {{/if}}
                    {{#unless ttexcludedMessage}}
                        {{#unless ttassignedMessage}}
                        <div class="feedback-info small">
                            <span class="icon-info"></span>
                            {{__ 'Delivery is not assigned to any test-taker'}}.
                        </div>
                        {{/unless}}
                    {{/unless}}
                </td>
            </tr>
        </table>
        <button class="form-submitter btn-success small" type="button">
            <span class="icon-save"></span>Save
        </button>
    </form>
</div>