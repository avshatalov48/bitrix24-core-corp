import {Loc, Tag, Dom, Type, Runtime} from "main.core";
import {EventEmitter, BaseEvent} from "main.core.events";
import './fieldset.css';

export class EntityEditorFieldsetField extends BX.UI.EntityEditorField
{
	constructor()
	{
		super();
		this._entityEditorList = {};
		this._entityId = null;
		this._fieldsetContainer = null;

		this._addEmptyValue = false;

		this._nextIndex = 0;

		this._isDeleted = false;
	}

	static create(id, settings)
	{
		let self = new this(id, settings);
		self.initialize(id, settings);
		return self;
	}

	doInitialize()
	{
		super.doInitialize();
		this._entityId = this._editor.getId() + '_' + this.getId() + '_fields';

		this._addEmptyValue = this.getDataBooleanParam("addEmptyValue", false);

		const nextIndex = BX.prop.getInteger(this.getSchemeElement().getData(), "nextIndex", 0);
		this._nextIndex = (nextIndex > 0) ? nextIndex : 0;

		this._config = BX.prop.getObject(this.getSchemeElement().getData(), "config", {});
	}

	layout(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({classNames: ["ui-entity-editor-content-block-field-fieldset-wrapper"]});
		this.adjustWrapper();

		if (!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		this._fieldsetContainer = Tag.render`<div class="ui-entity-editor-container"></div>`;
		Dom.append(this._fieldsetContainer, this._wrapper);

		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			let addButtonPanel = this.getAddButton();
			Dom.append(addButtonPanel, this._wrapper);
		}
		setTimeout(() => this.initializeExistedValues(), 0);

		this.registerLayout(options);
		this._hasLayout = true;
	}

	clearLayout()
	{
		super.clearLayout();
		this._entityEditorList = {};
	}

