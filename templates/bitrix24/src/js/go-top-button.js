import { Dom, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { ButtonIcon, ButtonManager } from 'ui.buttons';

export class GoTopButton extends EventEmitter
{
	#lastScrollOffset = 0;
	#isReversed = false;
	#button: Button;

	constructor() {
		super();

		this.setEventNamespace('GoTopButton');
		this.#bindEvents();
	}

	isShown(): boolean
	{
		return Dom.hasClass(this.#getButtonWrapper(), '--show');
	}

	#show(): void
	{
		this.emit('show');
		Dom.addClass(this.#getButtonWrapper(), '--show');
	}

	#hide(): void
	{
		this.emit('hide');
		Dom.removeClass(this.#getButtonWrapper(), '--show');
	}

	#getButtonWrapper(): HTMLElement
	{
		return document.getElementById('goTopButtonWrapper');
	}

	#bindEvents(): void
	{
		Event.ready(() => {
			this.#handleScroll();

			Event.bind(window, 'scroll', () => {
				this.#handleScroll();
			});

			Event.bind(this.#getButtonWrapper(), 'click', () => {
				this.#handleButtonClick();
			});
		});
	}

	#handleScroll(): boolean
	{
		if (window.pageYOffset > document.documentElement.clientHeight)
		{
			this.#show();

			if (this.#isReversed)
			{
				this.#setReversed(false);
				this.#lastScrollOffset = 0;
			}
		}
		else if (this.#isReversed === false)
		{
			this.#hide();
		}
	}

	#handleButtonClick(): void
	{
		if (this.#isReversed)
		{
			this.#setReversed(false);

			window.scrollTo({
				top: this.#lastScrollOffset,
				behavior: 'instant',
			});

			this.#lastScrollOffset = 0;
		}
		else
		{
			this.#setReversed(true);
			this.#lastScrollOffset = window.pageYOffset;

			window.scrollTo({
				top: 0,
				behavior: 'instant',
			});
		}
	}

	#setReversed(flag: boolean = true): void
	{
		this.#isReversed = flag;

		if (this.#isReversed)
		{
			this.#getButton().setIcon(ButtonIcon.ANGLE_DOWN);
		}
		else
		{
			this.#getButton().setIcon(ButtonIcon.ANGLE_UP);
		}
	}

	#getButton(): Button
	{
		return this.#button || ButtonManager.createFromNode(document.getElementById('goTopButton'));
	}
}
