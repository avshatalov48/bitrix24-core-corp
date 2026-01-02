import { Dom, Event } from 'main.core';

import './highlighter.css';

export class Highlighter
{
	static HIGHLIGHT_CLASS = 'element-highlight';
	static ANIMATE_CLASS = '--animate';
	static ANIMATION_DURATION = 1500;

	#activeHighlights = new WeakMap();

	async highlight(element: HTMLElement): Promise<void>
	{
		await this.#nextTick();

		this.#cleanup(element);

		this.#activeHighlights.set(element, {
			animationStart: null,
			timeoutId: null,
			handler: null,
		});

		this.#startAnimation(element);

		const handler = () => this.#cleanup(element);

		Event.bind(window, 'click', handler);
		Event.bind(window, 'keydown', handler);

		const state = this.#activeHighlights.get(element);
		state.handler = handler;
		state.timeoutId = setTimeout(
			() => this.#cleanup(element),
			Highlighter.ANIMATION_DURATION,
		);
	}

	#startAnimation(element: HTMLElement): void
	{
		Dom.addClass(element, [Highlighter.HIGHLIGHT_CLASS, Highlighter.ANIMATE_CLASS]);

		const state = this.#activeHighlights.get(element);
		if (state)
		{
			state.animationStart = Date.now();
		}
	}

	#cleanup(element: HTMLElement): void
	{
		const state = this.#activeHighlights.get(element);
		if (!state)
		{
			return;
		}

		Dom.removeClass(element, [Highlighter.HIGHLIGHT_CLASS, Highlighter.ANIMATE_CLASS]);

		if (state.timeoutId)
		{
			clearTimeout(state.timeoutId);
		}

		if (state.handler)
		{
			Event.unbind(window, 'click', state.handler);
			Event.unbind(window, 'keydown', state.handler);
		}

		this.#activeHighlights.delete(element);
	}

	#nextTick(): Promise<void>
	{
		return new Promise((resolve) => {
			// eslint-disable-next-line no-promise-executor-return
			return setTimeout(resolve, 0);
		});
	}
}
