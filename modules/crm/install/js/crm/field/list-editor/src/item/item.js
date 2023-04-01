import {EventEmitter} from 'main.core.events';
import {Cache, Tag, Text, Dom, Type} from 'main.core';
import 'ui.forms';
import '"ui.layout-form';
import type {ListEditorItemOptions} from '../types/list-editor-item-options';

import './css/style.css';

const {MemoryCache} = Cache;

export class Item extends EventEmitter
{
	#cache = new MemoryCache();

	constructor(options: ListEditorItemOptions)
	{
		super();
		this.setEventNamespace('BX.Crm.Field.ListEditor.Item');
		this.subscribeFromOptions(options.events);
		this.setOptions(options);

		this.onFormChange = this.onFormChange.bind(this);
	}

	setOptions(options: ListEditorItemOptions)
	{
		this.#cache.set('options', {...options});
	}

	getOptions(): ListEditorItemOptions
	{
		return this.#cache.get('options', {});
	}

	getCustomTitleLayout(): HTMLDivElement
	{
		return this.#cache.remember('customTitleLayout', () => {
			return this.getLayout().querySelector('.crm-field-list-editor-item-text-custom-title');
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			const {data, categoryCaption} = this.getOptions();
			const {sourceData} = this.getOptions();
			const label = data.label || sourceData.caption;

			const preparedCategoryCaption = (() => {
				if (Type.isStringFilled(categoryCaption))
				{
					return `&middot; ${Text.encode(categoryCaption)}`;
				}
				
				return '';
			})();

			return Tag.render`
				<div class="crm-field-list-editor-item" data-name="${Text.encode(sourceData?.name || '')}">
					<div class="crm-field-list-editor-item-header">
						<div class="crm-field-list-editor-item-drag-button"></div>
						<div class="crm-field-list-editor-item-text">
							<div class="crm-field-list-editor-item-text-source-title">
								<span class="crm-field-list-editor-item-text-source-title-inner">${Text.encode(sourceData?.caption || '')}</span>
								<span class="crm-field-list-editor-item-text-source-title-inner">${preparedCategoryCaption}</span>
							</div>
							<div class="crm-field-list-editor-item-text-custom-title">
								<div class="crm-field-list-editor-item-text-custom-title-inner">${Text.encode(label)}</div>
							</div>
						</div>
						<div class="crm-field-list-editor-item-actions">
							<div 
								class="crm-field-list-editor-item-button-edit"
								onclick="${this.onEditClick.bind(this)}"
							></div>
							<div 
								class="crm-field-list-editor-item-button-remove"
								onclick="${this.onRemoveClick.bind(this)}"
							></div>
						</div>
					</div>
					<div class="crm-field-list-editor-item-body">
						${this.getFormLayout()}
					</div>
				</div>
			`;
		});
	}

	onEditClick(event: MouseEvent)
	{
		event.preventDefault();

		if (!this.isOpened())
		{
			this.open();
		}
		else
		{
			this.close();
		}
	}

	onRemoveClick(event: MouseEvent)
	{
		event.preventDefault();

		this.emit('onRemove');
	}

	open()
	{
		Dom.addClass(this.getLayout(), 'crm-field-list-editor-item-opened');
	}

	isOpened(): boolean
	{
		return Dom.hasClass(this.getLayout(), 'crm-field-list-editor-item-opened');
	}

	close()
	{
		Dom.removeClass(this.getLayout(), 'crm-field-list-editor-item-opened');
	}

	createTextInput(options: {name: string, label: string, value: string}): HTMLDivElement
	{
		return Tag.render`
			<div class="ui-form-row crm-field-list-editor-item-form-text-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">${options.label}</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							type="text"
							name="${options.name}"
							value="${options.value}"
							oninput="${this.onFormChange}"
							class="ui-ctl-element">	
					</div>	
				</div>
			</div>
		`;
	}

	createCheckbox(options: {name: string, label: string, checked: boolean}): HTMLLabelElement
	{
		return Tag.render`
			<div class="ui-form-row crm-field-list-editor-item-form-checkbox-row">
				<div class="ui-form-content">
					<label class="ui-ctl ui-ctl-checkbox">
						<input 
							type="checkbox" 
							name="${options.name}"
							class="ui-ctl-element"
							onchange="${this.onFormChange}"
							${options.checked ? 'checked': ''}
						>
						<div class="ui-ctl-label-text">${options.label}</div>
					</label>	
				</div>
			</div>
		`;
	}

	getAllInputs(): Array<HTMLInputElement>
	{
		return [...this.getLayout().querySelectorAll('.ui-ctl-element')];
	}

	getValue(): {[key: string]: any}
	{
		return this.getAllInputs().reduce((acc, input) => {
			acc[input.name] = input.type === 'checkbox' ? input.checked : input.value;
			return acc;
		}, {...this.getOptions().data});
	}

	onFormChange()
	{
		const value = this.getValue();
		this.getCustomTitleLayout().textContent = value.caption || value.label;

		this.emit('onChange');
	}

	getFormControls(): Array<HTMLElement>
	{
		return this.#cache.remember('formControls', () => {
			const editableEntries = Object.entries(this.getOptions().editable);
			const {data} = this.getOptions();

			return editableEntries.map(([name, options]) => {
				if (options.type === 'string')
				{
					return this.createTextInput({
						name,
						label: options.label,
						value: data[name],
					});
				}

				return this.createCheckbox({
					name,
					label: options.label,
					checked: data[name],
				});
			});
		});
	}

	getFormLayout(): HTMLDivElement
	{
		return this.#cache.remember('formLayout', () => {
			return Tag.render`
				<div class="crm-field-list-editor-item-form">
					<div class="ui-form">
						${this.getFormControls()}
					</div>
				</div>
			`;
		});
	}
}