	createEntityEditor(id, values = {}, context = {})
	{
		let containerId = this._entityId + '_container_' + id;
		Dom.append(Tag.render`<div id="${containerId}" class="ui-entity-editor-field-fieldset"></div>`, this._fieldsetContainer);

		let entityEditorId = this._entityId + '_' + id;
		let prefix = this.getName() + '[' + id + ']';
		let section = {
			'name': this._entityId + '_SECTION',
			'type': 'section',
			'enableToggling': false,
			'transferable': false,
			'data': {'isRemovable': false, 'enableTitle': false, 'enableToggling': false},
			'elements': this.prepareFieldsWithPrefix(this.getSchemeSectionElements(), prefix)
		};

		const configId =
			(
				Type.isPlainObject(this._config)
				&& this._config.hasOwnProperty("GUID")
				&& Type.isStringFilled(this._config["GUID"])
			)
				? this._config["GUID"]
				: entityEditorId
		;
		let config = BX.UI.EntityConfig.create(
			configId,
			{
				data: [section],
				scope: this._editor.getConfigScope(),
				enableScopeToggle: false,
				canUpdatePersonalConfiguration: this._editor._config._canUpdatePersonalConfiguration,
				canUpdateCommonConfiguration: this._editor.canChangeCommonConfiguration(),
				options: {},
				signedParams: BX.prop.getString(this._config, 'ENTITY_CONFIG_SIGNED_PARAMS', '')
			}
		);

		const availableFields = this.prepareFieldsWithPrefix(
			BX.clone(BX.prop.getArray(this._config, 'ENTITY_AVAILABLE_FIELDS', [])),
			prefix
		);
		let scheme = BX.UI.EntityScheme.create(
			entityEditorId,
			{
				current: [section],
				available: availableFields
			}
		);

		let entityEditor = BX.UI.EntityEditor.create(
			entityEditorId,
			{
				model: BX.UI.EntityEditorModelFactory.create(
					"",
					"",
					{
						isIdentifiable: false,
						data: this.getFieldsValues(prefix, values)
					}
				),
				config: config,
				userFieldManager: null,
				scheme: scheme,
				context: context,
				containerId: containerId,
				serviceUrl: this._editor.getServiceUrl(),
				entityTypeName: "",
				entityId: 0,
				validators: [],
				controllers: [],
				detailManagerId: "",
				initialMode: BX.UI.EntityEditorMode.getName(this._mode),
				enableModeToggle: true,
				enableConfigControl: false,
				enableShowAlwaysFeauture: this.getEditor().isShowAlwaysFeautureEnabled(),
				enableVisibilityPolicy: true,
				enableToolPanel: true,
				enableBottomPanel: false,
				enableFieldsContextMenu: true,
				enablePageTitleControls: false,
				readOnly: (this._mode === BX.UI.EntityEditorMode.view),
				enableAjaxForm: false,
				enableRequiredUserFieldCheck: true,
				enableSectionEdit: false,
				enableSectionCreation: false,
				enableSectionDragDrop: true,
				enableFieldDragDrop: true,
				enableSettingsForAll: false,
				enableContextDataLayout: false,
				formTagName: 'div',
				externalContextId: "",
				contextId: "",
				options: {'show_always': 'Y'},
				ajaxData: [],
				isEmbedded: true
			}
		);
		entityEditor._enableCloseConfirmation = false;

		// Set CRM attribute manager
		const settings = this.getAttributeManagerSettings();
		if (BX.Type.isPlainObject(settings))
		{
			const attributeManager = BX.Crm.EntityFieldAttributeManager.create(
				entityEditor.getId() + "_ATTR_MANAGER",
				{
					entityTypeId: BX.prop.getInteger(
						settings,
						"ENTITY_TYPE_ID",
						BX.CrmEntityType.enumeration.undefined
					),
					entityScope: BX.prop.getString(settings, "ENTITY_SCOPE", ""),
					isPermitted: BX.prop.getBoolean(settings, "IS_PERMITTED", true),
					isPhaseDependent: BX.prop.getBoolean(settings, "IS_PHASE_DEPENDENT", true),
					isAttrConfigButtonHidden: BX.prop.getBoolean(
						settings, "IS_ATTR_CONFIG_BUTTON_HIDDEN", true
					),
					lockScript: BX.prop.getString(settings, "LOCK_SCRIPT", ""),
					captions: BX.prop.getObject(settings, "CAPTIONS", {}),
					entityPhases: BX.prop.getArray(settings, 'ENTITY_PHASES', null)
				}
			);
			entityEditor.setAttributeManager(attributeManager);
		}

		EventEmitter.subscribe(
			entityEditor,
			'onControlChanged',
			(event) => {
				if (!this.isChanged())
				{
					this.markAsChanged();
				}
			}
		);

		this.subscribeEditorEvents(
			entityEditor,
			[
				'onControlMove',
				'onFieldModify',
				'onFieldModifyAttributeConfigs',
				'onControlAdd',
				'onControlRemove',
				'onSchemeSave',
			]
		);

		let container = entityEditor.getContainer();
		if (Type.isDomNode(container))
		{
			Dom.prepend(this.getDeleteButton(id), container);
		}

		if(values.hasOwnProperty("DELETED") && values["DELETED"] === 'Y')
		{
			this._isDeleted = true;
			this.layoutDeletedValue(entityEditor, id);
		}

		BX.Crm.RequisiteDetailsManager.create({ entityEditorId });

		return entityEditor;
	}

	getCorrespondedControl(eventCode, controlId, editor)
	{
		return(
			(eventCode === "add")
				? editor.getAvailableControlByCombinedId(controlId)
				: editor.getControlByCombinedIdRecursive(controlId)
		);
	}

	getEditorSchemeSectionElements(editor)
	{
		let elements = [];

		const schemeElements = editor.getScheme().getElements();
		if (Type.isArray(schemeElements) && schemeElements.length > 0)
		{
			const section = schemeElements[0];
			if (section && section instanceof BX.UI.EntitySchemeElement && section.getType() === "section")
			{
				elements = section.getElements();
			}
		}

		return elements;
	}

	prepareSectionElementsBySchemeElements(schemeElements)
	{
		let elements = [];

		if (Type.isArray(schemeElements))
		{
			for (let i = 0; i < schemeElements.length; i++)
			{
				const element = {
					"name": schemeElements[i].getName(),
					"title": schemeElements[i].getTitle(),
					"type": schemeElements[i].getType(),
					"required": schemeElements[i].isRequired(),
					"optionFlags": schemeElements[i].getOptionFlags(),
					"options": schemeElements[i].getOptions()
				};
				elements.push(element);
			}
		}

		return elements;
	}

