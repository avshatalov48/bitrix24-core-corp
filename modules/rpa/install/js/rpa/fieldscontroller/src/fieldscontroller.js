import {Text, Dom, Loc, Tag, Type, Event} from 'main.core';
import {Factory} from 'ui.userfieldfactory';
import {UserField} from 'ui.userfield';
import {Loader} from 'main.loader';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {MenuManager, Menu} from 'main.popup';

import {Manager} from 'rpa.manager';

import 'ui.design-tokens';
import 'ui.switcher';

/**
 * @memberof BX.Rpa
 * @mixes EventEmitter
 */
export class FieldsController
{
	constructor(params: {
		fields: ?Object,
		hiddenFields: ?Object,
		factory: ?Factory,
		fieldSubTitle: ?string,
		errorContainer: ?Element,
		settings: ?{
			inputName: string,
			values: ?Object
		},
		typeId: number,
		languageId: string,
	})
	{
		EventEmitter.makeObservable(this, 'BX.Rpa.FieldsController');
		this.fields = new Map();
		this.hiddenFields = new Map();
		this.layout = {};
		this.fieldSubTitle = Loc.getMessage('RPA_FIELDS_SELECTOR_FIELD_DEFAULT_SUBTITLE');
		this.errorContainer = null;
		this.progress = false;
		if(Type.isPlainObject(params))
		{
			if(params.factory instanceof Factory)
			{
				this.factory = params.factory;
			}
			if(Type.isPlainObject(params.fields))
			{
				this.setFields(params.fields);
			}
			if(Type.isPlainObject(params.hiddenFields))
			{
				this.setHiddenFields(params.hiddenFields);
			}
			if(Type.isString(params.fieldSubTitle))
			{
				this.fieldSubTitle = params.fieldSubTitle;
			}
			if(Type.isDomNode(params.errorContainer))
			{
				this.errorContainer = params.errorContainer;
			}
			if(Type.isPlainObject(params.settings))
			{
				this.settings = params.settings;
				if(!Type.isString(this.settings.inputName))
				{
					this.settings.inputName = '';
				}
				if(!Type.isPlainObject(this.settings.values))
				{
					this.settings.values = {};
				}
			}
			this.typeId = Text.toInteger(params.typeId);
			this.languageId = params.languageId || Loc.getMessage('LANGUAGE_ID');
			if(this.factory)
			{
				this.factory.setCustomTypesUrl(Manager.Instance.getFieldDetailUrl(this.typeId, 0));
				this.factory.subscribe('onCreateCustomUserField', (event: BaseEvent) =>
				{
					const userField = event.getData().userField;
					this.addField(userField)
						.renderFields();
				});
			}
		}
	}

	getFields(): Map
	{
		return this.fields;
	}

	setFields(fields: Object): this
	{
		Object.keys(fields).forEach((fieldName) =>
		{
			this.addField(new UserField(fields[fieldName], {
				languageId: this.languageId,
				moduleId: this.factory ? this.factory.moduleId : null,
			}));
		});

		return this;
	}

	addField(userField: UserField): this
	{
		this.fields.set(userField.getName(), userField);

		return this;
	}

	removeField(userField: UserField): this
	{
		this.fields.delete(userField.getName());

		return this;
	}

	setHiddenFields(fields: Object): this
	{
		Object.keys(fields).forEach((fieldName) =>
		{
			this.addHiddenField(new UserField(fields[fieldName], {
				languageId: this.languageId,
				moduleId: this.factory ? this.factory.moduleId : null,
			}));
		});

		return this;
	}

	addHiddenField(userField: UserField): this
	{
		this.hiddenFields.set(userField.getName(), userField);

		return this;
	}

	removeHiddenField(userField: UserField): this
	{
		this.hiddenFields.delete(userField.getName());

		return this;
	}

	render(): Element
	{
		const container = this.renderContainer();

		container.appendChild(this.renderFields());

		if(this.factory)
		{
			this.layout.configurator = Tag.render`<div></div>`;
			container.appendChild(this.layout.configurator);

			container.appendChild(this.renderFooter());
		}

		return container;
	}

	renderContainer(): Element
	{
		this.layout.container = Tag.render`<div class="rpa-fields-controller-container"></div>`;

		return this.getContainer();
	}

	getContainer(): ?Element
	{
		return this.layout.container;
	}

	renderFields(): Element
	{
		if(this.layout.fieldsContainer)
		{
			Dom.clean(this.layout.fieldsContainer);
			if(this.settings)
			{
				this.settings.values = this.getSettings();
			}
		}
		else
		{
			this.layout.fieldsContainer = Tag.render`<div class="rpa-fields-controller-fields"></div>`;
		}

		Array.from(this.fields.values()).forEach((userField: UserField) =>
		{
			this.layout.fieldsContainer.appendChild(this.renderField(userField));
		});

		return this.layout.fieldsContainer;
	}

	getFieldRow(userField: UserField): ?Element
	{
		if(!this.layout.fieldsContainer)
		{
			return null;
		}

		return this.layout.fieldsContainer.querySelector(`[data-role="field-row-${userField.getName()}"]`);
	}

