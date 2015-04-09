<div class="event-tooltip">
    <span class="event-tooltip__close js-close icon icon-result-nok"></span>
    <p class="event-tooltip__content">
        <b>{{__ 'Start'}}:</b> {{start}}<br>
        {{#if end}}
        <b>{{__ 'End'}}:</b> {{end}}<br>
        {{/if}}
    </p>
    <p class="event-tooltip__content">
        <?=get_data('myForm')?>
    </p>
    <hr>
    <p class="event-tooltip__controls">
        <a href="#" class="js-close">{{__ 'Cancel'}}</a>
        <a href="#" class="js-edit-event">{{__ 'Create'}} &raquo;</a>
    </p>
</div>