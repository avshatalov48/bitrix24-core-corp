import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Button, ButtonColor, ButtonSize } from 'ui.buttons';

export class BurnDownButton extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.BurnDownButton');
	}

	render(): HTMLElement
	{
		const burnDownButton = new Button({
			text: Loc.getMessage('TASKS_SCRUM_ACTIVE_SPRINT_BUTTON'),
			color: ButtonColor.PRIMARY,
			size: ButtonSize.EXTRA_SMALL,
			round: true,
			noCaps: true,
			onclick: () => {
				this.onClick();
			},
		});

		return burnDownButton.render();
	}

	onClick()
	{
		this.emit('click');
	}
}
