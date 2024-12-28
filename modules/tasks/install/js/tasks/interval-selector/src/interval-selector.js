import { Event, Loc, Tag } from 'main.core';
import { MenuManager } from 'main.popup';
import { EventEmitter } from 'main.core.events';
import './style.css';

export type Interval = 'minutes' | 'hours' | 'days' | 'months';

type Params = {
	intervals: Interval[],
	defaultInterval: Interval,
	value: number,
}

const DEFAULT_INTERVAL = 'days';
const DEFAULT_INTERVALS = ['hours', 'days', 'months'];

export class IntervalSelector extends EventEmitter
{
	#params: Params;
	#layout: {
		intervalSelector: HTMLElement,
	};

	#intervals: Interval[];
	#currentInterval: Interval;

	constructor(params: Params = {})
	{
		super(params);
		this.setEventNamespace('BX.Tasks.IntervalSelector');

		this.#params = params;
		this.#layout = {};

		this.#intervals = params.intervals || DEFAULT_INTERVALS;
		this.#currentInterval = params.defaultInterval || DEFAULT_INTERVAL;

		if (this.#params.value)
		{
			this.setSuitableInterval(this.#params.value);
		}
	}

	render(): HTMLElement
	{
		this.#layout.intervalSelector = Tag.render`
			<div class="tasks-interval-selector">
				${this.#getIntervalPhrase(this.#currentInterval)}
			</div>
		`;

		Event.bind(this.#layout.intervalSelector, 'click', this.#showIntervalMenu.bind(this));

		return this.#layout.intervalSelector;
	}

	getInterval(): Interval
	{
		return this.#currentInterval;
	}

	getDuration(): number
	{
		return this.#getIntervalDuration(this.#currentInterval);
	}

	setSuitableInterval(value: number): void
	{
		const durations = this.#intervals.map((interval: Interval) => ({
			interval,
			value: value / this.#getIntervalDuration(interval),
		}));

		const mostSuitable = durations.reduce((acc, duration) => {
			if (duration.value % 1 === 0 && duration.value <= acc.value)
			{
				return {
					interval: duration.interval,
					value: duration.value,
				};
			}

			return acc;
		}, { value: Math.max(...durations.map((duration) => duration.value)) });

		this.setInterval(mostSuitable.interval);
	}

	setInterval(interval: Interval): void
	{
		this.#currentInterval = interval;
		if (this.#layout.intervalSelector)
		{
			this.#layout.intervalSelector.innerText = this.#getIntervalPhrase(interval);
		}
	}

	#showIntervalMenu(): void
	{
		let menu;

		const handleScroll = () => {
			const popup = menu.getPopupWindow();
			popup.adjustPosition();

			const popupRect = popup.bindElement.getBoundingClientRect();
			if (popupRect.top > window.innerHeight || popupRect.bottom < 0)
			{
				menu.close();
			}
		};

		menu = MenuManager.create({
			id: 'tasks-flow-create-planned-completion-time-interval-menu' + Date.now(),
			bindElement: this.#layout.intervalSelector,
			items: this.#intervals.map((interval: Interval) => ({
				id: interval,
				text: this.#getIntervalPhrase(interval),
				onclick: (e, item) => {
					this.setInterval(item.id);
					this.emit('intervalChanged', {'interval': item.id});

					menu.close();
				},
			})),
			events: {
				onShow: () => {
					const popup = menu.getPopupWindow();
					const popupWidth = popup.getPopupContainer().offsetWidth;
					const elementWidth = popup.bindElement.offsetWidth;

					popup.setOffset({ offsetLeft: elementWidth / 2 - popupWidth / 2 + 4, offsetTop: 5 });
					popup.adjustPosition();

					Event.bind(window, 'scroll', handleScroll, true);
				},
				onClose: () => {
					menu.destroy();

					Event.unbind(window, 'scroll', handleScroll, true);
				},
			},
		});

		menu.show();
	}

	#getIntervalPhrase(interval: Interval): string
	{
		return Loc.getMessage(`TASKS_INTERVAL_SELECTOR_${interval.toUpperCase()}`);
	}

	#getIntervalDuration(interval: Interval): number
	{
		const intervalDurations = {
			'minutes': 60,
			'hours': 60 * 60,
			'days': 60 * 60 * 24,
			'months': 60 * 60 * 24 * 30,
		};

		return intervalDurations[interval];
	}
}