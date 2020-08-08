BX.namespace("BX.Crm");

//region USER FIELD
if(typeof BX.Crm.EntityUserFieldType === "undefined")
{
	BX.Crm.EntityUserFieldType =
		{
			string: "string",
			integer: "integer",
			double: "double",
			boolean: "boolean",
			money: "money",
			date: "date",
			datetime: "datetime",
			enumeration: "enumeration",
			employee: "employee",
			crm: "crm",
			crmStatus: "crm_status",
			file: "file",
			url: "url"
		};
}

if(typeof BX.Crm.EntityUserFieldManager === "undefined")
{
	BX.Crm.EntityUserFieldManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityId = 0;
		this._fieldEntityId = "";
		this._enableCreation = false;
		this._creationSignature = "";
		this._creationUrl = "";
		this._activeFields = {};
		this._validationResult = null;
		this._validationPromise = null;

		this._config = null;
	};
	BX.Crm.EntityUserFieldManager.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
				this._fieldEntityId = BX.prop.getString(this._settings, "fieldEntityId", "");
				this._enableCreation = BX.prop.getBoolean(this._settings, "enableCreation", false);
				this._creationSignature = BX.prop.getString(this._settings, "creationSignature", "");
				this._creationPageUrl = BX.prop.getString(this._settings, "creationPageUrl", "");
			},
			isCreationEnabled: function()
			{
				return this._enableCreation;
			},
			isModificationEnabled: function()
			{
				return this._enableCreation;
			},
			getDefaultFieldLabel: function(typeId)
			{
				if(typeId === "string")
				{
					return this.getMessage("stringLabel");
				}
				else if(typeId === "double")
				{
					return this.getMessage("doubleLabel");
				}
				else if(typeId === "money")
				{
					return this.getMessage("moneyLabel");
				}
				else if(typeId === "datetime")
				{
					return this.getMessage("datetimeLabel");
				}
				else if(typeId === "enumeration")
				{
					return this.getMessage("enumerationLabel");
				}
				else if(typeId === "file")
				{
					return this.getMessage("fileLabel");
				}
				return this.getMessage("label");
			},
			getMessage: function(name)
			{
				var m = BX.Crm.EntityUserFieldManager.messages;
				return m.hasOwnProperty(name) ? m[name] : name;
			},
			getAdditionalTypeList: function()
			{
				return BX.Crm.EntityUserFieldManager.additionalTypeList;
			},
			getTypeInfos: function()
			{
				var items = [];
				items.push({ name: "string", title: this.getMessage("stringTitle"), legend: this.getMessage("stringLegend") });
				items.push({ name: "enumeration", title: this.getMessage("enumerationTitle"), legend: this.getMessage("enumerationLegend") });
				items.push({ name: "datetime", title: this.getMessage("datetimeTitle"), legend: this.getMessage("datetimeLegend") });
				items.push({ name: "address", title: this.getMessage("addressTitle"), legend: this.getMessage("addressLegend") });

				if (this._fieldEntityId === 'CRM_LEAD' || this._fieldEntityId === 'CRM_DEAL')
				{
					items.push({ name: "resourcebooking", title: this.getMessage("resourcebookingTitle"), legend: this.getMessage("resourcebookingLegend") });
				}

				items.push({ name: "url", title: this.getMessage("urlTitle"), legend: this.getMessage("urlLegend") });
				items.push({ name: "file", title: this.getMessage("fileTitle"), legend: this.getMessage("fileLegend") });
				items.push({ name: "money", title: this.getMessage("moneyTitle"), legend: this.getMessage("moneyLegend") });
				items.push({ name: "boolean", title: this.getMessage("booleanTitle"), legend: this.getMessage("booleanLegend") });
				items.push({ name: "double", title: this.getMessage("doubleTitle"), legend: this.getMessage("doubleLegend") });

				var additionalList = this.getAdditionalTypeList();
				for(var i = 0; i < additionalList.length; i++)
				{
					items.push({
						name: additionalList[i].USER_TYPE_ID,
						title: additionalList[i].TITLE,
						legend: additionalList[i].LEGEND
					});
				}

				items.push({ name: "custom", title: this.getMessage("customTitle"), legend: this.getMessage("customLegend") });

				return items;
			},
			getCreationPageUrl: function()
			{
				return this._creationPageUrl;
			},
			createField: function(fieldData, mode)
			{
				if(!this._enableCreation)
				{
					return;
				}

				var typeId = BX.prop.getString(fieldData, "USER_TYPE_ID", "");
				if(typeId === "")
				{
					typeId = BX.Crm.EntityUserFieldType.string;
				}

				if(!BX.type.isNotEmptyString(fieldData["EDIT_FORM_LABEL"]))
				{
					fieldData["EDIT_FORM_LABEL"] = this.getDefaultFieldLabel(typeId);
				}

				if(!BX.type.isNotEmptyString(fieldData["LIST_COLUMN_LABEL"]))
				{
					fieldData["LIST_COLUMN_LABEL"] = fieldData["EDIT_FORM_LABEL"];
				}

				if(!BX.type.isNotEmptyString(fieldData["LIST_FILTER_LABEL"]))
				{
					fieldData["LIST_FILTER_LABEL"] = fieldData["LIST_COLUMN_LABEL"];
				}

				this.addFieldLabel("EDIT_FORM_LABEL", fieldData["EDIT_FORM_LABEL"], fieldData);
				this.addFieldLabel("LIST_COLUMN_LABEL", fieldData["LIST_COLUMN_LABEL"], fieldData);
				this.addFieldLabel("LIST_FILTER_LABEL", fieldData["LIST_FILTER_LABEL"], fieldData);

				var promise = new BX.Promise();
				var onSuccess = function(result)
				{
					promise.fulfill(result);
				};

				if(!BX.type.isNotEmptyString(fieldData["FIELD"]))
				{
					fieldData["FIELD"] = "UF_CRM_" + (new Date()).getTime().toString();
				}

				fieldData["ENTITY_ID"] = this._fieldEntityId;
				fieldData["SIGNATURE"] = this._creationSignature;

				if(!BX.type.isNotEmptyString(fieldData["MULTIPLE"]))
				{
					fieldData["MULTIPLE"] = "N";
				}

				if(!BX.type.isNotEmptyString(fieldData["MANDATORY"]))
				{
					fieldData["MANDATORY"] = "N";
				}

				if(typeId === BX.Crm.EntityUserFieldType.file)
				{
					fieldData["SHOW_FILTER"] = "N";
					fieldData["SHOW_IN_LIST"] = "N";
				}
				else
				{
					if(typeId === BX.Crm.EntityUserFieldType.employee
						|| typeId === BX.Crm.EntityUserFieldType.crm
						|| typeId === BX.Crm.EntityUserFieldType.crmStatus
					)
					{
						//Force exact match for 'employee', 'crm' and 'crm_status' types
						fieldData["SHOW_FILTER"] = "I";
					}
					else
					{
						fieldData["SHOW_FILTER"] = "E";
					}
					fieldData["SHOW_IN_LIST"] = "Y";
				}

				if(typeId === BX.Crm.EntityUserFieldType.enumeration)
				{
					if(!fieldData.hasOwnProperty("SETTINGS"))
					{
						fieldData["SETTINGS"] = {};
					}

					fieldData["SETTINGS"]["DISPLAY"] = "UI";
				}

				if(typeId === BX.Crm.EntityUserFieldType.boolean)
				{
					if(!fieldData.hasOwnProperty("SETTINGS"))
					{
						fieldData["SETTINGS"] = {};
					}

					fieldData["SETTINGS"]["LABEL_CHECKBOX"] = fieldData["EDIT_FORM_LABEL"];
				}

				if(typeId === BX.Crm.EntityUserFieldType.double)
				{
					if(!fieldData.hasOwnProperty("SETTINGS"))
					{
						fieldData["SETTINGS"] = {};
					}

					fieldData["SETTINGS"]["PRECISION"] = 2;
				}

				if(mode === BX.Crm.EntityEditorMode.view)
				{
					BX.Main.UF.ViewManager.add({ "FIELDS": [fieldData] }, onSuccess);
				}
				else
				{
					BX.Main.UF.EditManager.add({ "FIELDS": [fieldData] }, onSuccess);
				}
				return promise;
			},
			updateField: function(fieldData, mode)
			{
				fieldData["ENTITY_ID"] = this._fieldEntityId;
				fieldData["SIGNATURE"] = this._creationSignature;

				if(BX.type.isNotEmptyString(fieldData["EDIT_FORM_LABEL"]))
				{
					this.addFieldLabel("EDIT_FORM_LABEL", fieldData["EDIT_FORM_LABEL"], fieldData);
				}

				if(BX.type.isNotEmptyString(fieldData["LIST_COLUMN_LABEL"]))
				{
					this.addFieldLabel("LIST_COLUMN_LABEL", fieldData["LIST_COLUMN_LABEL"], fieldData);
				}

				if(BX.type.isNotEmptyString(fieldData["LIST_FILTER_LABEL"]))
				{
					this.addFieldLabel("LIST_FILTER_LABEL", fieldData["LIST_FILTER_LABEL"], fieldData);
				}

				var promise = new BX.Promise();
				var onSuccess = function(result)
				{
					promise.fulfill(result);
				};

				if(mode === BX.Crm.EntityEditorMode.view)
				{
					BX.Main.UF.ViewManager.update({ "FIELDS": [fieldData] }, onSuccess);
				}
				else
				{
					BX.Main.UF.EditManager.update({ "FIELDS": [fieldData] }, onSuccess);
				}
				return promise;
			},
			resolveFieldName: function(fieldInfo)
			{
				return BX.prop.getString(fieldInfo, "FIELD", "");
			},
			addFieldLabel: function(name, value, fieldData)
			{
				var languages = BX.prop.getArray(this._settings, "languages", []);
				if(languages.length === 0)
				{
					fieldData[name] = value;
					return;
				}

				fieldData[name] = {};
				for(var i = 0, length = languages.length; i < length; i++)
				{
					var language = languages[i];
					fieldData[name][language["LID"]] = value;
				}
			},
			prepareSchemeElementSettings: function(fieldInfo)
			{
				var name = BX.prop.getString(fieldInfo, "FIELD", "");
				if(name === "")
				{
					return null;
				}

				if(BX.prop.getString(fieldInfo, "USER_TYPE_ID", "") === "")
				{
					fieldInfo["USER_TYPE_ID"] = "string";
				}

				if(BX.prop.getString(fieldInfo, "ENTITY_ID", "") === "")
				{
					fieldInfo["ENTITY_ID"] = this._fieldEntityId;
				}

				if(BX.prop.getInteger(fieldInfo, "ENTITY_VALUE_ID", 0) <= 0)
				{
					fieldInfo["ENTITY_VALUE_ID"] = this._entityId;
				}

				return(
					{
						name: name,
						originalTitle: BX.prop.getString(fieldInfo, "EDIT_FORM_LABEL", name),
						title: BX.prop.getString(fieldInfo, "EDIT_FORM_LABEL", name),
						type: "userField",
						required: BX.prop.getString(fieldInfo, "MANDATORY", "N") === "Y",
						data: { fieldInfo: fieldInfo }
					}
				);
			},
			createSchemeElement: function(fieldInfo)
			{
				return BX.Crm.EntitySchemeElement.create(this.prepareSchemeElementSettings(fieldInfo));
			},
			updateSchemeElement: function(element, fieldInfo)
			{
				var settings = this.prepareSchemeElementSettings(fieldInfo);
				settings["title"] = element.getTitle();
				element.mergeSettings(settings);
			},
			registerActiveField: function(field)
			{
				var name = field.getName();
				this._activeFields[name] = field;

				BX.Main.UF.EditManager.registerField(name, field.getFieldInfo(), field.getFieldNode());
			},
			unregisterActiveField: function(field)
			{
				var name = field.getName();
				if(this._activeFields.hasOwnProperty(name))
				{
					delete this._activeFields[name];
				}
				BX.Main.UF.EditManager.unRegisterField(name);
			},
			validate: function(result)
			{
				var names = [];
				for(var name in this._activeFields)
				{
					if(this._activeFields.hasOwnProperty(name))
					{
						names.push(name);
					}
				}

				if(names.length > 0)
				{
					this._validationResult = result;
					BX.Main.UF.EditManager.validate(
						names,
						BX.delegate(this.onValidationComplete, this)
					);
				}
				else
				{
					window.setTimeout(
						BX.delegate(
							function()
							{
								if(this._validationPromise)
								{
									this._validationPromise.fulfill();
									this._validationPromise = null;
								}
							},
							this
						),
						0
					);
				}

				this._validationPromise = new BX.Promise();
				return this._validationPromise;
			},
			onValidationComplete: function(results)
			{
				var name;
				//Reset previous messages
				for(name in this._activeFields)
				{
					if(this._activeFields.hasOwnProperty(name))
					{
						this._activeFields[name].clearError();
					}
				}

				//Add new messages
				for(name in results)
				{
					if(!results.hasOwnProperty(name))
					{
						continue;
					}

					if(this._activeFields.hasOwnProperty(name))
					{
						var field = this._activeFields[name];
						field.showError(results[name]);
						this._validationResult.addError(BX.Crm.EntityValidationError.create({ field: field }));
					}
				}

				if(this._validationPromise)
				{
					this._validationPromise.fulfill();
				}

				this._validationResult = null;
				this._validationPromise = null;
			}
		};
	if(typeof(BX.Crm.EntityUserFieldManager.messages) === "undefined")
	{
		BX.Crm.EntityUserFieldManager.messages = {};
	}
	BX.Crm.EntityUserFieldManager.items = {};
	BX.Crm.EntityUserFieldManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityUserFieldManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}

