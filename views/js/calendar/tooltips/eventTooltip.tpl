<div class="event-tooltip">
    <span class="event-tooltip__close js-close icon icon-result-nok"></span>
    <p class="event-tooltip__content">
        <b>{{__ 'Start'}}:</b> {{start}}<br>
        {{#if end}}
        <b>{{__ 'End'}}:</b> {{end}}<br>
        {{/if}}
    </p>
    <form class="hidden">
        <input type="hidden" name="id" value="{{fcEvent.id}}" />
        <input type="hidden" name="classUri" value="{{fcEvent.classUri}}" />
        <input type="hidden" name="uri" value="{{fcEvent.uri}}" />
    </form>
    <hr>
    <p class="event-tooltip__controls">
    {{#if fcEvent.subEvent}}
        <a href="#" class="js-go-to-parent-event">{{__ 'Go to parent event'}} &raquo;</a>
        <a href="#" class="js-edit-event">{{__ 'Edit sub delivery'}} &raquo;</a>
    {{else}}
        <a href="#" class="js-delete-event">{{__ 'Delete'}}</a>
        <a href="#" class="js-edit-event">{{__ 'Edit delivery'}} &raquo;</a>
    {{/if}}
    </p>
</div>