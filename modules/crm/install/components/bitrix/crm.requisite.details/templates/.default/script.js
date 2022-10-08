BX.namespace("BX.Crm");
if (typeof BX.Crm.RequisiteDetailsManager === "undefined")
{
	BX.Crm.RequisiteDetailsManager = function()
	{
		this.needEmitCancelEvent = true;
		this.entityEditor = null;
	};

	BX.Crm.RequisiteDetailsManager.presetControlName = 'PRESET_ID';
	BX.Crm.RequisiteDetailsManager.autocompleteControlName = 'AUTOCOMPLETE';
	BX.Crm.RequisiteDetailsManager.bankDetailsControlName = 'BANK_DETAILS';
	BX.Crm.RequisiteDetailsManager.addressControlName = 'RQ_ADDR';

	BX.Crm.RequisiteDetailsManager.prototype = {
		initialize: function(settings)
		{
			this._settings = settings;
			this.entityEditor = this.getEntityEditor();
			this.prevPresetId = null;

			this.addEvents();
			if (this.getSetting("markPresetAsChanged", false))
			{
				this.markPresetAsChanged();
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getEntityEditor: function()
		{
			var entityEditor;
			var entityEditorId = this.getSetting("entityEditorId", "");
			if (BX.Type.isStringFilled(entityEditorId))
			{
				entityEditor = BX.UI.EntityEditor.get(entityEditorId);
				if (!entityEditor)
				{
					entityEditor = null;
				}
			}

			return entityEditor;
		},
		initializeDupControl: function()
		{
			var editor = this.getEntityEditor();
			if (!editor)
			{
				return;
			}
			var id = editor.getId().toLowerCase() + "_dup";
			var duplicateControlConfig = this.getSetting("duplicateControl", {});
			duplicateControlConfig['editor'] = editor;
			BX.Crm.RequisiteDetailsDupManager.create(
				id,
				duplicateControlConfig
			);
		},
		markPresetAsChanged: function()
		{
			if (!this.entityEditor)
			{
				return;
			}
			var presetControl = this.entityEditor.getControlById(BX.Crm.RequisiteDetailsManager.presetControlName);
			if (presetControl)
			{
				presetControl.markAsChanged();
			}
		},
		addNewBankDetails: function()
		{
			if (!this.entityEditor)
			{
				return;
			}
			var bankDetailsControl = this.entityEditor.getControlById(BX.Crm.RequisiteDetailsManager.bankDetailsControlName);
			if (bankDetailsControl)
			{
				bankDetailsControl.addEmptyValue({scrollToTop: true});
			}
		},
		addEvents: function()
		{
			BX.Event.EventEmitter.subscribe("BX.UI.EntityEditor:onCancel", this.onCancelEdit.bind(this));
			BX.Event.EventEmitter.subscribe("BX.UI.EntityEditor:onNothingChanged", this.onCancelEdit.bind(this));
			BX.Event.EventEmitter.subscribe("onEntityUpdate", this.onSave.bind(this));
			var slider = this.getSliderInstance();
			if (slider)
			{
				BX.addCustomEvent(slider, "SidePanel.Slider:onClose", this.onCloseSlider.bind(this));
				BX.addCustomEvent(slider, "SidePanel.Slider:onLoad",  this.onLoadSlider.bind(this));
			}

			if (this.entityEditor)
			{
				BX.Event.EventEmitter.subscribe(this.entityEditor, "onControlChanged", this.onControlChanged.bind(this));

				var nameControl = this.entityEditor.getControlById('NAME');
				var titleInput = document.querySelector('.ui-side-panel-wrap-title-input') || BX('pagetitle_input');
				if (nameControl && BX.Type.isDomNode(titleInput))
				{
					BX.bind(titleInput, 'change', function()
					{
						nameControl.markAsChanged();
					});
				}
			}
		},
		onControlChanged: function(event)
		{
			var control;
			var eventData = event.getData();
			if (BX.Type.isArray(eventData))
			{
				control = (eventData.length > 0) ? eventData[0] : null;
				if (control instanceof BX.UI.EntityEditorList
					&& control.getId() === BX.Crm.RequisiteDetailsManager.presetControlName)
				{
					if (this.prevPresetId === null)
					{
						this.prevPresetId = control.getValue();
					}
					if (this.prevPresetId !== control.getRuntimeValue())
					{
						this.onPresetChanged(control, this.prevPresetId);
						this.prevPresetId = control.getRuntimeValue();
					}
				}
				if (control instanceof BX.Crm.EntityEditorRequisiteAutocomplete
					&& control.getId() === BX.Crm.RequisiteDetailsManager.autocompleteControlName)
				{
					var autocompleteData = control.getAutocompleteData();
					var newFieldsValues = BX.prop.getObject(autocompleteData, 'fields', {});
					var newFieldsNames = Object.keys(newFieldsValues);

					var needSaveHiddenFields = false;
					var presetField = null;
					if (newFieldsValues.hasOwnProperty(BX.Crm.RequisiteDetailsManager.presetControlName))
					{
						presetField = this.getEntityEditor().getControlByIdRecursive(BX.Crm.RequisiteDetailsManager.presetControlName);
						if (presetField && presetField.getRuntimeValue() != newFieldsValues[BX.Crm.RequisiteDetailsManager.presetControlName])
						{
							this.prevPresetId = presetField.getRuntimeValue();
							needSaveHiddenFields = true;
						}
					}
					for (var i = 0; i < newFieldsNames.length; i++)
					{
						var controlName = newFieldsNames[i];
						var newFieldValue = newFieldsValues[controlName];
						var modifiedControl = this.getEntityEditor().getControlByIdRecursive(controlName);
						if (modifiedControl)
						{
							// addresses should be merged:
							if (controlName === BX.Crm.RequisiteDetailsManager.addressControlName)
							{
								var oldFieldValue = this.getEntityEditor()._model.getField(controlName);
								if (BX.Type.isPlainObject(oldFieldValue))
								{
									newFieldValue = BX.merge(oldFieldValue, newFieldValue);
								}
							}

							this.getEntityEditor()._model.setField(controlName, newFieldValue);
							modifiedControl.refreshLayout();

							if (controlName !== BX.Crm.RequisiteDetailsManager.presetControlName)
							{
								modifiedControl.markAsChanged();
							}
						}
						else if (needSaveHiddenFields && BX.Type.isStringFilled(newFieldValue))
						{
							this.getEntityEditor().getFormElement().appendChild(BX.create(
								"input",
								{
									props: {
										type: "hidden",
										name: controlName,
										value: newFieldValue
									}
								}
							));
						}
					}
					if (needSaveHiddenFields && presetField)
					{
						this.onPresetChanged(presetField, this.prevPresetId);
					}
				}
			}
		},
		onPresetChanged: function(control, prevPresetId)
		{
			var editor = control.getEditor();
			if (editor)
			{
				var userFieldManager = BX.UI.EntityUserFieldManager.getById(editor.getId());
				if (userFieldManager && typeof userFieldManager.setValidationEnabled === 'function')
				{
					userFieldManager.setValidationEnabled(false);
				}
				BX.Event.EventEmitter.subscribe("BX.UI.EntityEditor:onSave", this.onSaveNewPreset.bind(this, prevPresetId));
				editor.releaseAjaxForm();
				editor.save();
			}
		},
		onSaveNewPreset: function(prevPresetId)
		{
			var editor = this.getEntityEditor();
			if (editor)
			{
				var form = editor.getFormElement();
				if (form)
				{
					form.appendChild(
						BX.create(
							"input",
							{
								props: {
									type: "hidden",
									name: "PREV_PRESET_ID",
									value: prevPresetId
								}
							}
						)
					);
					form.appendChild(
						BX.create(
							"input",
							{
								props: {
									type: "hidden",
									name: "ACTION",
									value: "RELOAD"
								}
							}
						)
					);
					form.method = 'post';

					form.submit();
				}
			}
		},
		onCancelEdit: function(context)
		{
			for (var id in context.getData())
			{
				if (context.data[id].cancel === false) {
					context.data[id].cancel = true;
				}
			}
			this.closeSlider();
		},
		onCloseSlider: function()
		{
			if (!this.needEmitCancelEvent)
			{
				this.needEmitCancelEvent = true;
			}
			else
			{
				this.emitEvent('onCancelEdit');
			}
		},
		onLoadSlider: function()
		{
			if (this.getSetting("duplicateControlEnabled", false))
			{
				this.initializeDupControl();
			}
			if (this.getSetting("autoAddBankDetailsItem", false))
			{
				this.addNewBankDetails();
			}
		},
		onSave: function(event)
		{
			var eventData = event.getData();
			var entityData = BX.prop.getObject(eventData[0], 'entityData', {});

			this.emitEvent('onSave', {
				'requisiteData': BX.prop.getString(entityData, 'REQUISITE_DATA', ""),
				'requisiteDataSign': BX.prop.getString(entityData, 'REQUISITE_DATA_SIGN', ""),
				'presetId': this.getPresetId(),
				'presetCountryId': this.getPresetCountryId()
			});

			setTimeout(this.closeSliderSilently.bind(this), 10);
		},
		emitEvent: function(eventName, eventData)
		{
			if (!eventData)
			{
				eventData = {};
			}
			eventData.contextId = this.getContextId();

			BX.localStorage.set("BX.Crm.RequisiteSliderDetails:" + eventName, eventData, 10);
		},
		closeSlider: function()
		{
			var slider = this.getSliderInstance();
			if (slider)
			{
				slider.close();
			}
		},
		closeSliderSilently: function()
		{
			this.needEmitCancelEvent = false;
			this.closeSlider();
		},
		getContextId: function()
		{
			return this.getContextField('external_context_id');
		},
		getPresetId: function()
		{
			return this.getContextField('pid');
		},
		getPresetCountryId: function()
		{
			return this.getContextField('presetCountryId');
		},
		getContextField: function(fieldName)
		{
			if (!this.entityEditor)
			{
				return "";
			}
			var context = this.entityEditor.getContext();
			return BX.prop.getString(context, fieldName, "");
		},
		getSliderInstance: function()
		{
			if (typeof (top.BX.SidePanel) !== "undefined")
			{
				var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
				if (slider && slider.isOpen())
				{
					return slider;
				}
			}
			return null;
		}
	};

	BX.Crm.RequisiteDetailsManager.create = function(settings)
	{
		var self = new BX.Crm.RequisiteDetailsManager();
		self.initialize(settings);
		return self;
	}
}

if(typeof BX.Crm.RequisiteDetailsDupManager === "undefined")
{
	BX.Crm.RequisiteDetailsDupManager = function()
	{
		this._id = "";
		this._settings = null;

		this._serviceUrl = "";
		this._entityTypeName = "";
		this._form = null;
		this._controller = null;
	};
	BX.Crm.RequisiteDetailsDupManager.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
				this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
				this._entityId = BX.prop.getString(this._settings, "entityId", "");
				this._editor = BX.prop.get(this._settings, 'editor', null);
				this._requisiteFieldsMap = BX.prop.getArray(this._settings, 'requisiteFieldsMap', []);
				this._bankDetailsFieldsMap = BX.prop.getArray(this._settings, 'bankDetailsFieldsMap', []);
				this._requisiteId = BX.prop.getString(this._settings, 'requisiteId', '');
				this._presetId = BX.prop.getString(this._settings, 'presetId', '');
				this._form = this._editor.getFormElement();
				this._bankDetailsValues = {};

				this._controller = BX.Crm.RequisiteDetailsDupController.create(
					this._id,
					{
						serviceUrl: this._serviceUrl,
						entityTypeName: this._entityTypeName,
						entityId: this._entityId,
						form: this._form
					}
				);

				for (var control, i = 0; i < this._requisiteFieldsMap.length; i++)
				{
					control = this._editor.getControlByIdRecursive(this._requisiteFieldsMap[i]);
					if (control)
					{
						this.registerField(control.getId(),{
							'id': control.getId(),
							'field': control
						});
					}
				}
				BX.Event.EventEmitter.subscribe(this._editor, "onControlChanged", this.onChangeBankDetails.bind(this));
				this.processBankDetails();
			},
			onChangeBankDetails: function(event)
			{
				var eventData = event.getData();
				if (BX.Type.isArray(eventData))
				{
					var field = (eventData.length > 0) ? eventData[0] : null;
					if (field && field.getId() === BX.Crm.RequisiteDetailsManager.bankDetailsControlName)
					{
						this.processBankDetails();
					}
				}
			},
			processBankDetails: function()
			{
				var bankDetailsControl = this._editor.getControlById(BX.Crm.RequisiteDetailsManager.bankDetailsControlName);
				if (bankDetailsControl)
				{
					var editors = bankDetailsControl.getEditors();
					for (var id in editors)
					{
						if (!editors.hasOwnProperty(id))
						{
							continue;
						}
						if (this._bankDetailsValues[id])
						{
							continue;
						}
						this._bankDetailsValues[id] = true;
						for (var control, j = 0; j < this._bankDetailsFieldsMap.length; j++)
						{
							// controlId looks like "BANK_DETAILS[n0][RQ_ACC_NUM]"
							var controlId = BX.Crm.RequisiteDetailsManager.bankDetailsControlName + '[' + id + '][' +
								this._bankDetailsFieldsMap[j] + ']';
							control = editors[id].getControlByIdRecursive(controlId);
							if (control)
							{
								var fieldId = this._bankDetailsFieldsMap[j];
								this.registerField(fieldId,{
									'id': fieldId,
									'context': {
										BANK_DETAILS_ID: id
									},
									'field': control
								});
							}
						}
					}
				}
			},
			search: function()
			{
				this._controller.initialSearch();
			},
			getGroup: function(groupId)
			{
				return this._controller.getGroup(groupId);
			},
			ensureGroupRegistered: function(groupId, fieldData)
			{
				var group = this.getGroup(groupId);
				if(!group)
				{
					group = this._controller.registerGroup(groupId, {
						'parameterName': groupId,
						'requisiteId': this._requisiteId,
						'presetId': this._presetId,
						'context': fieldData.context,
						'groupType': 'requisite',
						'groupSummaryTitle': fieldData.field ? '"' + fieldData.field.getTitle() + '"' : ''
					});
				}
				return group;
			},
			registerField: function(groupId, fieldData)
			{
				var group = this.ensureGroupRegistered(groupId, fieldData);
				if(!group)
				{
					return null;
				}
				return group.registerField(fieldData);
			},
			unregisterField: function(groupId, field)
			{
				var group = this.getGroup(groupId);
				if(!group)
				{
					return;
				}

				group.unregisterField(field);
			}
		};
	BX.Crm.RequisiteDetailsDupManager.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteDetailsDupManager();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.RequisiteDetailsDupController === "undefined")
{
	BX.Crm.RequisiteDetailsDupController = function()
	{
		BX.Crm.RequisiteDetailsDupController.superclass.constructor.apply(this);
	};
	BX.ready(function()
	{
		BX.extend(BX.Crm.RequisiteDetailsDupController, BX.CrmDupController);
		BX.Crm.RequisiteDetailsDupController.prototype.registerGroup = function(groupId, settings)
		{
			var type = BX.type.isNotEmptyString(settings["groupType"]) ? settings["groupType"] : "";
			var ctrl = null;
			try
			{
				if(type === "requisite")
				{
					ctrl = BX.Crm.RequisiteDetailsSingleField.create(groupId, settings);
				}
			}
			catch(ex)
			{
			}

			if(ctrl)
			{
				this.addGroup(ctrl);
			}
			else
			{
				ctrl = BX.Crm.RequisiteDetailsDupController.superclass.registerGroup.apply(this, [groupId, settings]);
			}

			return ctrl;
		};
	});
	BX.Crm.RequisiteDetailsDupController.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteDetailsDupController();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.RequisiteDetailsSingleField === "undefined")
{
	BX.Crm.RequisiteDetailsSingleField = function()
	{
		BX.Crm.RequisiteDetailsSingleField.superclass.constructor.apply(this);
		this._requisiteId = null;
		this._presetId = null;
		this._context = null;
	};
	BX.ready(function()
	{
		BX.extend(BX.Crm.RequisiteDetailsSingleField, BX.CrmDupCtrlSingleField);

		BX.Crm.RequisiteDetailsSingleField.prototype._afterInitialize = function()
		{
			this._requisiteId = this.getSetting("requisiteId", "");
			this._presetId = this.getSetting("presetId", "");
			this._context = this.getSetting("context", null);
			this._paramName = this.getSetting("parameterName", "");
			if(!BX.type.isNotEmptyString(this._paramName))
			{
				throw "BX.Crm.RequisiteDetailsSingleField. Could not find parameter name.";
			}

			var field = this.getSetting("field", null);
			if(field)
			{
				this._field = this.addField(BX.Crm.RequisiteDetailsCtrlField.create(this._paramName, field));
			}
		};
		BX.Crm.RequisiteDetailsSingleField.prototype.registerField = function(settings)
		{
			var fieldId = BX.prop.getString(settings, "id", "");
			if(fieldId !== this._paramName)
			{
				return null;
			}

			var field = BX.prop.get(settings, "field", null);
			if(!field)
			{
				return null;
			}

			if(!this._field)
			{
				this._field = this.addField(BX.Crm.RequisiteDetailsCtrlField.create(this._paramName, field));
			}
			return this._field;
		};
		BX.Crm.RequisiteDetailsSingleField.prototype.prepareSearchParams = function()
		{
			var result = BX.Crm.RequisiteDetailsSingleField.superclass.prepareSearchParams.apply(this);
			if (result)
			{
				result.PRESET_ID = this._presetId;
				result.ID = this._requisiteId;
				if (BX.Type.isPlainObject(this._context))
				{
					result = BX.mergeEx(result, this._context);
				}
			}
			return result;
		};
	});
	BX.Crm.RequisiteDetailsSingleField.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteDetailsSingleField();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.RequisiteDetailsCtrlField === "undefined")
{
	BX.Crm.RequisiteDetailsCtrlField = function()
	{
		this._field = null;
		BX.Crm.RequisiteDetailsCtrlField.superclass.constructor.apply(this);
	};
	BX.ready(function()
	{
		BX.extend(BX.Crm.RequisiteDetailsCtrlField, BX.CrmDupCtrlField);
		BX.Crm.RequisiteDetailsCtrlField.prototype.initialize = function(id, field)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "BX.Crm.RequisiteDetailsCtrlField. Invalid parameter 'id': is not defined.";
			}
			this._id = id;

			if(!(field instanceof BX.UI.EntityEditorField))
			{
				throw "BX.Crm.RequisiteDetailsCtrlField. Invalid parameter 'field': not instance of BX.UI.EntityEditorField.";
			}
			this._field = field;
			this._value = field._input.value;

			BX.Event.EventEmitter.subscribe(this._field.getEditor(), "onControlChanged", this.onFieldChanged.bind(this));
			this._initialized = true;
		};
		BX.Crm.RequisiteDetailsCtrlField.prototype.onFieldChanged = function(event)
		{
			var eventData = event.getData();
			if (BX.Type.isArray(eventData))
			{
				var field = (eventData.length > 0) ? eventData[0] : null;
				if(field !== this._field || this._value === field._input.value)
				{
					return;
				}
				if(this._elementTimeoutId > 0)
				{
					window.clearTimeout(this._elementTimeoutId);
					this._elementTimeoutId = 0;
				}
				this._elementTimeoutId = window.setTimeout(this._elementTimeoutHandler, 1500);
			}
		};
		BX.Crm.RequisiteDetailsCtrlField.prototype.getElementTitle = function()
		{
			return this._field.getWrapper();
		};
		BX.Crm.RequisiteDetailsCtrlField.prototype.getValue = function()
		{
			return this._field._input.value;
		};
	});
	BX.Crm.RequisiteDetailsCtrlField.create = function(id, field)
	{
		var self = new BX.Crm.RequisiteDetailsCtrlField();
		self.initialize(id, field);
		return self;
	}
}