	renderField(userField: UserField): Element
	{
		const row = Tag.render`<div class="rpa-fields-controller-field-row" data-role="field-row-${userField.getName()}"></div>`;
		const container = Tag.render`<div class="rpa-fields-controller-field-container"></div>`;
		if(this.fieldSubTitle)
		{
			container.appendChild(Tag.render`<div class="rpa-fields-controller-field-subtitle">${Text.encode(this.fieldSubTitle)}</div>`)
		}
		container.appendChild(Tag.render`<div class="rpa-fields-controller-field-title">${Text.encode(userField.getTitle())}</div>`);

		const wrapper = Tag.render`<div class="rpa-fields-controller-field-wrapper"></div>`;
		wrapper.appendChild(container);

		if(this.settings)
		{
			wrapper.appendChild(this.renderSwitcher(userField));
		}
		else
		{
			const fieldSettingsButton = Tag.render`<div class="rpa-fields-controller-field-wrapper-gear"></div>`;
			this.getSettingsMenu(fieldSettingsButton, userField).destroy();
			Event.bind(fieldSettingsButton, 'click', () =>
			{
				this.getSettingsMenu(fieldSettingsButton, userField).show();
			});
			wrapper.appendChild(fieldSettingsButton);
		}

		row.appendChild(wrapper);
		row.appendChild(Tag.render`<div class="rpa-fields-controller-field-settings"></div>`);

		return row;
	}

	static getSwitcherId(inputName: string, fieldName: string): string
	{
		return 'rpa-fields-controller-' + inputName + '-' + fieldName;
	}

	renderSwitcher(userField: UserField): Element
	{
		const data = {
			id: FieldsController.getSwitcherId(this.settings.inputName, userField.getName()),
			checked: (this.settings.values[userField.getName()] === true),
			inputName: this.settings.inputName + '[' + userField.getName() + ']',
		};

		const switcher = Tag.render`<span data-switcher='${JSON.stringify(data)}' class="ui-switcher rpa-fields-controller-switcher"></span>`;

		new BX.UI.Switcher({node: switcher});

		return switcher;
	}

	renderFooter(): Element
	{
		if(!this.layout.footer)
		{
			this.layout.footer = Tag.render`<div class="rpa-fields-controller-footer">
				${this.getSelectButton()}
				${this.getCreateButton()}
			</div>`;
		}

		this.updateSelectButtonAppearance();

		return this.layout.footer;
	}

	updateSelectButtonAppearance(): this
	{
		if(this.hiddenFields.size <= 0)
		{
			this.getSelectButton().style.display = 'none';
		}
		else
		{
			this.getSelectButton().style.display = 'inline-block';
		}

		return this;
	}

	getCreateButton(): Element
	{
		if(!this.layout.createButton)
		{
			this.layout.createButton = Tag.render`<div class="rpa-fields-controller-create-field-button" onclick="${this.handleCreateButtonClick.bind(this)}">${Loc.getMessage('RPA_FIELDS_SELECTOR_FILED_CREATE_BUTTON')}</div>`;
		}

		return this.layout.createButton;
	}

	handleCreateButtonClick()
	{
		if(this.factory)
		{
			this.factory.getMenu({
				bindElement: this.getCreateButton(),
			}).open(this.handleUserFieldTypeClick.bind(this))
		}
	}

	getSelectButton(): Element
	{
		if(!this.layout.selectButton)
		{
			this.layout.selectButton = Tag.render`<div class="rpa-fields-controller-select-field-button" onclick="${this.handleSelectButtonClick.bind(this)}">${Loc.getMessage('RPA_FIELDS_SELECTOR_FIELD_SELECT_BUTTON')}</div>`;
		}

		return this.layout.selectButton;
	}

	handleSelectButtonClick()
	{
		this.getSelectFieldsMenu().show();
	}

	handleUserFieldTypeClick(fieldType: string)
	{
		if(!this.factory)
		{
			return;
		}

		const userField = this.factory.createUserField(fieldType);
		if(!userField)
		{
			return;
		}

		this.showFieldConfigurator(userField);
	}

	showFieldConfigurator(userField: UserField)
	{
		if(userField.isSaved())
		{
			const row = this.getFieldRow(userField);
			if(row)
			{
				const settings = row.querySelector('.rpa-fields-controller-field-settings');
				if(settings)
				{
					row.classList.add('rpa-fields-controller-edit');
					Dom.clean(settings);
					settings.appendChild(
						this.factory.getConfigurator({
							userField,
							onSave: this.handleFieldSave.bind(this),
							onCancel: (() =>
							{
								this.hideFieldConfigurator(userField);
							}),
						}).render()
					);
				}
			}
		}
		else
		{
			Dom.clean(this.layout.configurator);
			this.layout.configurator.appendChild(
				this.factory.getConfigurator({
					userField,
					onSave: this.handleFieldSave.bind(this)
				}).render()
			);
		}
	}

