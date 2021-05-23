import {Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class EpicCreationButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.EpicCreationButton');
	}

	render(): HTMLElement
	{
		const element = Tag.render`
			<a class="ui-link ui-link-dashed ui-link-secondary tasks-scrum-action-epic">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_LIST_ACTIONS_EPIC_ADD')}
			</a>
		`;

		Event.bind(element, 'click', this.onClick.bind(this));

		return element;
	}

	onClick()
	{
		this.emit('openAddEpicForm')
	}
}