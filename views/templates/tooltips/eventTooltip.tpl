<div class="event-tooltip">
    <span class="event-tooltip__close js-close icon icon-result-nok"></span>
    <p class="event-tooltip__content">
        <b>{{__ 'Start'}}:</b> {{start}}<br>
        {{#if end}}
        <b>{{__ 'End'}}:</b> {{end}}<br>
        {{/if}}
    </p>
    <hr>
    <p class="event-tooltip__controls">
        <a href="#" class="js-delete-event">{{__ 'Delete'}}</a>
        <a href="#" class="js-edit-event">{{__ 'Edit delivery'}} &raquo;</a>
    </p>
</div>