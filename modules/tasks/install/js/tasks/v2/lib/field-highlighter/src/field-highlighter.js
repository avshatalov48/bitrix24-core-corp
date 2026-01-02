import { Dom, Event } from 'main.core';
import './field-highlighter.css';

const fieldAttribute = 'data-task-field-id';
const chipAttribute = 'data-task-chip-id';
const fieldSelector = '[data-field-container]';

class FieldHighlighter
{
	#container: HTMLElement = document.body;
	#highlightTimeouts: { [fieldId: string]: number } = {};

	setContainer(container: HTMLElement): FieldHighlighter
	{
		this.#container = container;

		return this;
	}

	addHighlight(fieldId: string): FieldHighlighter
	{
		this.highlightContainer(this.getFieldContainer(fieldId));

		return this;
	}

	addChipHighlight(fieldId: string): FieldHighlighter
	{
		this.highlightContainer(this.getChipContainer(fieldId));

		return this;
	}

	highlightContainer(container: ?HTMLElement): void
	{
		if (!container)
		{
			return;
		}

		Dom.addClass(container, 'tasks-field-highlight');

		const removeHighlight = () => {
			Dom.removeClass(container, 'tasks-field-highlight');
			Event.unbind(window, 'click', removeHighlight);
			Event.unbind(window, 'keydown', removeHighlight);
		};
		Event.bind(window, 'click', removeHighlight);
		Event.bind(window, 'keydown', removeHighlight);
	}

	async highlight(fieldId: string): Promise<void>
	{
		await this.#nextTick();

		const fieldContainer = this.getFieldContainer(fieldId);

		if (!fieldContainer)
		{
			return;
		}

		this.#stopAnimation(fieldContainer);
		setTimeout(() => this.#startAnimation(fieldContainer));

		clearTimeout(this.#highlightTimeouts[fieldId]);
		this.#highlightTimeouts[fieldId] = setTimeout(() => this.#stopAnimation(fieldContainer), 1500);

		this.#scrollToField(fieldId);
	}

	#startAnimation(fieldContainer: HTMLElement): void
	{
		Dom.addClass(fieldContainer, ['tasks-field-highlight', '--animate']);
	}

	#stopAnimation(fieldContainer: HTMLElement): void
	{
		Dom.removeClass(fieldContainer, ['tasks-field-highlight', '--animate']);
	}

	#scrollToField(fieldId: string): void
	{
		const fieldContainer = this.getFieldContainer(fieldId);

		if (!fieldContainer)
		{
			return;
		}

		Dom.style(fieldContainer, 'scrollMarginTop', '100px');

		fieldContainer.scrollIntoView({
			block: 'start',
			behavior: 'smooth',
		});

		setTimeout(() => {
			Dom.style(fieldContainer, 'scrollMarginTop', null);
		}, 1000);
	}

	getFieldContainer(fieldId: string): ?HTMLElement
	{
		return this.#container.querySelector(`[${fieldAttribute}="${fieldId}"]`)?.closest(fieldSelector);
	}

	getChipContainer(fieldId: string): ?HTMLElement
	{
		return this.#container.querySelector(`[${chipAttribute}="${fieldId}"]`);
	}

	#nextTick(): Promise<void>
	{
		return new Promise((resolve) => {
			setTimeout(resolve, 0);
		});
	}
}

export const fieldHighlighter = new FieldHighlighter();
