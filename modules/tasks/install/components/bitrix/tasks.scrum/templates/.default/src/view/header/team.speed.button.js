import {Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class TeamSpeedButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.TeamSpeedButton');
	}

	render(): HTMLElement
	{
		const node = Tag.render`
			<button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-round ui-btn-xs">
				${Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_BUTTON')}
			</button>
		`;

		Event.bind(node, 'click', this.onClick.bind(this));

		return node;
	}

	onClick()
	{
		this.emit('click');
	}
}