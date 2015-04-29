<span class="event-tooltip__close js-close icon icon-result-nok"></span>
<div>
    <div>
        <table id="history-list"></table>
        <div id="history-list-pager"></div>
    </div>
</div>
<div>
    <form method="post" class="edit-delivery-form" >
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
                    <input class="full-width" type="text" value="{{title}}">
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

                    <input class="js-delivery-start" type="hidden" value="{{start}}">
                    <input class="js-delivery-end" type="hidden" value="{{end}}">
                </td>
            </tr>
            <tr>
                <td>
                    <label class="form_desc">{{__ 'Max. number of executions (default: unlimited)'}}
                    </label>
                </td>
                <td>
                    <input class="full-width" type="text" value="{{maxexec}}">
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
                    {{ttassignedMessage}}.
                    
                    {{#if ttexcludedMessage}}
                    <div class="feedback-info small">
                        <span class="icon-info"></span>
                        {{ttexcludedMessage}}.
                    </div>
                    {{/if}}
                </td>
            </tr>
        </table>
        <button class="form-submitter btn-success small" type="button">
            <span class="icon-save"></span>Save
        </button>
    </form>
</div>