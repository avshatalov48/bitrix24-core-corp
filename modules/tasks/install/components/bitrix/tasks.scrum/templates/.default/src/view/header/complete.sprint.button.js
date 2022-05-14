import {Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

type Params = {
	canCompleteSprint: boolean
}

export class CompleteSprintButton extends EventEmitter
{
	constructor(params: Params)
	{
		super();

		this.canCompleteSprint = params.canCompleteSprint;

		this.setEventNamespace('BX.Tasks.Scrum.ActiveSprintButton');
	}

	render(): HTMLElement
	{
		const disableUiClass = (this.canCompleteSprint ? '' : 'ui-btn-disabled');

		const node = Tag.render`
			<div class="ui-btn ui-btn-sm ui-btn-primary ui-btn-xs ui-btn-round ui-btn-no-caps ${disableUiClass}">
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
		if (this.canCompleteSprint)
		{
			this.emit('completeSprint');
		}
	}
}