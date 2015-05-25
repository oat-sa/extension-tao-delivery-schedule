<?php  
$timeZones = get_data('timeZones');
$userTimeZone = get_data('userTimeZone');
$days = array(
    'MO' => 'Monday',
    'TU' => 'Tuesday',
    'WE' => 'Wednesday',
    'TH' => 'Thursday',
    'FR' => 'Friday',
    'SA' => 'Saturday',
    'SU' => 'Sunday',
);
?>
<span class="event-tooltip__close js-close icon icon-result-nok"></span>
<div>
    <div>
        <table id="history-list"></table>
        <div id="history-list-pager"></div>
    </div>
</div>
<div>
    <form method="post" class="edit-delivery-form">
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
                                    <input name="recurrence" type="hidden" value="{{recurrence}}">
                                    <select name="rrule[freq]">
                                        <option value="3" title="<?= __('Daily') ?>"><?= __('Daily') ?></option>
                                        <option value="2" title="<?= __('Weekly') ?>"><?= __('Weekly') ?></option>
                                        <option value="1" title="<?= __('Monthly') ?>"><?= __('Monthly') ?></option>
                                        <option value="0" title="<?= __('Yearly') ?>"><?= __('Yearly') ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="form_desc">{{__ 'Repeat every'}}</label>
                                </td>
                                <td>
                                    <select name="rrule[interval]">
                                        <?php for ($i = 1; $i <= 30; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr class="js-byday-row">
                                <td>
                                    <label class="form_desc">{{__ 'Repeat on'}}</label>
                                </td>
                                <td>
                                    <div>
                                        <?php 
                                        $i = 0;
                                        foreach ($days as $abbr => $name): 
                                        ?>
                                        <span>
                                            <label title="<?= __($name) ?>">
                                                <input name="rrule[byweekday][]" value="<?= $i ?>" type="checkbox" title="{{<?= __($name) ?>}}">
                                                <?= $abbr ?>
                                            </label>
                                        </span>
                                        <?php
                                            $i++;
                                            endforeach; 
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="form_desc">{{__ 'Count'}}</label>
                                </td>
                                <td>
                                    <input name="rrule[count]" value="1" type="text" title="{{__ 'Count'}}">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label class="form_desc">{{__ 'Summary'}}</label>
                                </td>
                                <td>
                                    <span class="js-rrule-summary"></span>
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
                    <table class="tt-list js-tt-list" border="0">
                        <thead>
                            <tr>
                                <th>
                                    <label class="form_desc">{{__ 'Assigned test-takers'}}</label>
                                </th>
                                <th>
                                    <label class="form_desc">{{__ 'Excluded test-takers'}}</label>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="js-assigned-summary-wrap">
                                        \{{#if total}}
                                            <span class="filters-summary">
                                                \{{#if filtered}}
                                                \{{__ 'Filtered'}} \{{filtered}} {{__ 'of'}} \{{total}}
                                                <a href="#" class="reset-filter js-reset-filter">{{__ 'Show all'}}</a>
                                                \{{else}}
                                                    \{{message}}
                                                \{{/if}}
                                            </span>
                                        \{{else}}
                                            <div class="summary-warning">
                                                <span class="icon-info"></span> \{{{message}}}
                                            </div>
                                        \{{/if}}
                                    </div>
                                    
                                    <input placeholder="{{__ 'Filter'}}" type="text"class="tt-list__filter full-width js-excluded-tt-filter">
                                    <select multiple="multiple" class="tt-select js-assigned-tt-list" name="assigned-tt-list" size="9">
                                    </select>
                                </td>
                                <td>
                                    <div class="js-excluded-summary-wrap">
                                        \{{#if filtered}}
                                            <span class="filters-summary">
                                                {{__ 'Filtered'}} \{{filtered}} {{__ 'of'}} \{{total}}
                                                <a href="#" class="reset-filter js-reset-filter">{{__ 'Show all'}}</a>
                                            </span>
                                        \{{else}}
                                            \{{{message}}}
                                        \{{/if}}
                                    </div>
                                    <input placeholder="{{__ 'Filter'}}" type="text" class="tt-list__filter full-width js-assigned-tt-filter">
                                    <select multiple="multiple" class="tt-select js-excluded-tt-list" name="ttexcluded" size="9">
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
        <button class="form-submitter btn-success small" type="button">
            <span class="icon-save"></span>{{__ 'Save'}}
        </button>
    </form>
</div>