	syncEditorEvent(eventName, target, params)
	{
		const eventMap = {
			"onControlAdd": "add",
			"onControlMove": "move",
			"onFieldModify": "modify",
			"onFieldModifyAttributeConfigs": "modifyAttributes",
			"onControlRemove": "remove",
			"onSchemeSave": "saveScheme",
		};

		if (
			Type.isStringFilled(eventName)
			&& eventMap.hasOwnProperty(eventName)
			&& target instanceof BX.UI.EntityEditor
		)
		{
			if (eventMap[eventName] === "saveScheme")
			{
				this.setSchemeSectionElements(
					this.prepareFieldsWithoutPrefix(
						this.prepareSectionElementsBySchemeElements(
							this.getEditorSchemeSectionElements(target)
						)
					)
				);
				this.setSchemeAvailableElements(
					this.prepareFieldsWithoutPrefix(
						this.getEditorAvailableElements(target)
					)
				);
			}
			else if (
				Type.isArray(params)
				&& params.length > 1
				&& Type.isPlainObject(params[1])
			)
			{
				const eventParams = params[1];
				for (let index in this._entityEditorList)
				{
					if (this._entityEditorList.hasOwnProperty(index))
					{
						const editor = this._entityEditorList[index];
						if (editor instanceof BX.UI.EntityEditor && editor !== target)
						{
							if (eventMap[eventName] === "modify" || eventMap[eventName] === "modifyAttributes")
							{
								setTimeout(() => {
									const field = BX.prop.get(eventParams, "field", null);
									if (field && field instanceof BX.UI.EntityEditorField)
									{
										const control = this.getCorrespondedControl(
											eventMap[eventName],
											field.getId(),
											editor
										);
										if (control)
										{
											let needRefreshTitleLayout = false;
											if (eventMap[eventName] === "modifyAttributes")
											{
												const exists = [];
												const configs = BX.prop.getArray(eventParams, "attrConfigs", null);
												if (Type.isArray(configs) && configs.length > 0)
												{
													for(let i = 0, length = configs.length; i < length; i++)
													{
														const config = configs[i];
														const typeId = BX.prop.getInteger(
															config,
															"typeId",
															BX.UI.EntityFieldAttributeType.undefined
														);
														if (typeId !== BX.UI.EntityFieldAttributeType.undefined)
														{
															exists.push(typeId);
															control.getSchemeElement()
																.setAttributeConfiguration(config)
															;
														}
													}
												}
												for (let index in BX.UI.EntityFieldAttributeType)
												{
													if (BX.UI.EntityFieldAttributeType.hasOwnProperty(index))
													{
														const typeId = BX.UI.EntityFieldAttributeType[index];
														if (
															typeId !== BX.UI.EntityFieldAttributeType.undefined
															&& exists.indexOf(typeId) < 0
														)
														{
															control.getSchemeElement()
																.removeAttributeConfiguration(typeId)
															;
														}
													}
												}
												needRefreshTitleLayout = true;
											}
											else
											{
												const label = BX.prop.getString(eventParams, "label", "");
												if (Type.isStringFilled(label))
												{
													control.getSchemeElement().setTitle(label);
													needRefreshTitleLayout = true;
												}
											}
											if (needRefreshTitleLayout)
											{
												control.refreshTitleLayout();
											}
										}
									}
								});
							}
							else if (
								eventParams.hasOwnProperty("control")
								&& Type.isObject(eventParams["control"])
							)
							{
								const options = BX.prop.getObject(eventParams, "params", {});
								const controlId = eventParams["control"].getId();
								const control = this.getCorrespondedControl(eventMap[eventName], controlId, editor);
								if (control)
								{
									if (eventMap[eventName] === "add")
									{
										setTimeout(() => {
											editor.getControlByIndex(0).addChild(
												control,
												{
													layout: { forceDisplay: true },
													enableSaving: false,
													skipEvents: true
												}
											);
										});
									}
									else if (eventMap[eventName] === "move")
									{
										const index = BX.prop.getInteger(options, "index", -1);
										if (index >= 0)
										{
											setTimeout(() => {
												control.getParent().moveChild(
													control,
													index,
													{ enableSaving: false, skipEvents: true }
												);
												editor.processSchemeChange();
											});
										}
									}
									else if (eventMap[eventName] === "remove")
									{
										setTimeout(() => {
											control.hide(
												{ enableSaving: false, skipEvents: true }
											);
											editor.processSchemeChange();
										});
									}
								}
							}
						}
					}
				}
			}
		}
	}

