import {Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class ActiveSprintActionButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.ActiveSprintButton');
	}

	render(): HTMLElement
	{
		const node = Tag.render`
			<div class="ui-btn ui-btn-sm ui-btn-primary ui-btn-xs ui-btn-round ui-btn-no-caps">
				<span>
					${Loc.getMessage('TASKS_SCRUM_ACTIONS_COMPLETE_SPRINT')}
				</span>
			</div>
		`;

		Event.bind(node, 'click', this.onCompleteSprintClick.bind(this));

		return node;
	}

	onCompleteSprintClick()
	{
		this.emit('completeSprint');
	}
}