import { Tag, Event } from 'main.core';
import { IntervalSelector } from 'tasks.interval-selector';

function removeNonNumeric(value: string): string
{
	return value.replaceAll(/\D/g, '');
}

function clamp(value: number, min: number, max: number): number
{
	let clampedValue = Math.max(value, min);
	clampedValue = Math.min(clampedValue, max);

	return clampedValue;
}

const MAX_INT = (2 ** 32) / 2 - 1;
const SECONDS_IN_MONTH = 60 * 60 * 24 * 31;

export class DefaultDeadlineSelector
{
	#intervalSelector: IntervalSelector;
	#input: HTMLInputElement;

	constructor()
	{
		this.#input = Tag.render`
			<input class="ui-ctl-element" placeholder="0" data-id="tasks-deadline-notification-default-deadline">
		`;
		Event.bind(this.#input, 'input', this.#onInput.bind(this));

		this.#intervalSelector = new IntervalSelector({
			showDropdownIcon: true,
		});
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div hidden>
				<div class="tasks-deadline-notification-form__content_default-deadline-container">
					<div class="ui-ctl ui-ctl-w25">
						${this.#input}
					</div>
					<div
						class="tasks-deadline-notification-form__content_default-deadline-container_interval-selector"
						data-id="tasks-deadline-notification-interval-selector"
					>
						${this.#intervalSelector.render()}
					</div>
				</div>
			</div>
		`;
	}

	isExactDeadlineTime(): boolean
	{
		return ['minutes', 'hours'].includes(this.#intervalSelector.getInterval());
	}

	getDeadlineInSeconds(): number
	{
		return Number(this.#input.value) * this.#intervalSelector.getDuration();
	}

	focus(): void
	{
		this.#input.focus();
	}

	clear(): void
	{
		this.#input.value = '';
	}

	select(deadline: number): void
	{
		this.#input.value = deadline;
	}

	#onInput(): void
	{
		let normalizedValue = removeNonNumeric(this.#input.value);
		normalizedValue = parseInt(normalizedValue, 10);

		const minValue = 1;
		const maxValue = Math.floor(MAX_INT / SECONDS_IN_MONTH);

		normalizedValue = clamp(normalizedValue, minValue, maxValue);

		this.#input.value = normalizedValue || '';
	}
}