if(typeof BX.Crm.EntityUserFieldLayoutLoader === "undefined")
{
	BX.Crm.EntityUserFieldLayoutLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._mode = BX.Crm.EntityEditorMode.view;
		this._enableBatchMode = true;
		this._owner = null;
		this._items = [];
	};
	BX.Crm.EntityUserFieldLayoutLoader.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._mode = BX.prop.getInteger(this._settings, "mode", BX.Crm.EntityEditorMode.view);
				this._enableBatchMode = BX.prop.getBoolean(this._settings, "enableBatchMode", true);
				this._owner = BX.prop.get(this._settings, "owner", null);
			},
			getId: function()
			{
				return this._id;
			},
			getOwner: function()
			{
				return this._owner;
			},
			addItem: function(item)
			{
				this._items.push(item);
			},
			run: function()
			{
				if(!this._enableBatchMode)
				{
					this.startRequest();
				}
			},
			runBatch: function()
			{
				if(this._enableBatchMode)
				{
					this.startRequest();
				}
			},
			startRequest: function()
			{
				if(this._items.length === 0)
				{
					return;
				}

				var fields = [];
				for(var i = 0, length = this._items.length; i < length; i++)
				{
					if(BX.prop.getString(this._items[i], "name", "") !== "")
					{
						fields.push(BX.prop.getObject(this._items[i], "field", {}));
					}
				}

				if(fields.length === 0)
				{
					return;
				}

				var data = { "FIELDS": fields, "FORM": this._id, "CONTEXT": "CRM_EDITOR" };

				if(this._mode === BX.Crm.EntityEditorMode.view)
				{
					BX.Main.UF.Manager.getView(data, BX.delegate(this.onRequestComplete, this));
				}
				else
				{
					BX.Main.UF.Manager.getEdit(data, BX.delegate(this.onRequestComplete, this));
				}
			},
			onRequestComplete: function(result)
			{
				for(var i = 0, length = this._items.length; i < length; i++)
				{
					var item = this._items[i];
					var name = BX.prop.getString(item, "name", "");
					var callback = BX.prop.getFunction(item, "callback", null);
					if(name !== "" && callback !== null)
					{
						callback(BX.prop.getObject(result, name, {}));
					}
				}
			}
		};
	BX.Crm.EntityUserFieldLayoutLoader.create = function(id, settings)
	{
		var self = new BX.Crm.EntityUserFieldLayoutLoader();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorUserFieldConfigurator === "undefined")
{
	BX.Crm.EntityEditorUserFieldConfigurator = function()
	{
		BX.Crm.EntityEditorUserFieldConfigurator.superclass.constructor.apply(this);
		this._field = null;
		this._typeId = "";
		this._isLocked = false;

		this._labelInput = null;
		this._saveButton = null;
		this._cancelButton = null;
		this._isTimeEnabledCheckBox = null;
		this._isRequiredCheckBox = null;
		this._isUserAccessCheckBox = null;
		this._isMultipleCheckBox = null;
		this._showAlwaysCheckBox = null;
		this._enumItemWrapper = null;
		this._enumItemContainer = null;
		this._enumButtonWrapper = null;
		this._optionWrapper = null;

		this._userSelector = null;

		this._enumItems = null;

		this._mandatoryConfigurator = null;
		this._visibilityConfigurator = null;
	};
	BX.extend(BX.Crm.EntityEditorUserFieldConfigurator, BX.Crm.EntityEditorControl);
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorUserFieldConfigurator.superclass.doInitialize.apply(this);
		this._field = BX.prop.get(this._settings, "field", null);
		if(this._field && !(this._field instanceof BX.Crm.EntityEditorUserField))
		{
			throw "EntityEditorUserFieldConfigurator. The 'field' param must be EntityEditorUserField.";
		}

		this._mandatoryConfigurator = BX.prop.get(this._settings, "mandatoryConfigurator", null);
		this._visibilityConfigurator = BX.prop.get(this._settings, "visibilityConfigurator", null);

		this._typeId = BX.prop.getString(this._settings, "typeId", "");
		this._enumItems = [];
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorUserFieldConfigurator.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			throw "EntityEditorUserFieldConfigurator. View mode is not supported by this control type.";
		}

		var isNew = this._field === null;

		var title = this.getMessage("labelField");
		var manager = this._editor.getUserFieldManager();
		var label = this._field ? this._field.getTitle() : manager.getDefaultFieldLabel(this._typeId);
		this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-new-fields" } });

		this._labelInput = BX.create("input",
			{
				attrs:
					{
						className: "crm-entity-widget-content-input",
						type: "text",
						value: label
					}
			}
		);

		this._saveButton = BX.create(
			"span",
			{
				props: { className: "ui-btn ui-btn-primary" },
				text: BX.message("CRM_EDITOR_SAVE"),
				events: {  click: BX.delegate(this.onSaveButtonClick, this) }
			}
		);
		this._cancelButton = BX.create(
			"span",
			{
				props: { className: "ui-btn ui-btn-light-border" },
				text: BX.message("CRM_EDITOR_CANCEL"),
				events: {  click: BX.delegate(this.onCancelButtonClick, this) }
			}
		);

		this._wrapper.appendChild(
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block" },
					children:
						[
							BX.create(
								"div",
								{
									props: { className: "crm-entity-widget-content-block-title" },
									children:
										[
											BX.create(
												"span",
												{
													attrs: { className: "crm-entity-widget-content-block-title-text" },
													text: title
												}
											)
										]
								}
							),
							BX.create(
								"div",
								{
									props: { className: "crm-entity-widget-content-block-inner" },
									children:
										[
											BX.create(
												"div",
												{
													props: { className: "crm-entity-widget-content-block-field-container" },
													children: [ this._labelInput ]
												}
											)
										]
								}
							)
						]
				}
			)
		);

		if(this._typeId === "enumeration")
		{
			this._wrapper.appendChild(
				BX.create("hr", { props: { className: "crm-entity-widget-hr" } })
			);

			this._enumItemWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block" }
				}
			);

			this._wrapper.appendChild(this._enumItemWrapper);
			this._enumItemWrapper.appendChild(
				BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-title" },
						children: [
							BX.create(
								"span",
								{
									attrs: { className: "crm-entity-widget-content-block-title-text" },
									text: this.getMessage("enumItems")
								}
							)
						]
					}
				)
			);

			this._enumItemContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner" } });
			this._enumItemWrapper.appendChild(this._enumItemContainer);

			this._enumButtonWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-add-field" } });
			this._enumItemWrapper.appendChild(this._enumButtonWrapper);

			this._enumButtonWrapper.appendChild(
				BX.create(
					"span",
					{
						props: { className: "crm-entity-widget-content-add-field" },
						events: { click: BX.delegate(this.onEnumerationItemAddButtonClick, this) },
						text: this.getMessage("add")
					}
				)
			);

			if(this._field)
			{
				var fieldInfo = this._field.getFieldInfo();
				var enums = BX.prop.getArray(fieldInfo, "ENUM", []);
				for(var i = 0, length = enums.length; i < length; i++)
				{
					this.createEnumerationItem(enums[i]);
				}
			}

			this.createEnumerationItem();
		}

		this._optionWrapper = BX.create(
			"div",
			{
				props: { className: "crm-entity-widget-content-block-inner" }
			}
		);
		this._wrapper.appendChild(
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block crm-entity-widget-content-block-checkbox" },
					children: [ this._optionWrapper ]
				}
			)
		);

		var flagCount = 0;
		if(isNew && (this._typeId === "datetime" || this._typeId === "date"))
		{
			this._isTimeEnabledCheckBox = this.createOption({ caption: this.getMessage("enableTime") });
			flagCount++;
		}

		//region Use timezone
		if(this._typeId === "datetime")
		{
			this._useTimezoneCheckBox = this.createOption(
				{ caption: BX.Crm.EntityEditorFieldConfigurator.messages['useTimezone']}
			);

			this._useTimezoneCheckBox.checked = isNew
				? false
				: (this._field.getFieldSettings().USE_TIMEZONE === 'Y');
			flagCount++;
		}
		//endregion

		if(this._typeId !== "boolean")
		{
			if(this._mandatoryConfigurator)
			{
				this._isRequiredCheckBox = this.createOption(
					{
						caption: this._mandatoryConfigurator.getTitle() + ":",
						labelSettings: { props: { className: "crm-entity-new-field-addiction-label" } },
						containerSettings: { style: { alignItems: "center" } },
						elements: this._mandatoryConfigurator.getButton().prepareLayout()
					}
				);

				this._isRequiredCheckBox.checked = (this._field && this._field.isRequired())
					|| this._mandatoryConfigurator.isCustomized();

				this._mandatoryConfigurator.setSwitchCheckBox(this._isRequiredCheckBox);
				this._mandatoryConfigurator.setLabel(this._isRequiredCheckBox.nextSibling);

				this._mandatoryConfigurator.setEnabled(this._isRequiredCheckBox.checked);
				this._mandatoryConfigurator.adjust();
			}
			else
			{
				this._isRequiredCheckBox = this.createOption({ caption: this.getMessage("isRequiredField") });
				this._isRequiredCheckBox.checked = this._field && this._field.isRequired();
			}

			flagCount++;

			if(isNew)
			{
				this._isMultipleCheckBox = this.createOption({ caption: this.getMessage("isMultipleField") });
				flagCount++;
			}
		}

		//region Show Always
		this._showAlwaysCheckBox = this.createOption(
			{ caption: this.getMessage("showAlways"), help: { code: "7046149" } }
		);
		this._showAlwaysCheckBox.checked = isNew
			? BX.prop.getBoolean(this._settings, "showAlways", true)
			: this._field.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
		flagCount++;
		//endregion

		//region Visibility configurator
		if (this._visibilityConfigurator) {
			this._visibilityUserFieldCheckbox = this.createOption(
				{
					caption: this._visibilityConfigurator.getTitle(),
					containerSettings: {style: {alignItems: "center"}},
					elements: this._visibilityConfigurator.getButton().prepareLayout(),
					wrapperClass: 'crm-entity-widget-content-block-field-container-block'
				});
			this._visibilityUserFieldCheckbox.checked = this._visibilityConfigurator.isCustomized();
			this._visibilityConfigurator.setSwitchCheckBox(this._visibilityUserFieldCheckbox);
			this._visibilityConfigurator.setEnabled(this._visibilityUserFieldCheckbox.checked);
			this._visibilityConfigurator.adjust();
			flagCount++;
		}
		//endregion

		if(flagCount > 0)
		{
			this._wrapper.appendChild(
				BX.create("hr", { props: { className: "crm-entity-widget-hr" } })
			);
		}

		this._wrapper.appendChild(
			BX.create(
				"div",
				{
					props: {
						className: "crm-entity-widget-content-block-new-fields-btn-container"
					},
					children: [
						this._saveButton,
						this._cancelButton
					]
				}
			)
		);

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._wrapper = BX.remove(this._wrapper);

		this._labelInput = null;
		this._saveButton = null;
		this._cancelButton = null;
		this._isTimeEnabledCheckBox = null;
		this._isRequiredCheckBox = null;
		this._isMultipleCheckBox = null;
		this._showAlwaysCheckBox = null;
		this._enumItemWrapper = null;
		this._enumButtonWrapper = null;
		this._enumItemContainer = null;
		this._optionWrapper = null;

		this._enumItems = [];

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.onEnumerationItemAddButtonClick = function(e)
	{
		this.createEnumerationItem().focus();
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.createEnumerationItem = function(data)
	{
		var item = BX.Crm.EntityEditorUserFieldListItem.create(
			"",
			{
				configurator: this,
				container: this._enumItemContainer,
				data: data
			}
		);

		this._enumItems.push(item);
		item.layout();
		return item;
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.removeEnumerationItem = function(item)
	{
		for(var i = 0, length = this._enumItems.length; i < length; i++)
		{
			if(this._enumItems[i] === item)
			{
				this._enumItems[i].clearLayout();
				this._enumItems.splice(i, 1);
				break;
			}
		}
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.createOption = function(params)
	{
		var element = BX.create("input", { props: { type: "checkbox" } });
		var label = BX.create(
			"label",
			{ children: [ element, BX.create("span", { text: BX.prop.getString(params, "caption", "") }) ] }
		);

		var labelSettings = BX.prop.getObject(params, "labelSettings", null);
		if(labelSettings)
		{
			BX.adjust(label, labelSettings);
		}

		var help = BX.prop.getObject(params, "help", null);
		if(help)
		{
			var helpLink = BX.create("a", { props: { className: "crm-entity-new-field-helper-icon" } });

			var helpUrl = BX.prop.getString(help, "url", "");
			if(helpUrl !== "")
			{
				helpLink.href = helpUrl;
				helpLink.target = "_blank";
			}
			else
			{
				helpLink.href = "#";
				BX.bind(
					helpLink,
					"click",
					function(e) {
						window.top.BX.Helper.show("redirect=detail&code=" + BX.prop.getString(help, "code", ""));
						e.preventDefault();
					}
				);
			}
			label.appendChild(helpLink);
		}

		var childElements = [ label ];
		var elements = BX.prop.getArray(params, "elements", []);
		for(var i = 0, length = elements.length; i < length; i++)
		{
			childElements.push(elements[i]);
		}

		var wrapperClass = BX.prop.getString(params, "wrapperClass", '');
		var container = BX.create(
			"div",
			{
				props: { className: "crm-entity-widget-content-block-field-container "+wrapperClass },
				children: childElements
			}
		);

		var containerSettings = BX.prop.getObject(params, "containerSettings", null);
		if(containerSettings)
		{
			BX.adjust(container, containerSettings);
		}
		this._optionWrapper.appendChild(container);

		return element;
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.onSaveButtonClick = function(e)
	{
		if(this._isLocked)
		{
			return;
		}

		if(this._mandatoryConfigurator)
		{
			if(this._mandatoryConfigurator.isChanged())
			{
				this._mandatoryConfigurator.acceptChanges();
			}
			this._mandatoryConfigurator.close();
		}

		var params =
			{
				typeId: this._typeId,
				label: this._labelInput.value
			};

		if(this._field)
		{
			params["field"] = this._field;
			if(this._isRequiredCheckBox)
			{
				params["mandatory"] = this._isRequiredCheckBox.checked;
			}
		}
		else
		{
			if(this._typeId === "boolean")
			{
				params["multiple"] = false;
			}
			else
			{
				if(this._isMultipleCheckBox)
				{
					params["multiple"] = this._isMultipleCheckBox.checked;
				}
				params["mandatory"] = this._isRequiredCheckBox.checked;
			}

			if(this._typeId === "datetime")
			{
				params["enableTime"] = this._isTimeEnabledCheckBox.checked;
			}
		}

		if(this._typeId === "datetime")
		{
			params["settings"] = [];
			params["settings"].USE_TIMEZONE = (this._useTimezoneCheckBox.checked ? 'Y' : 'N');
		}

		if(this._typeId === "enumeration")
		{
			params["enumeration"] = [];
			var hashes = [];
			for(var i = 0, length = this._enumItems.length; i < length; i++)
			{
				var enumData = this._enumItems[i].prepareData();
				if(!enumData)
				{
					continue;
				}

				var hash = BX.util.hashCode(enumData["VALUE"]);
				if(BX.util.in_array(hash, hashes))
				{
					continue;
				}

				hashes.push(hash);
				enumData["SORT"] = (params["enumeration"].length + 1) * 100;
				params["enumeration"].push(enumData);
			}
		}

		params["showAlways"] = this._showAlwaysCheckBox.checked;

		BX.onCustomEvent(this, "onSave", [ this, params]);
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.onCancelButtonClick = function(e)
	{
		if(this._isLocked)
		{
			return;
		}

		var params = { typeId: this._typeId };
		if(this._field)
		{
			params["field"] = this._field;
		}

		BX.onCustomEvent(this, "onCancel", [ this, params ]);
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.setLocked = function(locked)
	{
		locked = !!locked;
		if(this._isLocked === locked)
		{
			return;
		}

		this._isLocked = locked;
		if(this._isLocked)
		{
			BX.addClass(this._saveButton, "ui-btn-clock");
		}
		else
		{
			BX.removeClass(this._saveButton, "ui-btn-clock");
		}
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.getField = function()
	{
		return this._field;
	};
	if(typeof(BX.Crm.EntityEditorUserFieldConfigurator.messages) === "undefined")
	{
		BX.Crm.EntityEditorUserFieldConfigurator.messages = {};
	}
	BX.Crm.EntityEditorUserFieldConfigurator.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserFieldConfigurator();
		self.initialize(id, settings);
		return self;
	};
	BX.onCustomEvent(window, "BX.Crm.EntityEditorUserFieldConfigurator:onDefine");
}

if(typeof BX.Crm.EntityEditorUserFieldListItem === "undefined")
{
	BX.Crm.EntityEditorUserFieldListItem = function()
	{
		this._id = "";
		this._settings = null;
		this._data = null;
		this._configurator = null;
		this._container = null;
		this._labelInput = null;

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorUserFieldListItem.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = BX.type.isPlainObject(settings) ? settings : {};

				this._data = BX.prop.getObject(this._settings, "data", {});
				this._configurator = BX.prop.get(this._settings, "configurator");
				this._container = BX.prop.getElementNode(this._settings, "container");
			},
			layout: function()
			{
				if(this._hasLayout)
				{
					return;
				}

				this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container" } });

				this._labelInput = BX.create(
					"input",
					{
						props:
							{
								className: "crm-entity-widget-content-input",
								type: "input",
								value: BX.prop.getString(this._data, "VALUE", "")
							}
					}
				);

				this._wrapper.appendChild(this._labelInput);
				this._wrapper.appendChild(
					BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-content-remove-block" },
							events: { click: BX.delegate(this.onDeleteButtonClick, this) }
						}
					)
				);

				var anchor = BX.prop.getElementNode(this._settings, "anchor");
				if(anchor)
				{
					this._container.insertBefore(this._wrapper, anchor);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}

				this._hasLayout = true;
			},
			clearLayout: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				this._wrapper = BX.remove(this._wrapper);
				this._hasLayout = false;
			},
			focus: function()
			{
				if(this._labelInput)
				{
					this._labelInput.focus();
				}
			},
			prepareData: function()
			{
				var value = this._labelInput ? BX.util.trim(this._labelInput.value) : "";
				if(value === "")
				{
					return null;
				}

				var data = { "VALUE": value };
				var id = BX.prop.getInteger(this._data, "ID", 0);
				if(id > 0)
				{
					data["ID"] = id;
				}

				var xmlId = BX.prop.getString(this._data, "XML_ID", "");
				if(id > 0)
				{
					data["XML_ID"] = xmlId;
				}

				return data;
			},
			onDeleteButtonClick: function(e)
			{
				this._configurator.removeEnumerationItem(this);
			}
		};
	BX.Crm.EntityEditorUserFieldListItem.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserFieldListItem();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorUserField === "undefined")
{
	BX.Crm.EntityEditorUserField = function()
	{
		BX.Crm.EntityEditorUserField.superclass.constructor.apply(this);
		this._innerWrapper = null;

		this._isLoaded = false;
		this._focusOnLoad = false;
	};

	BX.extend(BX.Crm.EntityEditorUserField, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorUserField.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorUserField.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorUserField.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorUserField.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorUserField.superclass.doInitialize.apply(this);
		this._manager = this._editor.getUserFieldManager();
	};
	BX.Crm.EntityEditorUserField.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorUserField.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorUserField.prototype.getOptions = function()
	{
		return this._schemeElement.getDataParam("options", {});
	};
	BX.Crm.EntityEditorUserField.prototype.getFieldInfo = function()
	{
		return this._schemeElement.getDataParam("fieldInfo", {});
	};
	BX.Crm.EntityEditorUserField.prototype.getFieldType = function()
	{
		return BX.prop.getString(this.getFieldInfo(), "USER_TYPE_ID", "");
	};
	BX.Crm.EntityEditorUserField.prototype.getFieldSettings = function()
	{
		return BX.prop.getObject(this.getFieldInfo(), "SETTINGS", {});
	};
	BX.Crm.EntityEditorUserField.prototype.isMultiple = function()
	{
		return BX.prop.getString(this.getFieldInfo(), "MULTIPLE", "N") === "Y";
	};
	BX.Crm.EntityEditorUserField.prototype.getEntityValueId = function()
	{
		return BX.prop.getString(this.getFieldInfo(), "ENTITY_VALUE_ID", "");
	};
	BX.Crm.EntityEditorUserField.prototype.getFieldValue = function()
	{
		var fieldData = this.getValue();
		var value = BX.prop.getArray(fieldData, "VALUE", null);
		if(value === null)
		{
			value = BX.prop.getString(fieldData, "VALUE", "");
		}
		return value;
	};
	BX.Crm.EntityEditorUserField.prototype.getFieldSignature = function()
	{
		return BX.prop.getString(this.getValue(), "SIGNATURE", "");
	};
	BX.Crm.EntityEditorUserField.prototype.isTitleEnabled = function()
	{
		var info = this.getFieldInfo();
		var typeName = BX.prop.getString(info, "USER_TYPE_ID", "");

		if(typeName !== 'boolean')
		{
			return true;
		}

		//Disable title for checkboxes only.
		return BX.prop.getString(BX.prop.getObject(info, "SETTINGS", {}), "DISPLAY", "") !== "CHECKBOX";
	};
	BX.Crm.EntityEditorUserField.prototype.getFieldNode = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorUserField.prototype.doGetEditPriority = function()
	{
		return (BX.prop.get(BX.prop.getObject(this.getFieldInfo(), "SETTINGS"), "DEFAULT_VALUE")
				? BX.Crm.EntityEditorPriority.high
				: BX.Crm.EntityEditorPriority.normal
		);
	};
	BX.Crm.EntityEditorUserField.prototype.checkIfNotEmpty = function(value)
	{
		if(BX.prop.getBoolean(value, "IS_EMPTY", false))
		{
			return false;
		}

		var fieldValue;
		if(this.getFieldType() === BX.Crm.EntityUserFieldType.boolean)
		{
			fieldValue = BX.prop.getString(value, "VALUE", "");
			return fieldValue !== "";
		}

		fieldValue = BX.prop.getArray(value, "VALUE", null);
		if(fieldValue === null)
		{
			fieldValue = BX.prop.getString(value, "VALUE", "");
		}
		return BX.type.isArray(fieldValue) ? fieldValue.length > 0 : fieldValue !== "";
	};
	BX.Crm.EntityEditorUserField.prototype.getValue = function(defaultValue)
	{
		if(defaultValue === undefined)
		{
			defaultValue = null;
		}

		if(!this._model)
		{
			return defaultValue;
		}

		return this._model.getField(this.getName(), defaultValue);
	};
	BX.Crm.EntityEditorUserField.prototype.hasContentToDisplay = function()
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			return true;
		}
		return this.checkIfNotEmpty(this.getValue());
	};
	BX.Crm.EntityEditorUserField.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var name = this.getName();
		var title = this.getTitle();

		var fieldInfo = this.getFieldInfo();
		var fieldData = this.getValue();

		var signature = BX.prop.getString(fieldData, "SIGNATURE", "");

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var fieldType = this.getFieldType();
		if(fieldType === BX.Crm.EntityUserFieldType.string)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-text");
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.integer || fieldType === BX.Crm.EntityUserFieldType.double)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-number");
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.money)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-money");
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.date || fieldType === BX.Crm.EntityUserFieldType.datetime)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-date");
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.boolean)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-checkbox");
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.enumeration)
		{
			BX.addClass(
				this._wrapper,
				this.isMultiple()
					? "crm-entity-widget-content-block-field-custom-multiselect"
					: "crm-entity-widget-content-block-field-custom-select"
			);
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.file)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-file");
		}
		else if(fieldType === BX.Crm.EntityUserFieldType.url)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-custom-link");
		}

		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			if(this.isTitleEnabled())
			{
				this._wrapper.appendChild(this.createTitleNode(title));
			}

			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner" }
				}
			);
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner" }
				}
			);
		}
		this._wrapper.appendChild(this._innerWrapper);

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		//It is strongly required to append wrapper to container before "setupContentHtml" will be called otherwise user field initialization will fail.
		this.registerLayout(options);

		if(this.hasContentToDisplay())
		{
			var html = BX.prop.getString(options, "html", "");
			if(html === "")
			{
				//Try get preloaded HTML
				html = BX.prop.getString(
					BX.prop.getObject(fieldData, "HTML", {}),
					BX.Crm.EntityEditorMode.getName(this._mode).toUpperCase(),
					""
				);

			}
			if(html !== "")
			{
				this.setupContentHtml(html);
				this._hasLayout = true;
			}
			else
			{
				this._isLoaded = false;

				var loader = null;
				//Ignore group loader for single edit mode
				if(!this.isInSingleEditMode())
				{
					loader = BX.prop.get(options, "userFieldLoader", null);
				}

				if(!loader)
				{
					loader = BX.Crm.EntityUserFieldLayoutLoader.create(
						this._id,
						{ mode: this._mode, enableBatchMode: false }
					);
				}


				var fieldParams = BX.clone(fieldInfo);
				fieldParams["SIGNATURE"] = signature;
				if(fieldType === BX.Crm.EntityUserFieldType.file && BX.type.isObject(fieldParams["ADDITIONAL"]))
				{
					var ownerToken = BX.prop.getString(
						BX.prop.getObject(fieldData, "EXTRAS", {}),
						"OWNER_TOKEN",
						""
					);
					if(ownerToken !== "")
					{
						fieldParams["ADDITIONAL"]["URL_TEMPLATE"] += "&owner_token=" + encodeURIComponent(ownerToken);
					}
				}
				if(this.checkIfNotEmpty(fieldData))
				{
					var value = BX.prop.getArray(fieldData, "VALUE", null);
					if(value === null)
					{
						value = BX.prop.getString(fieldData, "VALUE", "");
					}
					fieldParams["VALUE"] = value;
				}

				this.adjustFieldParams(fieldParams, true);
				loader.addItem(
					{
						name: name,
						field: fieldParams,
						callback: BX.delegate(this.onLayoutLoaded, this)
					}
				);
				loader.run();
			}
		}
		else
		{
			this._innerWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
			this._hasLayout = true;
		}
	};
	BX.Crm.EntityEditorUserField.prototype.doRegisterLayout = function()
	{
	};
	BX.Crm.EntityEditorUserField.prototype.adjustFieldParams = function(fieldParams, isLayoutContext)
	{
		var fieldType = this.getFieldType();
		if(fieldType === BX.Crm.EntityUserFieldType.boolean)
		{
			//HACK: Overriding original label for boolean field
			if(!BX.type.isPlainObject(fieldParams["SETTINGS"]))
			{
				fieldParams["SETTINGS"] = {};
			}
			fieldParams["SETTINGS"]["LABEL_CHECKBOX"] = this.getTitle();
		}

		//HACK: We have to assign fake ENTITY_VALUE_ID for render predefined value of new entity
		if(isLayoutContext
			&& typeof fieldParams["VALUE"] !== "undefined"
			&& this._mode === BX.Crm.EntityEditorMode.edit
			&& BX.prop.getInteger(fieldParams, "ENTITY_VALUE_ID") <= 0
		)
		{
			fieldParams["ENTITY_VALUE_ID"] = 1;
		}

	};
	BX.Crm.EntityEditorUserField.prototype.doClearLayout = function(options)
	{
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorUserField.prototype.validate = function()
	{
		return true;
	};
	BX.Crm.EntityEditorUserField.prototype.save = function()
	{
	};
	BX.Crm.EntityEditorUserField.prototype.focus = function()
	{
		if(this._mode !== BX.Crm.EntityEditorMode.edit)
		{
			return;
		}

		if(this._isLoaded)
		{
			this.doFocus();
		}
		else
		{
			this._focusOnLoad = true;
		}
	};
	BX.Crm.EntityEditorUserField.prototype.doFocus = function()
	{
		BX.Main.UF.Factory.focus(this.getName());
	};
	BX.Crm.EntityEditorUserField.prototype.setupContentHtml = function(html)
	{
		if(this._innerWrapper)
		{
			//console.log("setupContentHtml: %s->%s->%s", this._editor.getId(), this._id, BX.Crm.EntityEditorMode.getName(this._mode));

			BX.html(this._innerWrapper, html).then(
				function()
				{
					this.onLayoutSuccess();

					this._isLoaded = true;
					if(this._focusOnLoad === true)
					{
						this.doFocus();
						this._focusOnLoad = false;
					}
				}.bind(this)
			);
		}
	};
	BX.Crm.EntityEditorUserField.prototype.doSetActive = function()
	{
		//We can't call this._manager.registerActiveField. We have to wait field layout load(see onLayoutSuccess)
		if(!this._isActive)
		{
			this._manager.unregisterActiveField(this);
		}
	};
	BX.Crm.EntityEditorUserField.prototype.rollback = function()
	{
		this._manager.unregisterActiveField(this);
	};
	BX.Crm.EntityEditorUserField.prototype.onLayoutSuccess = function()
	{
		if(this._isActive)
		{
			this._manager.registerActiveField(this);
		}

		//Add Change Listener after timeout for prevent markAsChanged call in process of field initialization.
		window.setTimeout(
			function(){
				BX.bindDelegate(
					this._innerWrapper,
					"bxchange",
					{ tag: [ "input", "select", "textarea" ] },
					this._changeHandler
				);
			}.bind(this),
			200
		);

		//HACK: Try to resolve employee change button
		var fieldType = this.getFieldType();
		if(fieldType === BX.Crm.EntityUserFieldType.employee)
		{
			var button = this._innerWrapper.querySelector('.feed-add-destination-link');
			if(button)
			{
				BX.bind(button, "click", BX.delegate(this.onEmployeeSelectorOpen, this));
			}
		}

		//HACK: Mark empty boolean field as changed because of default value
		if(fieldType === BX.Crm.EntityUserFieldType.boolean)
		{
			if(this._mode === BX.Crm.EntityEditorMode.edit && !this.checkIfNotEmpty(this.getValue()))
			{
				this.markAsChanged();
			}
		}

		//Field content is added successfully. Layout is ready.
		if(!this._hasLayout)
		{
			this._hasLayout = true;
		}

		// Handler could be called by UF to trigger _changeHandler in complicated cases
		BX.removeCustomEvent(window, "onCrmEntityEditorUserFieldExternalChanged", BX.proxy(this.userFieldExternalChangedHandler, this));
		BX.addCustomEvent(window, "onCrmEntityEditorUserFieldExternalChanged", BX.proxy(this.userFieldExternalChangedHandler, this));

		BX.removeCustomEvent(window, "onCrmEntityEditorUserFieldSetValidator", BX.proxy(this.userFieldSetValidatorHandler, this));
		BX.addCustomEvent(window, "onCrmEntityEditorUserFieldSetValidator", BX.proxy(this.userFieldSetValidatorHandler, this));
	};
	//region Context Menu
	BX.Crm.EntityEditorUserField.prototype.processContextMenuCommand = function(e, command)
	{
		if(command === "moveAddrToRequisite")
		{
			this.showUfAddrConverterPopup();
		}

		BX.Crm.EntityEditorUserField.superclass.processContextMenuCommand.apply(this, [e, command]);
	};
	BX.Crm.EntityEditorUserField.prototype.showUfAddrConverterPopup = function()
	{
		var fieldInfo = this.getFieldInfo();

		var popupId = "crmUfAddrConvPopup_" + fieldInfo["ENTITY_ID"] + "_" +
			fieldInfo["ENTITY_VALUE_ID"] + "_" + fieldInfo["FIELD"];
		var popupContentHtml = this.getMessage("moveAddrToRequisiteHtml");
		var bindElement = window.body/*this.getWrapper()*/;
		var wrapperNode = this.getWrapper();

		var popup  = BX.Main.PopupManager.create(
			popupId,
			bindElement,
			{
				cacheable: false,
				closeIcon: true,
				offsetLeft: 15,
				lightShadow: true,
				overlay: true,
				titleBar: this.getMessage("moveAddrToRequisite"),
				draggable: true,
				/*autoHide: true,*/
				closeByEsc: true,
				/*bindOptions: { forceBindPosition: false },*/
				maxHeight: window.innerHeight - 50,
				width: wrapperNode.clientWidth/*width: bindElement.clientWidth*/,
				content: popupContentHtml,
				buttons: [
					new BX.UI.Button({
						text: this.getMessage("moveAddrToRequisiteBtnStart"),
						className: "ui-btn ui-btn-primary",
						events:
							{
								click: function()
								{
									popup.close();
									var responseHandler = function(response)
									{
										var status = BX.prop.getString(response, "status", "");
										var data = BX.prop.getObject(response, "data", {});
										var messages = [];
										var errors;
										var i;

										if (status === "error")
										{
											errors = BX.prop.getArray(response, "errors", []);
											for (i = 0; i < errors.length; i++)
											{
												messages.push(BX.prop.getString(errors[i], "message"));
											}
										}

										if (messages.length > 0)
										{
											BX.UI.Notification.Center.notify(
												{
													content: messages.join("<br>"),
													position: "top-center",
													autoHideDelay: 10000
												}
											);
										}
										else
										{
											this.hide();
											BX.UI.Notification.Center.notify(
												{
													content: this.getMessage("moveAddrToRequisiteStartSuccess"),
													position: "top-center",
													autoHideDelay: 10000
												}
											);
										}
									}.bind(this);
									BX.ajax.runAction(
										'crm.requisite.converter.ufAddressConvert',
										{
											data: {
												entityTypeId: fieldInfo["ENTITY_ID"],
												fieldName: fieldInfo["FIELD"]
											}
										}
									).then(responseHandler, responseHandler);
								}.bind(this)
							}
					}),
					new BX.UI.Button({
						text: this.getMessage("moveAddrToRequisiteBtnCancel"),
						className: "ui-btn ui-btn-link",
						events:
							{
								click: function()
								{
									popup.close();
								}.bind(this)
							}
					})
				]
			}
		);
		popup.show();
	};
	BX.Crm.EntityEditorUserField.prototype.prepareContextMenuItems = function()
	{
		var results = BX.Crm.EntityEditorUserField.superclass.prepareContextMenuItems.apply(this);

		var options = this.getOptions();
		if (BX.type.isPlainObject(options)
			&& options.hasOwnProperty("canActivateUfAddressConverter")
			&& options["canActivateUfAddressConverter"] === "Y")
		{
			results.push({ value: "moveAddrToRequisite", text: this.getMessage("moveAddrToRequisite") });
		}

		return results;
	};
	//endregion Context Menu
	BX.Crm.EntityEditorUserField.prototype.userFieldExternalChangedHandler = function(fieldId)
	{
		if (fieldId == this._id && BX.type.isFunction(this._changeHandler))
		{
			this._changeHandler();
		}
	};
	BX.Crm.EntityEditorUserField.prototype.userFieldSetValidatorHandler = function(fieldId, callback)
	{
		if (fieldId == this._id && BX.type.isFunction(callback))
		{
			this.validate = callback;
		}
	};
	BX.Crm.EntityEditorUserField.prototype.onLayoutLoaded = function(result)
	{
		var html = BX.prop.getString(result, "HTML", "");
		if(html !== "")
		{
			this.setupContentHtml(html);
			this._hasLayout = true;
			this.raiseLayoutEvent();
		}
	};
	BX.Crm.EntityEditorUserField.prototype.onEmployeeSelectorOpen = function(e)
	{
		var button = BX.getEventTarget(e);
		if(!button)
		{
			return;
		}
	};
	BX.Crm.EntityEditorUserField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserField();
		self.initialize(id, settings);
		return self;
	}
	if(typeof(BX.Crm.EntityEditorUserField.messages) === "undefined")
	{
		BX.Crm.EntityEditorUserField.messages = {};
	}
}

//endregion
