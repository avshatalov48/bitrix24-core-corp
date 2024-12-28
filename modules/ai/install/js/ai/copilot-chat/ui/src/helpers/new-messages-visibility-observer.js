import { Dom } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

export const NewMessagesVisibilityObserverEvents = Object.freeze({
	VIEW_NEW_MESSAGE: 'viewNewMessage',
});

export class NewMessagesVisibilityObserver extends EventEmitter
{
	#observer: IntersectionObserver | null = null;
	#root: HTMLElement = null;
	#observableElements: HTMLElement[] = [];

	constructor(options) {
		super(options);

		this.setEventNamespace('AI.CopilotChat.InterSectionManager');
	}

	init(): void
	{
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-io-without-polyfill
		this.#observer = new IntersectionObserver((entries: IntersectionObserverEntry[]) => {
			entries.forEach((entry) => {
				const isMessageVisible = entry.isIntersecting && entry.intersectionRatio > 0.5;

				if (isMessageVisible)
				{
					const messageElement = entry.target;

					this.emit(NewMessagesVisibilityObserverEvents.VIEW_NEW_MESSAGE, new BaseEvent({
						data: {
							id: Dom.attr(messageElement, 'data-id'),
						},
					}));

					this.#observer.unobserve(messageElement);
				}
			});
		}, {
			root: this.#root,
			threshold: this.#getThreshold(),
		});

		this.#observableElements.forEach((element) => {
			this.#observer.observe(element);
		});
	}

	#getThreshold(): number[]
	{
		const arrayWithZeros = Array.from({ length: 101 }).fill(0);

		return arrayWithZeros.map((zero, index) => index * 0.01);
	}

	observe(element: HTMLElement): void
	{
		if (!this.#root || !this.#observer)
		{
			this.#observableElements.push(element);
		}
		else
		{
			this.#observer.observe(element);
		}
	}

	unobserve(element: HTMLElement): void
	{
		this.#observer.unobserve(element);
	}

	setRoot(root: HTMLElement): void

	{
		this.#root = root;
	}
}