	subscribeEditorEvents(editor, eventNames)
	{
		for (let i = 0; i < eventNames.length; i++)
		{
			EventEmitter.subscribe(
				editor,
				"BX.UI.EntityEditor:" + eventNames[i],
				(event) => {
					this.syncEditorEvent(eventNames[i], event.getTarget(), event.getData());
				}
			);
		}
	}

	unsubscribeEditorEvents(editor)
	{
		EventEmitter.unsubscribeAll(editor);
	}

	isDeleted()
	{
		return this._isDeleted;
	}

	layoutDeletedValue(entityEditor, id)
	{
		if (entityEditor instanceof BX.UI.EntityEditor)
		{
			let container = entityEditor.getContainer();
			if (Type.isDomNode(container))
			{
				container.style.display = 'none';

				let inputName = `${this.getName()}[${id}][DELETED]`;
				if (!container.querySelector(`input[name="${inputName}"]`))
				{
					Dom.append(
						Tag.render`<input type="hidden" name="${inputName}" value="Y" />`,
						container
					);
				}
			}
		}
	}

	onDeleteButtonClick(id)
	{
		if (this._entityEditorList.hasOwnProperty(id))
		{
			this.unsubscribeEditorEvents(this._entityEditorList[id]);

			this._isDeleted = true;
			this.layoutDeletedValue(this._entityEditorList[id], id);
			this.markAsChanged();
		}
	}

	onAddButtonClick()
	{
		this.addEmptyValue();
	}

	addEmptyValue(options)
	{
		let value = this.getValue();

		let id = 'n' + this._nextIndex++;

		value.push({
			'ID': id
		});

		this.getModel().setField(this.getName(), value);

		this._entityEditorList[id] = this.createEntityEditor(id, {}, this.prepareEntityEditorContext());
		this.markAsChanged();

		return this._entityEditorList[id];
	}

	getEditors()
	{
		return this._entityEditorList;
	}

	getSchemeSection()
	{
		let section = null;

		const entityScheme = BX.prop.getArray(this._config, 'ENTITY_SCHEME', []);
		if (Type.isArray(entityScheme) && entityScheme.length > 0)
		{
			const column = entityScheme[0];
			if (
				Type.isPlainObject(column)
				&& column.hasOwnProperty("elements")
				&& Type.isArray(column["elements"])
				&& column["elements"].length > 0
				&& Type.isPlainObject(column["elements"][0])
			)
			{
				section = column["elements"][0];
			}
		}

		return section;
	}

	getEditorAvailableElements(editor)
	{
		let elements = [];

		if (editor && editor instanceof BX.UI.EntityEditor)
		{
			const schemeElements = editor.getAvailableSchemeElements();
			for (let i = 0; i < schemeElements.length; i++)
			{
				const element = {
					"name": schemeElements[i].getName(),
					"title": schemeElements[i].getTitle(),
					"type": schemeElements[i].getType(),
					"required": schemeElements[i].isRequired(),
				};
				elements.push(element)
			}

		}

		return elements;
	}

	setSchemeAvailableElements(availableElements)
	{
		this._config["ENTITY_AVAILABLE_FIELDS"] = availableElements;
	}

	setSchemeSectionElements(sectionElements)
	{
		let section = null;

		if (!Type.isArray(sectionElements))
		{
			sectionElements = [];
		}

		const entityScheme = BX.prop.getArray(this._config, 'ENTITY_SCHEME', []);
		if (Type.isArray(entityScheme) && entityScheme.length > 0)
		{
			const column = entityScheme[0];
			if (
				Type.isPlainObject(column)
				&& column.hasOwnProperty("elements")
				&& Type.isArray(column["elements"])
				&& column["elements"].length > 0
				&& Type.isPlainObject(column["elements"][0])
			)
			{
				section = column["elements"][0];
			}
		}

		if (Type.isPlainObject(section))
		{
			section["elements"] = sectionElements;
		}
	}

