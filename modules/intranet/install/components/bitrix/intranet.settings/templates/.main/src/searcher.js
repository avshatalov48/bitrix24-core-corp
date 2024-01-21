import { Event, Runtime } from 'main.core';
import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';

export class Searcher extends EventEmitter
{
	#node: HTMLElement;
	#fastSearchValue: string = '';
	#fastSearchDelay: 800;
	#searchValue: string = '';

	constructor(params)
	{
		super(params);
		this.setEventNamespace('BX.Intranet.Settings:Searcher');

		this.#node = params.node;
		Event.bind(this.#node, 'input', Runtime.debounce(this.#onInput, this.#fastSearchDelay, this));

		if (document.activeElement === this.#node)
		{
			this.#fastCheck();
		}
	}

	#onInput()
	{
		this.#fastCheck();
	}

	#fastCheck()
	{
		const currentValue = String(this.#node.value).trim();
		if (this.#fastSearchValue !== currentValue)
		{
			const previousValue = this.#fastSearchValue;

			let eventName;
			if (currentValue.length > 1)
			{
				this.#fastSearchValue = currentValue;
				eventName = 'fastSearch';
			}
			else
			{
				this.#fastSearchValue = '';
				eventName = 'clearSearch';
			}
			this.emit(eventName, {
				previous: previousValue,
				current: this.#fastSearchValue
			});
			this.#search();
		}
	}

	#search()
	{
		if (this.#searchValue !== this.#fastSearchValue
			&& Type.isStringFilled(this.#fastSearchValue)
			&& this.#fastSearchValue.length > 1
		)
		{
			const previousValue = this.#searchValue;
			this.#searchValue = this.#fastSearchValue;

			this.emit('search', {
				previous: previousValue,
				current: this.#searchValue
			});
		}
	}

	getValue(): String
	{
		return this.#node.value;
	}
}
