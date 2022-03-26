BX.namespace("BX.Crm");

//region USER FIELD
if(typeof BX.Crm.EntityUserFieldType === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityUserFieldType = BX.UI.EntityUserFieldType;
}

if(typeof BX.Crm.EntityUserFieldManager === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityUserFieldManager = BX.UI.EntityUserFieldManager;
}

if(typeof BX.Crm.EntityUserFieldLayoutLoader === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityUserFieldLayoutLoader = BX.UI.EntityUserFieldLayoutLoader;
}

if(typeof BX.Crm.EntityEditorUserFieldConfigurator === "undefined")
{
	/**
	 * @extends BX.UI.EntityEditorUserFieldConfigurator
	 */
	BX.Crm.EntityEditorUserFieldConfigurator = function()
	{
		BX.Crm.EntityEditorUserFieldConfigurator.superclass.constructor.apply(this);
		this._visibilityConfigurator = null;
	};
	BX.extend(BX.Crm.EntityEditorUserFieldConfigurator, BX.UI.EntityEditorUserFieldConfigurator);
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorUserFieldConfigurator.superclass.doInitialize.apply(this);
		if (this.getEditor().canChangeCommonConfiguration())
		{
			this._visibilityConfigurator = BX.prop.get(this._settings, "visibilityConfigurator", null);
		}
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.getOptionContainer = function()
	{
		this._optionWrapper = BX.Crm.EntityEditorUserFieldConfigurator.superclass.getOptionContainer.apply(this);

		var isNew = (this._field === null);
		//region Use timezone
		if(this._typeId === "datetime")
		{
			this._useTimezoneCheckBox = this.createOption(
				{ caption: BX.UI.EntityEditorFieldConfigurator.messages['useTimezone']}
			);

			this._useTimezoneCheckBox.checked = isNew
				? false
				: (this._field.getFieldSettings().USE_TIMEZONE === 'Y');
		}
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
		}
		//endregion

		return this._optionWrapper;
	};
	BX.Crm.EntityEditorUserFieldConfigurator.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorUserFieldConfigurator.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: name
		);
	};

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
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorUserFieldListItem = BX.UI.EntityEditorUserFieldListItem;
}

if(typeof BX.Crm.EntityEditorUserField === "undefined")
{
	/**
	 * @extends BX.UI.EntityEditorUserField
	 */
	BX.Crm.EntityEditorUserField = function()
	{
		BX.Crm.EntityEditorUserField.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorUserField, BX.UI.EntityEditorUserField);
	BX.Crm.EntityEditorUserField.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorUserField.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorUserField.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorUserField.prototype.getOptions = function()
	{
		return this._schemeElement.getDataParam("options", {});
	};
	BX.Crm.EntityEditorUserField.prototype.doGetEditPriority = function()
	{
		return (BX.prop.get(BX.prop.getObject(this.getFieldInfo(), "SETTINGS"), "DEFAULT_VALUE")
				? BX.UI.EntityEditorPriority.high
				: BX.UI.EntityEditorPriority.normal
		);
	};
	BX.Crm.EntityEditorUserField.prototype.doClearLayout = function(options)
	{
		this._innerWrapper = null;
		this.removeExternalEventsHandlers();
	};
	BX.Crm.EntityEditorUserField.prototype.addExternalEventsHandlers = function()
	{
		this.removeExternalEventsHandlers();
		// Handler could be called by UF to trigger _changeHandler in complicated cases
		BX.addCustomEvent(window, "onCrmEntityEditorUserFieldExternalChanged", BX.proxy(this.userFieldExternalChangedHandler, this));
		BX.addCustomEvent(window, "onCrmEntityEditorUserFieldSetValidator", BX.proxy(this.userFieldSetValidatorHandler, this));
	};
	BX.Crm.EntityEditorUserField.prototype.removeExternalEventsHandlers = function()
	{
		BX.removeCustomEvent(window, "onCrmEntityEditorUserFieldExternalChanged", BX.proxy(this.userFieldExternalChangedHandler, this));
		BX.removeCustomEvent(window, "onCrmEntityEditorUserFieldSetValidator", BX.proxy(this.userFieldSetValidatorHandler, this));
	};
	BX.Crm.EntityEditorUserField.prototype.release = function()
	{
		this.removeExternalEventsHandlers();
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
	BX.Crm.EntityEditorUserField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserField();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.Crm.EntityEditorUserField.messages) === "undefined")
	{
		BX.Crm.EntityEditorUserField.messages = {};
	}
}

//endregion

if(typeof(BX.Crm.UserFieldTypeMenu) === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.UserFieldTypeMenu = BX.UI.UserFieldTypeMenu;
}

if(typeof(BX.Crm.UserFieldTypeMenuItem) === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.UserFieldTypeMenuItem = BX.UI.UserFieldTypeMenuItem;
}