	getSchemeSectionElements()
	{
		let elements = [];

		const section = this.getSchemeSection();
		if (
			section
			&& section.hasOwnProperty("elements")
			&& Type.isArray(section["elements"])
		)
		{
			elements = Runtime.clone(section["elements"]);
		}

		return elements;
	}

	setSchemeSectionElements(elements)
	{
		const section = this.getSchemeSection();
		if (section)
		{
			section["elements"] = Runtime.clone(elements);
		}
	}

	getFields(prefix)
	{
		const fields = BX.clone(BX.prop.getArray(this.getSchemeElement().getData(), 'fields', []));

		return this.prepareFieldsWithPrefix(fields, prefix);
	};

	prepareFieldsWithPrefix(fields, prefix)
	{
		for (let index = 0; index < fields.length; index++)
		{
			fields[index].name = this.getFieldName(fields[index].name, prefix);
		}

		return fields;
	}

	prepareFieldsWithoutPrefix(fields)
	{
		for (let index = 0; index < fields.length; index++)
		{
			if (Type.isStringFilled(fields[index].name))
			{
				const matches = fields[index].name.match(/\[(\w+)]$/);
				if (matches && matches.length > 1 && Type.isStringFilled(matches[1]))
				{
					fields[index].name = matches[1];
				}
			}
		}

		return fields;
	}

	getFieldsValues(prefix, values)
	{
		let result = {};
		for (let fieldId in values)
		{
			result[this.getFieldName(fieldId, prefix)] = values[fieldId];
		}
		return result;
	};

	getFieldName(originalName, prefix)
	{
		return prefix + '[' + originalName + ']';
	}

	getDeleteButton(id)
	{
		return Tag.render`
		<div class="ui-entity-editor-field-fieldset-delete">
			<span class="ui-link ui-link-secondary" onclick="${this.onDeleteButtonClick.bind(this, id)}">
				${Loc.getMessage('UI_ENTITY_EDITOR_DELETE')}
			</span>
		</div>`;
	}

	getAddButton()
	{
		return Tag.render`
		<div class="ui-entity-editor-content-block-add-field">
			<span class="ui-entity-card-content-add-field" onclick="${this.onAddButtonClick.bind(this)}">
				${Loc.getMessage('UI_ENTITY_EDITOR_ADD')}
			</span>
		</div>`;
	}

	initializeExistedValues()
	{
		let existedItems = this.getValue();
		if (existedItems.length)
		{
			for (let item of existedItems)
			{
				if (!this._entityEditorList[item.ID])
				{
					this._entityEditorList[item.ID] = this.createEntityEditor(
						item.ID,
						item,
						this.prepareEntityEditorContext()
					);
				}
			}
		}
		else if (this._mode === BX.UI.EntityEditorMode.edit && this._addEmptyValue)
		{
			this.addEmptyValue();
		}
	}

	getAttributeManagerSettings()
	{
		return BX.prop.getObject(this._config, "ATTRIBUTE_CONFIG", null);
	}

	getResolverProperty()
	{
		return BX.prop.getObject(this._settings, "resolverProperty", null);
	}

	getActiveControlById(id)
	{
		for (let pseudoId in this._entityEditorList)
		{
			if (this._entityEditorList.hasOwnProperty(pseudoId))
			{
				const control = this._entityEditorList[pseudoId].getActiveControlById(id, true);
				if (control)
				{
					return control;
				}
			}
		}
	}

	validate(result)
	{
		if(this._isDeleted || this._mode !== BX.UI.EntityEditorMode.edit)
		{
			return true;
		}

		const validator = BX.UI.EntityAsyncValidator.create();
		for (let pseudoId in this._entityEditorList)
		{
			if (this._entityEditorList.hasOwnProperty(pseudoId))
			{
				const field = this._entityEditorList[pseudoId];
				if(field.getMode() !== BX.UI.EntityEditorMode.edit)
				{
					continue;
				}

				validator.addResult(field.validate(result));
			}
		}

		return validator.validate();
	}

	prepareEntityEditorContext()
	{
		return {};
	}
}

EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', (event: BaseEvent) => {
		let data = event.getData();
		if (data[0])
		{
			data[0].methods["fieldset"] = (type, controlId, settings) => {
				if (type === "fieldset")
				{
					return EntityEditorFieldsetField.create(controlId, settings);
				}
				return null;
			};
		}
		event.setData(data);
});
