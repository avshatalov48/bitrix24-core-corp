import { Dom, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Icon, Main } from 'ui.icon-set.api.core';

import './style.css';

type DocumentCountersOption = {
	sizeLimit: Number,
}

export class DocumentCounters extends EventEmitter
{
	#sizeLimit: Number;
	#container: HTMLDivElement;
	#counterNode: HTMLSpanElement;

	constructor(options: DocumentCountersOption)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.DocumentCounters');
		this.#sizeLimit = Number(options.documentCountersLimit);
		this.#counterNode = Tag.render`<span class="sign-b2e-settings__document-counter-select">0</span>`;
		this.#container = Tag.render`
			<div class="sign-b2e-settings__document-counter">
				${this.#getIcon().render()}
				<div class="sign-b2e-settings__document-counter_limit-block">
					${this.#counterNode}
					${this.#getLimitContainer()}
				</div>
			</div>
		`;
	}

	getLayout(): HTMLElement
	{
		return this.#container;
	}

	#getIcon(): Icon
	{
		return new Icon({
			icon: Main.DOCUMENT,
			size: 18,
			color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-60'),
		});
	}

	#getLimitContainer(): HTMLElement
	{
		return Tag.render`<span class="sign-b2e-settings__document-counter-limit">/ ${this.#sizeLimit}</span>`;
	}

	getCount(): number
	{
		return Number(this.#counterNode.textContent);
	}

	update(size: number): void
	{
		this.#counterNode.textContent = size;

		if (size >= this.#sizeLimit)
		{
			this.emit('limitExceeded');
			Dom.addClass(this.#container, '--alert');
		}
		else
		{
			this.emit('limitNotExceeded');
			Dom.removeClass(this.#container, '--alert');
		}
	}
}
