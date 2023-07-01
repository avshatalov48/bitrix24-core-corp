import {EventEmitter, BaseEvent} from 'main.core.events';
import {Cache, Dom, Text, Loc, Tag, Type, Runtime, Event} from 'main.core';
import {FieldsPanel} from 'landing.ui.panel.fieldspanel';
import {Notification} from 'ui.notification';
import {Draggable} from 'ui.draganddrop.draggable';
import {Layout} from 'ui.sidepanel.layout';
import {SaveButton} from 'ui.buttons';
import {Loader} from 'main.loader';
import {Item} from './item/item';
import {Backend} from './backend/backend';
import 'landing.master';

import type {ListEditorOptions} from './types/list-editor-options';
import type {ListEditorItemOptions} from './types/list-editor-item-options';

import './css/style.css';

const {MemoryCache} = Cache;

export {
	Backend,
};

/**
 * @memberOf BX.Crm.Field
 */
export class ListEditor extends EventEmitter
{
	#cache = new MemoryCache();
	#loadPromise: Promise;

	static #defaultOptions = {
		setId: 0,
		autoSave: true,
		cacheable: true,
		fieldsPanelOptions: {},
		debouncingDelay: 500,
	};

	constructor(options: ListEditorOptions = {})
	{
		super();
		this.setEventNamespace('BX.Crm.Field.ListEditor');
		this.subscribeFromOptions(options.events);
		this.setTitle(options.title || '');
		this.onWindowResize = this.onWindowResize.bind(this);

		this.setOptions({
			...ListEditor.#defaultOptions,
			...options,
		});

		this.onDebounceChange = Runtime.debounce(
			this.onDebounceChange,
			this.getOptions().debouncingDelay,
			this,
		);

		this.draggable = new Draggable({
			container: this.getListContainer(),
			draggable: '.crm-field-list-editor-item',
			dragElement: '.crm-field-list-editor-item-drag-button',
			offset: {
				x: -800,
			},
			context: window.top,
		});

		this.draggable.subscribe('end', this.onSortEnd.bind(this));

		this.showLoader();

		this.#loadPromise = Promise
			.all([
				this.loadFieldsDictionary(),
				this.loadValue(),
			])
			.then(([fieldsDictionary, value]) => {
				if (Type.isPlainObject(fieldsDictionary))
				{
					this.setFieldsDictionary(fieldsDictionary);	
				}
				else
				{
					console.error('BX.Crm.Field.ListEditor: Invalid fields dictionary');
				}

				if (Type.isPlainObject(value))
				{
					this.setClientEntityTypeId(value.clientEntityTypeId);
					this.setEntityTypeId(value.entityTypeId);

					if (Type.isStringFilled(value.title) && !Type.isStringFilled(this.getTitle()))
					{
						this.setTitle(value.title);
					}

					if (Type.isArrayFilled(value.fields))
					{
						value.fields.forEach((itemData) => {
							this.addItem({
								sourceData: this.getFieldByName(itemData.name),
								data: itemData,
							});
						});
					}
				}
				else
				{
					console.error('BX.Crm.Field.ListEditor: Invalid value');
				}

				this.hideLoader();
			});
	}

	setData(data: {[key: string]: any})
	{
		this.#cache.set('data', {...data});
	}

	getData(): {[key: string]: any}
	{
		return this.#cache.get('data', {});
	}

	setTitle(title: string)
	{
		this.#cache.set('title', title);
	}

	getTitle(): string
	{
		return this.#cache.get('title', '');
	}

	setClientEntityTypeId(clientEntityTypeId)
	{
		this.#cache.set('clientEntityTypeId', clientEntityTypeId);
	}

	getClientEntityTypeId(): number
	{
		return this.#cache.get('clientEntityTypeId');
	}

	setEntityTypeId(entityTypeId)
	{
		this.#cache.set('entityTypeId', entityTypeId);
	}

	getEntityTypeId(): number
	{
		return this.#cache.get('entityTypeId');
	}

	getLoader(): Loader
	{
		return this.#cache.remember('loader', () => {
			return new Loader({
				target: this.getLayout(),
			});
		});
	}

	showLoader()
	{
		Dom.addClass(this.getLayout(), 'crm-field-list-editor-state-load');
		void this.getLoader().show();
	}

	hideLoader()
	{
		Dom.removeClass(this.getLayout(), 'crm-field-list-editor-state-load');
		void this.getLoader().hide();
	}

	setOptions(options: ListEditorOptions)
	{
		this.#cache.set('options', {...options});
	}

	getOptions(): ListEditorOptions
	{
		return this.#cache.get('options', {});
	}

	setFieldsDictionary(fields: Array<any>)
	{
		this.#cache.set('fieldsDictionary', fields);
	}

	getFieldsDictionary(): Array<any>
	{
		return this.#cache.get('fieldsDictionary', []);
	}

	getListContainer(): HTMLDivElement
	{
		return this.#cache.remember('listContainer', () => {
			return Tag.render`
				<div class="crm-field-list-editor-list"></div>
			`;
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('layout', () => {
			return Tag.render`
				<div class="crm-field-list-editor">
					${this.getListContainer()}
					<div class="crm-field-list-editor-footer">
						<span 
							class="ui-link ui-link-dashed"
							onclick="${this.onAddFieldClick.bind(this)}"
						>
							${Loc.getMessage('CRM_FIELD_LIST_EDITOR_ADD_FIELD_BUTTON_LABEL')}
						</span>
					</div>
				</div>
			`;
		});
	}

	renderTo(target: HTMLElement)
	{
		if (!Type.isDomNode(target))
		{
			console.error('target is not a DOM element');
		}

		Dom.append(this.getLayout(), target);
	}

	loadFieldsDictionary(): Promise<any>
	{
		const fieldsPanelOptions = {
			...this.getOptions().fieldsPanelOptions,
			disabledFields: this.getValue().map((field) => {
				return field.name;
			}),
		};

		return Backend.getInstance().getFieldsList(fieldsPanelOptions?.presetId || null);
	}

	loadValue(): Promise<any>
	{
		return Backend
			.getInstance()
			.getFieldsSet(this.getOptions().setId)
			.then((result) => {
				return result.options;
			});
	}

	getItems(): Array<Item>
	{
		return this.#cache.remember('items', []);
	}

	setItems(items: Array<Item>)
	{
		this.#cache.set('items', items);
	}

	addItem(options: ListEditorItemOptions)
	{
		const items = this.getItems();

		const hasItem = items.some((item) => {
			return item.getOptions().data.name === options.data.name;
		});

		if (!hasItem)
		{
			const item = new Item({
				...options,
				categoryCaption: this.getCategoryCaption(options.data.name),
				editable: this.getOptions().editable,
				events: {
					onChange: () => {
						this.onChange();
					},
					onRemove: this.onRemoveItemClick.bind(this),
				},
			});
			items.push(item);
			Dom.append(item.getLayout(), this.getListContainer());
		}
	}

	onRemoveItemClick(event: BaseEvent)
	{
		const target: Item = event.getTarget();

		Dom.remove(target.getLayout());

		this.setItems(
			this.getItems().filter((item) => {
				return item !== target;
			}),
		);

		this.onChange();
	}

	getFieldByName(name: string): ?{[key: string]: any}
	{
		const fieldsDictionary = this.getFieldsDictionary();

		return Object.values(fieldsDictionary).reduce((acc, category) => {
			if (!acc)
			{
				return category.FIELDS.find((field) => {
					return field.name === name;
				});
			}

			return acc;
		}, null);
	}

	getCategoryCaption(fieldName: string): string
	{
		const fieldsDictionary = this.getFieldsDictionary();

		return Object.values(fieldsDictionary).reduce((acc, category) => {
			if (!acc)
			{
				const hasField = category.FIELDS.some((field) => {
					return field.name === fieldName;
				});

				if (hasField)
				{
					return category.CAPTION;
				}
			}

			return acc;
		}, '');
	}

	showFieldsPanel(panelOptions): Promise<Array<string>>
	{
		const fieldsPanel = FieldsPanel.getInstance();
		Dom.append(fieldsPanel.layout, window.top.document.body);
		return fieldsPanel.show(panelOptions);
	}

	onAddFieldClick(event: MouseEvent)
	{
		event.preventDefault();

		const fieldsPanelOptions = {
			...this.getOptions().fieldsPanelOptions,
			disabledFields: this.getValue().map((field) => {
				return field.name;
			}),
		};

		this.showFieldsPanel(fieldsPanelOptions)
			.then((result) => {
				this.setFieldsDictionary(
					FieldsPanel.getInstance().getCrmFields(),
				);

				return result;
			})
			.then((result) => {
				result.forEach((fieldName) => {
					const fieldData = this.getFieldByName(fieldName);
					if (!Type.isString(fieldData.label) && Type.isString(fieldData.caption))
					{
						fieldData.label = fieldData.caption;
					}
					this.addItem({
						sourceData: fieldData,
						data: fieldData,
					});

					this.onChange();
				});
			});
	}

	onChange()
	{
		this.emit('onChange');
		this.onDebounceChange();
	}

	onDebounceChange()
	{
		if (this.getOptions().autoSave)
		{
			void this.save();
		}

		this.emit('onDebounceChange');
	}

	save(): Promise<any>
	{
		const fieldsPanelOptions = {
			...this.getOptions().fieldsPanelOptions,
			disabledFields: this.getValue().map((field) => {
				return field.name;
			}),
		};

		return Backend
			.getInstance()
			.saveFieldsSet({
				id: this.getOptions().setId,
				presetId: fieldsPanelOptions?.presetId || null,
				entityTypeId: this.getEntityTypeId(),
				clientEntityTypeId: this.getClientEntityTypeId(),
				...this.getData(),
				fields: this.getValue(),
			})
			.then(() => {
				this.emit('onSave');
			});
	}

	getValue(): Array<{[key: string]: any}>
	{
		return this.getItems().map((item) => {
			return item.getValue();
		});
	}

	#adjustSliderDragAndDropOffsets()
	{
		const sliderLayout = this.getLayout().closest('.ui-sidepanel-layout');
		if (Type.isDomNode(sliderLayout))
		{
			const offsetLeft = -(sliderLayout.getBoundingClientRect().left);

			this.draggable.setOptions({
				...this.draggable.getOptions(),
				offset: {
					x: offsetLeft,
				},
			});
		}
	}

	onWindowResize()
	{
		this.#adjustSliderDragAndDropOffsets();
	}

	showSlider()
	{

		const buttons = [];
		if (!this.getOptions().autoSave)
		{
			buttons.push(
				new SaveButton({
					onclick: (button) => {
						button.setWaiting(true);
						this.save().then(() => {
							button.setWaiting(false);
							BX.SidePanel.Instance.close();
						}).catch((data) => {
							top.BX.UI.Notification.Center.notify({
								content: data.errors.map((item) => Text.encode(item.message)).join('\n'),
								autoHide: false
							});
							button.setWaiting(false);
						});
					}
				}),
			);
		}

		BX.SidePanel.Instance.open('crm:field-list-editor', {
			width: 600,
			cacheable: this.getOptions().cacheable,
			contentCallback: () => {
				return this.#loadPromise
					.then(() => Layout.createContent({
						extensions: ['crm.field.list-editor'],
						title: this.getTitle(),
						content: () => this.getLayout(),
						buttons: ({cancelButton}) => {
							return [
								...buttons,
								cancelButton,
							];
						},
					}))
					.catch(({errors}) => Layout.createContent({
						extensions: ['ui.sidepanel-content'],
						design: {section: false},
						content: () => {
							const title = Loc.getMessage('CRM_FIELD_LIST_EDITOR_ERROR_IN_LOAD');
							const msg = ((errors||[])[0]||{}).message || 'Unknown error';
							return Tag.render`
								<div class="ui-slider-no-access">
									<div class="ui-slider-no-access-inner">
										<div class="ui-slider-no-access-title">${Text.encode(title)}</div>
										<div class="ui-slider-no-access-subtitle">${Text.encode(msg)}</div>
										<div class="ui-slider-no-access-img">
											<div class="ui-slider-no-access-img-inner"></div>
										</div>
									</div>
								</div>
							`;
						},
						buttons: ({closeButton}) => {
							return [
								closeButton,
							];
						},
					}))
				;
			},
			events: {
				onOpenComplete: () => {
					const timeoutId = setTimeout(() => {
						clearTimeout(timeoutId);
						this.#adjustSliderDragAndDropOffsets();
					}, 500);
					Event.bind(window, 'resize', this.onWindowResize);
				},
				onClose: () => {
					Event.unbind(window, 'resize', this.onWindowResize);
				},
			}
		});
	}

	onSortEnd()
	{
		const listNodes = [...this.getListContainer().children];

		this.getItems().sort((a, b) => {
			const aIndex = listNodes.findIndex((node) => {
				return a.getLayout() === node;
			});

			const bIndex = listNodes.findIndex((node) => {
				return b.getLayout() === node;
			});

			return aIndex - bIndex;
		});

		this.onChange();
	}
}