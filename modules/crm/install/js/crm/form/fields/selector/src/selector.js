import {EventEmitter, BaseEvent} from 'main.core.events';
import {Cache, Type, Dom, Reflection, Runtime, Tag, Loc, Text} from 'main.core';
import {Layout} from 'ui.sidepanel.layout';
import {Factory} from 'ui.userfieldfactory';
import {Button} from 'ui.buttons';

import Backend from './backend/backend';
import Search from './search/search';
import ListItem from './list-item/list-item';

import type {SelectorOptions, SelectorFilter, FieldsFactoryFilter} from './types/selector-options';
import type {FieldsList} from './types/fields-list';
import type {Field} from './types/field';

import './css/style.css';

/**
 * @memberOf BX.Crm.Form.Fields
 */
export class Selector extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	static #defaultFilter = {
		'-categories': [
			'CATALOG',
			'ACTIVITY',
			'INVOICE',
		],
		'-fields': [
			{name: 'CONTACT_ORIGIN_VERSION'},
			{name: 'CONTACT_LINK'},
		],
	};

	static #defaultFieldsFactoryFilter = {
		'-types': ['employee', 'datetime'],
	};

	constructor(options: SelectorOptions = {})
	{
		super();
		this.setEventNamespace('BX.Crm.Form.Fields.Selector');
		this.subscribeFromOptions(options.events);
		this.#setOptions(options);
	}

	#setOptions(options: SelectorOptions)
	{
		this.#cache.set(
			'options',
			{
				filter: {},
				multiple: true,
				...options,
			},
		);
	}

	#getOptions(): SelectorOptions
	{
		return Runtime.clone(this.#cache.get('options', {filter: {}}));
	}

	#getBackend(): Backend
	{
		return this.#cache.remember('backend', () => {
			return new Backend({
				events: {
					onError: this.#onBackendError.bind(this),
				},
			});
		});
	}

	#setFieldsList(fieldsList: FieldsList)
	{
		this.#cache.set('fieldsList', {...fieldsList});
	}

	#applyCategoriesFilter(fieldsList: FieldsList, filter: SelectorFilter): FieldsList
	{
		const fieldsEntries = Object.entries(fieldsList);

		return fieldsEntries.reduce((acc, [categoryId, category]) => {
			if (
				(
					!Type.isArrayFilled(filter['+categories'])
					|| filter['+categories'].includes(categoryId)
				)
				&& (
					!Type.isArrayFilled(filter['-categories'])
					|| !filter['-categories'].includes(categoryId)
				)
			)
			{
				acc[categoryId] = category;
			}

			return acc;
		}, {});
	}

	#applyFieldsFilter(fieldsList: FieldsList, filter: SelectorFilter): FieldsList
	{
		const fieldsEntries = Object.entries(fieldsList);

		return fieldsEntries.reduce((acc, [categoryId, category]) => {
			const filteredFields = category.FIELDS.filter((field) => {
				const allowed = (
					!Type.isArrayFilled(filter['+fields'])
					|| filter['+fields'].some((condition) => {
						if (Type.isStringFilled(condition))
						{
							return field.type === condition;
						}

						if (Type.isFunction(condition))
						{
							return condition(field);
						}

						if (Type.isPlainObject(condition))
						{
							return Object.entries(condition).every(([key, value]) => {
								return field[key] === value;
							});
						}

						return false;
					})
				);

				const disallowed = (
					Type.isArrayFilled(filter['-fields'])
					&& filter['-fields'].some((condition) => {
						if (Type.isStringFilled(condition))
						{
							return field.type === condition;
						}

						if (Type.isFunction(condition))
						{
							return condition(field);
						}

						if (Type.isPlainObject(condition))
						{
							return Object.entries(condition).every(([key, value]) => {
								return field[key] === value;
							});
						}

						return false;
					})
				);

				return allowed && !disallowed;
			});

			if (Type.isArrayFilled(filteredFields))
			{
				acc[categoryId] = {
					...category,
					FIELDS: filteredFields,
				};
			}

			return acc;
		}, {});
	}

	#applySearchFilter(fieldsList: FieldsList, query): FieldsList
	{
		const fieldsEntries = Object.entries(fieldsList);
		if (Type.isStringFilled(query))
		{
			const preparedQuery = String(query).toLowerCase();
			return fieldsEntries.reduce((acc, [categoryId, category]) => {
				const filteredFields = category.FIELDS.filter((field) => {
					return (
						Type.isStringFilled(field.caption)
						&& String(field.caption).toLowerCase().includes(preparedQuery)
					);
				});

				if (Type.isArrayFilled(filteredFields))
				{
					acc[categoryId] = {
						...category,
						FIELDS: filteredFields,
					};
				}

				return acc;
			}, {});
		}

		return fieldsList;
	}

	getFieldsList(): FieldsList
	{
		const fieldsList = this.#cache.get('fieldsList', {});

		const filter = this.#getFilter();
		if (Type.isPlainObject(filter))
		{
			const query = this.#getSearch().getValue();
			return this.#applySearchFilter(
				this.#applyFieldsFilter(
					this.#applyCategoriesFilter(fieldsList, filter),
					filter,
				),
				query,
			);
		}

		if (Type.isFunction(filter))
		{
			const defaultFilter = Runtime.clone(Selector.#defaultFilter);
			if (!this.#isLeadEnabled())
			{
				defaultFilter['-categories'].push('LEAD');
			}

			const prefilteredFieldsList = this.#applyFieldsFilter(
				this.#applyCategoriesFilter(fieldsList, defaultFilter),
				filter,
			);

			return filter(Runtime.clone(prefilteredFieldsList));
		}

		return fieldsList;
	}

	#load(): Promise<any>
	{
		const {controllerOptions = {}} = this.#getOptions();
		return this.#getBackend()
			.getData({options: controllerOptions})
			.then(({data}) => {
				this.#setFieldsList(data.fields);
				this.#setIsLeadEnabled(data.options.isLeadEnabled);
				this.#setIsAllowedCreateField(data.options.permissions.userField.add);
			});
	}

	#setIsLeadEnabled(value: boolean)
	{
		this.#cache.set('isLeadEnabled', value);
	}

	#isLeadEnabled(): boolean
	{
		return this.#cache.get('isLeadEnabled');
	}

	#setIsAllowedCreateField(value: boolean)
	{
		this.#cache.set('isAllowedCreateField', value);
	}

	#isAllowedCreateField(): boolean
	{
		return this.#cache.get('isAllowedCreateField', false);
	}

	#getSidebarItems(): {[key: string]: any}
	{
		return Object
			.entries(this.getFieldsList())
			.map(([categoryId, category]) => {
				return {
					label: category.CAPTION,
					id: categoryId,
					onclick: this.#onSidebarItemClick.bind(this, categoryId),
				};
			});
	}

	#getFilter(): SelectorFilter
	{
		const customFilter = this.#getOptions().filter;
		if (Type.isPlainObject(customFilter))
		{
			const defaultFilter = Selector.#defaultFilter;

			if (Type.isArray(customFilter['-categories']))
			{
				customFilter['-categories'] = [
					...customFilter['-categories'],
					...defaultFilter['-categories'],
				];
			}
			else
			{
				customFilter['-categories'] = [
					...defaultFilter['-categories'],
				];
			}

			if (!this.#isLeadEnabled())
			{
				customFilter['-categories'].push('LEAD');
			}

			if (Type.isArray(customFilter['-fields']))
			{
				customFilter['-fields'] = [
					...customFilter['-fields'],
					...defaultFilter['-fields'],
				];
			}
			else
			{
				customFilter['-fields'] = [
					...defaultFilter['-fields'],
				];
			}
		}

		return customFilter;
	}

	#cleanFieldsList()
	{
		Dom.clean(this.#getFieldsListLayout());
	}

	#getSelectedFields(): Array<Field>
	{
		return this.#cache.get('selectedFields', []);
	}

	#addSelectedField(field: Field)
	{
		const selectedFields = this.#getSelectedFields();
		const hasField = selectedFields.some((currentField) => {
			return currentField.name === field.name;
		});

		if (!hasField)
		{
			selectedFields.push(field);
			this.#setSelectedFields(selectedFields);
		}
	}

	#removeSelectedField(field: Field)
	{
		const selectedFields = this.#getSelectedFields().filter((currentField) => {
			return currentField.name !== field.name;
		});

		this.#setSelectedFields(selectedFields);
	}

	#setSelectedFields(fields: Array<Field>)
	{
		this.#cache.set('selectedFields', fields);
	}

	#isMultiple(): boolean
	{
		return this.#getOptions().multiple;
	}

	#renderCategoryFields(categoryId: string)
	{
		this.#cleanFieldsList();

		const fields = this.getFieldsList()[categoryId].FIELDS;
		if (Type.isArrayFilled(fields))
		{
			fields.forEach((field) => {
				void new ListItem({
					field,
					targetContainer: this.#getFieldsListLayout(),
					events: {
						onChange: this.#onListItemChange.bind(this),
					},
					selected: this.#getSelectedFields().some((selectedField) => {
						return selectedField.name === field.name;
					}),
					type: this.#isMultiple() ? ListItem.Type.CHECKBOX : ListItem.Type.RADIO,
					disabled: this.#isFieldDisabled(field),
				});
			});
		}
	}

	#getDisabledFields(): SelectorOptions['disabledFields'] | null
	{
		return this.#getOptions().disabledFields ?? null;
	}

	#isFieldDisabled(field: Field): boolean
	{
		const disabledFields = this.#getDisabledFields();
		if (Type.isNull(disabledFields))
		{
			return false;
		}

		return disabledFields.some(fieldRule =>
			(Type.isString(fieldRule) && field.name === fieldRule)
			|| (Type.isFunction(fieldRule) && fieldRule(field)),
		);
	};

	#onListItemChange(event: BaseEvent)
	{
		const listItem: ListItem = event.getTarget();
		if (this.#isMultiple())
		{
			if (listItem.isSelected())
			{
				this.#addSelectedField(listItem.getField());
			}
			else
			{
				this.#removeSelectedField(listItem.getField());
			}
		}
		else
		{
			this.#setSelectedFields([listItem.getField()]);
		}
	}

	#onSidebarItemClick(categoryId: string)
	{
		this.#renderCategoryFields(categoryId);
	}

	#onBackendError(error)
	{
		console.error(error);
		this.emit('onError', {error});
	}

	#getLayout(): HTMLDivElement
	{
		return Tag.render`
			<div class="crm-form-fields-selector">
				${this.#getFieldsListLayout()}
			</div>
		`;
	}

	async #onSearchChange()
	{
		const sliderLayout: Layout = await this.#getSliderLayout();
		const sidebarItems = this.#getSidebarItems();
		sliderLayout.getMenu().setItems(sidebarItems);

		this.#cleanFieldsList();

		const [firstSidebarItem] = sidebarItems;
		if (firstSidebarItem)
		{
			this.#onSidebarItemClick(firstSidebarItem.id);
			sliderLayout.getMenu().setActiveFirstItem();
			if (this.#isAllowedCreateField())
			{
				this.#getCreateFieldButton().setDisabled(false);
			}
		}
		else
		{
			if (this.#isAllowedCreateField())
			{
				this.#getCreateFieldButton().setDisabled(true);
			}
		}
	}

	#getSearch(): Search
	{
		return this.#cache.remember('search', () => {
			return new Search({
				events: {
					onChange: this.#onSearchChange.bind(this),
				},
			});
		});
	}

	#getFieldsListLayout(): HTMLDivElement
	{
		return this.#cache.remember('fieldsListLayout', () => {
			return Tag.render`
				<div class="crm-form-fields-selector-fields-list"></div>
			`;
		});
	}

	#getCreateFieldButton(): Button
	{
		return this.#cache.remember('createFieldButton', () => {
			return new Button({
				text: Loc.getMessage('CRM_FORM_FIELDS_SELECTOR_CREATE_BUTTON_LABEL'),
				color: Button.Color.SUCCESS,
				onclick: this.#onCreateFieldClick.bind(this),
			});
		});
	}

	async #onCreateFieldClick()
	{
		const sliderLayout = await this.#getSliderLayout();
		const sliderMenu = sliderLayout.getMenu();
		if (sliderMenu.hasActive())
		{
			const currentCategoryId = sliderMenu.getActiveItem().getId();
			const factory = this.#getUserFieldFactory(currentCategoryId);
			const menu = factory.getMenu();

			menu.open((selectedType) => {
				const configurator = factory.getConfigurator({
					userField: factory.createUserField(selectedType),
					onSave: (userField) => {
						Dom.addClass(configurator.saveButton, 'ui-btn-wait');

						return userField
							.save()
							.then(() => {
								return this.#load();
							})
							.then(() => {
								Dom.removeClass(configurator.saveButton, 'ui-btn-wait');
								this.#onSidebarItemClick(currentCategoryId);
								this.#getSearch().setValue(
									userField.getData().editFormLabel[Loc.getMessage('LANGUAGE_ID')],
								);
							});
					},
					onCancel: () => {
						this.#onSidebarItemClick(currentCategoryId);
					},
				});

				this.#cleanFieldsList();
				Dom.append(configurator.render(), this.#getFieldsListLayout());
			});
		}
	}

	#getPreparedCategoryId(categoryId: string): string | number
	{
		if (categoryId.startsWith('DYNAMIC_'))
		{
			const fieldsList = this.getFieldsList();
			if (Type.isPlainObject(fieldsList[categoryId]))
			{
				return fieldsList[categoryId].DYNAMIC_ID;
			}
		}

		return `CRM_${categoryId}`;
	}

	#getFieldsFactoryTypesFilter(): FieldsFactoryFilter
	{
		const defaultFilter = Runtime.clone(Selector.#defaultFieldsFactoryFilter);
		const customFilter = this.#getOptions()?.fieldsFactory?.filter;

		if (Type.isPlainObject(customFilter))
		{
			if (Type.isArrayFilled(customFilter['-types']))
			{
				customFilter['-types'] = [
					...defaultFilter['-types'],
					...customFilter['-types'],
				];
			}
			else
			{
				customFilter['-types'] = [
					...defaultFilter['-types'],
				];
			}

			return customFilter;
		}

		if (Type.isFunction(customFilter))
		{
			return customFilter;
		}

		return defaultFilter;
	}

	#applyFieldsFactoryTypesFilter(types, filter): Array<any>
	{
		if (Type.isPlainObject(filter))
		{
			return types.filter((type) => {
				const allowed = (
					!Type.isArrayFilled(filter['+types'])
					|| filter['+types'].some((condition) => {
						if (Type.isStringFilled(condition))
						{
							return type.name === condition;
						}

						if (Type.isFunction(condition))
						{
							return condition(type);
						}

						return false;
					})
				);

				const disallowed = (
					Type.isArrayFilled(filter['-types'])
					&& filter['-types'].some((condition) => {
						if (Type.isStringFilled(condition))
						{
							return type.name === condition;
						}

						if (Type.isFunction(condition))
						{
							return condition(type);
						}

						return false;
					})
				);

				return allowed && !disallowed;
			});
		}

		return types;
	}

	#getUserFieldFactory(categoryId: string | number): Factory
	{
		return this.#cache.remember(`factory_${categoryId}`, () => {
			const rootWindow = window.top;
			const Factory = (() => {
				if (rootWindow.BX.UI.UserFieldFactory)
				{
					return rootWindow.BX.UI.UserFieldFactory.Factory
				}

				return BX.UI.UserFieldFactory.Factory;
			})();

			const factory = new Factory(
				this.#getPreparedCategoryId(categoryId),
				{
					moduleId: 'crm',
					bindElement: this.#getCreateFieldButton().render(),
				},
			);

			const filter = this.#getFieldsFactoryTypesFilter();
			if (Type.isFunction(filter))
			{
				factory.types = this.#applyFieldsFactoryTypesFilter(
					factory.types,
					Selector.#defaultFieldsFactoryFilter,
				);
				factory.types = filter(factory.types);
			}

			if (Type.isPlainObject(filter))
			{
				factory.types = this.#applyFieldsFactoryTypesFilter(
					factory.types,
					filter,
				);
			}

			return factory;
		});
	}

	#getSliderLayout(): Promise<Layout>
	{
		return this.#cache.remember('sliderLayout', () => {
			return new Promise((resolve) => {
				Layout
					.createLayout({
						extensions: ['crm.form.fields.selector'],
						title: Loc.getMessage('CRM_FORM_FIELDS_SELECTOR_SLIDER_TITLE'),
						content: () => {
							return this.#getLayout();
						},
						menu: {
							items: this.#getSidebarItems(),
						},
						toolbar: () => {
							return [
								this.#getSearch().getLayout(),
								this.#getCreateFieldButton(),
							];
						},
						buttons: ({SaveButton, closeButton}) => {
							return [
								new SaveButton({
									text: Loc.getMessage('CRM_FORM_FIELDS_SELECTOR_APPLY_BUTTON_LABEL'),
									onclick: this.#onSaveClick.bind(this),
								}),
								closeButton,
							];
						},
					})
					.then((result) => {
						resolve(result);
					});
			});
		});
	}

	#getRenderedSliderLayout(): Promise<any>
	{
		return this.#cache.remember('renderedSliderLayout', () => {
			return this.#getSliderLayout().then((layout) => {
				return layout.render();
			});
		});
	}

	#onSaveClick()
	{
		const selectedFields = this.#getSelectedFields();
		const result = (() => {
			const {resultModifier} = this.#getOptions();
			if (Type.isFunction(resultModifier))
			{
				return resultModifier(selectedFields);
			}

			return selectedFields.map((field) => {
				return field.name;
			});
		})();

		this.#getPromiseResolver()(result);
		this.hide();
	}

	#setPromiseResolver(resolver: () => void)
	{
		this.#cache.set('promiseResolver', resolver);
	}

	#getPromiseResolver(): () => void
	{
		return this.#cache.get('promiseResolver', () => {});
	}

	#selectFirstCategory()
	{
		const [firstSidebarItem] = this.#getSidebarItems();
		if (Type.isPlainObject(firstSidebarItem))
		{
			this.#onSidebarItemClick(firstSidebarItem.id);
		}
	}

	hide()
	{
		const SidePanel: BX.SidePanel = Reflection.getClass('BX.SidePanel');
		if (SidePanel.Instance)
		{
			SidePanel.Instance.close();
		}
	}

	#getSliderId(): string
	{
		return this.#cache.remember('sliderId', () => {
			return `crm.form.fields.selector-${Text.getRandom()}`
		});
	}

	show(): Promise<Array<any>>
	{
		const SidePanel: BX.SidePanel = Reflection.getClass('BX.SidePanel');
		if (SidePanel.Instance)
		{
			const createFieldButton = this.#getCreateFieldButton();
			createFieldButton.setDisabled(
				!this.#isAllowedCreateField(),
			);

			SidePanel.Instance.open(
				this.#getSliderId(),
				{
					width: 740,
					contentCallback: () => {
						return this.#load()
							.then(() => {
								createFieldButton.setDisabled(
									!this.#isAllowedCreateField(),
								);
								this.#selectFirstCategory();

								return this.#getRenderedSliderLayout();
							})
							.catch(({errors}) => {
								return Tag.render`
									<div class="ui-alert ui-alert-danger">
										<span class="ui-alert-message">${errors.map((item) => Text.encode(item.message)).join('\n')}</span>
									</div>
								`;
							});
					},
					events: {
						onCloseComplete: () => this.#onSliderCloseComplete(),
					},
				},
			);
		}

		return new Promise((resolve) => {
			this.#setPromiseResolver(resolve);
		});
	}

	#onSliderCloseComplete(): void
	{
		this.emit('onSliderCloseComplete');
		this.#setSelectedFields([]);
	}
}
