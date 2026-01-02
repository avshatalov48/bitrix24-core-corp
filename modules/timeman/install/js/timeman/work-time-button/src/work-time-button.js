import { Extension, Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { AirButtonStyle, Button, ButtonIcon } from 'ui.buttons';

import 'timeman';
import 'CJSTask';
import 'planner';
import 'tasks_planner_handler';
import 'calendar_planner_handler';
import 'ajax';
import 'timer';
import 'popup';
import 'ls';
import 'ui.fonts.opensans';
import 'ui.layout-form';
import 'ui.analytics';

import './work-time-button.css';

type Data = {
	workReport: Object,
	info: Object & {
		STATE: string,
		CAN_OPEN?: string,
		INFO: Object & {
			DATE_START: number,
			TIME_LEAKS: number,
		},
	},
	siteId: string,
};

const State = Object.freeze({
	Closed: 'CLOSED',
	Opened: 'OPENED',
	Paused: 'PAUSED',
	Expired: 'EXPIRED',
});
const Action = Object.freeze({
	Open: 'OPEN',
	Reopen: 'REOPEN',
});

export class WorkTimeButton
{
	#data: Data = {};
	#button: Button;
	#node: HTMLElement;
	#timerInterval: number;

	constructor()
	{
		const settings = Extension.getSettings('timeman.work-time-button');

		this.#data.workReport = settings.get('workReport');
		this.#data.info = settings.get('info');
		this.#data.siteId = settings.get('siteId');

		this.#button = new Button({
			size: Button.Size.MEDIUM,
			text: this.#getButtonText(),
			icon: this.#getButtonIcon(),
			useAirDesign: true,
			style: this.#getButtonStyle(),
			noCaps: true,
			wide: true,
			events: {
				click: this.#handleClick.bind(this),
			},
			className: 'timeman-work-time-button',
		});
		this.#node = this.#button.render();

		EventEmitter.subscribe('onTimemanInit', this.#init.bind(this));
		EventEmitter.subscribe('onTimeManDataRecieved', this.#updateState.bind(this));

		window.BX.timeman('bx_tm', this.#data.info, this.#data.siteId);
	}

	render(): HTMLElement
	{
		return this.#node;
	}

	#getState(): string
	{
		return this.#data.info.STATE;
	}

	#getAvailableAction(): ?string
	{
		return this.#data.info?.CAN_OPEN ?? null;
	}

	#getDateStart(): number
	{
		return this.#data.info.INFO.DATE_START;
	}

	#getTimeLeaks(): number
	{
		return this.#data.info.INFO.TIME_LEAKS;
	}

	#init(): void
	{
		this.#initWorkingButtonTimer();

		window.BXTIMEMAN.initFormWeekly(this.#data.workReport);
	}

	#updateState(baseEvent: BaseEvent): void
	{
		const [data] = baseEvent.getCompatData();

		this.#data.info = data;

		this.#button.setText(this.#getButtonText());
		this.#button.setIcon(this.#getButtonIcon());
		this.#button.setStyle(this.#getButtonStyle());

		this.#initWorkingButtonTimer();
	}

	#handleClick(button: Button, event: PointerEvent): void
	{
		event.stopPropagation();

		window.BXTIMEMAN.setBindOptions({
			node: this.#node,
			mode: 'popup',
			popupOptions: {
				autoHide: true,
				angle: false,
				offsetTop: -40,
				closeByEsc: true,
				bindOptions: {
					forceBindPosition: true,
					forceTop: true,
					forceLeft: false,
				},
				events: {
					onClose: () => {},
					onDestroy: () => {},
				},
				fixed: true,
			},
		});

		window.BXTIMEMAN.Open();
	}

	#getButtonText(): string
	{
		if (
			this.#getState() === State.Closed
			&& this.#getAvailableAction() === Action.Open
		)
		{
			return Loc.getMessage('TIMEMAN_WORK_TIME_BUTTON_TEXT_START');
		}

		if (this.#getState() === State.Opened)
		{
			return this.#getWorkingButtonText();
		}

		if (this.#getState() === State.Paused)
		{
			return Loc.getMessage('TIMEMAN_WORK_TIME_BUTTON_TEXT_CONTINUE');
		}

		if (
			this.#getState() === State.Closed
			&& this.#getAvailableAction() === Action.Reopen
		)
		{
			return Loc.getMessage('TIMEMAN_WORK_TIME_BUTTON_TEXT_CONTINUE');
		}

		if (this.#getState() === State.Expired)
		{
			return Loc.getMessage('TIMEMAN_WORK_TIME_BUTTON_TEXT_EXPIRED');
		}

		return '';
	}

	#getButtonIcon(): ?string
	{
		if (
			this.#getState() === State.Closed
			&& this.#getAvailableAction() === Action.Open
		)
		{
			return ButtonIcon.START;
		}

		if (this.#getState() === State.Opened)
		{
			return ButtonIcon.START;
		}

		if (this.#getState() === State.Paused)
		{
			return ButtonIcon.START;
		}

		if (
			this.#getState() === State.Closed
			&& this.#getAvailableAction() === Action.Reopen
		)
		{
			return ButtonIcon.REFRESH;
		}

		if (this.#getState() === State.Expired)
		{
			return ButtonIcon.ALERT;
		}

		return null;
	}

	#getButtonStyle(): string
	{
		if (this.#getState() === State.Opened)
		{
			return AirButtonStyle.TINTED;
		}

		if (this.#getState() === State.Paused)
		{
			return AirButtonStyle.FILLED;
		}

		if (
			this.#getState() === State.Closed
			&& this.#getAvailableAction() === Action.Reopen
		)
		{
			return AirButtonStyle.TINTED;
		}

		if (this.#getState() === State.Expired)
		{
			return AirButtonStyle.TINTED_ALERT;
		}

		return AirButtonStyle.FILLED;
	}

	#initWorkingButtonTimer(): void
	{
		this.#stopWorkingButtonTimer();

		if (this.#getState() === State.Opened)
		{
			this.#startWorkingButtonTimer();
		}
	}

	#startWorkingButtonTimer(): void
	{
		this.#timerInterval = setInterval(() => {
			this.#button.setText(this.#getWorkingButtonText());
		}, 1000);
	}

	#stopWorkingButtonTimer(): void
	{
		clearInterval(this.#timerInterval);
	}

	#getWorkingButtonText(): string
	{
		const nowSeconds = Math.floor(Date.now() / 1000);

		const workingSeconds = (nowSeconds - this.#getDateStart()) - (this.#getTimeLeaks() || 0);
		if (workingSeconds < 0)
		{
			return this.#formatTime(0, 0, 0);
		}

		const hours = Math.floor(workingSeconds / 3600);
		const minutes = Math.floor((workingSeconds % 3600) / 60);
		const seconds = workingSeconds % 60;

		return this.#formatTime(hours, minutes, seconds);
	}

	#formatTime(hours, minutes, seconds): string
	{
		const format = (value) => (value ?? 0).toString().padStart(2, '0');

		return Loc.getMessage('TIMEMAN_WORK_TIME_BUTTON_TEXT_WORKING')
			.replace('#time#', `${format(hours)}:${format(minutes)}:${format(seconds)}`);
	}
}
