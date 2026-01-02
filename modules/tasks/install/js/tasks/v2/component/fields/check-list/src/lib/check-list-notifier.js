import { Loc, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { UI } from 'ui.notification';

type Params = {
	content: string,
	timerValue?: number,
}

export class CheckListNotifier extends EventEmitter
{
	#interval: ?number = null;
	#timerValue: number = 5;
	#counter: number = 5;
	#content: string = '';

	#balloonWithTimer: BX.UI.Notification.Balloon;

	constructor(params: Params)
	{
		super();

		this.setEventNamespace('Tasks.V2.CheckList.CheckListNotifier');

		this.#content = params.content;
		this.#timerValue = Type.isUndefined(params.timerValue) ? this.#timerValue : params.timerValue;
	}

	showBalloonWithTimer(): void
	{
		this.#counter = this.#timerValue;

		this.#balloonWithTimer = UI.Notification.Center.notify({
			id: `check-list-balloon-${Text.getRandom()}`,
			content: this.#getBalloonContent(),
			actions: [{
				title: Loc.getMessage('TASKS_V2_CHECK_LIST_BALLOON_CANCEL'),
				events: {
					mouseup: this.#handleCancelClick.bind(this),
				},
			}],
			events: {
				onClose: this.#handleClosingBalloon.bind(this),
			},
		});

		this.#startTimer();
	}

	stopTimer(): void
	{
		this.emit('complete', true);

		this.#balloonWithTimer.close();
	}

	#startTimer(): void
	{
		this.#interval = setInterval(() => {
			this.#counter--;

			this.#balloonWithTimer.update({ content: this.#getBalloonContent() });

			if (this.#counter <= 0)
			{
				this.emit('complete', true);

				this.#balloonWithTimer.close();
			}
		}, 1000);
	}

	#handleCancelClick()
	{
		this.#balloonWithTimer.close();
	}

	#handleClosingBalloon()
	{
		clearInterval(this.#interval);

		this.emit('complete', false);
	}

	#getBalloonContent(): string
	{
		return this.#content.replace('#countdown#', this.#counter);
	}
}
