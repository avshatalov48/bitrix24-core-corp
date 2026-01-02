import { Event, Dom } from 'main.core';
import type { GoTopButton } from './go-top-button';

export type RightBarOptions = {
	goTopButton: GoTopButton;
};

export class RightBar
{
	#isTransparentMode: boolean = false;
	#isScrollMode: boolean = false;
	#scrollModeThreshold: number = window.innerHeight;
	#goTopButton: GoTopButton;

	constructor(options: RightBarOptions)
	{
		const redraw = this.redraw.bind(this);
		Event.bind(window, 'scroll', redraw, { passive: true });
		Event.bind(window, 'resize', redraw);
		this.#scrollModeThreshold = window.innerHeight;
		this.#goTopButton = options.goTopButton;

		this.#goTopButton.subscribe('show', () => {
			Dom.addClass(this.getContainer(), '--show-scroll-btn');
		});

		this.#goTopButton.subscribe('hide', () => {
			Dom.removeClass(this.getContainer(), '--show-scroll-btn');
		});

		Event.ready(() => {
			this.redraw();
		});
	}

	redraw(): void
	{
		const rightBar = this.getContainer();

		this.#scrollModeThreshold = window.innerHeight;

		if (window.pageYOffset > this.#scrollModeThreshold)
		{
			if (!this.#isScrollMode)
			{
				Dom.addClass(rightBar, '--scroll-mode');
				this.#isScrollMode = true;
			}
		}
		else if (this.#isScrollMode)
		{
			Dom.removeClass(rightBar, '--scroll-mode');
			this.#isScrollMode = false;
		}

		if (!rightBar || rightBar.dataset.lockRedraw === 'true')
		{
			return;
		}

		const scrollWidth = document.documentElement.scrollWidth - document.documentElement.clientWidth;
		if (scrollWidth > 0)
		{
			if (!this.#isTransparentMode)
			{
				Dom.addClass(rightBar, '--transparent');
				this.#isTransparentMode = true;
			}
		}
		else if (this.#isTransparentMode)
		{
			Dom.removeClass(rightBar, '--transparent');
			this.#isTransparentMode = false;
		}
	}

	getContainer(): HTMLElement
	{
		return document.getElementById('right-bar');
	}
}
