import {Dom, Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class GroupActionsButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.GroupActionsButton');

		this.element = null;
	}

	render(): HTMLElement
	{
		this.element = Tag.render`
			<a class="ui-link ui-link-dashed ui-link-secondary tasks-scrum-group-actions">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_GROUP')}
			</a>
		`;

		Event.bind(this.element, 'click', this.onClick.bind(this, this.element));

		return this.element;
	}

	deactivateGroupMode()
	{
		if (this.element && this.element.classList.contains('tasks-scrum-group-actions-active'))
		{
			Dom.toggleClass(this.element, 'tasks-scrum-group-actions');
			Dom.toggleClass(this.element, 'tasks-scrum-group-actions-active');
			Dom.toggleClass(this.element, 'ui-link-secondary');
			Dom.toggleClass(this.element, 'ui-link-dashed');

			this.emit('deactivateGroupMode');
		}
	}

	onClick(element: HTMLElement)
	{
		Dom.toggleClass(element, 'tasks-scrum-group-actions');
		Dom.toggleClass(element, 'tasks-scrum-group-actions-active');
		Dom.toggleClass(element, 'ui-link-secondary');
		Dom.toggleClass(element, 'ui-link-dashed');
		if (element.classList.contains('tasks-scrum-group-actions-active'))
		{
			this.emit('activateGroupMode');
		}
		else
		{
			this.emit('deactivateGroupMode');
		}
	}

	removeYourself()
	{
		Dom.remove(this.element);
		this.element = null;
	}
}