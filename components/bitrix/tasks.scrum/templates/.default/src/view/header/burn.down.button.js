import {Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class BurnDownButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.BurnDownButton');
	}

	render(): HTMLElement
	{
		const node = Tag.render`
			<div class="tasks-scrum-button-container">
				<button class="ui-btn ui-btn-primary ui-btn-xs">
					${Loc.getMessage('TASKS_SCRUM_ACTIVE_SPRINT_BUTTON')}
				</button>
			</div>
		`;

		Event.bind(node, 'click', this.onClick.bind(this));

		return node;
	}

	onClick()
	{
		this.emit('click');
	}
}