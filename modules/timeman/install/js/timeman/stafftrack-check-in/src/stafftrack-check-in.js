import { Event, Extension, Loc, Tag, Type } from 'main.core';
import { Actions, Outline } from 'ui.icon-set.api.core';
import { UserStatisticsLink as CheckInQrAuthPopup } from 'stafftrack.user-statistics-link';
import { sendData } from 'ui.analytics';
import './style.css';
import 'ui.counter';

type Params = {
	container: HTMLElement,
};

type Counter = {
	CLASS: string,
	VALUE: string,
};

export class StafftrackCheckIn
{
	// eslint-disable-next-line no-unused-private-class-members
	#params: Params;
	#data: ?Counter;
	#layout: {
		counter: HTMLElement,
	};
	#isAirTemplate: boolean;

	constructor(params: Params)
	{
		const settings = Extension.getSettings('timeman.stafftrack-check-in');
		if (!settings.get('isCheckinEnabled'))
		{
			return;
		}

		this.#data = settings.get('counter');
		this.#params = params;
		this.#layout = {};
		this.#isAirTemplate = settings.get('isAirTemplate');

		const pwtContainer = params.container.querySelector('#timeman-pwt-container');
		if (pwtContainer)
		{
			pwtContainer.before(this.#render());
		}
		else
		{
			params.container.append(this.#render());
		}

		this.onDataReceived = this.#onDataReceived.bind(this);

		this.#bindEvents();
	}

	#bindEvents(): void
	{
		BX.addCustomEvent('onTimeManDataRecieved', this.onDataReceived);
		BX.addCustomEvent('onTimeManNeedRebuild', this.onDataReceived);
	}

	#onDataReceived(data: { CHECKIN_COUNTER: any })
	{
		this.#data = data.CHECKIN_COUNTER;
		this.#renderCounter();
	}

	#render(): HTMLElement
	{
		const iconOutlineName = (this.#isAirTemplate ? Outline.PLAY_L : Actions.PLAY);

		const wrap = Tag.render`
			<div class="timeman-stafftrack-check-in">
				<div class="ui-icon-set --${iconOutlineName}"></div>
				<div class="timeman-stafftrack-check-in-text">
					${Loc.getMessage('TIMEMAN_STAFFTRACK_CHECK_IN')}
				</div>
				${this.#renderCounter()}
			</div>
		`;

		Event.bind(wrap, 'click', this.#onClickHandler.bind(this));

		return wrap;
	}

	#onClickHandler(): void
	{
		if (!CheckInQrAuthPopup)
		{
			return;
		}

		new CheckInQrAuthPopup({ intent: 'check-in' }).show();

		sendData({
			tool: 'checkin',
			category: 'shift',
			event: 'popup_open',
			c_section: 'timeman',
		});
	}

	#renderCounter(): HTMLElement | string
	{
		const display = Type.isStringFilled(this.#data.VALUE) ? '' : 'none';

		const counter = Tag.render`
			<div class="timeman-stafftrack-check-in-counter" style="display: ${display};">
				<span class="ui-counter ${this.#data.CLASS} ui-counter-sm">
					<span class="ui-counter-inner">
						${this.#data.VALUE}
					</span>
				</span>
			</div>
		`;

		this.#layout.counter?.replaceWith(counter);
		this.#layout.counter = counter

		return this.#layout.counter;
	}
}
