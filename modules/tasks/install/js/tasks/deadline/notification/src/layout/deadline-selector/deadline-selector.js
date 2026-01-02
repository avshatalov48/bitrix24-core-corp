import { Tag, Extension, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DeadlinePicker } from './deadline-picker';
import { MovedDeadlinePopup } from './moved-deadline-popup';
import { OverdueDeadlinePopup } from './overdue-deadline-popup';

type DeadlineSelectorOptions = {
	matchWorkTime: boolean,
	events: {
		[eventName: string]: (event: BaseEvent) => void,
	},
}

const DAYS_IN_WEEK = 7;

export class DeadlineSelector extends EventEmitter
{
	#EVENT_NAMESPACE: string = 'BX.Tasks.Deadline.Notification.Layout.DeadlineSelector';

	#calendar: BX.Tasks.Calendar;
	#deadlinePicker: DeadlinePicker;
	#input: HTMLInputElement;
	#matchWorkTime: boolean;

	#movedDeadlinePopup: MovedDeadlinePopup;
	#overdueDeadlinePopup: OverdueDeadlinePopup;

	constructor(options: DeadlineSelectorOptions)
	{
		super();

		this.setEventNamespace(this.#EVENT_NAMESPACE);
		this.subscribeFromOptions(options.events);

		this.#init(options);
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-deadline-notification-form__content_deadline-selector">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-datetime ui-ctl-w75">
					<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
					${this.#input}
				</div>
			</div>
		`;
	}

	getInputValue(): string
	{
		return this.#input.value;
	}

	getDeadline(): Date | null
	{
		return this.#deadlinePicker.getSelectedDate();
	}

	getToday(): Date
	{
		return this.#deadlinePicker.getToday();
	}

	getFormattedDeadline(format: string): string
	{
		return this.#deadlinePicker.formatDate(this.getDeadline(), format);
	}

	#init(options: DeadlineSelectorOptions): void
	{
		this.#input = Tag.render`
			<input class="ui-ctl-element" data-id="tasks-deadline-notification-deadline" readonly>
		`;

		const portalSettings = this.#getPortalSettings();

		this.#initCalendar(portalSettings);
		this.#initMatchWorkTime(options, portalSettings);

		this.#initDeadlinePicker(this.#input, this.#calendar, this.#matchWorkTime);
	}

	#initMatchWorkTime(options: DeadlineSelectorOptions, portalSettings: Object): void
	{
		this.#matchWorkTime = options.matchWorkTime;

		const weekends = portalSettings.WEEKEND;
		if (!Type.isArray(weekends))
		{
			return;
		}

		const countOfWeekends = weekends.length;

		if (countOfWeekends === DAYS_IN_WEEK)
		{
			this.#matchWorkTime = false;
		}
	}

	#initCalendar(portalSettings: Object): void
	{
		const adaptedSettings = BX.Tasks.Calendar.adaptSettings(portalSettings);

		this.#calendar = new BX.Tasks.Calendar(adaptedSettings);
	}

	#initDeadlinePicker(input: HTMLInputElement, calendar: BX.Tasks.Calendar, matchWorkTime: boolean): void
	{
		this.#deadlinePicker = new DeadlinePicker({
			events: {
				onMoveDateToWorkTime: this.#onMoveDateToWorkTime.bind(this),
				onLessCurrentDateSelect: this.#onLessCurrentDateSelect.bind(this),
				onSelect: () => this.emit('onSelect'),
			},
			input,
			calendar,
			matchWorkTime,
		});
	}

	#onMoveDateToWorkTime(): void
	{
		const overdueDeadlinePopup = this.#getOverdueDeadlinePopup();
		if (overdueDeadlinePopup.isShown())
		{
			return;
		}

		const movedDeadlinePopup = this.#getMovedDeadlinePopup();
		movedDeadlinePopup.show();
	}

	#onLessCurrentDateSelect(): void
	{
		const overdueDeadlinePopup = this.#getOverdueDeadlinePopup();
		overdueDeadlinePopup.show();
	}

	#getOverdueDeadlinePopup(): OverdueDeadlinePopup
	{
		if (!this.#overdueDeadlinePopup)
		{
			this.#overdueDeadlinePopup = new OverdueDeadlinePopup({
				bindElement: this.#input,
			});
		}

		return this.#overdueDeadlinePopup;
	}

	#getMovedDeadlinePopup(): MovedDeadlinePopup
	{
		if (!this.#movedDeadlinePopup)
		{
			this.#movedDeadlinePopup = new MovedDeadlinePopup({
				bindElement: this.#input,
			});
		}

		return this.#movedDeadlinePopup;
	}

	#getPortalSettings(): Object
	{
		const settings = Extension.getSettings('tasks.deadline.notification');

		return settings.get('portalSettings');
	}
}
