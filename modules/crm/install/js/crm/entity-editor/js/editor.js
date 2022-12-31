BX.namespace("BX.Crm");

//region EDITOR
if(typeof BX.Crm.EntityEditor === "undefined")
{
	/**
	 * @extends BX.UI.EntityEditor
	 * @constructor
	 */
	BX.Crm.EntityEditor = function()
	{
		BX.Crm.EntityEditor.superclass.constructor.apply(this);

		this._entityTypeId = 0;

		this._dupControlManager = null;
		this._bizprocManager = null;
		this._attributeManager = null;

		this._afterFormSubmitHandler = BX.delegate(this.onAfterFormSubmit, this);
		this._cancelFormSubmitHandler = BX.delegate(this.onCancelFormSubmit, this);

		this._haslayout = false;

		this._enableCommunicationControls = true;
		this._enableExternalLayoutResolvers = false;
		this._showEmptyFields = false;

		this._modeChangeNotifier = null;
		this._controlChangeNotifier = null;

		this._entityUpdateHandler = BX.delegate(this.onEntityUpdate, this);
		this._toolbarMenuBuildHandler = BX.delegate(this.onInterfaceToolbarMenuBuild, this);
		this._configurationManagerInitializeHandler = BX.delegate(this.onConfigurationManagerInitialize, this);

		this._helpWrapper = null;
		this.eventsNamespace = 'BX.Crm.EntityEditor';
		this.pageTitleInputClassName = "pagetitle-item crm-pagetitle-item";
	};

	BX.extend(BX.Crm.EntityEditor, BX.UI.EntityEditor);

	BX.Crm.EntityEditor.prototype.initialize = function(id, settings)
	{
		this._controlChangeNotifier = BX.CrmNotifier.create(this);
		this._modeChangeNotifier = BX.CrmNotifier.create(this);

		this._settings = settings ? settings : {};

		this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
		this._entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);

		this._createSectionButton = BX(BX.prop.get(this._settings, "createSectionButtonId"));
		this._configMenuButton = BX(BX.prop.get(this._settings, "configMenuButtonId"));

		this._enableCommunicationControls = BX.prop.getBoolean(this._settings, "enableCommunicationControls", true);

		this._enableExternalLayoutResolvers = BX.prop.getBoolean(this._settings, "enableExternalLayoutResolvers", false);
		this._showEmptyFields = BX.prop.getBoolean(this._settings, "showEmptyFields", false);

		BX.Crm.EntityEditor.superclass.initialize.apply(this, [id, settings]);

		this._modeChangeNotifier.notify([ this ]);

		if(!BX.type.isElementNode(this._container))
		{
			throw this.eventsNamespace + ": Could not find settings param 'container'.";
		}
	};
	BX.Crm.EntityEditor.prototype.initializeManagers = function()
	{
		BX.addCustomEvent("BX.UI.EntityConfigurationManager:onInitialize", this._configurationManagerInitializeHandler);

		BX.Crm.EntityEditor.superclass.initializeManagers.apply(this);

		//region Duplicate manager
		var duplicateControlConfig = BX.prop.getObject(this._settings, "duplicateControl", {});
		if(this._ajaxForm)
		{
			duplicateControlConfig["form"] = this._ajaxForm;
		}

		this._dupControlManager = BX.Crm.EntityEditorDupManager.create(
			this._id.toLowerCase() + "_dup",
			duplicateControlConfig
		);
		//endregion

		this._bizprocManager = BX.prop.get(this._settings, "bizprocManager", null);
		if(this._bizprocManager)
		{
			this._bizprocManager._editor = this;
		}

		this._restPlacementTabManager = BX.prop.get(this._settings, "restPlacementTabManager", null);
		if(this._restPlacementTabManager)
		{
			this._restPlacementTabManager._editor = this;
		}
	};
	BX.Crm.EntityEditor.prototype.attachToEvents = function()
	{
		BX.Crm.EntityEditor.superclass.attachToEvents.apply(this);

		BX.addCustomEvent(
			window,
			"Crm.InterfaceToolbar.MenuBuild",
			this._toolbarMenuBuildHandler
		);

		BX.addCustomEvent("onCrmEntityUpdate", this._entityUpdateHandler);
	};
	BX.Crm.EntityEditor.prototype.deattachFromEvents = function()
	{
		BX.Crm.EntityEditor.superclass.deattachFromEvents.apply(this);

		BX.removeCustomEvent(
			window,
			"Crm.InterfaceToolbar.MenuBuild",
			this._toolbarMenuBuildHandler
		);
		BX.removeCustomEvent("onCrmEntityUpdate", this._entityUpdateHandler);
		BX.removeCustomEvent("BX.UI.EntityConfigurationManager:onInitialize", this._configurationManagerInitializeHandler);
	};
	BX.Crm.EntityEditor.prototype.onConfigurationManagerInitialize = function(editor, eventArgs) {
		if(eventArgs.type === 'editor')
		{
			eventArgs.configurationFieldManager = BX.Crm.EntityConfigurationManager.create(
				this._id,
				{ editor: this }
			);
		}
	};
	BX.Crm.EntityEditor.prototype.initializeControlsEditMode = function()
	{
		var i, length, control;
		for(i = 0, length = this._controls.length; i < length; i++)
		{
			control = this._controls[i];
			//Enable edit mode for required fields only.
			var priority = control.getEditPriority();
			if(priority === BX.UI.EntityEditorPriority.high)
			{
				control.setMode(BX.UI.EntityEditorMode.edit, { notify: false });
			}
		}

		if(this.getActiveControlCount() === 0)
		{
			this._controls[0].setMode(BX.UI.EntityEditorMode.edit, { notify: false });
		}
	};
	BX.Crm.EntityEditor.prototype.release = function()
	{
		BX.Crm.EntityEditor.superclass.release.apply(this);

		if(this._dragContainerController)
		{
			this._dragContainerController.removeDragFinishListener(this._dropHandler);
			this._dragContainerController.release();
			this._dragContainerController = null;
		}

		var i, length;
		for(i = 0, length = this._controllers.length; i < length; i++)
		{
			this._controllers[i].release();
		}

		this._haslayout = false;
	};
	BX.Crm.EntityEditor.prototype.clone = function(params)
	{
		//var settings = Object.assign({}, this._settings);
		var wrapper = BX(BX.prop.get(params, "wrapper"));
		if(!BX.type.isElementNode(wrapper))
		{
			throw this.eventsNamespace + ": Could not find param 'wrapper'.";
		}

		var id = BX.prop.getString(params, "id", "");
		if(id === "")
		{
			id = BX.util.getRandomString(4);
		}

		var container = BX.create(
			"DIV",
			{
				props: { id: id.toLowerCase() + "_container",  className: "crm-entity-card-container-content" }
			}
		);
		wrapper.appendChild(container);

		var settings = BX.clone(this._settings);
		delete settings["containerId"];
		settings["container"] = container;

		return BX.Crm.EntityEditor.create(id, settings);
	};
	BX.Crm.EntityEditor.prototype.onEntityUpdate = function(eventParams)
	{
		if(this._isReleased)
		{
			return;
		}

		if(this._entityTypeId === BX.prop.getInteger(eventParams, "entityTypeId", 0)
			&& this._entityId === BX.prop.getInteger(eventParams, "entityId", 0)
			&& this !== BX.prop.get(eventParams, "sender", 0)
		)
		{
			var data = BX.prop.getObject(eventParams, "entityData", null);
			if(data)
			{
				this._model.setData(data, { enableNotification: false });

				this.adjustTitle();
				this.adjustSize();

				this.refreshLayout({ reset: true });
			}
		}
	};
	BX.Crm.EntityEditor.prototype.getEntityTypeForAction = function()
	{
		return BX.CrmEntityType.resolveAbbreviation(this._entityTypeName);
	};
	BX.Crm.EntityEditor.prototype.initializeAjaxForm = function()
	{
		BX.Crm.EntityEditor.superclass.initializeAjaxForm.apply(this);
		BX.addCustomEvent(this._ajaxForm, "onAfterSubmit", this._afterFormSubmitHandler);
		BX.addCustomEvent(this._ajaxForm, "onSubmitCancel", this._cancelFormSubmitHandler);
	};
	BX.Crm.EntityEditor.prototype.getAjaxFormConfigData = function()
	{
		return {
			'ACTION_ENTITY_TYPE': this.getEntityTypeForAction(),
			'ACTION_ENTITY_ID': this._entityId
		};
	};
	BX.Crm.EntityEditor.prototype.releaseAjaxForm = function()
	{
		BX.Crm.EntityEditor.superclass.releaseAjaxForm.apply(this);
		BX.removeCustomEvent(this._ajaxForm, "onSubmitCancel", this._cancelFormSubmitHandler);
	};
	BX.Crm.EntityEditor.prototype.getEntityTypeId = function()
	{
		return this._entityTypeId;
	};
	BX.Crm.EntityEditor.prototype.getModel = function()
	{
		return this._model;
	};
	BX.Crm.EntityEditor.prototype.isPersistent = function()
	{
		return(this._entityId > 0 && this._entityId === this._model.getIntegerField("ID", 0));
	};
	BX.Crm.EntityEditor.prototype.isNeedToDisplayEmptyFields = function()
	{
		return this._showEmptyFields;
	};
	BX.Crm.EntityEditor.prototype.areCommunicationControlsEnabled = function()
	{
		return this._enableCommunicationControls;
	};
	BX.Crm.EntityEditor.prototype.getEntityCreateUrl = function(entityTypeName)
	{
		if(entityTypeName === BX.CrmEntityType.names.contact)
		{
			return BX.prop.getString(this._settings, "contactCreateUrl", "");
		}
		else if(entityTypeName === BX.CrmEntityType.names.company)
		{
			return BX.prop.getString(this._settings, "companyCreateUrl", "");
		}
		return "";
	};
	BX.Crm.EntityEditor.prototype.getEntityEditUrl = function(entityTypeName, entityId)
	{
		var url = "";
		if(entityTypeName === BX.CrmEntityType.names.contact)
		{
			url = BX.prop.getString(this._settings, "contactEditUrl", "");
		}
		else if(entityTypeName === BX.CrmEntityType.names.company)
		{
			url = BX.prop.getString(this._settings, "companyEditUrl", "");
		}

		if(url !== "")
		{
			url = url.replace("#id#", entityId, "gi");
		}

		return url;
	};
	BX.Crm.EntityEditor.prototype.getEntityRequisiteSelectUrl = function(entityTypeName, entityId)
	{
		var url = "";
		if(entityTypeName === BX.CrmEntityType.names.contact)
		{
			url = BX.prop.getString(this._settings, "contactRequisiteSelectUrl", "").replace(/#contact_id#/gi, entityId);
		}
		else if(entityTypeName === BX.CrmEntityType.names.company)
		{
			url = BX.prop.getString(this._settings, "companyRequisiteSelectUrl", "").replace(/#company_id#/gi, entityId);
		}
		return url;
	};
	BX.Crm.EntityEditor.prototype.getRequisiteEditUrl = function(id)
	{
		return BX.prop.getString(this._settings, "requisiteEditUrl", "").replace(/#requisite_id#/gi, id);
	};
	BX.Crm.EntityEditor.prototype.getBizprocManager = function()
	{
		return this._bizprocManager;
	};
	BX.Crm.EntityEditor.prototype.getAttributeManager = function()
	{
		if(!this._attributeManager)
		{
			var settings = this.getAttributeManagerSettings();
			if(settings)
			{
				this._attributeManager = BX.Crm.EntityFieldAttributeManager.create(
					this._id,
					{
						entityTypeId: this.getEntityTypeId(),
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
			}
		}
		return this._attributeManager;
	};
	BX.Crm.EntityEditor.prototype.registerActiveControl = function(control)
	{
		var index = this.getActiveControlIndex(control);
		if(index >= 0)
		{
			return;
		}

		var mode = this._mode;
		BX.Crm.EntityEditor.superclass.registerActiveControl.apply(this, [control]);

		if(mode !== BX.UI.EntityEditorMode.edit && this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._modeChangeNotifier.notify([ this ]);
		}
	};
	BX.Crm.EntityEditor.prototype.unregisterActiveControl = function(control)
	{
		var index = this.getActiveControlIndex(control);
		if(index < 0)
		{
			return;
		}
		var mode = this._mode;
		BX.Crm.EntityEditor.superclass.unregisterActiveControl.apply(this, [control]);

		if(mode !== BX.UI.EntityEditorMode.view && this._activeControls.length === 0 && this._mode === BX.UI.EntityEditorMode.view)
		{
			this._modeChangeNotifier.notify([ this ]);
		}
	};
	BX.Crm.EntityEditor.prototype.createControl = function(type, controlId, settings)
	{
		settings["serviceUrl"] = this._serviceUrl;
		settings["container"] = this._formElement;
		settings["model"] = this._model;
		settings["editor"] = this;

		return BX.Crm.EntityEditorControlFactory.create(type, controlId, settings);
	};
	BX.Crm.EntityEditor.prototype.releaseActiveControls = function(options)
	{
		var mode = this._mode;
		BX.Crm.EntityEditor.superclass.releaseActiveControls.apply(this, [options]);
		if(this._mode !== BX.UI.EntityEditorMode.view && !this.getActiveControlCount())
		{
			this._mode = BX.UI.EntityEditorMode.view;
		}
		if(mode !== this._mode)
		{
			this._modeChangeNotifier.notify([ this ]);
		}
	};

	BX.Crm.EntityEditor.prototype.processControlChange = function(control, params)
	{
		this._enableCloseConfirmation = true;

		BX.Crm.EntityEditor.superclass.processControlChange.apply(this, [control, params]);
		this._controlChangeNotifier.notify([ params ]);
	};
	BX.Crm.EntityEditor.prototype.processControlRemove = function(control)
	{
		if(control instanceof BX.Crm.EntityEditorField
			|| control instanceof BX.UI.EntityEditorField
			|| control instanceof BX.Crm.EntityEditorSubsection)
		{
			this.addAvailableSchemeElement(control.getSchemeElement());
		}
		else if(control instanceof BX.Crm.EntityEditorSection)
		{
			var children = control.getChildren();
			for(var i= 0, length = children.length; i < length; i++)
			{
				this.addAvailableSchemeElement(children[i].getSchemeElement());
			}
		}
	};
	//region Controllers
	BX.Crm.EntityEditor.prototype.createController = function(data)
	{
		return BX.Crm.EntityEditorControllerFactory.create(
			BX.prop.getString(data, "type", ""),
			BX.prop.getString(data, "name", ""),
			{
				config: BX.prop.getObject(data, "config", {}),
				model: this._model,
				editor: this
			}
		);
	};
	BX.Crm.EntityEditor.prototype.processControllerChange = function(controller)
	{
		this._enableCloseConfirmation = true;
		BX.Crm.EntityEditor.superclass.processControlChange.apply(this, [controller]);
	};
	BX.Crm.EntityEditor.prototype.tapController = function(controllerId, callback)
	{
		if (BX.type.isNotEmptyString(controllerId) && BX.type.isFunction(callback))
		{
			var i, length;
			for(i = 0, length = this._controllers.length; i < length; i++)
			{
				if (this._controllers[i]._id === controllerId)
				{
					return callback.call(this, this._controllers[i]);
				}
			}
		}
	};
	//endregion
	//region Layout
	BX.Crm.EntityEditor.prototype.hasLayout = function()
	{
		return this._haslayout;
	};
	BX.Crm.EntityEditor.prototype.layout = function()
	{
		//todo refactor
		var eventArgs = { cancel: false };
		BX.onCustomEvent(window, this.eventsNamespace + ":onBeforeLayout", [ this, eventArgs ]);
		if(eventArgs["cancel"])
		{
			return;
		}

		this.prepareContextDataLayout(this._context, "");

		if(this._toolPanel)
		{
			this._toolPanel.layout();
		}

		if(this._createSectionButton)
		{
			if(this.isSectionCreationEnabled())
			{
				BX.bind(this._createSectionButton, "click", BX.delegate(this.onCreateSectionButtonClick, this));
			}
			else
			{
				this._createSectionButton.style.display = "none";
			}
		}

		if(this._configMenuButton)
		{
			BX.bind(this._configMenuButton, "click", BX.delegate(this.onConfigMenuButtonClick, this));
		}

		var enableInlineEditSpotlight = BX.prop.getBoolean(this._settings, "enableInlineEditSpotlight", false);

		var userFieldLoaders =
			{
				edit: BX.UI.EntityUserFieldLayoutLoader.create(
					this._id,
					{ mode: BX.UI.EntityEditorMode.edit, enableBatchMode: true, owner: this }
				),
				view: BX.UI.EntityUserFieldLayoutLoader.create(
					this._id,
					{ mode: BX.UI.EntityEditorMode.view, enableBatchMode: true, owner: this }
				)
			};

		var i, length, control;
		for(i = 0, length = this._controls.length; i < length; i++)
		{
			control = this._controls[i];
			var mode = control.getMode();

			var layoutOptions =
				{
					userFieldLoader: userFieldLoaders[BX.UI.EntityEditorMode.getName(mode)],
					enableFocusGain: !this._isEmbedded
				};

			if(i === 0 && enableInlineEditSpotlight && mode === BX.UI.EntityEditorMode.view && !this.isReadOnly())
			{
				layoutOptions["lighting"] =
					{
						id: BX.prop.getString(this._settings, "inlineEditSpotlightId", ""),
						text: this.getMessage("inlineEditHint")
					};
			}

			control.layout(layoutOptions);

			if(mode === BX.UI.EntityEditorMode.edit)
			{
				this.registerActiveControl(control);
			}
		}

		for(var key in userFieldLoaders)
		{
			if(userFieldLoaders.hasOwnProperty(key))
			{
				userFieldLoaders[key].runBatch();
			}
		}

		if(this.getActiveControlCount() > 0)
		{
			this.showToolPanel();
		}

		if(this._model.isCaptionEditable())
		{
			BX.bind(
				this._pageTitle,
				"click",
				BX.delegate(this.onPageTileClick, this)
			);

			if(this._editPageTitleButton)
			{
				BX.bind(
					this._editPageTitleButton,
					"click",
					BX.delegate(this.onPageTileClick, this)
				);
			}
		}

		if(this._mode === BX.UI.EntityEditorMode.edit && this._dupControlManager.isEnabled())
		{
			this._dupControlManager.search();
		}

		if(this._enableBottomPanel && this._buttonContainer)
		{
			this._buttonContainer.style.display = "";
		}

		this.adjustButtons();
		this._haslayout = true;

		BX.onCustomEvent(window, this.eventsNamespace + ":onLayout", [ this ]);
	};
	//endregion
	BX.Crm.EntityEditor.prototype.adjustTitle = function()
	{
		BX.Crm.EntityEditor.superclass.adjustTitle.apply(this);

		if (!this._enablePageTitleControls)
		{
			return;
		}

		document.title = this._model.getCaption().trim();
		if (BX.getClass("BX.SidePanel.Instance.updateBrowserTitle"))
		{
			BX.SidePanel.Instance.updateBrowserTitle();
		}
	};
	BX.Crm.EntityEditor.prototype.adjustSize = function()
	{
		BX.Crm.EntityEditor.superclass.adjustSize.apply(this);
		if(!this._enablePageTitleControls || !this._pageTitle)
		{
			return;
		}

		var wrapper = this._pageTitle.parentNode ? this._pageTitle.parentNode : this._pageTitle;
		BX.addClass(wrapper, "crm-pagetitle");
	};
	BX.Crm.EntityEditor.prototype.adjustButtons = function()
	{
		//Move configuration menu button to last section if bottom panel is hidden.
		if(this._config.isScopeToggleEnabled() && !this._enableBottomPanel && this._controls.length > 0)
		{
			var lastSection = this._controls[this._controls.length - 1];
			var sectionControls = lastSection.getChildren();
			var lastSectionControl = sectionControls[sectionControls.length - 1];
			lastSectionControl.ensureButtonPanelWrapperCreated().appendChild(
				BX.create(
					"span",
					{
						props:
							{
								className: this._config.getScope() === BX.UI.EntityConfigScope.common
									? "crm-entity-card-common" : "crm-entity-card-private"
							},
						events: { click: BX.delegate(this.onConfigMenuButtonClick, this) }
					}
				)
			);
		}
	};
	BX.Crm.EntityEditor.prototype.addModeChangeListener = function(listener)
	{
		this._modeChangeNotifier.addListener(listener);
	};
	BX.Crm.EntityEditor.prototype.removeModeChangeListener = function(listener)
	{
		this._modeChangeNotifier.removeListener(listener);
	};
	BX.Crm.EntityEditor.prototype.addControlChangeListener = function(listener)
	{
		this._controlChangeNotifier.addListener(listener);
	};
	BX.Crm.EntityEditor.prototype.removeControlChangeListener = function(listener)
	{
		this._controlChangeNotifier.removeListener(listener);
	};
	BX.Crm.EntityEditor.prototype.validate = function(result)
	{
		//todo move to ui
		var validator = BX.UI.EntityAsyncValidator.create();
		for(var i = 0, length = this._activeControls.length; i < length; i++)
		{
			validator.addResult(this._activeControls[i].validate(result));
		}
		for(i = 0, length = this._controllers.length; i < length; i++)
		{
			validator.addResult(this._controllers[i].validate(result));
		}
		if (this._userFieldManager)
		{
			validator.addResult(this._userFieldManager.validate(result));
		}

		return validator.validate();
	};
	BX.Crm.EntityEditor.prototype.getActionEventArguments = function()
	{
		var eventArguments = BX.Crm.EntityEditor.superclass.getActionEventArguments.apply(this);
		eventArguments['entityTypeId'] = this._entityTypeId;

		return eventArguments;
	};
	BX.Crm.EntityEditor.prototype.innerCancel = function() {
		if (this._isNew)
		{
			this._enableCloseConfirmation = false;
		}
		BX.Crm.EntityEditor.superclass.innerCancel.apply(this);
	};
	BX.Crm.EntityEditor.prototype.processSchemeChange = function()
	{
		// todo return after adding processSchemeChange to the controls
		// for(var i = 0, length = this._controls.length; i < length; i++)
		// {
		// 	this._controls[i].processSchemeChange();
		// }
	};
	BX.Crm.EntityEditor.prototype.onSaveSuccess = function(result, params)
	{
		//todo refactor it on parent class
		this._isRequestRunning = false;

		this._enableCloseConfirmation = false;

		if(this._toolPanel)
		{
			this._toolPanel.setLocked(false);
			this._toolPanel.clearErrors();
		}

		//region Event Params
		var eventParams = BX.prop.getObject(result, "EVENT_PARAMS", {});
		eventParams["entityTypeId"] = this._entityTypeId;

		var entityInfo = BX.prop.getObject(result, "ENTITY_INFO", null);
		if(entityInfo)
		{
			eventParams["entityInfo"] = entityInfo;
		}

		var slider = BX.Crm.Page.getTopSlider();
		if(slider)
		{
			eventParams["sliderUrl"] = slider.getUrl();
		}
		//endregion

		var checkErrors = BX.prop.getObject(result, "CHECK_ERRORS", null);
		var error = BX.prop.getString(result, "ERROR", "");
		var hasRestriction = BX.prop.getBoolean(result, 'RESTRICTION', false);
		if(checkErrors || error !== "" || hasRestriction)
		{
			if(checkErrors)
			{
				var firstField = null;
				var errorMessages = [];
				for(var fieldId in checkErrors)
				{
					if(!checkErrors.hasOwnProperty(fieldId))
					{
						return;
					}

					var field = this.getActiveControlById(fieldId, true);
					if(field)
					{
						field.showError(checkErrors[fieldId]);
						if(!firstField)
						{
							firstField = field;
						}
					}
					else
					{
						errorMessages.push(checkErrors[fieldId]);
					}
				}

				if(firstField)
				{
					firstField.scrollAnimate();
				}

				error = errorMessages.join("<br/>");
			}

			var restrictionAction = BX.prop.getString(result, "RESTRICTION_ACTION", "");
			if (hasRestriction && restrictionAction.length)
			{
				eval(restrictionAction);
				BX.onCustomEvent(window, "BX.Crm.EntityEditor:onRestrictionAction", []);
			}
			else
			{
				if (error !== "" && this._toolPanel)
				{
					this._toolPanel.addError(error);
				}

				eventParams["checkErrors"] = checkErrors;
				eventParams["error"] = error;


				if (this._isNew)
				{
					BX.onCustomEvent(window, "onCrmEntityCreateError", [eventParams]);
				}
				else
				{
					eventParams["entityId"] = this._entityId;
					BX.onCustomEvent(window, "onCrmEntityUpdateError", [eventParams]);
				}
			}

			this.releaseAjaxForm();
			this.initializeAjaxForm();

			return;
		}

		var entityData = BX.prop.getObject(result, "ENTITY_DATA", null);
		eventParams["entityData"] = entityData;
		eventParams["isCancelled"] = false;

		if(this._isNew)
		{
			this._entityId = BX.prop.getInteger(result, "ENTITY_ID", 0);
			if(this._entityId <= 0)
			{
				if(this._toolPanel)
				{
					this._toolPanel.addError(this.getMessage("couldNotFindEntityIdError"));
				}
				return;
			}

			//fire onCrmEntityCreate
			BX.Crm.EntityEvent.fireCreate(this._entityTypeId, this._entityId, this._externalContextId, eventParams);

			eventParams["sender"] = this;
			eventParams["entityId"] = this._entityId;

			BX.onCustomEvent(window, "onCrmEntityCreate", [eventParams]);

			if(BX.prop.getBoolean(eventParams, "isCancelled", true))
			{
				this._entityId = 0;

				this.rollback();

				this.releaseAjaxForm();
				this.initializeAjaxForm();

				return;
			}

			this._isNew = false;
		}
		else
		{
			//fire onCrmEntityUpdate
			BX.Crm.EntityEvent.fireUpdate(this._entityTypeId, this._entityId, this._externalContextId, eventParams);

			eventParams["sender"] = this;
			eventParams["entityId"] = this._entityId;
			BX.onCustomEvent(window, "onCrmEntityUpdate", [eventParams]);

			if(BX.prop.getBoolean(eventParams, "isCancelled", true))
			{
				this.rollback();

				this.releaseAjaxForm();
				this.initializeAjaxForm();

				return;
			}
		}

		var redirectUrl = BX.prop.getString(result, "REDIRECT_URL", "");

		var additionalEventParams = BX.prop.getObject(result, "EVENT_PARAMS", null);
		if(additionalEventParams)
		{
			var eventName = BX.prop.getString(additionalEventParams, "name", "");
			var eventArgs = BX.prop.getObject(additionalEventParams, "args", null);
			if(eventName !== "" && eventArgs !== null)
			{
				if(redirectUrl !== "")
				{
					eventArgs["redirectUrl"] = redirectUrl;
				}
				BX.localStorage.set(eventName, eventArgs, 10);
			}
		}

		if(this._isReleased)
		{
			return;
		}

		if(redirectUrl !== "" && !this._isEmbedded)
		{
			eventParams.redirectUrl = redirectUrl;
			BX.onCustomEvent(window, "beforeCrmEntityRedirect", [eventParams]);
			window.location.replace(
				BX.util.add_url_param(
					redirectUrl,
					{ "IFRAME": "Y", "IFRAME_TYPE": "SIDE_SLIDER" }
				)
			);
		}
		else
		{
			var needSwitchMode =  BX.prop.getBoolean(params, "switchMode", true);
			if (needSwitchMode)
			{
				if (BX.type.isPlainObject(entityData))
				{
					//Notification event is disabled because we will call "refreshLayout" for all controls at the end.
					this._model.setData(entityData, {enableNotification: false});
				}

				this.adjustTitle();
				this.adjustSize();
				this.releaseAjaxForm();
				this.initializeAjaxForm();

				for (var i = 0, length = this._controllers.length; i < length; i++)
				{
					this._controllers[i].onAfterSave();
				}

				if (this._modeSwitch.isRunning())
				{
					this._modeSwitch.complete();
				}
				else
				{
					this.switchToViewMode({refreshLayout: false});
				}

				this.refreshLayout({reset: true});
				if (!this.isToolPanelAlwaysVisible())
				{
					this.hideToolPanel();
				}
			}
			else if(BX.type.isPlainObject(entityData))
			{
				var previousModel = Object.create(this._model); // clone model object
				previousModel.setData(  // copy model data
					BX.clone(this._model.getData()),
					{
						enableNotification: false
					}
				);

				//Notification event is disabled because we will call "refreshViewModeLayout" for all controls at the end.
				this._model.setData(entityData, {enableNotification: false});

				this.adjustTitle();
				this.adjustSize();

				for(var i = 0, length = this._controllers.length; i < length; i++)
				{
					this._controllers[i].onReload();
				}

				this.refreshViewModeLayout({
					previousModel: previousModel,
					reset: true
				});
			}
		}
	};
	BX.Crm.EntityEditor.prototype.onAfterFormSubmit = function(sender, eventArgs)
	{
		this._isRequestRunning = true;
		if(this._toolPanel)
		{
			this._toolPanel.setLocked(true);
		}
	};
	BX.Crm.EntityEditor.prototype.onCancelFormSubmit = function(sender, eventArgs)
	{
		this._isRequestRunning = false;
		if(this._toolPanel)
		{
			this._toolPanel.setLocked(false);
		}
	};
	//region Duplicate Control
	BX.Crm.EntityEditor.prototype.isDuplicateControlEnabled = function()
	{
		return this._dupControlManager.isEnabled();
	};
	BX.Crm.EntityEditor.prototype.getDuplicateManager = function()
	{
		return this._dupControlManager;
	};
	//endregion
	//region Configuration
	BX.Crm.EntityEditor.prototype.getAttributeManagerSettings = function()
	{
		return BX.prop.getObject(this._settings, "attributeConfig", null);
	};
	//endregion
	//region D&D
	BX.Crm.EntityEditor.prototype.onDrop = function(dragContainer, draggedItem, x, y)
	{
		//todo possible inconsistent problem here (parent method has another arguments)
		this.processDraggedItemDrop(dragContainer, draggedItem);
	};
	//endregion
	//region Permissions
	BX.Crm.EntityEditor.prototype.canCreateContact = function()
	{
		return BX.prop.getBoolean(this._settings, "canCreateContact", false);
	};
	BX.Crm.EntityEditor.prototype.canCreateCompany = function()
	{
		return BX.prop.getBoolean(this._settings, "canCreateCompany", false);
	};
	//endregion
	BX.Crm.EntityEditor.prototype.addHelpLink = function(data)
	{
		if(!this._helpWrapper)
		{
			this._helpWrapper = BX.create("DIV", { props: { className: "crm-entity-card-widget-help" } });
			this._container.append(this._helpWrapper);

			var link = BX.create("A",
				{
					props: { className: "crm-entity-card-widget-help-link" },
					text: BX.prop.getString(data, "text", "For Your information")
				}
			);
			var url = BX.prop.getString(data, "url", "");
			if(url !== "")
			{
				link.href = helpUrl;
				link.target = "_blank";
			}
			else
			{
				link.href = "#";
				BX.bind(
					link,
					"click",
					function(e) {
						window.top.BX.Helper.show("redirect=detail&code=" + BX.prop.getString(data, "code", ""));
						e.preventDefault();
					}
				);
			}
			this._helpWrapper.appendChild(link);
		}
	};
	BX.Crm.EntityEditor.prototype.getMessage = function(name)
	{
		var message = BX.Crm.EntityEditor.superclass.getMessage.apply(this, [name]);
		if (message === name)
		{
			var m = BX.Crm.EntityEditor.messages;
			return m.hasOwnProperty(name) ? m[name] : message;
		}
		return message;
	};
	BX.Crm.EntityEditor.defaultInstance = null;
	BX.Crm.EntityEditor.items = {};
	BX.Crm.EntityEditor.get = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	if(typeof(BX.Crm.EntityEditor.messages) === "undefined")
	{
		BX.Crm.EntityEditor.messages = {};
	}
	BX.Crm.EntityEditor.setDefault = function(instance)
	{
		BX.Crm.EntityEditor.defaultInstance = instance;
	};
	BX.Crm.EntityEditor.getDefault = function()
	{
		return BX.Crm.EntityEditor.defaultInstance;
	};
	BX.Crm.EntityEditor.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion

//region ENTITY EDITOR MODE
if(typeof(BX.Crm.EntityEditorScopeConfig) === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorScopeConfig = BX.UI.EntityEditorScopeConfig;
}
//endregion

//region ENTITY EDITOR MODE QUEUE
if(typeof BX.Crm.EntityEditorModeQueue === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorModeQueue = BX.UI.EntityEditorModeQueue;
}
//endregion

//region ENTITY EDITOR MODE SWITCH
if(typeof BX.UI.EntityEditorModeSwitch === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorModeSwitch = BX.UI.EntityEditorModeSwitch;
}
//endregion


//region CONTROL VISIBILITY POLICY
if(typeof BX.Crm.EntityEditorVisibilityPolicy === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorVisibilityPolicy = BX.UI.EntityEditorModeSwitch;
}
//endregion
