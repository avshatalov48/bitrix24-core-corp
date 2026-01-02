import { Event, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { DatePicker } from 'ui.date-picker';

type DeadlinePickerOptions = {
	calendar: BX.Tasks.Calendar,
	input: HTMLInputElement,
	matchWorkTime: boolean,
	events: {
		[eventName: string]: (event: BaseEvent) => void,
	},
}

export class DeadlinePicker extends EventEmitter
{
	#EVENT_NAMESPACE: string = 'BX.Tasks.Deadline.Notification.Layout.DeadlinePicker';

	#calendar: BX.Tasks.Calendar;
	#datePicker: DatePicker;
	#input: HTMLInputElement;
	#matchWorkTime: boolean;

	constructor(options: DeadlinePickerOptions)
	{
		super();

		this.setEventNamespace(this.#EVENT_NAMESPACE);
		this.subscribeFromOptions(options.events);

		this.#init(options);
	}

	getSelectedDate(): Date | null
	{
		return this.#datePicker.getSelectedDate();
	}

	getToday(): Date
	{
		return this.#datePicker.getToday();
	}

	formatDate(date: Date, format: string): string
	{
		return this.#datePicker.formatDate(date, format);
	}

	#init(options: DeadlinePickerOptions): void
	{
		const calendar = options.calendar;
		if (calendar instanceof BX.Tasks.Calendar)
		{
			this.#calendar = calendar;
		}
		const defaultTime = this.#getDefaultTime();

		const input = options.input;
		if (input instanceof HTMLInputElement)
		{
			this.#input = input;
		}

		const matchWorkTime = options.matchWorkTime;
		this.#matchWorkTime = Type.isBoolean(matchWorkTime) ? matchWorkTime : false;

		const addZero = (unit: number) => `0${unit}`.slice(-2);
		const pickerOptions = {
			defaultTime: `${addZero(defaultTime.getHours())}:${addZero(defaultTime.getMinutes())}`,
			targetNode: this.#input,
			enableTime: true,
			cutZeroTime: false,
			events: {
				onSelect: this.#onSelect.bind(this),
				onHide: this.#onHide.bind(this),
			},
		};

		this.#datePicker = new DatePicker({ ...options, ...pickerOptions });
		Event.bind(this.#input, 'click', this.#datePicker.show.bind(this.#datePicker));
	}

	#getDefaultTime(): Date
	{
		const now = new Date();
		const workInterval = this.#calendar.getWorkIntervals(now).pop();

		return new Date(workInterval.endDate.toLocaleString('en-US', { timeZone: 'UTC' }));
	}

	#onSelect(event: BaseEvent): void
	{
		const { date } = event.getData();

		if (!this.#matchWorkTime)
		{
			this.#setInputValue(date);
			this.emit('onSelect');

			return;
		}

		const closestWorkDate = this.#calendar.getClosestWorkTime(date, true);

		if (date.getTime() !== closestWorkDate.getTime())
		{
			this.#datePicker.selectDate(new Date(closestWorkDate.toLocaleString('en-US', { timeZone: 'UTC' })));

			this.#datePicker.hide();

			this.emit('onMoveDateToWorkTime');
		}

		this.#setInputValue(closestWorkDate);
		this.emit('onSelect');
	}

	#onHide(): void
	{
		const date = this.getSelectedDate();

		const now = this.getToday();

		if (Type.isDate(date) && date < now)
		{
			this.emit('onLessCurrentDateSelect');
		}
	}

	#setInputValue(date: Date): void
	{
		const dateFormat = this.#getDateFormatByDate(date);

		this.#input.value = this.#datePicker.formatDate(date, dateFormat);
	}

	#getDateFormatByDate(date: Date): string
	{
		const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

		const now = new Date();

		let dateFormat = DateTimeFormat.getFormat('DAY_OF_WEEK_MONTH_FORMAT');
		if (Type.isDate(date) && now.getFullYear() !== date.getFullYear())
		{
			dateFormat = DateTimeFormat.getFormat('FULL_DATE_FORMAT');
		}

		return `${dateFormat}, ${timeFormat}`;
	}
}