	hideFieldConfigurator(userField: UserField)
	{
		const row = this.getFieldRow(userField);
		if(row)
		{
			row.classList.remove('rpa-fields-controller-edit');
			const settings = row.querySelector('.rpa-fields-controller-field-settings');
			if(settings)
			{
				Dom.clean(settings);
			}
		}
	}

	handleFieldSave(userField: UserField)
	{
		if(!this.factory)
		{
			return;
		}

		if(this.isProgress())
		{
			return;
		}
		this.startProgress();

		userField.save().then(() =>
		{
			this.stopProgress().addField(userField).renderFields();
			Dom.clean(this.layout.configurator);
			this.emit('onFieldSave', {
				userField,
			});
		}).catch((errors) =>
		{
			this.stopProgress();
			this.showError(errors);
		})
	}

	isProgress()
	{
		return this.progress;
	}

	startProgress(): this
	{
		this.progress = true;
		if(!this.getLoader().isShown())
		{
			this.getLoader().show(this.getContainer());
		}

		return this;
	}

	stopProgress(): this
	{
		this.progress = false;
		this.getLoader().hide();

		return this;
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new Loader({size: 150});
		}

		return this.loader;
	}

	showError(error, errorContainer: ?Element)
	{
		if(!errorContainer)
		{
			errorContainer = this.errorContainer;
		}
		let message = '';
		if(Type.isArray(error))
		{
			message = error.join(", ");
		}
		else if(Type.isString(error))
		{
			message = error;
		}

		if(message)
		{
			if(Type.isDomNode(errorContainer))
			{
				errorContainer.innerHTML = message;
				errorContainer.parentNode.style.display = 'block';
				window.scrollTo(0, Dom.getPosition(errorContainer).top);
			}
			else
			{
				console.error(message);
			}
		}
	}

	getSettings(): Object
	{
		const settings = {};

		if(!this.settings)
		{
			return settings;
		}

		Array.from(this.fields.values()).forEach((userField) =>
		{
			const switcher = BX.UI.Switcher.getById(FieldsController.getSwitcherId(this.settings.inputName, userField.getName()));
			if(switcher)
			{
				settings[userField.getName()] = switcher.isChecked();
			}
		});

		return settings;
	}

	getSelectFieldsMenuId(): string
	{
		return 'rpa-fieldscontorller-select-field-menu';
	}

	getSelectFieldsMenuItems(): Array
	{
		const items = [];

		Array.from(this.hiddenFields.values()).forEach((userField: UserField) => {
			items.push({
				text: Text.encode(userField.getTitle()),
				onclick: () =>
				{
					this.handleHiddenUserFieldClick(userField);
				}
			});
		});

		return items;
	}

	getSelectFieldsMenu(): Menu
	{
		if(!this.getSelectButton())
		{
			return;
		}

		return MenuManager.create({
			id: this.getSelectFieldsMenuId(),
			bindElement: this.getSelectButton(),
			items: this.getSelectFieldsMenuItems(),
			offsetTop: 0,
			offsetLeft: 16,
			angle: {
				position: "top",
				offset: 0,
			},
			events: {
				onPopupClose: () => {
					this.getSelectFieldsMenu().destroy();
				},
			},
		});
	}

	handleHiddenUserFieldClick(userField: UserField)
	{
		this.addField(userField)
			.removeHiddenField(userField)
			.updateSelectButtonAppearance()
			.renderFields();
		this.getSelectFieldsMenu().close();
	}

	getSettingsMenu(button: Element, userField: UserField): Menu
	{
		return MenuManager.create({
			id: 'rpa-fieldscontroller-field-settings-' + userField.getId(),
			bindElement: button,
			items: [
				{
					text: Loc.getMessage('RPA_FIELDS_SELECTOR_CONFIGURATOR_ACTION_HIDE'),
					onclick: (event, item) => {
						this.removeField(userField)
							.addHiddenField(userField)
							.updateSelectButtonAppearance()
							.renderFields();

						if(item && item.menuWindow)
						{
							item.menuWindow.close();
						}
					},
				},
				{
					text: Loc.getMessage('RPA_FIELDS_SELECTOR_CONFIGURATOR_ACTION_EDIT'),
					onclick: (event, item) => {
						this.showFieldConfigurator(userField);

						if(item && item.menuWindow)
						{
							item.menuWindow.close();
						}
					},
				},
				{
					text: Loc.getMessage('RPA_FIELDS_SELECTOR_CONFIGURATOR_ACTION_ADJUST'),
					onclick: (event, item) => {
						Manager.Instance.openFieldDetail(
							this.typeId,
							userField.getId(),
							{
								width: 900,
								cacheable: false,
							}
						).then((slider) =>
						{
							const userFieldData = slider.getData().get('userFieldData');
							if(userFieldData)
							{
								userField = UserField.unserialize(userFieldData);
								if(userField.isDeleted())
								{
									this.removeField(userField)
										.renderFields();

								}
								else
								{
									this.addField(userField);
									this.renderFields();
								}
							}
						});

						if(item && item.menuWindow)
						{
							item.menuWindow.close();
						}
					},
				},
			],
		});
	}
}