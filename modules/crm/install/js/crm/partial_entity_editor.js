BX.namespace("BX.Crm");
if(typeof BX.Crm.PartialEditorDialog === "undefined")
{
	BX.Crm.PartialEditorDialog = function()
	{
		this._id = "";
		this._settings = {};

		this._serviceUrl = "";
		this._entityTypeId = 0;
		this._entityTypeName = "";
		this._entityId = 0;
		this._stageId = '';
		this._fieldNames = null;
		this._html = null;
		this._presetValues = {};

		this._editor = null;
		this._wrapper = null;
		this._popup = null;
		this._buttons = null;

		this._isLocked = false;
		this._isController = false;

		this._notAvailableFieldsErrorTextWrapper = null;
		this._notAvailableFieldsErrorText = [];

		this._editorEventsBinded = false;
		this._entityUpdateSuccessHandler = BX.delegate(this.onEntityUpdateSuccess, this);
		this._entityUpdateFailureHandler = BX.delegate(this.onEntityUpdateFailure, this);
		this._entityValidationFailureHandler = BX.delegate(this.onEntityValidationFailure, this);
		this._entityAjaxFormSubmitErrorHandler = BX.delegate(this.onEntityAjaxFormSubmitError, this);
	};
	BX.Crm.PartialEditorDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			if(this._entityTypeId !== BX.CrmEntityType.enumeration.undefined)
			{
				this._entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);
			}
			else
			{
				this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
				this._entityTypeId = BX.CrmEntityType.resolveId(this._entityTypeName);
			}

			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
			this._fieldNames = BX.prop.getArray(this._settings, "fieldNames", []);

			this._presetValues = BX.prop.getObject(this._settings, "presetValues", {});

			this._isAccepted = false;
			this._isController = BX.prop.getBoolean(this._settings, 'isController', false);
			this._stageId = BX.prop.getString(this._settings, 'stageId', '');
		},
		getSetting: function(name, defaultValue)
		{
			return BX.prop.get(this._settings, name, defaultValue);
		},
		getId: function()
		{
			return this._id;
		},
		getEditorId: function()
		{
			//return this._editorId;
			return this._entityTypeName.toLowerCase() + "_" + this._entityId + "_partial_editor";
		},
		isLoaded: function()
		{
			return this._html !== null;
		},
		getServiceUrl: function()
		{
			return BX.prop.getString(BX.Crm.PartialEditorDialog.entityEditorUrls, this._entityTypeName, "");
		},
		load: function()
		{
			if(this._isController)
			{
				BX.ajax.runAction('crm.api.item.getEditor', {
					data: {
						entityTypeId: this._entityTypeId,
						id: this._entityId,
						stageId: this._stageId,
						guid: this.getEditorId(),
						configId: this.getEditorId(),
						params: {
							forceDefaultConfig: 'Y',
							requiredFields: this._fieldNames,
							title: BX.prop.getString(this._settings, "title", ""),
						}
					}
				})
				.then(
					function(response) {

						if(typeof(BX.Crm.EntityEditor) !== "undefined")
						{
							var editor = BX.Crm.EntityEditor.get(this.getEditorId());
							if(editor)
							{
								editor.release();
							}
						}

						this.createPopup();
						this.bindEntityEditorInitEvent();
						BX.Runtime.html(this._editorNode, response.data.html).then(function() {
							this._html = response.data.html;
							this.innerOpen();
						}.bind(this));
					}.bind(this))
				.catch(function(response) {
					BX.onCustomEvent(
						window,
						"Crm.PartialEditorDialog.Error",
						[
							this,
							{
								errors: response.errors
							}
						]
					);
				}.bind(this));
			}
			else
			{
				BX.ajax.post(
					this.getServiceUrl(),
					{
						ACTION: "PREPARE_EDITOR_HTML",
						ACTION_ENTITY_TYPE_NAME: this._entityTypeName,
						ACTION_ENTITY_ID: this._entityId,
						GUID: this.getEditorId(),
						FIELDS: this._fieldNames,
						PARAMS: {},
						CONTEXT: BX.prop.getObject(this._settings, "context", {}),
						TITLE: BX.prop.getString(this._settings, "title", "No title"),
						FORCE_DEFAULT_CONFIG: "Y",
						ENABLE_CONFIG_SCOPE_TOGGLE: "N",
						ENABLE_CONFIGURATION_UPDATE: "N",
						ENABLE_FIELDS_CONTEXT_MENU: "N",
						IS_EMBEDDED: "Y"
					},
					function(result)
					{
						if(typeof(BX.Crm.EntityEditor) !== "undefined")
						{
							var editor = BX.Crm.EntityEditor.get(this.getEditorId());
							if(editor)
							{
								editor.release();
							}
						}

						this._html = result;
						this.createPopup();
						this.bindEntityEditorInitEvent();
						this.innerOpen();
					}.bind(this)
				);
			}
		},
		open: function()
		{
			if(!this.isLoaded())
			{
				this.load();
			}
			else
			{
				this.innerOpen();
			}
		},
		createPopup: function()
		{
			this._popup = new BX.PopupWindow(
				this._id,
				null,
				{
					autoHide: false,
					draggable: false,
					closeByEsc: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: BX.prop.getInteger(this._settings, "zIndex", 0),
					bindOptions: { forceBindPosition: true },
					content: this.prepareContent(),
					events:
						{
							onPopupShow: BX.delegate(this.onPopupShow, this),
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
		},
		innerOpen: function()
		{
			if(!this.isLoaded() || !this._popup)
			{
				return;
			}

			this._popup.show();
			this._isAccepted = false;
		},
		close: function()
		{
			if(this._popup)
			{
				this._popup.close();
			}
		},
		isOpen: function()
		{
			return this._popup && this._popup.isShown();
		},
		prepareContent: function()
		{
			this._editorNode = BX.create("div");
			this._wrapper = BX.create("div",
				{
					props: { id: this._id + "_wrapper"/*, className: "crm-entity-popup-fill-required-fields"*/ },
					style: { width: "500px" },
					children: [
						this._editorNode
					]
				}
			);

			this._editorNode.innerHTML = this._html;

			this._notAvailableFieldsErrorTextWrapper = BX.create('div');
			this._wrapper.appendChild(this._notAvailableFieldsErrorTextWrapper);

			var buttonWrapper = BX.create("div",
				{
					props: { className: "crm-entity-popup-fill-required-fields-btns" }
				}
			);
			this._wrapper.appendChild(buttonWrapper);

			this._buttons = {};
			this._buttons[BX.Crm.DialogButtonType.names.accept] = BX.create(
				"span",
				{
					props: { className: "ui-btn ui-btn-primary" },
					text: BX.message("JS_CORE_WINDOW_SAVE"),
					events: {  click: BX.delegate(this.onSaveButtonClick, this) }
				}
			);
			this._buttons[BX.Crm.DialogButtonType.names.cancel] = BX.create(
				"span",
				{
					props: { className: "ui-btn ui-btn-link" },
					text: BX.message("JS_CORE_WINDOW_CANCEL"),
					events: {  click: BX.delegate(this.onCancelButtonClick, this) }
				}
			);

			buttonWrapper.appendChild(this._buttons[BX.Crm.DialogButtonType.names.accept]);
			buttonWrapper.appendChild(this._buttons[BX.Crm.DialogButtonType.names.cancel]);

			return this._wrapper;
		},
		onSaveButtonClick: function(e)
		{
			if(this._isLocked)
			{
				return;
			}
			this._isLocked = true;
			this._isAccepted = true;

			if(!this.getEditor())
			{
				return;
			}

			BX.addClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");

			this.bindEditorEvents();

			this.getEditor().save();
		},
		onCancelButtonClick: function(e)
		{
			if(this._isLocked)
			{
				return;
			}
			this._isLocked = true;
			this._isAccepted = false;

			if(this._popup)
			{
				this._popup.close();
			}
		},
		onEntityUpdateSuccess: function(eventParams)
		{
			if(this._entityTypeId === BX.prop.getInteger(eventParams, "entityTypeId", 0)
				&& this._entityId === BX.prop.getInteger(eventParams, "entityId", 0)
			)
			{
				this._isLocked = false;

				BX.removeClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");

				this.unbindEditorEvents();

				if(this._popup)
				{
					this._popup.close();
				}

				BX.onCustomEvent(
					window,
					"Crm.PartialEditorDialog.Close",
					[
						this,
						{
							entityTypeId: this._entityTypeId,
							entityTypeName: BX.CrmEntityType.resolveName(this._entityTypeId),
							entityId: this._entityId,
							entityData: BX.prop.getObject(eventParams, "entityData", null),
							bid: BX.Crm.DialogButtonType.accept,
							isCancelled: false,
							stageId: this._stageId
						}
					]
				);
			}
		},
		onEntityUpdateFailure: function(eventParams)
		{
			this._notAvailableFieldsErrorText = [];
			this._notAvailableFieldsErrorTextWrapper.innerHTML = '';
			BX.removeClass(
				this._notAvailableFieldsErrorTextWrapper,
				'crm-entity-widget-content-error-text'
			);

			if (eventParams.checkErrors)
			{
				var fieldNames = this.getNotAccessibleFieldNames(
					Object.keys(eventParams.checkErrors)
				);

				if (fieldNames.length)
				{
					fieldNames.forEach(function(fieldName){
						if (this.isFieldAvailableForCurrentUser(fieldName))
						{
							this._notAvailableFieldsErrorText.push(
								eventParams.checkErrors[fieldName]
							);
						}
					}, this);

					if (this._notAvailableFieldsErrorText.length)
					{
						BX.addClass(
							this._notAvailableFieldsErrorTextWrapper,
							'crm-entity-widget-content-error-text'
						);

						this._notAvailableFieldsErrorTextWrapper.innerHTML =
							BX.Crm.PartialEditorDialog.messages.entityHasInaccessibleFields
							+ ' '
							+ this._notAvailableFieldsErrorText.join('; ');
					}
				}
			}
			else if (eventParams.error && (typeof eventParams.error === 'string' || eventParams.error instanceof String))
			{
				BX.addClass(
					this._notAvailableFieldsErrorTextWrapper,
					'crm-entity-widget-content-error-text'
				);

				this._notAvailableFieldsErrorTextWrapper.innerText = eventParams.error;
			}

			if(this._entityTypeId === BX.prop.getInteger(eventParams, "entityTypeId", 0)
				&& this._entityId === BX.prop.getInteger(eventParams, "entityId", 0)
			)
			{
				this._isLocked = false;

				BX.removeClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");

				this.unbindEditorEvents();
			}
		},
		onEntityValidationFailure: function(sender, eventArgs)
		{
			if(this.getEditor() !== sender)
			{
				return;
			}

			this._isLocked = false;

			BX.removeClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");

			this.unbindEditorEvents();
		},
		onEntityAjaxFormSubmitError: function(errors)
		{
			if(!this._isLocked || !this._isAccepted || !this.getEditor())
			{
				return;
			}

			var message = '';
			for (var i in errors)
			{
				if (!errors.hasOwnProperty(i))
				{
					continue;
				}
				if (errors[i].message)
				{
					message += errors[i].message + ' ';
				}
			}

			if (message.length)
			{
				BX.addClass(
					this._notAvailableFieldsErrorTextWrapper,
					'crm-entity-widget-content-error-text'
				);

				this._notAvailableFieldsErrorTextWrapper.innerText = message;
			}

			this._isLocked = false;

			BX.removeClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");
		},
		bindEntityEditorInitEvent: function()
		{
			BX.addCustomEvent(
				window,
				"BX.Crm.EntityEditor:onInit",
				function(sender, eventArgs)
				{
					if(sender.getId() !== this.getEditorId())
					{
						return;
					}

					this._editor = sender;

					var helpData = BX.prop.getObject(this._settings, "helpData", null);
					if(helpData)
					{
						this.getEditor().addHelpLink(helpData);
					}

					if (this._presetValues)
					{
						for (var presetFieldName in this._presetValues)
						{
							if(BX.Type.isArray(this._fieldNames) && this._fieldNames.indexOf(presetFieldName) === -1)
							{
								this._presetValues[presetFieldName].forEach(function(value){
									this.addHiddenInputToForm(presetFieldName, value);
								}.bind(this));
							}
						}
					}

				}.bind(this)
			);
		},
		bindEditorEvents: function()
		{
			if (this._editorEventsBinded)
			{
				return;
			}

			this._editorEventsBinded = true;

			BX.addCustomEvent(window, "onCrmEntityUpdate", this._entityUpdateSuccessHandler);
			BX.addCustomEvent(window, "onCrmEntityUpdateError", this._entityUpdateFailureHandler);
			BX.addCustomEvent(window, "BX.Crm.EntityEditor:onFailedValidation", this._entityValidationFailureHandler);
			if(this._isController)
			{
				BX.addCustomEvent(window, "BX.Crm.EntityEditorAjax:onSubmitFailure", this._entityAjaxFormSubmitErrorHandler);
			}
		},
		unbindEditorEvents: function()
		{
			BX.removeCustomEvent(window, "onCrmEntityUpdate", this._entityUpdateSuccessHandler);
			BX.removeCustomEvent(window, "onCrmEntityUpdateError", this._entityUpdateFailureHandler);
			BX.removeCustomEvent(window, "BX.Crm.EntityEditor:onFailedValidation", this._entityValidationFailureHandler);
			if(this._isController)
			{
				BX.removeCustomEvent(window, "BX.Crm.EntityEditorAjax:onSubmitFailure", this._entityAjaxFormSubmitErrorHandler);
			}

			this._editorEventsBinded = false;
		},
		onPopupShow: function()
		{
			window.setTimeout(function() { if(this._popup) this._popup.adjustPosition(); }.bind(this), 150);
		},
		onPopupClose: function()
		{
			if(this.getEditor())
			{
				this.getEditor().release();
			}

			if(!this._isAccepted)
			{
				BX.onCustomEvent(
					window,
					"Crm.PartialEditorDialog.Close",
					[
						this,
						{
							entityTypeId: this._entityTypeId,
							entityTypeName: BX.CrmEntityType.resolveName(this._entityTypeId),
							entityId: this._entityId,
							bid: BX.Crm.DialogButtonType.cancel,
							isCancelled: true,
							stageId: this.stageId
						}
					]
				);
			}

			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
			delete BX.Crm.PartialEditorDialog.items[this.getId()];
		},
		addHiddenInputToForm: function(inputName, inputValue)
		{
			var editor = this.getEditor();

			if (!editor)
			{
				return;
			}

			var formInput = editor._ajaxForm._elementNode.querySelector('input[name="' + inputName + '"]');
			if (!formInput || formInput.type === 'hidden')
			{
				formInput = document.createElement('INPUT');
				formInput.type = 'hidden';
				formInput.name = inputName;
				editor._ajaxForm._elementNode.appendChild(formInput);
			}
			formInput.value = inputValue;
		},
		getNotAccessibleFieldNames: function(fieldsWithErrors)
		{
			var sections = this.getEditor().getScheme().getElements();
			var visibleFields = [];
			sections.forEach(function(section){
				visibleFields = visibleFields.concat(
					section.getElements().map(function(item){
						return item._name
					}, this)
				);
			});

			return fieldsWithErrors.filter(function(x){
				return !visibleFields.includes(x)
			});
		},
		isFieldAvailableForCurrentUser: function(fieldName)
		{
			return this.getEditor()._scheme._availableElements.some(function(availableField){
				return availableField._name === fieldName;
			});
		},
		getEditor: function()
		{
			return this._editor;
		}
	};
	if(typeof(BX.Crm.PartialEditorDialog.messages) == "undefined")
	{
		BX.Crm.PartialEditorDialog.messages = {};
	}
	if(typeof(BX.Crm.PartialEditorDialog.entityEditorUrls) === "undefined")
	{
		BX.Crm.PartialEditorDialog.entityEditorUrls = {};
	}
	BX.Crm.PartialEditorDialog.registerEntityEditorUrl = function(entityTypeName, url)
	{
		BX.Crm.PartialEditorDialog.entityEditorUrls[entityTypeName] = url;
	};
	BX.Crm.PartialEditorDialog.items = {};
	BX.Crm.PartialEditorDialog.hasOpenItems = function()
	{
		for(var key in this.items)
		{
			if(!this.items.hasOwnProperty(key))
			{
				continue;
			}

			if(this.items[key].isOpen())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.PartialEditorDialog.getItem = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	BX.Crm.PartialEditorDialog.close = function(id)
	{
		if(this.items.hasOwnProperty(id))
		{
			this.items[id].close();
		}
	};
	BX.Crm.PartialEditorDialog.create = function(id, settings)
	{
		var self = new BX.Crm.PartialEditorDialog();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof BX.Crm.QuickFormPartialEditorDialog === "undefined")
{
	/**
	 * @extends BX.Crm.PartialEditorDialog
	 * @constructor
	 */
	BX.Crm.QuickFormPartialEditorDialog = function()
	{
		BX.Crm.QuickFormPartialEditorDialog.superclass.constructor.apply(this);
		this._entityCreateSuccessHandler = this.onEntityCreateSuccess.bind(this);
		this._entityCreateFailureHandler = this.onEntityCreateFailure.bind(this);
	};
	BX.extend(BX.Crm.QuickFormPartialEditorDialog, BX.Crm.PartialEditorDialog);
	BX.Crm.QuickFormPartialEditorDialog.prototype.unbindEditorEvents = function()
	{
		BX.Crm.QuickFormPartialEditorDialog.superclass.unbindEditorEvents.apply(this);

		BX.removeCustomEvent(window, "onCrmEntityCreate", this._entityCreateSuccessHandler);
		BX.removeCustomEvent(window, "onCrmEntityCreateError", this._entityCreateFailureHandler);
	};
	BX.Crm.QuickFormPartialEditorDialog.prototype.createPopup = function()
	{
		if (document.getElementById(this._id))
		{
			this.unbindEditorEvents();
			BX.PopupWindowManager.getPopupById(this._id).destroy();
		}

		BX.Crm.QuickFormPartialEditorDialog.superclass.createPopup.apply(this);
	};
	BX.Crm.QuickFormPartialEditorDialog.prototype.bindEditorEvents = function()
	{
		BX.Crm.QuickFormPartialEditorDialog.superclass.bindEditorEvents.apply(this);

		BX.addCustomEvent(window, "onCrmEntityCreate", this._entityCreateSuccessHandler);
		BX.addCustomEvent(window, "onCrmEntityCreateError", this._entityCreateFailureHandler);
	};
	BX.Crm.QuickFormPartialEditorDialog.prototype.onEntityCreateFailure = function(eventParams)
	{
		this._notAvailableFieldsErrorText = [];
		this._notAvailableFieldsErrorTextWrapper.innerHTML = '';
		BX.removeClass(
			this._notAvailableFieldsErrorTextWrapper,
			'crm-entity-widget-content-error-text'
		);

		if (eventParams.checkErrors)
		{
			var fieldNames = this.getNotAccessibleFieldNames(
				Object.keys(eventParams.checkErrors)
			);

			if (fieldNames.length)
			{
				fieldNames.forEach(function(fieldName){
					if (this.isFieldAvailableForCurrentUser(fieldName))
					{
						this._notAvailableFieldsErrorText.push(
							eventParams.checkErrors[fieldName]
						);
					}
				}, this);

				if (this._notAvailableFieldsErrorText.length)
				{
					BX.addClass(
						this._notAvailableFieldsErrorTextWrapper,
						'crm-entity-widget-content-error-text'
					);

					this._notAvailableFieldsErrorTextWrapper.innerHTML =
						BX.Crm.PartialEditorDialog.messages.entityHasInaccessibleFields
						+ ' '
						+ this._notAvailableFieldsErrorText.join('; ');
				}
			}
		}

		if(
			this._entityTypeId === BX.prop.getInteger(eventParams, "entityTypeId", 0)
			&& this._entityId === BX.prop.getInteger(eventParams, "entityId", 0)
		)
		{
			this._isLocked = false;

			BX.removeClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");

			this.unbindEditorEvents();
		}
	};
	BX.Crm.QuickFormPartialEditorDialog.prototype.onEntityCreateSuccess = function(eventParams)
	{
		if(this._entityTypeId === BX.prop.getInteger(eventParams, "entityTypeId", 0))
		{
			this._isLocked = false;

			BX.removeClass(this._buttons[BX.Crm.DialogButtonType.names.accept], "ui-btn-clock");

			this.unbindEditorEvents();

			if(this._popup)
			{
				this._popup.close();
			}

			BX.onCustomEvent(
				window,
				"Crm.QuickFormPartialEditorDialog.Close",
				[
					this,
					{
						entityTypeId: this._entityTypeId,
						entityTypeName: BX.CrmEntityType.resolveName(this._entityTypeId),
						entityId: BX.prop.getInteger(eventParams, "entityId", null),
						entityData: BX.prop.getObject(eventParams, "entityData", null),
						bid: BX.Crm.DialogButtonType.accept,
						isCancelled: false
					}
				]
			);
		}
	};
	BX.Crm.QuickFormPartialEditorDialog.prototype.onPopupClose = function()
	{
		this._isLocked = false;

		if(this.getEditor())
		{
			this.getEditor().release();
		}

		if(!this._isAccepted)
		{
			BX.onCustomEvent(
				window,
				"Crm.QuickFormPartialEditorDialog.Close",
				[
					this,
					{
						entityTypeId: this._entityTypeId,
						entityTypeName: BX.CrmEntityType.resolveName(this._entityTypeId),
						bid: BX.Crm.DialogButtonType.cancel,
						isCancelled: true
					}
				]
			);
		}

		if(this._popup)
		{
			this._popup.destroy();
		}
	};
	BX.Crm.QuickFormPartialEditorDialog.items = {};
	BX.Crm.QuickFormPartialEditorDialog.create = function(id, settings)
	{
		var self = new BX.Crm.QuickFormPartialEditorDialog();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
