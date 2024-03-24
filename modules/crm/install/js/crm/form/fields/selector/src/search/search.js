import {EventEmitter, BaseEvent} from 'main.core.events';
import {Cache, Tag, Runtime, Text, Type, Loc} from 'main.core';

import './css/style.css';

type SearchOptions = {
	initialValue?: string,
	events?: {
		onChange?: (event: BaseEvent) => void,
		onDebouncedChange?: (event: BaseEvent) => void,
	},
};

export default class Search extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: SearchOptions = {})
	{
		super();
		this.setEventNamespace('BX.Crm.Form.Fields.Selector.Search');
		this.subscribeFromOptions(options.events);
		this.#setOptions(options);
	}

	#setOptions(options: SearchOptions)
	{
		this.#cache.set('options', {...options});
	}

	#getOptions(): SearchOptions
	{
		return this.#cache.get('options', {});
	}

	#onInput()
	{
		this.emit('onChange', {value: this.#getInput().value});
		this.#getDebounceWrapper()();
	}

	#getDebounceWrapper(): () => void
	{
		return this.#cache.remember('debounceWrapper', () => {
			return Runtime.debounce(
				() => {
					this.emit('onDebouncedChange', {value: this.#getInput().value});
				},
				50,
			)
		});
	}

	#getInput(): HTMLInputElement
	{
		return this.#cache.remember('input', () => {
			const initialValue = (() => {
				if (Type.isStringFilled(this.#getOptions().initialValue))
				{
					return this.#getOptions().initialValue;
				}

				return '';
			})();

			return Tag.render`
				<input 
					type="text" 
					class="ui-ctl-element" 
					oninput="${this.#onInput.bind(this)}"
					value="${Text.encode(initialValue)}"
					placeholder="${Loc.getMessage('CRM_FORM_FIELDS_SELECTOR_SEARCH_PLACEHOLDER')}"
				>
			`;
		});
	}

	getValue(): string
	{
		return this.#getInput().value;
	}

	#onClearClick(event: MouseEvent)
	{
		event.preventDefault();
		this.#getInput().value = '';
		this.#onInput();
	}

	setValue(value: string)
	{
		this.#getInput().value = value;
		this.#onInput();
	}

	#getClearButton(): HTMLButtonElement
	{
		return this.#cache.remember('clearButton', () => {
			return Tag.render`
				<button 
					class="ui-ctl-after ui-ctl-icon-clear" 
					onclick="${this.#onClearClick.bind(this)}"
				></button>
			`;
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			return Tag.render`
				<div class="crm-form-fields-selector-search">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100 ui-ctl-before-icon ui-ctl-after-icon">
						<div class="ui-ctl-before ui-ctl-icon-search"></div>
						${this.#getClearButton()}
						${this.#getInput()}
					</div>
				</div>
			`;
		});
	}
}