import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Button, ButtonState, ButtonColor, ButtonSize } from 'ui.buttons';

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
		const completeBtn = new Button({
			text: Loc.getMessage('TASKS_SCRUM_ACTIONS_COMPLETE_SPRINT'),
			color: ButtonColor.PRIMARY,
			size: ButtonSize.EXTRA_SMALL,
			round: true,
			noCaps: true,
			onclick: () => {
				this.onCompleteSprintClick();
			},
		});

		if (!this.canCompleteSprint)
		{
			completeBtn.setStyle(ButtonState.DISABLED);
		}

		return completeBtn.render();
	}

	onCompleteSprintClick()
	{
		if (this.canCompleteSprint)
		{
			this.emit('completeSprint');
		}
	}
}
