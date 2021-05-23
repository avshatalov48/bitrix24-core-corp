import {Loc, Tag} from 'main.core';

export class EventsHeader
{
	constructor()
	{
		this.element = null;
	}

	render(): HTMLElement
	{
		this.element = Tag.render`
			<a class="ui-link">
				${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_EVENT')}
			</a>
		`;

		return this.element;
	}
}