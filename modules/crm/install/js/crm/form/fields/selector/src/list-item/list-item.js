import {EventEmitter, BaseEvent} from 'main.core.events';
import {Cache, Tag, Dom, Text, Type} from 'main.core';

import {type Field} from '../types/field';

import './css/style.css';

type ListItemOptions = {
	field: Field,
	selected?: boolean,
	targetContainer?: HTMLElement,
	events?: {
		onChange?: (event: BaseEvent) => void,
	},
	type: $Values<ListItem.Type>,
};

export default class ListItem extends EventEmitter
{
	static Type = {
		CHECKBOX: 'checkbox',
		RADIO: 'radio',
	};

	#cache = new Cache.MemoryCache();

	constructor(options: ListItemOptions)
	{
		super();
		this.setEventNamespace('BX.Crm.Form.Field.Selector.ListItem');
		this.subscribeFromOptions(options.events);
		this.#setOptions(options);

		const {targetContainer} = options;
		if (Type.isDomNode(targetContainer))
		{
			this.renderTo(targetContainer);
		}
	}

	#setOptions(options: ListItemOptions)
	{
		this.#cache.set('options', {type: ListItem.Type.CHECKBOX, ...options});
	}

	#getOptions(): ListItemOptions
	{
		return this.#cache.get('options', {});
	}

	getField(): Field
	{
		return this.#getOptions().field;
	}

	#onChange()
	{
		this.emit('onChange');
	}

	#getCheckbox(): HTMLInputElement
	{
		return this.#cache.remember('checkbox', () => {
			return Tag.render`
				<input 
					type="${Text.encode(this.#getOptions().type)}" 
					class="ui-ctl-element"
					onchange="${this.#onChange.bind(this)}"
					name="CRM_FIELDS_SELECTOR_ITEM"
					${this.#getOptions().selected ? 'checked' : ''}
				>
			`;
		});
	}

	isSelected(): boolean
	{
		return this.#getCheckbox().checked;
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			return Tag.render`
				<div class="crm-form-fields-selector-field">
					<label class="ui-ctl ui-ctl-checkbox crm-form-fields-selector-field-checkbox">
						${this.#getCheckbox()}
						<div class="ui-ctl-label-text">${Text.encode(this.#getOptions().field.caption)}</div>
					</label>
				</div>
			`;
		});
	}

	renderTo(targetContainer: HTMLElement)
	{
		if (Type.isDomNode(targetContainer))
		{
			Dom.append(this.getLayout(), targetContainer);
		}
	}
}