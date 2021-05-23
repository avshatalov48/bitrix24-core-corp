BX.namespace("BX.Crm");

//region EDITOR
if(typeof BX.Crm.EntityEditor === "undefined")
{
	BX.Crm.EntityEditor = function()
	{
		this._id = "";
		this._settings = {};

		this._entityTypeId = 0;
		this._entityId = 0;

		this._userFieldManager = null;
		this._dupControlManager = null;
		this._bizprocManager = null;
		this._attributeManager = null;

		this._container = null;
		this._buttonContainer = null;
		this._createSectionButton = null;
		this._configMenuButton = null;
		this._configIcon = null;

		this._pageTitle = null;
		this._pageTitleInput = null;
		this._buttonWrapper = null;
		this._editPageTitleButton = null;
		this._copyPageUrlButton = null;

		this._formElement = null;
		this._ajaxForm = null;
		this._afterFormSubmitHandler = BX.delegate(this.onAfterFormSubmit, this);
		this._cancelFormSubmitHandler = BX.delegate(this.onCancelFormSubmit, this);

		this._controllers = null;
		this._controls = null;
		this._activeControls = null;
		this._toolPanel = null;

		this._model = null;
		this._scheme = null;
		this._config = null;
		this._context = null;
		this._contextId = "";
		this._externalContextId = "";

		this._mode = BX.Crm.EntityEditorMode.intermediate;

		this._isNew = false;
		this._readOnly = false;
		this._haslayout = false;

		this._enableRequiredUserFieldCheck = true;
		this._enableAjaxForm = true;
		this._enableSectionEdit = false;
		this._enableSectionCreation = false;
		this._enableModeToggle = true;
		this._enableVisibilityPolicy = true;
		this._enablePageTitleControls = true;
		this._enableCommunicationControls = true;
		this._enableToolPanel = true;
		this._enableBottomPanel = true;
		this._enableFieldsContextMenu = true;
		this._enableExternalLayoutResolvers = false;
		this._showEmptyFields = false;

		this._serviceUrl = "";
		this._htmlEditorConfigs = null;

		this._areAvailableSchemeElementsChanged = false;
		this._availableSchemeElements = null;

		this._dragPlaceHolder = null;
		this._dragContainerController = null;
		this._dropHandler = BX.delegate(this.onDrop, this);

		this._pageTitleExternalClickHandler = BX.delegate(this.onPageTitleExternalClick, this);
		this._pageTitleKeyPressHandler = BX.delegate(this.onPageTitleKeyPress, this);

		this._modeChangeNotifier = null;
		this._controlChangeNotifier = null;

		this._validators = null;
		this._modeSwitch = null;
		this._delayedSaveHandle = 0;

		this._isEmbedded = false;
		this._isRequestRunning = false;
		this._isConfigMenuShown = false;
		this._isReleased = false;

		this._enableCloseConfirmation = true;
		this._closeConfirmationHandler = BX.delegate(this.onCloseConfirmButtonClick, this);
		this._cancelConfirmationHandler = BX.delegate(this.onCancelConfirmButtonClick, this);

		this._sliderOpenHandler = BX.delegate(this.onSliderOpen, this);
		this._sliderCloseHandler = BX.delegate(this.onSliderClose, this);
		this._entityUpdateHandler = BX.delegate(this.onEntityUpdate, this);

		this._helpWrapper = null;
		this._dragConfig = {};
	};
	BX.Crm.EntityEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._model = BX.prop.get(this._settings, "model", null);
			this._scheme = BX.prop.get(this._settings, "scheme", null);
			this._config = BX.prop.get(this._settings, "config", null);

			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
			this._isNew = this._entityId <= 0;

			this._isEmbedded = BX.prop.getBoolean(this._settings, "isEmbedded", false);

			var container = BX.prop.get(this._settings, "container");
			if(!BX.type.isElementNode(container))
			{
				container = BX(BX.prop.get(this._settings, "containerId"));
			}

			this._container = container;
			if(!BX.type.isElementNode(container))
			{
				throw "Crm.EntityEditor: Could not find settings param 'container'.";
			}

			this._parentContainer = BX.findParent(this._container, { className: "crm-entity-section" }, false);
			this._buttonContainer = BX(BX.prop.get(this._settings, "buttonContainerId"));
			this._createSectionButton = BX(BX.prop.get(this._settings, "createSectionButtonId"));
			this._configMenuButton = BX(BX.prop.get(this._settings, "configMenuButtonId"));
			this._configIcon = BX(BX.prop.get(this._settings, "configIconId"));

			this._enableVisibilityPolicy = BX.prop.getBoolean(this._settings, "enableVisibilityPolicy", true);
			this._enableCommunicationControls = BX.prop.getBoolean(this._settings, "enableCommunicationControls", true);
			this._enablePageTitleControls = BX.prop.getBoolean(this._settings, "enablePageTitleControls", true);
			if(this._enablePageTitleControls)
			{
				this._pageTitle = BX("pagetitle");
				this._buttonWrapper = BX("pagetitle_btn_wrapper");
				this._editPageTitleButton = BX("pagetitle_edit");
				this._copyPageUrlButton = BX("page_url_copy_btn");
			}

			this.adjustSize();
			this.adjustTitle();

			//region Form
			this._formElement = BX.create("form", {props: { name: this._id + "_form"}});
			this._container.appendChild(this._formElement);

			this._enableRequiredUserFieldCheck = BX.prop.getBoolean(this._settings, "enableRequiredUserFieldCheck", true);

			this._enableAjaxForm = BX.prop.getBoolean(this._settings, "enableAjaxForm", true);
			if(this._enableAjaxForm)
			{
				this.initializeAjaxForm();
			}
			//endregion

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

			this._context = BX.prop.getObject(this._settings, "context", {});
			this._contextId = BX.prop.getString(this._settings, "contextId", "");
			this._externalContextId = BX.prop.getString(this._settings, "externalContextId", "");

			this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
			if(this._readOnly)
			{
				this._enableSectionEdit = this._enableSectionCreation = false;
			}
			else
			{
				this._enableSectionEdit = BX.prop.getBoolean(this._settings, "enableSectionEdit", false);
				this._enableSectionCreation = BX.prop.getBoolean(this._settings, "enableSectionCreation", false);
			}

			this._userFieldManager = BX.prop.get(this._settings, "userFieldManager", null);

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

			this._modeChangeNotifier = BX.CrmNotifier.create(this);
			this._controlChangeNotifier = BX.CrmNotifier.create(this);

			this._availableSchemeElements = this._scheme.getAvailableElements();

			this._controllers = [];
			this._controls = [];
			this._activeControls = [];
			this._modeSwitch = BX.Crm.EntityEditorModeSwitch.create(this._id, { editor: this });

			this._htmlEditorConfigs = BX.prop.getObject(this._settings, "htmlEditorConfigs", {});

			var elements = this._scheme.getElements();

			var initialMode = BX.Crm.EntityEditorMode.view;
			if(!this._readOnly)
			{
				initialMode = BX.Crm.EntityEditorMode.parse(BX.prop.getString(this._settings, "initialMode", ""));
			}
			this._mode = initialMode !== BX.Crm.EntityEditorMode.intermediate ? initialMode : BX.Crm.EntityEditorMode.view;

			this._enableModeToggle = false;
			if(!this._readOnly)
			{
				this._enableModeToggle = BX.prop.getBoolean(this._settings, "enableModeToggle", true);
			}

			if(this._isNew && !this._readOnly)
			{
				this._mode = BX.Crm.EntityEditorMode.edit;
			}

			var i, length;
			var controllerData = BX.prop.getArray(this._settings, "controllers", []);
			for(i = 0, length = controllerData.length; i < length; i++)
			{
				var controller = this.createController(controllerData[i]);
				if(controller)
				{
					this._controllers.push(controller);
				}
			}

			var element, control;
			for(i = 0, length = elements.length; i < length; i++)
			{
				element = elements[i];
				control = this.createControl(
					element.getType(),
					element.getName(),
					{ schemeElement: element, mode: BX.Crm.EntityEditorMode.view }
				);

				if(!control)
				{
					continue;
				}

				this._controls.push(control);
			}

			if(this._mode === BX.Crm.EntityEditorMode.edit && this._controls.length > 0)
			{
				for(i = 0, length = this._controls.length; i < length; i++)
				{
					control = this._controls[i];
					//Enable edit mode for required fields only.
					var priority = control.getEditPriority();
					if(priority === BX.Crm.EntityEditorPriority.high)
					{
						control.setMode(BX.Crm.EntityEditorMode.edit, { notify: false });
					}
				}

				if(this.getActiveControlCount() === 0)
				{
					this._controls[0].setMode(BX.Crm.EntityEditorMode.edit, { notify: false });
				}
			}

			//region Validators
			this._validators = [];
			var validatorConfigs = BX.prop.getArray(this._settings, "validators", []);
			for(i = 0, length = validatorConfigs.length; i < length; i++)
			{
				var validator = this.createValidator(validatorConfigs[i]);
				if(validator)
				{
					this._validators.push(validator);
				}
			}
			//endregion

			this._modeChangeNotifier.notify([ this ]);

			this._enableToolPanel = BX.prop.getBoolean(this._settings, "enableToolPanel", true);
			if(this._enableToolPanel)
			{
				this._toolPanel = BX.Crm.EntityEditorToolPanel.create(
					this._id,
					{
						container: this._isEmbedded ? this._formElement : document.body,
						editor: this,
						visible: false
					}
				);
			}

			this._enableBottomPanel = BX.prop.getBoolean(this._settings, "enableBottomPanel", true);
			this._enableFieldsContextMenu = BX.prop.getBoolean(this._settings, "enableFieldsContextMenu", true);
			this._enableExternalLayoutResolvers = BX.prop.getBoolean(this._settings, "enableExternalLayoutResolvers", false);
			this._showEmptyFields = BX.prop.getBoolean(this._settings, "showEmptyFields", false);

			BX.addCustomEvent(
				window,
				"Crm.InterfaceToolbar.MenuBuild",
				BX.delegate(this.onInterfaceToolbarMenuBuild, this)
			);

			//region D&D Config
			this._dragConfig = {};

			var sectionDragModes = {};
			sectionDragModes[BX.Crm.EntityEditorMode.names.view]
				= sectionDragModes[BX.Crm.EntityEditorMode.names.edit]
				= BX.prop.getBoolean(this._settings, "enableSectionDragDrop", true);

			this._dragConfig[BX.Crm.EditorDragObjectType.section] =
				{
					scope: BX.Crm.EditorDragScope.form,
					modes: sectionDragModes
				};

			var fieldDragModes = {};
			fieldDragModes[BX.Crm.EntityEditorMode.names.view]
				= fieldDragModes[BX.Crm.EntityEditorMode.names.edit]
				= BX.prop.getBoolean(this._settings, "enableFieldDragDrop", true);

			this._dragConfig[BX.Crm.EditorDragObjectType.field] =
				{
					scope: BX.Crm.EditorDragScope.form,
					modes: fieldDragModes
				};

			if(this.canChangeScheme())
			{
				this._dragContainerController = BX.Crm.EditorDragContainerController.create(
					"editor_" + this.getId(),
					{
						charge: BX.Crm.EditorSectionDragContainer.create({ editor: this }),
						node: this._formElement
					}
				);
				this._dragContainerController.addDragFinishListener(this._dropHandler);
			}
			//endregion

			this.layout();

			BX.bind(window, "resize", BX.debounce(BX.delegate(this.onResize, this), 50));
			//BX.bind(window, "resize", BX.delegate(this.onResize, this));

			BX.addCustomEvent("SidePanel.Slider:onOpenComplete", this._sliderOpenHandler);
			BX.addCustomEvent("SidePanel.Slider:onClose", this._sliderCloseHandler);

			BX.addCustomEvent("onCrmEntityUpdate", this._entityUpdateHandler);

			var eventArgs =
				{
					id: this._id,
					externalContext: this._externalContextId,
					context: this._contextId,
					entityTypeId: this._entityTypeId,
					entityId: this._entityId,
					model: this._model
				};
			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onInit", [ this, eventArgs ]);
		},
		release: function()
		{
			//console.log("EntityEditor::release: %s", this.getId());

			if(this._dragContainerController)
			{
				this._dragContainerController.removeDragFinishListener(this._dropHandler);
				this._dragContainerController.release();
				this._dragContainerController = null;
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].clearLayout();
			}

			BX.removeCustomEvent("onCrmEntityUpdate", this._entityUpdateHandler);
			BX.removeCustomEvent("SidePanel.Slider:onOpenComplete", this._sliderOpenHandler);
			BX.removeCustomEvent("SidePanel.Slider:onClose", this._sliderCloseHandler);

			this.releaseAjaxForm();
			this._container = BX.remove(this._container);

			this._haslayout = false;
			this._isReleased = true;
		},
		clone: function(params)
		{
			//var settings = Object.assign({}, this._settings);
			var wrapper = BX(BX.prop.get(params, "wrapper"));
			if(!BX.type.isElementNode(wrapper))
			{
				throw "Crm.EntityEditor: Could not find param 'wrapper'.";
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
		},
		onSliderOpen: function(event)
		{
			//Reset close confirmation flag
			this._enableCloseConfirmation = true;
			var eventArgs =
				{
					id: this._id,
					externalContext: this._externalContextId,
					context: this._contextId,
					entityTypeId: this._entityTypeId,
					entityId: this._entityId,
					model: this._model
				};
			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onOpen", [ this, eventArgs ]);
		},
		onSliderClose: function(event)
		{
			if(!this._enableCloseConfirmation)
			{
				return;
			}

			var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if(slider !== event.getSlider())
			{
				return;
			}

			if(!slider.isOpen())
			{
				return;
			}

			if(!this.hasChangedControls() && !this.hasChangedControllers())
			{
				return;
			}

			event.denyAction();

			if(BX.Crm.EditorAuxiliaryDialog.isItemOpened("close_confirmation"))
			{
				return;
			}

			BX.Crm.EditorAuxiliaryDialog.create(
				"close_confirmation",
				{
					title: BX.message("CRM_EDITOR_CONFIRMATION"),
					content: BX.message("CRM_EDITOR_CLOSE_CONFIRMATION"),
					zIndex: 100,
					buttons:
						[
							{
								id: "close",
								type: BX.Crm.DialogButtonType.accept,
								text: BX.message("JS_CORE_WINDOW_CLOSE"),
								callback: this._closeConfirmationHandler
							},
							{
								id: "cancel",
								type: BX.Crm.DialogButtonType.cancel,
								text: BX.message("JS_CORE_WINDOW_CANCEL"),
								callback: this._closeConfirmationHandler
							}
						]
				}
			).open();
		},
		onCloseConfirmButtonClick: function(button)
		{
			button.getDialog().close();

			if(button.getId() === "close")
			{
				this._enableCloseConfirmation = false;
				top.BX.SidePanel.Instance.getSliderByWindow(window).close();
			}
		},
		onCancelConfirmButtonClick: function(button)
		{
			button.getDialog().close();

			if(button.getId() === "yes")
			{
				this.innerCancel();
			}
		},
		onEntityUpdate: function(eventParams)
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
		},
		initializeAjaxForm: function()
		{
			if(this._ajaxForm)
			{
				return;
			}

			this._ajaxForm = BX.Crm.AjaxForm.create(
				this._id,
				{
					elementNode: this._formElement,
					config:
					{
						url: this._serviceUrl,
						method: "POST",
						dataType: "json",
						processData : true,
						onsuccess: BX.delegate(this.onSaveSuccess, this),
						data:
						{
							"ACTION": "SAVE",
							"ACTION_ENTITY_ID": this._entityId,
							"ACTION_ENTITY_TYPE": BX.CrmEntityType.resolveAbbreviation(
								BX.CrmEntityType.resolveName(this._entityTypeId)
							),
							"ENABLE_REQUIRED_USER_FIELD_CHECK": this._enableRequiredUserFieldCheck ? 'Y' : 'N'
						}
					}
				}
			);

			//Prevent submit form by Enter if only one input on form
			this._formElement.setAttribute("onsubmit", "return false;");

			BX.addCustomEvent(this._ajaxForm, "onAfterSubmit", this._afterFormSubmitHandler);
			BX.addCustomEvent(this._ajaxForm, "onSubmitCancel", this._cancelFormSubmitHandler);
		},
		releaseAjaxForm: function()
		{
			if(!this._ajaxForm)
			{
				return;
			}

			BX.removeCustomEvent(this._ajaxForm, "onAfterSubmit", this._afterFormSubmitHandler);
			BX.removeCustomEvent(this._ajaxForm, "onSubmitCancel", this._cancelFormSubmitHandler);
			this._ajaxForm = null;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.resolveName(this._entityTypeId);
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getOwnerInfo: function()
		{
			return this._model.getOwnerInfo();
		},
		getMode: function()
		{
			return this._mode;
		},
		getModel: function()
		{
			return this._model;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getContext: function()
		{
			return this._context;
		},
		getExternalContextId: function()
		{
			return this._externalContextId;
		},
		getScheme: function()
		{
			return this._scheme;
		},
		isVisible: function()
		{
			return this._container.offsetParent !== null;
		},
		isVisibilityPolicyEnabled: function()
		{
			return this._enableVisibilityPolicy;
		},
		isSectionEditEnabled: function()
		{
			return this._enableSectionEdit;
		},
		isSectionCreationEnabled: function()
		{
			return this._enableSectionCreation && this.canChangeScheme();
		},
		isFieldsContextMenuEnabled: function()
		{
			return this._enableFieldsContextMenu;
		},
		isModeToggleEnabled: function()
		{
			return this._enableModeToggle;
		},
		isPersistent: function()
		{
			return(this._entityId > 0 && this._entityId === this._model.getIntegerField("ID", 0));
		},
		isNew: function()
		{
			return this._isNew;
		},
		isReadOnly: function()
		{
			return this._readOnly;
		},
		isEmbedded: function()
		{
			return this._isEmbedded;
		},
		isEditInViewEnabled: function()
		{
			return this._entityId > 0;
		},
		isNeedToDisplayEmptyFields: function()
		{
			return this._showEmptyFields;
		},
		areCommunicationControlsEnabled: function()
		{
			return this._enableCommunicationControls;
		},
		prepareFieldLayoutOptions: function(field)
		{
			var hasContent = field.hasContentToDisplay();
			var result = { isNeedToDisplay: (hasContent || this._showEmptyFields) };
			if(this._enableExternalLayoutResolvers)
			{
				var eventArgs =
					{
						id: this._id,
						field: field,
						hasContent: hasContent,
						showEmptyFields: this._showEmptyFields,
						layoutOptions: result
					};

				BX.onCustomEvent(
					window,
					"BX.Crm.EntityEditor:onResolveFieldLayoutOptions",
					[ this, eventArgs ]
				);
			}
			return result;
		},
		getEntityCreateUrl: function(entityTypeName)
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
		},
		getEntityEditUrl: function(entityTypeName, entityId)
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
		},
		getEntityRequisiteSelectUrl: function(entityTypeName, entityId)
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
		},
		getRequisiteEditUrl: function(id)
		{
			return BX.prop.getString(this._settings, "requisiteEditUrl", "").replace(/#requisite_id#/gi, id);
		},
		getDetailManager: function()
		{
			if(typeof(BX.Crm.EntityDetailManager) === "undefined")
			{
				return null;
			}

			return BX.Crm.EntityDetailManager.get(BX.prop.getString(this._settings, "detailManagerId", ""));
		},
		getUserFieldManager: function()
		{
			return this._userFieldManager;
		},
		getBizprocManager: function()
		{
			return this._bizprocManager;
		},
		getAttributeManager: function()
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
							lockScript: BX.prop.getString(settings, "LOCK_SCRIPT", ""),
							captions: BX.prop.getObject(settings, "CAPTIONS", {})
						}
					);
				}
			}
			return this._attributeManager;
		},
		getHtmlEditorConfig: function(fieldName)
		{
			return BX.prop.getObject(this._htmlEditorConfigs, fieldName, null);
		},
		//region Validators
		createValidator: function(settings)
		{
			settings["editor"] = this;
			return BX.Crm.EntityEditorValidatorFactory.create(
				BX.prop.getString(settings, "type", ""),
				settings
			);
		},
		//endregion
		//region Controls & Events
		getControlByIndex: function(index)
		{
			return (index >= 0 && index < this._controls.length) ? this._controls[index] : null;
		},
		getControlIndex: function(control)
		{
			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				if(this._controls[i] === control)
				{
					return i;
				}
			}
			return -1;
		},
		getControls: function()
		{
			return this._controls;
		},
		getControlCount: function()
		{
			return this._controls.length;
		},
		createControl: function(type, controlId, settings)
		{
			settings["serviceUrl"] = this._serviceUrl;
			settings["container"] = this._formElement;
			settings["model"] = this._model;
			settings["editor"] = this;

			return BX.Crm.EntityEditorControlFactory.create(type, controlId, settings);
		},
		addControlAt: function(control, index)
		{
			var options = {};
			if(index < this._controls.length)
			{
				options["anchor"] = this._controls[index].getWrapper();
				this._controls.splice(index, 0, control);
			}
			else
			{
				this._controls.push(control);
			}
			control.layout(options);
		},
		moveControl: function(control, index)
		{
			var qty = this._controls.length;
			var lastIndex = qty - 1;
			if(index < 0  || index > qty)
			{
				index = lastIndex;
			}

			var currentIndex = this.getControlIndex(control);
			if(currentIndex < 0 || currentIndex === index)
			{
				return false;
			}

			control.clearLayout();
			this._controls.splice(currentIndex, 1);
			qty--;

			var anchor = index < qty
				? this._controls[index].getWrapper()
				: null;

			if(index < qty)
			{
				this._controls.splice(index, 0, control);
			}
			else
			{
				this._controls.push(control);
			}

			if(anchor)
			{
				control.layout({ anchor: anchor });
			}
			else
			{
				control.layout();
			}

			this._config.moveSchemeElement(control.getSchemeElement(), index);
		},
		removeControl: function(control)
		{
			var index = this.getControlIndex(control);
			if(index < 0)
			{
				return false;
			}

			this.processControlRemove(control);
			control.clearLayout();
			this._controls.splice(index, 1);
		},
		getControlById: function(id)
		{
			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				var control = this._controls[i];
				if(control.getId() === id)
				{
					return control;
				}

				var child = control.getChildById(id);
				if(child)
				{
					return child;
				}
			}
			return null;
		},
		getControlByIdRecursive: function(name, controls)
		{
			var res;

			if(!controls)
			{
				controls = this.getControls();
			}

			for (var i=0; i < controls.length; i++)
			{
				if (!controls[i] instanceof BX.Crm.EntityEditorControl)
				{
					continue;
				}

				if(controls[i].getId() === name)
				{
					return controls[i];
				}
				else if (controls[i] instanceof BX.Crm.EntityEditorSection)
				{
					if(res = this.getControlByIdRecursive(name, controls[i].getChildren()))
					{
						return res;
					}
				}
			}

			return null;
		},
		getAllControls: function(controls)
		{
			var result = [], res;

			if(!controls)
			{
				controls = this.getControls();
			}

			for (var i=0; i < controls.length; i++)
			{
				if (controls[i] instanceof BX.Crm.EntityEditorControl)
				{
					if (controls[i] instanceof BX.Crm.EntityEditorSection)
					{
						if(res = this.getAllControls(controls[i].getChildren()))
						{
							result = result.concat(res);
						}
					}
					else
					{
						result.push(controls[i]);
					}
				}
			}

			return result;
		},
		getActiveControlCount: function()
		{
			return this._activeControls.length;
		},
		getActiveControlIndex: function(control)
		{
			var length = this._activeControls.length;
			if(length === 0)
			{
				return -1;
			}

			for(var i = 0; i < length; i++)
			{
				if(this._activeControls[i] === control)
				{
					return i;
				}
			}
			return -1;
		},
		getActiveControlById: function(id, recursive)
		{
			recursive = !!recursive;
			var length = this._activeControls.length;
			if(length === 0)
			{
				return null;
			}

			for(var i = 0; i < length; i++)
			{
				var control = this._activeControls[i];
				if(control.getId() === id)
				{
					return control;
				}

				if(recursive)
				{
					var child = control.getChildById(id);
					if(child)
					{
						return child;
					}
				}
			}
			return null;
		},
		getActiveControlByIndex: function(index)
		{
			return index >= 0 && index < this._activeControls.length ? this._activeControls[index] : null;
		},
		registerActiveControl: function(control)
		{
			var index = this.getActiveControlIndex(control);
			if(index >= 0)
			{
				return;
			}

			this._activeControls.push(control);
			control.setActive(true);
			if(this._mode !== BX.Crm.EntityEditorMode.edit)
			{
				this._mode = BX.Crm.EntityEditorMode.edit;
				this._modeChangeNotifier.notify([ this ]);
			}
		},
		unregisterActiveControl: function(control)
		{
			var index = this.getActiveControlIndex(control);
			if(index < 0)
			{
				return;
			}

			this._activeControls.splice(index, 1);
			control.setActive(false);
			if(this._activeControls.length === 0 && this._mode !== BX.Crm.EntityEditorMode.view)
			{
				this._mode = BX.Crm.EntityEditorMode.view;
				this._modeChangeNotifier.notify([ this ]);
			}
		},
		releaseActiveControls: function(options)
		{
			//region Release Event
			var eventArgs =
				{
					id: this._id,
					externalContext: this._externalContextId,
					context: this._contextId,
					entityTypeId: this._entityTypeId,
					entityId: this._entityId,
					model: this._model
				};
			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onRelease", [ this, eventArgs ]);
			//endregion

			for(var i = 0, length = this._activeControls.length; i < length; i++)
			{
				var control = this._activeControls[i];
				control.setActive(false);
				control.toggleMode(false, options);
			}
			this._activeControls = [];
		},
		hasChangedControls: function()
		{
			for(var i = 0, length = this._activeControls.length; i < length; i++)
			{
				if(this._activeControls[i].isChanged())
				{
					return true;
				}
			}
			return false;
		},
		hasChangedControllers: function()
		{
			for(var i = 0, length = this._controllers.length; i < length; i++)
			{
				if(this._controllers[i].isChanged())
				{
					return true;
				}
			}
			return false;
		},
		isWaitingForInput: function()
		{
			if(this._mode !== BX.Crm.EntityEditorMode.edit)
			{
				return false;
			}

			for(var i = 0, length = this._activeControls.length; i < length; i++)
			{
				if(this._activeControls[i].isWaitingForInput())
				{
					return true;
				}
			}
			return false;
		},
		processControlModeChange: function(control)
		{
			if(control.getMode() === BX.Crm.EntityEditorMode.edit)
			{
				this.registerActiveControl(control);
			}
			else //BX.Crm.EntityEditorMode.view
			{
				this.unregisterActiveControl(control);
			}

			if(this.getActiveControlCount() > 0)
			{
				this.showToolPanel();
			}
			else
			{
				this.hideToolPanel();
			}
		},
		processControlChange: function(control, params)
		{
			if(!this._enableCloseConfirmation)
			{
				this._enableCloseConfirmation = true;
			}

			this.showToolPanel();
			this._controlChangeNotifier.notify([ params ]);
		},
		processControlAdd: function(control)
		{
			this.removeAvailableSchemeElement(control.getSchemeElement());
		},
		processControlMove: function(control)
		{
		},
		processControlRemove: function(control)
		{
			if(control instanceof BX.Crm.EntityEditorField || control instanceof BX.Crm.EntityEditorSubsection)
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
		},
		//endregion
		//region Available Scheme Elements
		getAvailableSchemeElements: function()
		{
			return this._availableSchemeElements;
		},
		addAvailableSchemeElement: function(schemeElement)
		{
			this._availableSchemeElements.push(schemeElement);
			this._areAvailableSchemeElementsChanged = true;
			this.notifyAvailableSchemeElementsChanged();
		},
		removeAvailableSchemeElement: function(element)
		{
			var index = this.getAvailableSchemeElementIndex(element);
			if(index < 0)
			{
				return;
			}

			this._availableSchemeElements.splice(index, 1);
			this._areAvailableSchemeElementsChanged = true;
			this.notifyAvailableSchemeElementsChanged();
		},
		getAvailableSchemeElementIndex: function(element)
		{
			var schemeElements = this._availableSchemeElements;
			for(var i = 0, length = schemeElements.length; i < length; i++)
			{
				if(schemeElements[i] === element)
				{
					return i;
				}
			}
			return -1;
		},
		getAvailableSchemeElementByName: function(name)
		{
			var schemeElements = this._availableSchemeElements;
			for(var i = 0, length = schemeElements.length; i < length; i++)
			{
				var schemeElement = schemeElements[i];
				if(schemeElement.getName() === name)
				{
					return schemeElement;
				}
			}
			return null;
		},
		hasAvailableSchemeElements: function()
		{
			return (this._availableSchemeElements.length > 0);
		},
		getSchemeElementByName: function(name)
		{
			return this._scheme.findElementByName(name, { isRecursive: true });
		},
		notifyAvailableSchemeElementsChanged: function()
		{
			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].processAvailableSchemeElementsChange();
			}
		},
		//endregion
		//region Controllers
		createController: function(data)
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
		},
		processControllerChange: function(controller)
		{
			if(!this._enableCloseConfirmation)
			{
				this._enableCloseConfirmation = true;
			}

			this.showToolPanel();
		},
		//endregion
		//region Layout
		getContainer: function()
		{
			return this._container;
		},
		prepareContextDataLayout: function(context, parentName)
		{
			for(var key in context)
			{
				if(!context.hasOwnProperty(key))
				{
					continue;
				}

				var item = context[key];
				var name = key;
				if(BX.type.isNotEmptyString(parentName))
				{
					name = parentName + "[" + name + "]";
				}
				if(BX.type.isPlainObject(item))
				{
					this.prepareContextDataLayout(item, name);
				}
				else
				{
					this._formElement.appendChild(
						BX.create("input", { props: { type: "hidden", name: name, value: item } })
					);
				}
			}
		},
		hasLayout: function()
		{
			return this._haslayout;
		},
		layout: function()
		{
			var eventArgs = { cancel: false };
			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onBeforeLayout", [ this, eventArgs ]);
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
					edit: BX.Crm.EntityUserFieldLayoutLoader.create(
						this._id,
						{ mode: BX.Crm.EntityEditorMode.edit, enableBatchMode: true, owner: this }
					),
					view: BX.Crm.EntityUserFieldLayoutLoader.create(
						this._id,
						{ mode: BX.Crm.EntityEditorMode.view, enableBatchMode: true, owner: this }
					)
				};

			var i, length, control;
			for(i = 0, length = this._controls.length; i < length; i++)
			{
				control = this._controls[i];
				var mode = control.getMode();

				var layoutOptions =
					{
						userFieldLoader: userFieldLoaders[BX.Crm.EntityEditorMode.getName(mode)],
						enableFocusGain: !this._isEmbedded
					};

				if(i === 0 && enableInlineEditSpotlight && mode === BX.Crm.EntityEditorMode.view)
				{
					layoutOptions["lighting"] =
						{
							id: BX.prop.getString(this._settings, "inlineEditSpotlightId", ""),
							text: this.getMessage("inlineEditHint")
						};
				}

				control.layout(layoutOptions);

				if(mode === BX.Crm.EntityEditorMode.edit)
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

			if(this._mode === BX.Crm.EntityEditorMode.edit && this._dupControlManager.isEnabled())
			{
				this._dupControlManager.search();
			}

			if(this._enableBottomPanel && this._buttonContainer)
			{
				this._buttonContainer.style.display = "";
			}

			this.adjustButtons();
			this._haslayout = true;

			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onLayout", [ this ]);
		},
		refreshLayout: function(options)
		{
			var userFieldLoaders =
				{
					edit: BX.Crm.EntityUserFieldLayoutLoader.create(
						this._id,
						{ mode: BX.Crm.EntityEditorMode.edit, enableBatchMode: true, owner: this }
					),
					view: BX.Crm.EntityUserFieldLayoutLoader.create(
						this._id,
						{ mode: BX.Crm.EntityEditorMode.view, enableBatchMode: true, owner: this }
					)
				};


			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				var control = this._controls[i];
				var mode = control.getMode();

				var layoutOptions = BX.mergeEx(
					options,
					{
						userFieldLoader: userFieldLoaders[BX.Crm.EntityEditorMode.getName(mode)],
						enableFocusGain: !this._isEmbedded
					}
				);
				control.refreshLayout(layoutOptions);
			}

			for(var key in userFieldLoaders)
			{
				if(userFieldLoaders.hasOwnProperty(key))
				{
					userFieldLoaders[key].runBatch();
				}
			}

			this.adjustButtons();

			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onRefreshLayout", [ this ]);
		},
		//endregion
		switchControlMode: function(control, mode, options)
		{
			if(!this.isModeToggleEnabled())
			{
				return;
			}

			if(mode === BX.Crm.EntityEditorMode.view)
			{
				if(control.checkModeOption(BX.Crm.EntityEditorModeOptions.saveOnExit))
				{
					this._modeSwitch.getQueue().add(control, BX.Crm.EntityEditorMode.view);
					this._modeSwitch.run();
				}
				else
				{
					control.setMode(mode, { options: options, notify: true });
					control.refreshLayout();
				}
			}
			else// if(mode === BX.Crm.EntityEditorMode.edit)
			{
				if(!BX.Crm.EntityEditorModeOptions.check(options, BX.Crm.EntityEditorModeOptions.exclusive))
				{
					control.setMode(BX.Crm.EntityEditorMode.edit, { options: options, notify: true });
					control.refreshLayout();
				}
				else
				{
					var queuedControlQty = 0;
					for(var i = 0, length = this._activeControls.length; i < length; i++)
					{
						var activeControl = this._activeControls[i];
						if(activeControl.checkModeOption(BX.Crm.EntityEditorModeOptions.saveOnExit))
						{
							this._modeSwitch.getQueue().add(activeControl, BX.Crm.EntityEditorMode.view, options);
							queuedControlQty++;
						}
					}

					if(queuedControlQty > 0)
					{
						this._modeSwitch.getQueue().add(control, BX.Crm.EntityEditorMode.edit, options);
						this._modeSwitch.run();
					}
					else
					{
						control.setMode(BX.Crm.EntityEditorMode.edit, { options: options, notify: true });
						control.refreshLayout();
					}
				}
			}
		},
		switchToViewMode: function(options)
		{
			this.releaseActiveControls(options);
			this.hideToolPanel();
		},
		switchTitleMode: function(mode)
		{
			if(mode === BX.Crm.EntityEditorMode.edit)
			{
				this._pageTitle.style.display = "none";

				if(this._buttonWrapper)
				{
					this._buttonWrapper.style.display = "none";
				}

				this._pageTitleInput = BX.create(
					"input",
					{
						props:
						{
							type: "text",
							className: "pagetitle-item crm-pagetitle-item",
							value: this._model.getCaption()
						}
					}
				);
				//this._pageTitle.parentNode.insertBefore(this._pageTitleInput, this._buttonWrapper);
				this._pageTitle.parentNode.insertBefore(this._pageTitleInput, this._pageTitle);
				this._pageTitleInput.focus();

				window.setTimeout(
					BX.delegate(
						function()
							{
								BX.bind(document, "click", this._pageTitleExternalClickHandler);
								BX.bind(this._pageTitleInput, "keyup", this._pageTitleKeyPressHandler);
							},
						this
					),
					300
				);
			}
			else
			{
				if(this._pageTitleInput)
				{
					this._pageTitleInput = BX.remove(this._pageTitleInput);
				}

				this._pageTitle.innerHTML = BX.util.htmlspecialchars(this._model.getCaption());
				this._pageTitle.style.display = "";

				if(this._buttonWrapper)
				{
					this._buttonWrapper.style.display = "";
				}

				BX.unbind(document, "click", this._pageTitleExternalClickHandler);
				BX.unbind(this._pageTitleInput, "keyup", this._pageTitleKeyPressHandler);

				this.adjustTitle();
			}
		},
		adjustTitle: function()
		{
			if(!this._enablePageTitleControls)
			{
				return;
			}

			if(!this._buttonWrapper)
			{
				return;
			}

			var caption = this._model.getCaption().trim();
			var captionTail = "";

			document.title = caption;
			if (BX.getClass("BX.SidePanel.Instance.updateBrowserTitle"))
			{
				BX.SidePanel.Instance.updateBrowserTitle();
			}

			var match = caption.match(/\s+\S+\s*$/);
			if(match)
			{
				captionTail = caption.substr(match["index"]);
				caption = caption.substr(0, match["index"]);
			}
			else
			{
				captionTail = caption;
				caption = "";
			}

			BX.cleanNode(this._buttonWrapper);
			if(captionTail !== "")
			{
				this._buttonWrapper.appendChild(document.createTextNode(captionTail));
			}
			if(this._editPageTitleButton)
			{
				this._buttonWrapper.appendChild(this._editPageTitleButton);
			}
			if(this._copyPageUrlButton)
			{
				this._buttonWrapper.appendChild(this._copyPageUrlButton);
			}

			this._pageTitle.innerHTML = BX.util.htmlspecialchars(caption);
		},
		adjustSize: function()
		{
			if(!this._enablePageTitleControls)
			{
				return;
			}

			if(!this._pageTitle)
			{
				return;
			}

			var wrapper = this._pageTitle.parentNode ? this._pageTitle.parentNode : this._pageTitle;
			BX.addClass(wrapper, "crm-pagetitle")
			var enableNarrowSize = wrapper.offsetWidth <= 480 && this._model.getCaption().length >= 40;
			if(enableNarrowSize && !BX.hasClass(wrapper, "pagetitle-narrow"))
			{
				BX.addClass(wrapper, "pagetitle-narrow");
			}
			else if(!enableNarrowSize && BX.hasClass(wrapper, "pagetitle-narrow"))
			{
				BX.removeClass(wrapper, "pagetitle-narrow");
			}

		},
		adjustButtons: function()
		{
			//Move configuration menu button to last section if bottom panel is hidden.
			if(this._config.isScopeToggleEnabled() && !this._enableBottomPanel && this._controls.length > 0)
			{
				this._controls[this._controls.length - 1].ensureButtonPanelWrapperCreated().appendChild(
					BX.create(
						"span",
						{
							props:
								{
									className: this._config.getScope() === BX.Crm.EntityConfigScope.common
										? "crm-entity-card-common" : "crm-entity-card-private"
								},
							events: { click: BX.delegate(this.onConfigMenuButtonClick, this) }
						}
					)
				);
			}
		},
		showToolPanel: function()
		{
			if(!this._toolPanel || this._toolPanel.isVisible())
			{
				return;
			}

			this._toolPanel.setVisible(true);
			if(this._parentContainer)
			{
				this._parentContainer.style.paddingBottom = "50px";

				document.body.style.paddingBottom = "60px";
				document.body.style.height = "auto";
			}
		},
		hideToolPanel: function()
		{
			if(!this._toolPanel || !this._toolPanel.isVisible())
			{
				return;
			}

			this._toolPanel.setVisible(false);
			if(this._parentContainer)
			{
				this._parentContainer.style.paddingBottom = "";

				document.body.style.paddingBottom = "";
				document.body.style.height = "";
			}
		},
		showMessageDialog: function(id, title, content)
		{
			var dlg = BX.Crm.EditorAuxiliaryDialog.create(
				id,
				{
					title: title,
					content: content,
					buttons:
						[
							{
								id: "continue",
								type: BX.Crm.DialogButtonType.accept,
								text: BX.message("CRM_EDITOR_CONTINUE"),
								callback: function(button) { button.getDialog().close(); }
							}
						]
				}
			);
			dlg.open();
		},
		addModeChangeListener: function(listener)
		{
			this._modeChangeNotifier.addListener(listener);
		},
		removeModeChangeListener: function(listener)
		{
			this._modeChangeNotifier.removeListener(listener);
		},
		addControlChangeListener: function(listener)
		{
			this._controlChangeNotifier.addListener(listener);
		},
		removeControlChangeListener: function(listener)
		{
			this._controlChangeNotifier.removeListener(listener);
		},
		getMessage: function(name)
		{
			var m = BX.Crm.EntityEditor.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getFormElement: function()
		{
			return this._formElement;
		},
		isChanged: function()
		{
			return this._isNew || this.hasChangedControls() || this.hasChangedControllers();
		},
		savePageTitle: function()
		{
			if(!this._pageTitleInput)
			{
				return;
			}

			var title = BX.util.trim(this._pageTitleInput.value);
			if(title === "")
			{
				return;
			}

			this._model.setCaption(title);
			var data =
				{
					"ACTION": "SAVE",
					"ACTION_ENTITY_ID": this._entityId,
					"ACTION_ENTITY_TYPE": BX.CrmEntityType.resolveAbbreviation(
						BX.CrmEntityType.resolveName(this._entityTypeId)
					),
					"PARAMS": BX.prop.getObject(this._context, "PARAMS", {})
				};

			this._model.prepareCaptionData(data);

			for(var i = 0, length = this._controllers.length; i < length; i++)
			{
				data = this._controllers[i].onBeforesSaveControl(data);
			}

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: data,
					onsuccess: BX.delegate(this.onSaveSuccess, this)
				}
			);
		},
		saveChanged: function()
		{
			if(!this._isNew && !this.hasChangedControls() && !this.hasChangedControllers() && !this.isWaitingForInput())
			{
				this._modeSwitch.reset();
				this.releaseActiveControls();
				this.refreshLayout({ reset: true });
				this.hideToolPanel();
			}
			else
			{
				this._modeSwitch.reset();
				this._modeSwitch.getQueue().addBatch(this._activeControls, BX.Crm.EntityEditorMode.view);
				this._modeSwitch.run();
			}
		},
		saveDelayed: function(delay)
		{
			if(typeof(delay) === "undefined")
			{
				delay = 0;
			}

			if(this._delayedSaveHandle > 0)
			{
				window.clearTimeout(this._delayedSaveHandle);
			}
			this._delayedSaveHandle = window.setTimeout(BX.delegate(this.save, this), delay);
		},
		save: function()
		{
			if(this._toolPanel)
			{
				this._toolPanel.setLocked(true);
			}

			var result = BX.Crm.EntityValidationResult.create();
			this.validate(result).then(
				BX.delegate(
					function()
					{
						if(this._bizprocManager)
						{
							return this._bizprocManager.onBeforeSave(result);
						}

						var promise = new BX.Promise();
						window.setTimeout(function(){ promise.fulfill(); }, 0);
						return promise;
					},
					this
				)
			).then(
				BX.delegate(
					function()
					{
						if(result.getStatus())
						{
							this.innerSave();
							if(this._bizprocManager)
							{
								this._bizprocManager.onAfterSave();
							}
						}
						else
						{
							if(this.isVisible())
							{
								var field = result.getTopmostField();
								if(field)
								{
									field.focus();
								}
							}

							if(this._toolPanel)
							{
								this._toolPanel.setLocked(false);
							}

							BX.onCustomEvent(window, "BX.Crm.EntityEditor:onFailedValidation", [ this, result ]);
						}
					},
					this
				)
			);

			if(this._delayedSaveHandle > 0)
			{
				this._delayedSaveHandle = 0;
			}
		},
		saveControl: function(control)
		{
			if(this._entityId <= 0)
			{
				return;
			}

			var result = BX.Crm.EntityValidationResult.create();
			control.validate(result);

			if(!result.getStatus())
			{
				return;
			}

			var data =
			{
				"ACTION": "SAVE",
				"ACTION_ENTITY_ID": this._entityId,
				"ACTION_ENTITY_TYPE": BX.CrmEntityType.resolveAbbreviation(
					BX.CrmEntityType.resolveName(this._entityTypeId)
				)
			};

			data = BX.mergeEx(data, this._context);
			control.save();
			control.prepareSaveData(data);

			for(var i = 0, length = this._controllers.length; i < length; i++)
			{
				data = this._controllers[i].onBeforesSaveControl(data);
			}

			BX.ajax(
				{
					method: "POST",
					dataType: "json",
					url: this._serviceUrl,
					data: data,
					onsuccess: BX.delegate(this.onSaveSuccess, this)
				}
			);
		},
		saveData: function(data)
		{
			if(this._entityId <= 0)
			{
				return;
			}

			data = BX.mergeEx(data, this._context);
			data = BX.mergeEx(
				data,
				{
					"ACTION": "SAVE",
					"ACTION_ENTITY_ID": this._entityId,
					"ACTION_ENTITY_TYPE": BX.CrmEntityType.resolveAbbreviation(
						BX.CrmEntityType.resolveName(this._entityTypeId)
					)
				}
			);

			BX.ajax(
				{
					method: "POST",
					dataType: "json",
					url: this._serviceUrl,
					data: data,
					onsuccess: BX.delegate(this.onSaveSuccess, this)
				}
			);
		},
		validate: function(result)
		{
			for(var i = 0, length = this._activeControls.length; i < length; i++)
			{
				this._activeControls[i].validate(result);
			}

			var promise = new BX.Promise();
			this._userFieldManager.validate(result).then(
				BX.delegate(function() { promise.fulfill(); }, this)
			);
			return promise;
		},
		isRequestRunning: function()
		{
			return this._isRequestRunning;
		},
		innerSave: function()
		{
			if(this._isRequestRunning)
			{
				return;
			}

			var i, length;
			for(i = 0, length = this._controllers.length; i < length; i++)
			{
				this._controllers[i].onBeforeSubmit();
			}

			for(i = 0, length = this._activeControls.length; i < length; i++)
			{
				var control = this._activeControls[i];

				control.save();
				control.onBeforeSubmit();

				if(control.isSchemeChanged())
				{
					this._config.updateSchemeElement(control.getSchemeElement());
				}
			}

			if(this._areAvailableSchemeElementsChanged)
			{
				this._scheme.setAvailableElements(this._availableSchemeElements);
				this._areAvailableSchemeElementsChanged = false;
			}

			if(this._config && this._config.isChanged())
			{
				this._config.save(false);
			}

			//region Rise Save Event
			var eventArgs =
				{
					id: this._id,
					externalContext: this._externalContextId,
					context: this._contextId,
					entityTypeId: this._entityTypeId,
					entityId: this._entityId,
					model: this._model,
					cancel: false
				};

			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onSave", [ this, eventArgs ]);

			var enableCloseConfirmation = BX.prop.getBoolean(
				eventArgs,
				"enableCloseConfirmation",
				null
			);
			if(BX.type.isBoolean(enableCloseConfirmation))
			{
				this._enableCloseConfirmation = enableCloseConfirmation;
			}

			if(eventArgs["cancel"])
			{
				return;
			}

			if(this._ajaxForm)
			{
				var detailManager = this.getDetailManager();
				if(detailManager)
				{
					var params =  detailManager.prepareAnalyticParams(
						this._entityId > 0 ? "update" : "create",
						{ embedded: this.isEmbedded() ? "Y" : "N" }
					);

					if(params)
					{
						this._ajaxForm.addUrlParams(params);
					}
				}

				this._ajaxForm.submit();
			}
			//endregion
		},
		cancel: function()
		{
			//region Rise Cancel Event
			var eventArgs =
				{
					id: this._id,
					externalContext: this._externalContextId,
					context: this._contextId,
					entityTypeId: this._entityTypeId,
					entityId: this._entityId,
					model: this._model,
					cancel: false
				};

			BX.onCustomEvent(window, "BX.Crm.EntityEditor:onCancel", [ this, eventArgs ]);

			var enableCloseConfirmation = BX.prop.getBoolean(
				eventArgs,
				"enableCloseConfirmation",
				null
			);
			if(BX.type.isBoolean(enableCloseConfirmation))
			{
				this._enableCloseConfirmation = enableCloseConfirmation;
			}

			if(eventArgs["cancel"])
			{
				return;
			}
			//endregion

			if(this.hasChangedControls() || this.hasChangedControllers())
			{
				window.setTimeout(
					BX.delegate(this.openCancellationConfirmationDialog, this),
					250
				);
				return;
			}

			this.innerCancel();
		},
		innerCancel: function()
		{
			var i, length;
			for(i = 0, length = this._controllers.length; i < length; i++)
			{
				this._controllers[i].innerCancel();
			}

			this.rollback();

			if(this._isNew)
			{
				this.refreshLayout();
				if(typeof(top.BX.SidePanel) !== "undefined")
				{
					this._enableCloseConfirmation = false;
					window.setTimeout(
						function ()
						{
							var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
							if(slider && slider.isOpen())
							{
								slider.close(false);
							}
						},
						250
					);
				}
			}
			else
			{
				this.switchToViewMode({ refreshLayout: false });
				this.refreshLayout();
			}
		},
		openCancellationConfirmationDialog: function()
		{
			BX.Crm.EditorAuxiliaryDialog.create(
				"cancel_confirmation",
				{
					title: BX.message("CRM_EDITOR_CONFIRMATION"),
					content: BX.message("CRM_EDITOR_CANCEL_CONFIRMATION"),
					buttons:
						[
							{
								id: "yes",
								type: BX.Crm.DialogButtonType.accept,
								text: BX.message("CRM_EDITOR_YES"),
								callback: this._cancelConfirmationHandler
							},
							{
								id: "no",
								type: BX.Crm.DialogButtonType.cancel,
								text: BX.message("CRM_EDITOR_NO"),
								callback: this._cancelConfirmationHandler
							}
						]
				}
			).open();
		},
		rollback: function()
		{
			this._model.rollback();

			var i, length;
			for(i = 0, length = this._controllers.length; i < length; i++)
			{
				this._controllers[i].rollback();
			}

			for(i = 0, length = this._activeControls.length; i < length; i++)
			{
				this._activeControls[i].rollback();
			}

			if(this._areAvailableSchemeElementsChanged)
			{
				this._availableSchemeElements = this._scheme.getAvailableElements();
				this._areAvailableSchemeElementsChanged = false;
			}
		},
		addSchemeElementAt: function(schemeElement, index)
		{
			if(this._config)
			{
				this._config.addSchemeElementAt(schemeElement, index);
			}
		},
		updateSchemeElement: function(schemeElement)
		{
			if(this._config)
			{
				this._config.updateSchemeElement(schemeElement);
			}
		},
		removeSchemeElement: function(schemeElement)
		{
			if(this._config)
			{
				this._config.removeSchemeElement(schemeElement);
			}
		},
		canChangeScheme: function()
		{
			return this._config && this._config.isChangeable();
		},
		isSchemeChanged: function()
		{
			return this._config && this._config.isChanged();
		},
		saveScheme: function()
		{
			return this._config && this._config.save(false);
		},
		saveSchemeChanges: function()
		{
			this.commitSchemeChanges();
			return this._config && this._config.save(false);
		},
		commitSchemeChanges: function()
		{
			for(var i = 0, length = this._controls.length; i < length; i++)
			{
				this._controls[i].commitSchemeChanges();
			}

			if(this._areAvailableSchemeElementsChanged)
			{
				this._scheme.setAvailableElements(this._availableSchemeElements);
				this._areAvailableSchemeElementsChanged = false;
			}
		},
		onSaveSuccess: function(result)
		{
			this._isRequestRunning = false;

			if(this._enableCloseConfirmation)
			{
				this._enableCloseConfirmation = false;
			}

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
			if(checkErrors || error !== "")
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

				if(error !== "" && this._toolPanel)
				{
					this._toolPanel.addError(error);
				}

				eventParams["checkErrors"] = checkErrors;
				eventParams["error"] = error;

				if(this._isNew)
				{
					BX.onCustomEvent(window, "onCrmEntityCreateError", [eventParams]);
				}
				else
				{
					eventParams["entityId"] = this._entityId;
					BX.onCustomEvent(window, "onCrmEntityUpdateError", [eventParams]);
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
				window.location.replace(
					BX.util.add_url_param(
						redirectUrl,
						{ "IFRAME": "Y", "IFRAME_TYPE": "SIDE_SLIDER" }
					)
				);
			}
			else
			{
				if(BX.type.isPlainObject(entityData))
				{
					//Notification event is disabled because we will call "refreshLayout" for all controls at the end.
					this._model.setData(entityData, { enableNotification: false });
				}

				this.adjustTitle();
				this.adjustSize();
				this.releaseAjaxForm();
				this.initializeAjaxForm();

				for(var i = 0, length = this._controllers.length; i < length; i++)
				{
					this._controllers[i].onAfterSave();
				}

				//console.log("switchToViewMode");

				if(this._modeSwitch.isRunning())
				{
					this._modeSwitch.complete();
				}
				else
				{
					this.switchToViewMode({ refreshLayout: false });
				}

				this.refreshLayout({ reset: true });
				this.hideToolPanel();
			}
		},
		formatMoney: function(sum, currencyId, callback)
		{
			BX.ajax(
				{
					url: BX.prop.getString(this._settings, "serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": "GET_FORMATTED_SUM",
						"CURRENCY_ID": currencyId,
						"SUM": sum
					},
					onsuccess: callback
				}
			);
		},
		findOption: function (value, options)
		{
			for(var i = 0, l = options.length; i < l; i++)
			{
				if(value === options[i].VALUE)
				{
					return options[i].NAME;
				}
			}
			return value;
		},
		prepareConfigMenuItems: function()
		{
			var items = [];
			var callback = BX.delegate(this.onMenuItemClick, this);

			items.push(
				{
					id: "switchToPersonalConfig",
					text: this.getMessage("switchToPersonalConfig"),
					onclick: callback,
					className: this._config.getScope() === BX.Crm.EntityConfigScope.personal
						? "menu-popup-item-accept" : "menu-popup-item-none"
				}
			);

			items.push(
				{
					id: "switchToCommonConfig",
					text: this.getMessage("switchToCommonConfig"),
					onclick: callback,
					className: this._config.getScope() === BX.Crm.EntityConfigScope.common
						? "menu-popup-item-accept" : "menu-popup-item-none"
				}
			);

			if(this.canChangeScheme())
			{
				items.push({ delimiter: true });

				items.push(
					{
						id: "resetConfig",
						text: this.getMessage("resetConfig"),
						onclick: callback,
						className: "menu-popup-item-none"
					}
				);

				if(BX.prop.getBoolean(this._settings, "enableSettingsForAll", false))
				{
					items.push(
						{
							id: "forceCommonConfigForAllUsers",
							text: this.getMessage("forceCommonConfigForAllUsers"),
							onclick: callback,
							className: "menu-popup-item-none"
						}
					);
				}
			}

			return items;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		loadCustomHtml: function(actionName, actionData, callback)
		{
			actionData["ACTION"] = actionName;
			actionData["ACTION_ENTITY_ID"] = this._entityId;
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data: actionData,
					onsuccess: callback
				}
			);
		},
		onAfterFormSubmit: function(sender, eventArgs)
		{
			this._isRequestRunning = true;
			if(this._toolPanel)
			{
				this._toolPanel.setLocked(true);
			}
		},
		onCancelFormSubmit: function(sender, eventArgs)
		{
			this._isRequestRunning = false;
			if(this._toolPanel)
			{
				this._toolPanel.setLocked(false);
			}
		},
		//region Duplicate Control
		isDuplicateControlEnabled: function()
		{
			return this._dupControlManager.isEnabled();
		},
		getDuplicateManager: function()
		{
			return this._dupControlManager;
		},
		//endregion
		//region Events
		onResize: function(e)
		{
			this.adjustSize();
		},
		onPageTileClick: function(e)
		{
			if(this._readOnly)
			{
				return
			}

			if(this.isChanged())
			{
				this.showMessageDialog(
					"titleEditDenied",
					this.getMessage("titleEdit"),
					this.getMessage("titleEditUnsavedChanges")
				);
				return;
			}

			this.switchTitleMode(BX.Crm.EntityEditorMode.edit);
		},
		onCreateSectionButtonClick: function(e)
		{
			if(!this.isSectionCreationEnabled())
			{
				return;
			}

			var index = this.getControlCount();
			var name = "user_" + BX.util.getRandomString(8).toLowerCase();

			var schemeElement = BX.Crm.EntitySchemeElement.create(
				{
					type: "section",
					name: name,
					title: this.getMessage("newSectionTitle")
				}
			);

			this.addSchemeElementAt(schemeElement, index);

			var control = this.createControl(
				"section",
				name,
				{
					schemeElement: schemeElement,
					model: this._model,
					container: this._formElement
				}
			);
			this.addControlAt(control, index);
			this.saveScheme();

			control.setMode(BX.Crm.EntityEditorMode.edit, { notify: false });
			control.refreshLayout();
			control.setTitleMode(BX.Crm.EntityEditorMode.edit);
			this.registerActiveControl(control);
		},
		onConfigMenuButtonClick: function(e)
		{
			if(this._isConfigMenuShown)
			{
				return;
			}

			var menuItems = this.prepareConfigMenuItems();
			if(menuItems.length > 0)
			{
				BX.PopupMenu.show(
					this._id + "_config_menu",
					BX.getEventTarget(e),
					menuItems,
					{
						angle: false,
						autoHide: true,
						closeByEsc: true,
						events:
							{
								onPopupShow: function(){ this._isConfigMenuShown = true; }.bind(this),
								onPopupClose: function(){ BX.PopupMenu.destroy(this._id + "_config_menu"); }.bind(this),
								onPopupDestroy: function(){ this._isConfigMenuShown = false; }.bind(this)
							}
					}
				);
			}
		},
		onPageTitleExternalClick: function(e)
		{
			var target = BX.getEventTarget(e);
			if(target !== this._pageTitleInput)
			{
				this.savePageTitle();
				this.switchTitleMode(BX.Crm.EntityEditorMode.view);
			}
		},
		onPageTitleKeyPress: function(e)
		{
			var c = e.keyCode;
			if(c === 13)
			{
				this.savePageTitle();
				this.switchTitleMode(BX.Crm.EntityEditorMode.view);
			}
			else if(c === 27)
			{
				this.switchTitleMode(BX.Crm.EntityEditorMode.view);
			}
		},
		onInterfaceToolbarMenuBuild: function(sender, eventArgs)
		{
			var menuItems = BX.prop.getArray(eventArgs, "items", null);
			if(!menuItems)
			{
				return;
			}

			var configMenuItems = this.prepareConfigMenuItems();
			if(configMenuItems.length > 0)
			{
				if(menuItems.length > 0)
				{
					menuItems.push({ delimiter: true });
				}

				for(var i = 0, length = configMenuItems.length; i < length; i++)
				{
					menuItems.push(configMenuItems[i]);
				}
			}
		},
		//endregion
		//region Configuration
		onMenuItemClick: function(event, menuItem)
		{
			var id = BX.prop.getString(menuItem, "id", "");
			if(id === "resetConfig")
			{
				this.resetConfig();
			}
			else if(id === "switchToPersonalConfig")
			{
				this.setConfigScope(BX.Crm.EntityConfigScope.personal);
			}
			else if(id === "switchToCommonConfig")
			{
				this.setConfigScope(BX.Crm.EntityConfigScope.common);
			}
			else if(id === "forceCommonConfigForAllUsers")
			{
				this.forceCommonConfigScopeForAll();
			}

			if(menuItem.menuWindow)
			{
				menuItem.menuWindow.close();
			}
		},
		setConfigScope: function(scope)
		{
			if(this._config.getScope() === scope)
			{
				return;
			}

			this._config.setScope(scope).then(
				function()
				{
					var eventArgs = { id: this._id, scope: scope, enableReload: true };
					BX.onCustomEvent(window, "BX.Crm.EntityEditor:onConfigScopeChange", [ this, eventArgs ]);

					if(eventArgs["enableReload"] && !this._isEmbedded)
					{
						window.location.reload(true);
					}
				}.bind(this)
			);
		},
		forceCommonConfigScopeForAll: function()
		{
			this._config.forceCommonScopeForAll().then(
				function()
				{
					var scope = this._config.getScope();
					var eventArgs = { id: this._id, scope: scope, enableReload: true };
					BX.onCustomEvent(window, "BX.Crm.EntityEditor:onForceCommonConfigScopeForAll", [ this, eventArgs ]);

					if(eventArgs["enableReload"] && !this._isEmbedded && scope !== BX.Crm.EntityConfigScope.common)
					{
						window.location.reload(true);
					}
				}.bind(this)
			);
		},
		resetConfig: function()
		{
			this._config.reset(false).then(
				function()
				{
					var scope = this._config.getScope();
					var eventArgs = { id: this._id, scope: scope, enableReload: true };
					BX.onCustomEvent(window, "BX.Crm.EntityEditor:onConfigReset", [ this, eventArgs ]);

					if(eventArgs["enableReload"] && !this._isEmbedded)
					{
						window.location.reload(true);
					}
				}.bind(this)
			);
		},
		getConfigOption: function(name, defaultValue)
		{
			return this._config.getOption(name, defaultValue);
		},
		setConfigOption: function(name, value)
		{
			return this._config.setOption(name, value);
		},
		getAttributeManagerSettings: function()
		{
			return BX.prop.getObject(this._settings, "attributeConfig", null);
		},
		//endregion
		//region Options
		getOption: function(name, defaultValue)
		{
			return BX.prop.getString(this._settings["options"], name, defaultValue);
		},
		setOption: function(name, value)
		{
			if(typeof(value) === "undefined" || value === null)
			{
				return;
			}

			if(BX.prop.getString(this._settings["options"], name, null) === value)
			{
				return;
			}

			this._settings["options"][name] = value;
		},
		//endregion
		//region D&D
		getDragConfig: function(typeId)
		{
			return BX.prop.getObject(this._dragConfig, typeId, {});
		},
		hasPlaceHolder: function()
		{
			return !!this._dragPlaceHolder;
		},
		createPlaceHolder: function(index)
		{
			var qty = this.getControlCount();
			if(index < 0 || index > qty)
			{
				index = qty > 0 ? qty : 0;
			}

			if(this._dragPlaceHolder)
			{
				if(this._dragPlaceHolder.getIndex() === index)
				{
					return this._dragPlaceHolder;
				}

				this._dragPlaceHolder.clearLayout();
				this._dragPlaceHolder = null;
			}

			this._dragPlaceHolder = BX.Crm.EditorDragSectionPlaceholder.create(
				{
					container: this._formElement,
					anchor: (index < qty) ? this._controls[index].getWrapper() : null,
					index: index
				}
			);

			this._dragPlaceHolder.layout();
			return this._dragPlaceHolder;
		},
		getPlaceHolder: function()
		{
			return this._dragPlaceHolder;
		},
		removePlaceHolder: function()
		{
			if(this._dragPlaceHolder)
			{
				this._dragPlaceHolder.clearLayout();
				this._dragPlaceHolder = null;
			}
		},
		processDraggedItemDrop: function(dragContainer, draggedItem)
		{
			var containerCharge = dragContainer.getCharge();
			if(!((containerCharge instanceof BX.Crm.EditorSectionDragContainer) && containerCharge.getEditor() === this))
			{
				return;
			}

			var context = draggedItem.getContextData();
			var contextId = BX.type.isNotEmptyString(context["contextId"]) ? context["contextId"] : "";
			if(contextId !== BX.Crm.EditorSectionDragItem.contextId)
			{
				return;
			}

			var itemCharge = typeof(context["charge"]) !== "undefined" ?  context["charge"] : null;
			if(!(itemCharge instanceof BX.Crm.EditorSectionDragItem))
			{
				return;
			}

			var control = itemCharge.getControl();
			if(!control)
			{
				return;
			}

			var currentIndex = this.getControlIndex(control);
			if(currentIndex < 0)
			{
				return;
			}

			var placeholder = this.getPlaceHolder();
			var placeholderIndex = placeholder ? placeholder.getIndex() : -1;
			if(placeholderIndex < 0)
			{
				return;
			}

			var index = placeholderIndex <= currentIndex ? placeholderIndex : (placeholderIndex - 1);
			if(index !== currentIndex)
			{
				this.moveControl(control, index);
				this.saveScheme();
			}
		},
		onDrop: function(dragContainer, draggedItem, x, y)
		{
			this.processDraggedItemDrop(dragContainer, draggedItem);
		},
		//endregion
		//region Permissions
		canCreateContact: function()
		{
			return BX.prop.getBoolean(this._settings, "canCreateContact", false);
		},
		canCreateCompany: function()
		{
			return BX.prop.getBoolean(this._settings, "canCreateCompany", false);
		},
		//endregion
		addHelpLink: function(data)
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
		},
		getConfigScope: function()
		{
			return this._config.getScope();
		}
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

//region ENTITY EDITOR MODE QUEUE
if(typeof BX.Crm.EntityEditorModeQueue === "undefined")
{
	BX.Crm.EntityEditorModeQueue = function()
	{
		this._id = "";
		this._settings = {};
		this._items = [];
	};
	BX.Crm.EntityEditorModeQueue.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
		},
		findIndex: function(control)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				if(this._items[i]["control"] === control)
				{
					return i;
				}
			}
			return -1;
		},
		getLength: function ()
		{
			return this._items.length;
		},
		add: function(control, mode, options)
		{
			if(typeof(options) === "undefined")
			{
				options = BX.Crm.EntityEditorModeOptions.none;
			}
			var index = this.findIndex(control);
			if(index >= 0)
			{
				this._items[index] = { control: control, mode: mode, options: options };
			}
			else
			{
				this._items.push({ control: control, mode: mode, options: options });
			}
		},
		addBatch: function(controls, mode, options)
		{
			for(var i = 0, length = controls.length; i < length; i++)
			{
				this.add(controls[i], mode, options);
			}
		},
		remove: function(control)
		{
			var index = this.findIndex(control);
			if(index >= 0)
			{
				this._items.splice(index, 1)
			}
		},
		clear: function()
		{
			this._items = [];
		},
		process: function()
		{
			var length = this._items.length;
			if(length === 0)
			{
				return 0;
			}

			for(var i = 0; i < length; i++)
			{
				var item = this._items[i];
				item["control"].setMode(item["mode"], { options: item["options"], notify: true });
			}

			return length;
		}
	};
	BX.Crm.EntityEditorModeQueue.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorModeQueue();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region ENTITY EDITOR MODE SWITCH
if(typeof BX.Crm.EntityEditorModeSwitch === "undefined")
{
	BX.Crm.EntityEditorModeSwitch = function()
	{
		this._id = "";
		this._settings = {};
		this._queue = null;
		this._isRunning = false;
		this._runHandle = 0;
	};
	BX.Crm.EntityEditorModeSwitch.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._editor = BX.prop.get(this._settings, "editor");
			this._queue = BX.Crm.EntityEditorModeQueue.create(this._id, {});
		},
		getQueue: function()
		{
			return this._queue;
		},
		reset: function()
		{
			this._queue.clear();
			this._isRunning = false;
		},
		isRunning: function()
		{
			return this._isRunning;
		},
		run: function()
		{
			if(this._isRunning)
			{
				return;
			}

			if(this._runHandle > 0)
			{
				window.clearTimeout(this._runHandle);
			}
			this._runHandle = window.setTimeout(BX.delegate(this.doRun, this), 50);
		},
		doRun: function()
		{
			this._editor.saveDelayed();

			this._isRunning = true;
			this._runHandle = 0;
		},
		complete: function ()
		{
			this._queue.process();
			this.reset();
		}
	};
	BX.Crm.EntityEditorModeSwitch.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorModeSwitch();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region EDITOR MODE
if(typeof BX.Crm.EntityEditorMode === "undefined")
{
	BX.Crm.EntityEditorMode =
	{
		intermediate: 0,
		edit: 1,
		view: 2,
		names: { view: "view",  edit: "edit" },
		getName: function(id)
		{
			if(id === this.edit)
			{
				return this.names.edit;
			}
			else if(id === this.view)
			{
				return this.names.view;
			}
			return "";
		},
		parse: function(str)
		{
			str = str.toLowerCase();
			if(str === this.names.edit)
			{
				return this.edit;
			}
			else if(str === this.names.view)
			{
				return this.view;
			}
			return this.intermediate;
		}
	};
}
//endregion

//region EDITOR MODE OPTIONS
if(typeof BX.Crm.EntityEditorModeOptions === "undefined")
{
	BX.Crm.EntityEditorModeOptions =
	{
		none: 0,
		exclusive:  0x1,
		individual: 0x2,
		saveOnExit: 0x40,
		check: function(options, option)
		{
			return((options & option) === option);
		}
	};
}
//endregion

//region EDITOR CONTROL OPTIONS
if(typeof BX.Crm.EntityEditorControlOptions === "undefined")
{
	BX.Crm.EntityEditorControlOptions =
	{
		none: 0,
		showAlways: 1,
		check: function(options, option)
		{
			return((options & option) === option);
		}
	};
}
//endregion

//region EDITOR PRIORITY
if(typeof BX.Crm.EntityEditorPriority === "undefined")
{
	BX.Crm.EntityEditorPriority =
	{
		undefined: 0,
		normal: 1,
		high: 2
	};
}
//endregion

//region EDITOR MODE SWITCH TYPE
if(typeof BX.Crm.EntityEditorModeSwitchType === "undefined")
{
	BX.Crm.EntityEditorModeSwitchType =
		{
			none:       0x0,
			common:     0x1,
			button:     0x2,
			content:    0x4,
			check: function(options, option)
			{
				return((options & option) === option);
			}
		};
}
//endregion

//region DIALOG
if(typeof BX.Crm.EditorDialogButton === "undefined")
{
	BX.Crm.EditorDialogButton = function()
	{
		this._id = "";
		this._type = BX.Crm.DialogButtonType.undefined;
		this._settings = {};
		this._dialog = null;
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
	};
	BX.Crm.EditorDialogButton.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._type = BX.prop.getInteger(this._settings, "type", BX.Crm.DialogButtonType.undefined);
			this._dialog = BX.prop.get(this._settings, "dialog", null);
		},
		bind: function()
		{
			if(this._type === BX.Crm.DialogButtonType.accept)
			{
				BX.bind(document, "keydown", this._keyPressHandler);
			}
		},
		unbind: function()
		{
			if(this._type === BX.Crm.DialogButtonType.accept)
			{
				BX.unbind(document, "keydown", this._keyPressHandler);
			}
		},
		onKeyPress: function(e)
		{
			if(this._type !== BX.Crm.DialogButtonType.accept)
			{
				return;
			}

			e = e || window.event;
			if (e.keyCode === 13)
			{
				//Enter key
				this.onClick(e);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getDialog: function()
		{
			return this._dialog;
		},
		prepareContent: function()
		{
			if(this._type === BX.Crm.DialogButtonType.accept)
			{
				return (
					new BX.UI.SaveButton(
						{
							text : BX.prop.getString(this._settings, "text", this._id),
							events: { click: BX.delegate(this.onClick, this) }
						}
					)
				);
			}
			else if(this._type === BX.Crm.DialogButtonType.cancel)
			{
				return (
					new BX.UI.CancelButton(
						{
							text : BX.prop.getString(this._settings, "text", this._id),
							events: { click: BX.delegate(this.onClick, this) }
						}
					)
				);
			}
			else
			{
				return (
					new BX.UI.Button(
						{
							text : BX.prop.getString(this._settings, "text", this._id),
							events: { click: BX.delegate(this.onClick, this) }
						}
					)
				);
			}
		},
		onClick: function(e)
		{
			var callback = BX.prop.getFunction(this._settings, "callback", null);
			if(callback)
			{
				callback(this);
			}
		}
	};
	BX.Crm.EditorDialogButton.create = function(id, settings)
	{
		var self = new BX.Crm.EditorDialogButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EditorAuxiliaryDialog === "undefined")
{
	BX.Crm.EditorAuxiliaryDialog = function()
	{
		this._id = "";
		this._settings = {};

		this._popup = null;
		this._buttons = null;
	};
	BX.Crm.EditorAuxiliaryDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
		},
		getSetting: function(name, defaultValue)
		{
			return BX.prop.get(this._settings, name, defaultValue);
		},
		getId: function()
		{
			return this._id;
		},
		open: function()
		{
			this._popup = new BX.PopupWindow(
				this._id,
				BX.prop.getElementNode(this._settings, "anchor", null),
				{
					autoHide: false,
					draggable: false,
					closeByEsc: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: BX.prop.getInteger(this._settings, "zIndex", 0),
					bindOptions: { forceBindPosition: true },
					titleBar: BX.prop.getString(this._settings, "title", "No title"),
					content: BX.prop.getString(this._settings, "content", ""),
					buttons: this.prepareButtons(),
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					}
				}
			);
			this._popup.show();
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
		prepareButtons: function()
		{
			var results = [];

			this._buttons = [];
			var data = BX.prop.getArray(this._settings, "buttons", []);
			for(var i = 0, length = data.length; i < length; i++)
			{
				var buttonData = data[i];
				buttonData["dialog"] = this;
				var button = BX.Crm.EditorDialogButton.create(
					BX.prop.getString(buttonData, "id", ""),
					buttonData
				);
				this._buttons.push(button);
				results.push(button.prepareContent());
			}

			return results;
		},
		bind: function()
		{
			for(var i = 0, length = this._buttons.length; i < length; i++)
			{
				this._buttons[i].bind();
			}
		},
		unbind: function()
		{
			for(var i = 0, length = this._buttons.length; i < length; i++)
			{
				this._buttons[i].unbind();
			}
		},
		onPopupShow: function()
		{
			this.bind();
		},
		onPopupClose: function()
		{
			this.unbind();

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
			delete BX.Crm.EditorAuxiliaryDialog.items[this.getId()];
		}
	};
	BX.Crm.EditorAuxiliaryDialog.items = {};

	BX.Crm.EditorAuxiliaryDialog.isItemOpened = function(id)
	{
		return this.items.hasOwnProperty(id) && this.items[id].isOpen();
	};
	BX.Crm.EditorAuxiliaryDialog.hasOpenItems = function()
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
	BX.Crm.EditorAuxiliaryDialog.create = function(id, settings)
	{
		var self = new BX.Crm.EditorAuxiliaryDialog();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion

//region FILE STORAGE TYPE
if(typeof BX.Crm.EditorFileStorageType === "undefined")
{
	BX.Crm.EditorFileStorageType =
	{
		undefined: 0,
		file: 1,
		webdav: 2,
		diskfile: 3
	};
}
//endregion

//region VALIDATION
if(typeof BX.Crm.EntityValidator === "undefined")
{
	BX.Crm.EntityValidator = function()
	{
		this._settings = {};
		this._editor = null;
		this._data = null;
	};
	BX.Crm.EntityValidator.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._editor = BX.prop.get(this._settings, "editor", null);
			this._data = BX.prop.getObject(this._settings, "data", {});

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		release: function()
		{
		},
		getData: function()
		{
			return this._data;
		},
		getDataStringParam: function(name, defaultValue)
		{
			return BX.prop.getString(this._data, name, defaultValue);
		},
		getErrorMessage: function()
		{
			return BX.prop.getString(this._settings, "message", "");
		},
		validate: function(result)
		{
			return true;
		},
		processControlChange: function(control)
		{
		}
	};
}

if(typeof BX.Crm.EntityPersonValidator === "undefined")
{
	BX.Crm.EntityPersonValidator = function()
	{
		BX.Crm.EntityPersonValidator.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityPersonValidator, BX.Crm.EntityValidator);

	BX.Crm.EntityPersonValidator.prototype.doInitialize = function()
	{
		this._nameField = this._editor.getControlById(
			this.getDataStringParam("nameField", "")
		);
		if(this._nameField)
		{
			this._nameField.addValidator(this);
		}

		this._lastNameField = this._editor.getControlById(
			this.getDataStringParam("lastNameField", "")
		);
		if(this._lastNameField)
		{
			this._lastNameField.addValidator(this);
		}
	};
	BX.Crm.EntityPersonValidator.prototype.release = function()
	{
		if(this._nameField)
		{
			this._nameField.removeValidator(this);
		}

		if(this._lastNameField)
		{
			this._lastNameField.removeValidator(this);
		}
	};
	BX.Crm.EntityPersonValidator.prototype.validate = function(result)
	{
		var isNameActive = this._nameField.isActive();
		var isLastNameActive = this._lastNameField.isActive();

		if(!isNameActive && !isLastNameActive)
		{
			return true;
		}

		var name = isNameActive ? this._nameField.getRuntimeValue() : this._nameField.getValue();
		var lastName = isLastNameActive ? this._lastNameField.getRuntimeValue() : this._lastNameField.getValue();

		if(name !== "" || lastName !== "")
		{
			return true;
		}

		if(name === "" && isNameActive)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this._nameField }));
			this._nameField.showError(this.getErrorMessage());
		}

		if(lastName === "" && isLastNameActive)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this._lastNameField }));
			this._lastNameField.showError(this.getErrorMessage());
		}

		return false;
	};
	BX.Crm.EntityPersonValidator.prototype.processFieldChange = function(field)
	{
		if(field !== this._nameField && field !== this._lastNameField)
		{
			return;
		}

		if(this._nameField)
		{
			this._nameField.clearError();
		}

		if(this._lastNameField)
		{
			this._lastNameField.clearError();
		}
	};
	BX.Crm.EntityPersonValidator.create = function(settings)
	{
		var self = new BX.Crm.EntityPersonValidator();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityValidationError === "undefined")
{
	BX.Crm.EntityValidationError = function()
	{
		this._settings = {};
		this._field = null;
		this._message = "";
	};
	BX.Crm.EntityValidationError.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._field = BX.prop.get(this._settings, "field", null);
			this._message = BX.prop.getString(this._settings, "message", "");
		},
		getField: function()
		{
			return this._field;
		},
		getMessage: function()
		{
			return this._message;
		}
	};
	BX.Crm.EntityValidationError.create = function(settings)
	{
		var self = new BX.Crm.EntityValidationError();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityValidationResult === "undefined")
{
	BX.Crm.EntityValidationResult = function()
	{
		this._settings = {};
		this._errors = [];
	};
	BX.Crm.EntityValidationResult.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
		},
		getStatus: function()
		{
			return this._errors.length === 0;
		},
		addError: function(error)
		{
			this._errors.push(error);
		},
		getErrors: function()
		{
			return this._errors;
		},
		addResult: function(result)
		{
			var errors = result.getErrors();
			for(var i = 0, length = errors.length; i < length; i++)
			{
				this._errors.push(errors[i]);
			}
		},
		getTopmostField: function()
		{
			var field = null;
			var top = null;
			for(var i = 0, length = this._errors.length; i < length; i++)
			{
				var currentField = this._errors[i].getField();
				if(!field)
				{
					field = currentField;
					top = currentField.getPosition()["top"];
					continue;

				}
				var pos = currentField.getPosition();
				if(!pos)
				{
					continue;
				}

				var currentFieldTop = currentField.getPosition()["top"];
				if(currentFieldTop < top)
				{
					field = currentField;
					top = currentFieldTop;
				}
			}

			return field;
		}
	};
	BX.Crm.EntityValidationResult.create = function(settings)
	{
		var self = new BX.Crm.EntityValidationResult();
		self.initialize(settings);
		return self;
	};
}
//endregion

//region ENTITY CONFIGURATION SCOPE
if(typeof BX.Crm.EntityConfigScope === "undefined")
{
	BX.Crm.EntityConfigScope =
	{
		undefined: '',
		personal:  'P',
		common: 'C'
	};

	if(typeof(BX.Crm.EntityConfigScope.captions) === "undefined")
	{
		BX.Crm.EntityConfigScope.captions = {};
	}

	BX.Crm.EntityConfigScope.setCaptions = function(captions)
	{
		if(BX.type.isPlainObject(captions))
		{
			this.captions = captions;
		}
	};

	BX.Crm.EntityConfigScope.getCaption = function(scope)
	{
		return BX.prop.getString(this.captions, scope, scope);
	};
}
//endregion

//region CONFIG
if(typeof BX.Crm.EntityConfig === "undefined")
{
	BX.Crm.EntityConfig = function()
	{
		this._id = "";
		this._settings = {};
		this._scope = BX.Crm.EntityConfigScope.undefined;
		this._enableScopeToggle = true;

		this._canUpdatePersonalConfiguration = true;
		this._canUpdateCommonConfiguration = false;

		this._data = {};
		this._items = [];
		this._options = {};

		this._serviceUrl = "";
		this._isChanged = false;
	};
	BX.Crm.EntityConfig.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._scope = BX.prop.getString(this._settings, "scope", BX.Crm.EntityConfigScope.personal);
			this._enableScopeToggle = BX.prop.getBoolean(this._settings, "enableScopeToggle", true);

			this._canUpdatePersonalConfiguration = BX.prop.getBoolean(this._settings, "canUpdatePersonalConfiguration", true);
			this._canUpdateCommonConfiguration = BX.prop.getBoolean(this._settings, "canUpdateCommonConfiguration", false);

			this._data = BX.prop.getArray(this._settings, "data", []);

			this._items = [];
			for(var i = 0, length = this._data.length; i < length; i++)
			{
				var item = this._data[i];
				var type = BX.prop.getString(item, "type", "");
				if(type === "section")
				{
					this._items.push(BX.Crm.EntityConfigSection.create({ data: item }));
				}
				else
				{
					this._items.push(BX.Crm.EntityConfigField.create({ data: item }));
				}
			}

			this._options = BX.prop.getObject(this._settings, "options", {});
			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
		},
		findItemByName: function(name)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(item.getName() === name)
				{
					return item;
				}
			}
			return null;
		},
		findItemIndexByName: function(name)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(item.getName() === name)
				{
					return i;
				}
			}
			return -1;
		},
		toJSON: function()
		{
			var result = [];
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				result.push(this._items[i].toJSON());
			}
			return result;
		},
		addSchemeElementAt: function(schemeElement, index)
		{
			var data = schemeElement.createConfigItem();
			var item = schemeElement.getType() === "section"
				? BX.Crm.EntityConfigSection.create({ data: data })
				: BX.Crm.EntityConfigField.create({ data: data });

			if(index >= 0 && index < this._items.length)
			{
				this._items.splice(index, 0, item);
			}
			else
			{
				this._items.push(item);
			}

			this._isChanged = true;
		},
		moveSchemeElement: function(schemeElement, index)
		{
			var qty = this._items.length;
			var lastIndex = qty - 1;
			if(index < 0  || index > qty)
			{
				index = lastIndex;
			}

			var currentIndex = this.findItemIndexByName(schemeElement.getName());
			if(currentIndex < 0 || currentIndex === index)
			{
				return;
			}

			var item = this._items[currentIndex];
			this._items.splice(currentIndex, 1);

			qty--;

			if(index < qty)
			{
				this._items.splice(index, 0, item);
			}
			else
			{
				this._items.push(item);
			}

			this._isChanged = true;
		},
		updateSchemeElement: function(schemeElement)
		{
			var index;
			var parentElement = schemeElement.getParent();
			if(parentElement)
			{
				var parentItem = this.findItemByName(parentElement.getName());
				if(parentItem)
				{
					index = parentItem.findFieldIndexByName(schemeElement.getName());
					if(index >= 0)
					{
						parentItem.setField(
							BX.Crm.EntityConfigField.create({ data: schemeElement.createConfigItem() }),
							index
						);
						this._isChanged = true;
					}
				}
			}
			else
			{
				index = this.findItemIndexByName(schemeElement.getName());
				if(index >= 0)
				{
					if(schemeElement.getType() === "section")
					{
						this._items[index] = BX.Crm.EntityConfigSection.create({ data: schemeElement.createConfigItem() });
					}
					else
					{
						this._items[index] = BX.Crm.EntityConfigField.create({ data: schemeElement.createConfigItem() });
					}
					this._isChanged = true;
				}
			}

		},
		removeSchemeElement: function(schemeElement)
		{
			var index = this.findItemIndexByName(schemeElement.getName());
			if(index < 0)
			{
				return;
			}

			this._items.splice(index, 1);
			this._isChanged = true;
		},
		isChangeable: function()
		{
			if(this._scope === BX.Crm.EntityConfigScope.common)
			{
				return this._canUpdateCommonConfiguration;
			}
			else if(this._scope === BX.Crm.EntityConfigScope.personal)
			{
				return this._canUpdatePersonalConfiguration;
			}

			return false;
		},
		isChanged: function()
		{
			return this._isChanged;
		},
		isScopeToggleEnabled: function()
		{
			return this._enableScopeToggle;
		},
		getScope: function()
		{
			return this._scope;
		},
		setScope: function(scope)
		{
			var promise = new BX.Promise();
			if(!this._enableScopeToggle || this._scope === scope)
			{
				window.setTimeout(
					function(){ promise.fulfill(); },
					0
				);
				return promise;
			}

			this._scope = scope;

			//Scope is changed - data collections are invalid.
			this._data = [];
			this._items = [];

			BX.ajax.post(
				this._serviceUrl,
				{ guid: this._id, action: "setScope", scope: this._scope },
				function(){ promise.fulfill(); }
			);
			return promise;
		},
		registerField: function(scheme)
		{
			var parentScheme = scheme.getParent();
			if(!parentScheme)
			{
				return;
			}

			var section = this.findItemByName(parentScheme.getName());
			if(!section)
			{
				return;
			}

			section.addField(
				BX.Crm.EntityConfigField.create({ data: scheme.createConfigItem() })
			);
			this.save();
		},
		unregisterField: function(scheme)
		{
			var parentScheme = scheme.getParent();
			if(!parentScheme)
			{
				return;
			}

			var section = this.findItemByName(parentScheme.getName());
			if(!section)
			{
				return;
			}

			var field = section.findFieldByName(scheme.getName());
			if(!field)
			{
				return;
			}

			section.removeFieldByIndex(field.getIndex());
			this.save();
		},
		save: function(forAllUsers, enableOptions)
		{
			forAllUsers = !!forAllUsers;
			enableOptions = !!enableOptions;

			var promise = new BX.Promise();
			if(!this._isChanged && !forAllUsers)
			{
				window.setTimeout(
					function(){ promise.fulfill(); },
					0
				);
				return promise;
			}

			var data =
			{
				guid: this._id,
				action: "save",
				scope: this._scope,
				config: this.toJSON()
			};

			if(enableOptions)
			{
				data["options"] = this._options;
			}

			if(this._scope === BX.Crm.EntityConfigScope.personal && forAllUsers)
			{
				data["forAllUsers"] = "Y";
				data["delete"] = "Y";
			}

			BX.ajax.post(
				this._serviceUrl,
				data,
				function(){ promise.fulfill(); }
			);
			this._isChanged = false;
			return promise;
		},
		reset: function(forAllUsers)
		{
			var data =
			{
				guid: this._id,
				action: "reset",
				scope: this._scope,
				config: this.toJSON()
			};

			if(forAllUsers)
			{
				data["forAllUsers"] = "Y";
			}

			var promise = new BX.Promise();
			BX.ajax.post(
				this._serviceUrl,
				data,
				function(){ promise.fulfill(); }
			);
			return promise;
		},
		forceCommonScopeForAll: function()
		{
			var promise = new BX.Promise();
			BX.ajax.post(
				this._serviceUrl,
				{ guid: this._id, action: "forceCommonScopeForAll" },
				function(){ promise.fulfill(); }
			);
			return promise;
		},
		getOption: function(name, defaultValue)
		{
			return BX.prop.getString(this._options, name, defaultValue);
		},
		setOption: function(name, value)
		{
			if(typeof(value) === "undefined" || value === null)
			{
				return;
			}

			if(BX.prop.getString(this._options, name, null) === value)
			{
				return;
			}

			this._options[name] = value;

			if(this._scope === BX.Crm.EntityConfigScope.common)
			{
				BX.userOptions.save(
					"crm.entity.editor",
					this._id + "_common_opts",
					name,
					value,
					true
				);
			}
			else
			{
				BX.userOptions.save(
					"crm.entity.editor",
					this._id + "_opts",
					name,
					value,
					false
				);
			}
		}
	};
	BX.Crm.EntityConfig.create = function(id, settings)
	{
		var self = new BX.Crm.EntityConfig();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityConfigItem === "undefined")
{
	BX.Crm.EntityConfigItem = function()
	{
		this._settings = {};
		this._data = {};
		this._name = "";
		this._title = "";
	};

	BX.Crm.EntityConfigItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._data = BX.prop.getObject(this._settings, "data", []);
			this._name = BX.prop.getString(this._data, "name", "");
			this._title = BX.prop.getString(this._data, "title", "");

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getType: function()
		{
			return "";
		},
		getName: function()
		{
			return this._name;
		},
		getTitle: function()
		{
			return this._title;
		},
		toJSON: function()
		{
			return {};
		}
	};
}

if(typeof BX.Crm.EntityConfigSection === "undefined")
{
	BX.Crm.EntityConfigSection = function()
	{
		BX.Crm.EntityConfigSection.superclass.constructor.apply(this);
		this._fields = [];
	};
	BX.extend(BX.Crm.EntityConfigSection, BX.Crm.EntityConfigItem);

	BX.Crm.EntityConfigSection.prototype.doInitialize = function()
	{
		this._fields = [];
		var elements = BX.prop.getArray(this._data, "elements", []);
		for(var i = 0, length = elements.length; i < length; i++)
		{
			var field = BX.Crm.EntityConfigField.create({ data: elements[i] });
			field.setIndex(i);
			this._fields.push(field);
		}
	};
	BX.Crm.EntityConfigSection.prototype.getType = function()
	{
		return "section";
	};
	BX.Crm.EntityConfigSection.prototype.getFields = function()
	{
		return this._fields;
	};
	BX.Crm.EntityConfigSection.prototype.findFieldByName = function(name)
	{
		var index = this.findFieldIndexByName(name);
		return index >= 0 ? this._fields[index] : null;
	};
	BX.Crm.EntityConfigSection.prototype.findFieldIndexByName = function(name)
	{
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			var field = this._fields[i];
			if(field.getName() === name)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityConfigSection.prototype.addField = function(field)
	{
		this._fields.push(field);
	};
	BX.Crm.EntityConfigSection.prototype.setField = function(field, index)
	{
		this._fields[index] = field;
	};
	BX.Crm.EntityConfigSection.prototype.removeFieldByIndex = function(index)
	{
		var length = this._fields.length;
		if(index < 0 || index >= length)
		{
			return false;
		}

		this._fields.splice(index, 1);
		return true;
	};
	BX.Crm.EntityConfigSection.prototype.toJSON = function()
	{
		var result = { name: this._name, title: this._title, type: "section", elements: [] };
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			result.elements.push(this._fields[i].toJSON());
		}
		return result;
	};
	BX.Crm.EntityConfigSection.create = function(settings)
	{
		var self = new BX.Crm.EntityConfigSection();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityConfigField === "undefined")
{
	BX.Crm.EntityConfigField = function()
	{
		BX.Crm.EntityConfigField.superclass.constructor.apply(this);
		this._index = -1;
		this._optionFlags = 0;

	};
	BX.extend(BX.Crm.EntityConfigField, BX.Crm.EntityConfigItem);
	BX.Crm.EntityConfigField.prototype.doInitialize = function()
	{
		this._optionFlags = BX.prop.getInteger(this._data, "optionFlags", 0);
	};
	BX.Crm.EntityConfigField.prototype.toJSON = function()
	{
		var result = { name: this._name };
		if(this._title !== "")
		{
			result["title"] = this._title;
		}
		if(this._optionFlags > 0)
		{
			result["optionFlags"] = this._optionFlags;
		}
		return result;
	};
	BX.Crm.EntityConfigField.prototype.getIndex = function()
	{
		return this._index;
	};
	BX.Crm.EntityConfigField.prototype.setIndex = function(index)
	{
		this._index = index;
	};
	BX.Crm.EntityConfigField.create = function(settings)
	{
		var self = new BX.Crm.EntityConfigField();
		self.initialize(settings);
		return self;
	};
}
//endregion

//region SCHEME & ELEMENTS
if(typeof BX.Crm.EntityScheme === "undefined")
{
	BX.Crm.EntityScheme = function()
	{
		this._id = "";
		this._settings = {};
		this._elements = null;
		this._availableElements = null;
	};
	BX.Crm.EntityScheme.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._elements = [];
			this._availableElements = [];

			var i, length;
			var currentData = BX.prop.getArray(this._settings, "current", []);
			for(i = 0, length = currentData.length; i < length; i++)
			{
				this._elements.push(BX.Crm.EntitySchemeElement.create(currentData[i]));
			}

			var availableData = BX.prop.getArray(this._settings, "available", []);
			for(i = 0, length = availableData.length; i < length; i++)
			{
				this._availableElements.push(BX.Crm.EntitySchemeElement.create(availableData[i]));
			}
		},
		getId: function()
		{
			return this._id;
		},
		getElements: function()
		{
			return ([].concat(this._elements));
		},
		findElementByName: function(name, options)
		{
			var isRecursive = BX.prop.getBoolean(options, "isRecursive", false);
			for(var i = 0, length = this._elements.length; i < length; i++)
			{
				var element = this._elements[i];
				if(element.getName() === name)
				{
					return element;
				}

				if(!isRecursive)
				{
					continue;
				}

				var childElement = element.findElementByName(name);
				if(childElement !== null)
				{
					return childElement;
				}
			}

			return null;
		},
		getAvailableElements: function()
		{
			return([].concat(this._availableElements));
		},
		setAvailableElements: function(elements)
		{
			this._availableElements = BX.type.isArray(elements) ? elements : [];
		}
	};
	BX.Crm.EntityScheme.create = function(id, settings)
	{
		var self = new BX.Crm.EntityScheme();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntitySchemeElement === "undefined")
{
	BX.Crm.EntitySchemeElement = function()
	{
		this._settings = {};
		this._name = "";
		this._type = "";
		this._title = "";
		this._originalTitle = "";
		this._optionFlags = 0;

		this._isEditable = true;
		this._isTransferable = true;
		this._isContextMenuEnabled = true;
		this._isRequired = false;
		this._isRequiredConditionally = false;
		this._isHeading = false;
		this._isMergeable = true;

		this._visibilityPolicy = BX.Crm.EntityEditorVisibilityPolicy.always;
		this._data = null;
		this._elements = null;
		this._parent = null;
	};
	BX.Crm.EntitySchemeElement.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._name = BX.prop.getString(this._settings, "name", "");
			this._type = BX.prop.getString(this._settings, "type", "");

			this._data = BX.prop.getObject(this._settings, "data", {});

			this._isEditable = BX.prop.getBoolean(this._settings, "editable", true);
			this._isTransferable = BX.prop.getBoolean(this._settings, "transferable", true);
			this._isMergeable = BX.prop.getBoolean(this._settings, "mergeable", true);
			this._isContextMenuEnabled = BX.prop.getBoolean(this._settings, "enabledMenu", true);
			this._isTitleEnabled = BX.prop.getBoolean(this._settings, "enableTitle", true)
				&& this.getDataBooleanParam("enableTitle", true);
			this._isDragEnabled = BX.prop.getBoolean(this._settings, "isDragEnabled", true);
			this._isRequired = BX.prop.getBoolean(this._settings, "required", false);
			this._isRequiredConditionally = BX.prop.getBoolean(this._settings, "requiredConditionally", false);
			this._isHeading = BX.prop.getBoolean(this._settings, "isHeading", false);

			this._visibilityPolicy = BX.Crm.EntityEditorVisibilityPolicy.parse(
				BX.prop.getString(
					this._settings,
					"visibilityPolicy",
					""
				)
			);

			//region Titles
			var title = BX.prop.getString(this._settings, "title", "");
			var originalTitle = BX.prop.getString(this._settings, "originalTitle", "");

			if(title !== "" && originalTitle === "")
			{
				originalTitle = title;
			}
			else if(originalTitle !== "" && title === "")
			{
				title = originalTitle;
			}

			this._title = title;
			this._originalTitle = originalTitle;
			//endregion

			this._optionFlags = BX.prop.getInteger(this._settings, "optionFlags", 0);

			this._elements = [];
			var elementData = BX.prop.getArray(this._settings, "elements", []);
			for(var i = 0, l = elementData.length; i < l; i++)
			{
				this._elements.push(BX.Crm.EntitySchemeElement.create(elementData[i]));
			}
		},
		mergeSettings: function(settings)
		{
			this.initialize(BX.mergeEx(this._settings, settings));
		},
		getName: function()
		{
			return this._name;
		},
		getType: function()
		{
			return this._type;
		},
		getTitle: function()
		{
			return this._title;
		},
		setTitle: function(title)
		{
			this._title = this._settings["title"] = title;
		},
		getOriginalTitle: function()
		{
			return this._originalTitle;
		},
		hasCustomizedTitle: function()
		{
			return this._title !== "" && this._title !== this._originalTitle;
		},
		resetOriginalTitle: function()
		{
			this._originalTitle = this._title;
		},
		getOptionFlags: function()
		{
			return this._optionFlags;
		},
		setOptionFlags: function(flags)
		{
			this._optionFlags = this._settings["optionFlags"] = flags;
		},
		areAttributesEnabled: function()
		{
			return BX.prop.getBoolean(this._settings, "enableAttributes", true);
		},
		isEditable: function()
		{
			return this._isEditable;
		},
		isTransferable: function()
		{
			return this._isTransferable;
		},
		isRequired: function()
		{
			return this._isRequired;
		},
		isRequiredConditionally: function()
		{
			return this._isRequiredConditionally;
		},
		isContextMenuEnabled: function()
		{
			return this._isContextMenuEnabled;
		},
		isTitleEnabled: function()
		{
			return this._isTitleEnabled;
		},
		isDragEnabled: function()
		{
			return this._isDragEnabled;
		},
		isHeading: function()
		{
			return this._isHeading;
		},
		isMergeable: function()
		{
			return this._isMergeable;
		},
		getCreationPlaceholder: function()
		{
			return BX.prop.getString(
				BX.prop.getObject(this._settings, "placeholders", null),
				"creation",
				""
			);
		},
		getVisibilityPolicy: function()
		{
			return this._visibilityPolicy;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		getDataParam: function(name, defaultval)
		{
			return BX.prop.get(this._data, name, defaultval);
		},
		setDataParam: function(name, val)
		{
			this._data[name] = val;
		},
		getDataStringParam: function(name, defaultval)
		{
			return BX.prop.getString(this._data, name, defaultval);
		},
		getDataIntegerParam: function(name, defaultval)
		{
			return BX.prop.getInteger(this._data, name, defaultval);
		},
		getDataBooleanParam: function(name, defaultval)
		{
			return BX.prop.getBoolean(this._data, name, defaultval);
		},
		getDataObjectParam: function(name, defaultval)
		{
			return BX.prop.getObject(this._data, name, defaultval);
		},
		getDataArrayParam: function(name, defaultval)
		{
			return BX.prop.getArray(this._data, name, defaultval);
		},
		getElements: function()
		{
			return this._elements;
		},
		setElements: function(elements)
		{
			this._elements = elements;
		},
		findElementByName: function(name)
		{
			for(var i = 0, length = this._elements.length; i < length; i++)
			{
				var element = this._elements[i];
				if(element.getName() === name)
				{
					return element;
				}
			}
			return null;
		},
		getAffectedFields: function()
		{
			var results = this.getDataArrayParam("affectedFields", []);
			if(results.length === 0)
			{
				results.push(this._name);
			}
			return results;
		},
		getParent: function()
		{
			return this._parent;
		},
		setParent: function(parent)
		{
			this._parent = parent instanceof BX.Crm.EntitySchemeElement ? parent : null;
		},
		hasAttributeConfiguration: function(attributeTypeId)
		{
			return !!this.getAttributeConfiguration(attributeTypeId);
		},
		getAttributeConfiguration: function(attributeTypeId)
		{
			var data = this.getData();
			var configs = BX.prop.getArray(data, "attrConfigs", null);
			if(!configs)
			{
				return null;
			}

			for(var i = 0, length = configs.length; i < length; i++)
			{
				var config = configs[i];
				if(BX.prop.getInteger(config, "typeId", BX.Crm.EntityFieldAttributeType.undefined) === attributeTypeId)
				{
					return BX.clone(config);
				}
			}
			return null;
		},
		setAttributeConfiguration: function(config)
		{
			var typeId = BX.prop.getInteger(config, "typeId", BX.Crm.EntityFieldAttributeType.undefined);
			if(typeof(this._data["attrConfigs"]) === "undefined")
			{
				this._data["attrConfigs"] = [];
			}

			var index = -1;
			for(var i = 0, length = this._data["attrConfigs"].length; i < length; i++)
			{
				if(BX.prop.getInteger(this._data["attrConfigs"][i], "typeId", BX.Crm.EntityFieldAttributeType.undefined) === typeId)
				{
					index = i;
					break;
				}
			}

			if(index >= 0)
			{
				this._data["attrConfigs"].splice(index, 1, config);
			}
			else
			{
				this._data["attrConfigs"].push(config);
			}
		},
		removeAttributeConfiguration: function(attributeTypeId)
		{
			if(typeof(this._data["attrConfigs"]) === "undefined")
			{
				return;
			}

			for(var i = 0, length = this._data["attrConfigs"].length; i < length; i++)
			{
				if(BX.prop.getInteger(this._data["attrConfigs"][i], "typeId", BX.Crm.EntityFieldAttributeType.undefined) === attributeTypeId)
				{
					this._data["attrConfigs"].splice(i, 1);
					return;
				}
			}
		},
		createConfigItem: function()
		{
			var result = { name: this._name };

			if(this._type === "section")
			{
				result["type"] = "section";

				if(this._title !== "")
				{
					result["title"] = this._title;
				}

				result["elements"] = [];
				for(var i = 0, length = this._elements.length; i < length; i++)
				{
					//result["elements"].push({ name: this._elements[i].getName() });
					result["elements"].push(this._elements[i].createConfigItem());
				}
			}
			else
			{
				if(this._title !== "" && this._title !== this._originalTitle)
				{
					result["title"] = this._title;
				}

				if(this._optionFlags > 0)
				{
					result["optionFlags"] = this._optionFlags;
				}
			}

			return result;
		},
		clone: function()
		{
			return BX.Crm.EntitySchemeElement.create(BX.clone(this._settings));
		}
	};
	BX.Crm.EntitySchemeElement.create = function(settings)
	{
		var self = new BX.Crm.EntitySchemeElement();
		self.initialize(settings);
		return self;
	}
}
//endregion

//region FACTORY
if(typeof BX.Crm.EntityEditorValidatorFactory === "undefined")
{
	BX.Crm.EntityEditorValidatorFactory =
	{
		create: function(type, settings)
		{
			if(type === "person")
			{
				return BX.Crm.EntityPersonValidator.create(settings);
			}

			return null;
		}
	}
}

if(typeof BX.Crm.EntityEditorControlFactory === "undefined")
{
	BX.Crm.EntityEditorControlFactory =
	{
		initialized: false,
		methods: {},

		isInitialized: function()
		{
			return this.initialized;
		},
		initialize: function()
		{
			if(this.initialized)
			{
				return;
			}

			var eventArgs = { methods: {} };
			BX.onCustomEvent(
				window,
				"BX.Crm.EntityEditorControlFactory:onInitialize",
				[ this, eventArgs ]
			);

			for(var name in eventArgs.methods)
			{
				if(eventArgs.methods.hasOwnProperty(name))
				{
					this.registerFactoryMethod(name, eventArgs.methods[name]);
				}
			}

			this.initialized = true;
		},
		registerFactoryMethod: function(name, method)
		{
			if(BX.type.isFunction(method))
			{
				this.methods[name] = method;
			}
		},
		create: function(type, controlId, settings)
		{
			if(!this.initialized)
			{
				this.initialize();
			}


			if(type === "section")
			{
				return BX.Crm.EntityEditorSection.create(controlId, settings);
			}
			else if(type === "text")
			{
				return BX.Crm.EntityEditorText.create(controlId, settings);
			}
			else if(type === "number")
			{
				return BX.Crm.EntityEditorNumber.create(controlId, settings);
			}
			else if(type === "datetime")
			{
				return BX.Crm.EntityEditorDatetime.create(controlId, settings);
			}
			else if(type === "boolean")
			{
				return BX.Crm.EntityEditorBoolean.create(controlId, settings);
			}
			else if(type === "list")
			{
				return BX.Crm.EntityEditorList.create(controlId, settings);
			}
			else if(type === "multilist")
			{
				return BX.Crm.EntityEditorMultiList.create(controlId, settings);
			}
			else if(type === "html")
			{
				return BX.Crm.EntityEditorHtml.create(controlId, settings);
			}
			else if(type === "money")
			{
				return BX.Crm.EntityEditorMoney.create(controlId, settings);
			}
			else if(type === "image")
			{
				return BX.Crm.EntityEditorImage.create(controlId, settings);
			}
			else if(type === "user")
			{
				return BX.Crm.EntityEditorUser.create(controlId, settings);
			}
			else if(type === "multiple_user")
			{
				return BX.Crm.EntityEditorMultipleUser.create(controlId, settings);
			}
			else if(type === "address")
			{
				return BX.Crm.EntityEditorAddress.create(controlId, settings);
			}
			else if(type === "crm_entity")
			{
				return BX.Crm.EntityEditorEntity.create(controlId, settings);
			}
			else if(type === "file_storage")
			{
				return BX.Crm.EntityEditorFileStorage.create(controlId, settings);
			}
			else if(type === "client")
			{
				return BX.Crm.EntityEditorClient.create(controlId, settings);
			}
			else if(type === "client_light")
			{
				return BX.Crm.EntityEditorClientLight.create(controlId, settings);
			}
			else if(type === "multifield")
			{
				return BX.Crm.EntityEditorMultifield.create(controlId, settings);
			}
			else if(type === "product_row_summary")
			{
				return BX.Crm.EntityEditorProductRowSummary.create(controlId, settings);
			}
			else if(type === "requisite_selector")
			{
				return BX.Crm.EntityEditorRequisiteSelector.create(controlId, settings);
			}
			else if(type === "requisite_list")
			{
				return BX.Crm.EntityEditorRequisiteList.create(controlId, settings);
			}
			else if(type === "userField")
			{
				return BX.Crm.EntityEditorUserField.create(controlId, settings);
			}
			else if(type === "userFieldConfig")
			{
				return BX.Crm.EntityEditorUserFieldConfigurator.create(controlId, settings);
			}
			else if(type === "recurring")
			{
				return BX.Crm.EntityEditorRecurring.create(controlId, settings);
			}
			else if(type === "recurring_custom_row")
			{
				return BX.Crm.EntityEditorRecurringCustomRowField.create(controlId, settings);
			}
			else if(type === "recurring_single_row")
			{
				return BX.Crm.EntityEditorRecurringSingleField.create(controlId, settings);
			}
			else if(type === "custom")
			{
				return BX.Crm.EntityEditorCustom.create(controlId, settings);
			}
			else if(type === "shipment")
			{
				return BX.Crm.EntityEditorShipment.create(controlId, settings);
			}
			else if(type === "payment")
			{
				return BX.Crm.EntityEditorPayment.create(controlId, settings);
			}
			else if(type === "payment_status")
			{
				return BX.Crm.EntityEditorPaymentStatus.create(controlId, settings);
			}
			else if(type === "payment_check")
			{
				return BX.Crm.EntityEditorPaymentCheck.create(controlId, settings);
			}
			else if(type === "order_subsection")
			{
				return BX.Crm.EntityEditorSubsection.create(controlId, settings);
			}
			else if(type === "order_property_wrapper")
			{
				return BX.Crm.EntityEditorOrderPropertyWrapper.create(controlId, settings);
			}
			else if(type === "order_property_subsection")
			{
				return BX.Crm.EntityEditorOrderPropertySubsection.create(controlId, settings);
			}
			else if(type === "order_property_file")
			{
				return BX.Crm.EntityEditorOrderPropertyFile.create(controlId, settings);
			}
			else if(type === "order_product_property")
			{
				return BX.Crm.EntityEditorOrderProductProperty.create(controlId, settings);
			}
			else if(type === "order_person_type")
			{
				return BX.Crm.EntityEditorOrderPersonType.create(controlId, settings);
			}
			else if(type === "order_quantity")
			{
				return BX.Crm.EntityEditorOrderQuantity.create(controlId, settings);
			}
			else if(type === "order_user")
			{
				return BX.Crm.EntityEditorOrderUser.create(controlId, settings);
			}
			else if(type === "order_client")
			{
				return BX.Crm.EntityEditorOrderClient.create(controlId, settings);
			}
			else if(type === "hidden")
			{
				return BX.Crm.EntityEditorHidden.create(controlId, settings);
			}
			else if(type === "delivery_selector")
			{
				return BX.Crm.EntityEditorDeliverySelector.create(controlId, settings);
			}
			else if(type === "shipment_extra_services")
			{
				return BX.Crm.EntityEditorShipmentExtraServices.create(controlId, settings);
			}

			for(var name in this.methods)
			{
				if(!this.methods.hasOwnProperty(name))
				{
					continue;
				}

				var control = this.methods[name](type, controlId, settings);
				if(control)
				{
					return control;
				}
			}

			return null;
		}
	};
}

if(typeof BX.Crm.EntityEditorControllerFactory === "undefined")
{
	BX.Crm.EntityEditorControllerFactory =
	{
		create: function(type, controllerId, settings)
		{
			if(type === "product_row_proxy")
			{
				return BX.Crm.EntityEditorProductRowProxy.create(controllerId, settings);
			}
			else if(type === "order_controller")
			{
				return BX.Crm.EntityEditorOrderController.create(controllerId, settings);
			}
			else if(type === "order_shipment_controller")
			{
				return BX.Crm.EntityEditorOrderShipmentController.create(controllerId, settings);
			}
			else if(type === "order_payment_controller")
			{
				return BX.Crm.EntityEditorOrderPaymentController.create(controllerId, settings);
			}
			else if(type === "order_product_controller")
			{
				return BX.Crm.EntityEditorOrderProductController.create(controllerId, settings);
			}

			return null;
		}
	};
}

if(typeof BX.Crm.EntityEditorModelFactory === "undefined")
{
	BX.Crm.EntityEditorModelFactory =
	{
		create: function(entityTypeId, id, settings)
		{
			if(entityTypeId === BX.CrmEntityType.enumeration.lead)
			{
				return BX.Crm.LeadModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.contact)
			{
				return BX.Crm.ContactModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.company)
			{
				return BX.Crm.CompanyModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.deal)
			{
				return BX.Crm.DealModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.dealrecurring)
			{
				return BX.Crm.DealRecurringModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.quote)
			{
				return BX.Crm.QuoteModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.order)
			{
				return BX.Crm.OrderModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.orderpayment)
			{
				return BX.Crm.OrderPaymentModel.create(id, settings);
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.ordershipment)
			{
				return BX.Crm.OrderShipmentModel.create(id, settings);
			}
			return BX.Crm.EntityModel.create(id, settings);
		}
	};
}
//endregion

//region MODEL
if(typeof BX.Crm.EntityModel === "undefined")
{
	BX.Crm.EntityModel = function()
	{
		this._id = "";
		this._settings = {};
		this._data = null;
		this._initData = null;
		this._lockedFields = null;
		this._changeNotifier = null;
		this._lockNotifier = null;
	};
	BX.Crm.EntityModel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._data = BX.prop.getObject(this._settings, "data", {});
			this._initData = BX.clone(this._data);
			this._lockedFields = {};
			this._changeNotifier = BX.CrmNotifier.create(this);
			this._lockNotifier = BX.CrmNotifier.create(this);

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getEntityTypeId: function()
		{
			return BX.CrmEntityType.enumeration.undefined;
		},
		getEntityId: function()
		{
			return BX.prop.getInteger(this._data, "ID", 0);
		},
		getOwnerInfo: function()
		{
			return(
				{
					ownerID: this.getEntityId(),
					ownerType: BX.CrmEntityType.resolveName(this.getEntityTypeId())
				}
			);
		},
		getField: function(name, defaultValue)
		{
			if(defaultValue === undefined)
			{
				defaultValue = null;
			}
			return BX.prop.get(this._data, name, defaultValue);
		},
		getStringField: function(name, defaultValue)
		{
			if(defaultValue === undefined)
			{
				defaultValue = null;
			}
			return BX.prop.getString(this._data, name, defaultValue);
		},
		getIntegerField: function(name, defaultValue)
		{
			if(defaultValue === undefined)
			{
				defaultValue = null;
			}
			return BX.prop.getInteger(this._data, name, defaultValue);
		},
		getNumberField: function(name, defaultValue)
		{
			if(defaultValue === undefined)
			{
				defaultValue = null;
			}
			return BX.prop.getNumber(this._data, name, defaultValue);
		},
		getArrayField: function(name, defaultValue)
		{
			if(defaultValue === undefined)
			{
				defaultValue = null;
			}
			return BX.prop.getArray(this._data, name, defaultValue);
		},
		registerNewField: function(name, value)
		{
			//update data
			this._data[name] = value;
			//update initialization data because of rollback.
			this._initData[name] = value;
		},
		setField: function(name, value, options)
		{
			if(this._data.hasOwnProperty(name) && this._data[name] === value)
			{
				return;
			}

			this._data[name] = value;

			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			if(BX.prop.getBoolean(options, "enableNotification", true))
			{
				this._changeNotifier.notify(
					[
						{
							name: name,
							originator: BX.prop.get(options, "originator", null)
						}
					]
				);
				BX.onCustomEvent(
					window,
					"Crm.EntityModel.Change",
					[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), fieldName: name } ]
				);
			}
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data, options)
		{
			this._data = BX.type.isPlainObject(data) ? data : {};
			this._initData = BX.clone(this._data);

			if(BX.prop.getBoolean(options, "enableNotification", true))
			{
				this._changeNotifier.notify(
					[
						{
							forAll: true,
							originator: BX.prop.get(options, "originator", null)
						}
					]
				);
				BX.onCustomEvent(
					window,
					"Crm.EntityModel.Change",
					[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), forAll: true } ]
				);
			}
		},
		updateData: function(data, options)
		{
			if(!BX.type.isPlainObject(data))
			{
				return;
			}

			this._data = BX.mergeEx(this._data, data);
			if(BX.prop.getBoolean(options, "enableNotification", true))
			{
				this._changeNotifier.notify(
					[
						{
							forAll: true,
							originator: BX.prop.get(options, "originator", null)
						}
					]
				);
				BX.onCustomEvent(
					window,
					"Crm.EntityModel.Change",
					[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), forAll: true } ]
				);
			}
		},
		updateDataObject: function(name, data, options)
		{
			if(!this._data.hasOwnProperty(name))
			{
				this._data[name] = data;
			}
			else
			{
				this._data[name] = BX.mergeEx(this._data[name], data);
			}

			if(BX.prop.getBoolean(options, "enableNotification", true))
			{
				this._changeNotifier.notify(
					[
						{
							forAll: true,
							originator: BX.prop.get(options, "originator", null)
						}
					]
				);
				BX.onCustomEvent(
					window,
					"Crm.EntityModel.Change",
					[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), forAll: true } ]
				);
			}
		},
		getSchemeField: function(schemeElement, name, defaultValue)
		{
			return this.getField(schemeElement.getDataStringParam(name, ""), defaultValue);
		},
		setSchemeField: function(schemeElement, name, value)
		{
			var fieldName = schemeElement.getDataStringParam(name, "");
			if(fieldName !== "")
			{
				this.setField(fieldName, value);
			}
		},
		getMappedField: function(map, name, defaultValue)
		{
			var fieldName = BX.prop.getString(map, name, "");
			return fieldName !== "" ? this.getField(fieldName, defaultValue) : defaultValue;
		},
		setMappedField: function(map, name, value)
		{
			var fieldName = BX.prop.getString(map, name, "");
			if(fieldName !== "")
			{
				this.setField(fieldName, value);
			}
		},
		save: function()
		{
		},
		rollback: function()
		{
			this._data = BX.clone(this._initData);
		},
		lockField: function(fieldName)
		{
			if(this._lockedFields.hasOwnProperty(fieldName))
			{
				return;
			}

			this._lockedFields[fieldName] = true;
			this._lockNotifier.notify([ { name: name, isLocked: true } ]);
		},
		unlockField: function(fieldName)
		{
			if(!this._lockedFields.hasOwnProperty(fieldName))
			{
				return;
			}

			delete this._lockedFields[fieldName];
			this._lockNotifier.notify([ { name: name, isLocked: false } ]);
		},
		isFieldLocked: function(fieldName)
		{
			return this._lockedFields.hasOwnProperty(fieldName);
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		addLockListener: function(listener)
		{
			this._lockNotifier.addListener(listener);
		},
		removeLockListener: function(listener)
		{
			this._lockNotifier.removeListener(listener);
		},
		isCaptionEditable: function()
		{
			return false;
		},
		getCaption: function()
		{
			return "";
		},
		setCaption: function(caption)
		{
		},
		prepareCaptionData: function(data)
		{
		}
	};
	BX.Crm.EntityModel.create = function(id, settings)
	{
		var self = new BX.Crm.EntityModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.LeadModel === "undefined")
{
	BX.Crm.LeadModel = function()
	{
		BX.Crm.LeadModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.LeadModel, BX.Crm.EntityModel);
	BX.Crm.LeadModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.LeadModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.LeadModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.lead;
	};
	BX.Crm.LeadModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.LeadModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.LeadModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.LeadModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.LeadModel.create = function(id, settings)
	{
		var self = new BX.Crm.LeadModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.ContactModel === "undefined")
{
	BX.Crm.ContactModel = function()
	{
		BX.Crm.ContactModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.ContactModel, BX.Crm.EntityModel);
	BX.Crm.ContactModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.contact;
	};
	BX.Crm.ContactModel.prototype.getCaption = function()
	{
		return this.getField("FORMATTED_NAME", "");
	};
	BX.Crm.ContactModel.create = function(id, settings)
	{
		var self = new BX.Crm.ContactModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.CompanyModel === "undefined")
{
	BX.Crm.CompanyModel = function()
	{
		BX.Crm.CompanyModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.CompanyModel, BX.Crm.EntityModel);
	BX.Crm.CompanyModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.CompanyModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.company;
	};
	BX.Crm.CompanyModel.prototype.getCaption = function()
	{
		return this.getField("TITLE", "");
	};
	BX.Crm.CompanyModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.CompanyModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.CompanyModel.create = function(id, settings)
	{
		var self = new BX.Crm.CompanyModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.DealModel === "undefined")
{
	BX.Crm.DealModel = function()
	{
		BX.Crm.DealModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DealModel, BX.Crm.EntityModel);
	BX.Crm.DealModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Saved", BX.delegate(this.onEntityProgressSave, this));
	};
	BX.Crm.DealModel.prototype.onEntityProgressSave = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STAGE_ID", ""))
		{
			this.setField("STAGE_ID", stepId);
		}
	};
	BX.Crm.DealModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.deal;
	};
	BX.Crm.DealModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.DealModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.DealModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.DealModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.DealModel.create = function(id, settings)
	{
		var self = new BX.Crm.DealModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.DealRecurringModel === "undefined")
{
	BX.Crm.DealRecurringModel = function ()
	{
		BX.Crm.DealRecurringModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DealRecurringModel, BX.Crm.DealModel);

	BX.Crm.DealRecurringModel.create = function(id, settings)
	{
		var self = new BX.Crm.DealRecurringModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.QuoteModel === "undefined")
{
	BX.Crm.QuoteModel = function()
	{
		BX.Crm.QuoteModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.QuoteModel, BX.Crm.EntityModel);
	BX.Crm.QuoteModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.QuoteModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.QuoteModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.quote;
	};
	BX.Crm.QuoteModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.QuoteModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.QuoteModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.QuoteModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.QuoteModel.create = function(id, settings)
	{
		var self = new BX.Crm.QuoteModel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region D&D
if(typeof BX.Crm.EditorDragScope === "undefined")
{
	BX.Crm.EditorDragScope =
	{
		intermediate: 0,
		parent: 1,
		form: 2,
		getDefault: function()
		{
			return this.form;
		}
	};
}

if(typeof BX.Crm.EditorDragObjectType === "undefined")
{
	BX.Crm.EditorDragObjectType =
	{
		intermediate: "",
		field: "F",
		section: "S"
	};
}

if(typeof(BX.Crm.EditorDragItem) === "undefined")
{
	BX.Crm.EditorDragItem = function()
	{
	};
	BX.Crm.EditorDragItem.prototype =
	{
		getType: function()
		{
			return BX.Crm.EditorDragObjectType.intermediate;
		},
		getContextId: function()
		{
			return "";
		},
		createGhostNode: function()
		{
			return null;
		},
		processDragStart: function()
		{
		},
		processDragPositionChange: function(pos, ghostRect)
		{
		},
		processDragStop: function()
		{
		}
	};
}

if(typeof(BX.Crm.EditorFieldDragItem) === "undefined")
{
	BX.Crm.EditorFieldDragItem = function()
	{
		BX.Crm.EditorFieldDragItem.superclass.constructor.apply(this);
		this._scope = BX.Crm.EditorDragScope.undefined;
		this._control = null;
		this._contextId = "";
	};
	BX.extend(BX.Crm.EditorFieldDragItem, BX.Crm.EditorDragItem);
	BX.Crm.EditorFieldDragItem.prototype.initialize = function(settings)
	{
		this._control = BX.prop.get(settings, "control");
		if(!this._control)
		{
			throw "Crm.EditorFieldDragItem: The 'control' parameter is not defined in settings or empty.";
		}
		this._scope = BX.prop.getInteger(settings, "scope", BX.Crm.EditorDragScope.getDefault());
		this._contextId = BX.prop.getString(settings, "contextId", "");
	};
	BX.Crm.EditorFieldDragItem.prototype.getType = function()
	{
		return BX.Crm.EditorDragObjectType.field;
	};
	BX.Crm.EditorFieldDragItem.prototype.getControl = function()
	{
		return this._control;
	};
	BX.Crm.EditorFieldDragItem.prototype.getContextId = function()
	{
		return this._contextId !== "" ? this._contextId : BX.Crm.EditorFieldDragItem.contextId;
	};
	BX.Crm.EditorFieldDragItem.prototype.createGhostNode = function()
	{
		return this._control.createGhostNode();
	};
	BX.Crm.EditorFieldDragItem.prototype.processDragStart = function()
	{
		window.setTimeout(
			function()
			{
				//Ensure Field drag controllers are enabled.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorFieldDragItem.contextId, true);
				//Disable Section drag controllers for the avoidance of collisions.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorSectionDragItem.contextId, false);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();
			}
		);
		this._control.getWrapper().style.opacity = "0.2";
	};
	BX.Crm.EditorFieldDragItem.prototype.processDragPositionChange = function(pos, ghostRect)
	{
		//var startY = pos.y;

		var parentPos = this._scope === BX.Crm.EditorDragScope.parent
			? this._control.getParentPosition()
			: this._control.getRootContainerPosition();

		if(pos.y < parentPos.top)
		{
			pos.y = parentPos.top;
		}
		if((pos.y + ghostRect.height) > parentPos.bottom)
		{
			pos.y = parentPos.bottom - ghostRect.height;
		}
		if(pos.x < parentPos.left)
		{
			pos.x = parentPos.left;
		}
		if((pos.x + ghostRect.width) > parentPos.right)
		{
			pos.x = parentPos.right - ghostRect.width;
		}

		//var finishY = pos.y;
		//console.log("parent: %d start: %d final: %d", parentPos.top, startY, finishY);
	};
	BX.Crm.EditorFieldDragItem.prototype.processDragStop = function()
	{
		window.setTimeout(
			function()
			{
				//Returning Section drag controllers to work.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorSectionDragItem.contextId, true);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();
			}
		);
		this._control.getWrapper().style.opacity = "1";
	};
	BX.Crm.EditorFieldDragItem.contextId = "editor_field";
	BX.Crm.EditorFieldDragItem.create = function(settings)
	{
		var self = new BX.Crm.EditorFieldDragItem();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorSectionDragItem) === "undefined")
{
	BX.Crm.EditorSectionDragItem = function()
	{
		BX.Crm.EditorSectionDragItem.superclass.constructor.apply(this);
		this._control = null;
	};
	BX.extend(BX.Crm.EditorSectionDragItem, BX.Crm.EditorDragItem);
	BX.Crm.EditorSectionDragItem.prototype.initialize = function(settings)
	{
		this._control = BX.prop.get(settings, "control");
		if(!this._control)
		{
			throw "Crm.EditorSectionDragItem: The 'control' parameter is not defined in settings or empty.";
		}
	};
	BX.Crm.EditorSectionDragItem.prototype.getType = function()
	{
		return BX.Crm.EditorDragObjectType.section;
	};
	BX.Crm.EditorSectionDragItem.prototype.getControl = function()
	{
		return this._control;
	};
	BX.Crm.EditorSectionDragItem.prototype.getContextId = function()
	{
		return BX.Crm.EditorSectionDragItem.contextId;
	};
	BX.Crm.EditorSectionDragItem.prototype.createGhostNode = function()
	{
		return this._control.createGhostNode();
	};
	BX.Crm.EditorSectionDragItem.prototype.processDragStart = function()
	{
		BX.addClass(document.body, "crm-entity-widgets-drag");

		var control = this._control;
		control.getWrapper().style.opacity = "0.2";
		window.setTimeout(
			function()
			{
				//Ensure Section drag controllers are enabled.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorSectionDragItem.contextId, true);
				//Disable Field drag controllers for the avoidance of collisions.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorFieldDragItem.contextId, false);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();

				window.setTimeout(
					function()
					{
						var firstControl = control.getSiblingByIndex(0);
						if(firstControl !== null && firstControl !== control)
						{
							firstControl.getWrapper().scrollIntoView();
						}
					},
					200
				);
			}
		);
	};
	BX.Crm.EditorSectionDragItem.prototype.processDragStop = function()
	{
		BX.removeClass(document.body, "crm-entity-widgets-drag");
		window.setTimeout(
			function()
			{
				//Returning Field drag controllers to work.
				BX.Crm.EditorDragContainerController.enable(BX.Crm.EditorFieldDragItem.contextId, true);
				//Refresh all drag&drop destination areas.
				BX.Crm.EditorDragContainerController.refreshAll();
			}
		);

		var control = this._control;
		control.getWrapper().style.opacity = "1";
		window.setTimeout(
			function()
			{
				control.getWrapper().scrollIntoView();
			},
			150
		);
	};
	BX.Crm.EditorSectionDragItem.contextId = "editor_section";
	BX.Crm.EditorSectionDragItem.create = function(settings)
	{
		var self = new BX.Crm.EditorSectionDragItem();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragItemController) === "undefined")
{
	BX.Crm.EditorDragItemController = function()
	{
		BX.Crm.EditorDragItemController.superclass.constructor.apply(this);
		this._charge = null;
		this._preserveDocument = true;
	};
	BX.extend(BX.Crm.EditorDragItemController, BX.CrmCustomDragItem);
	BX.Crm.EditorDragItemController.prototype.doInitialize = function()
	{
		this._charge = this.getSetting("charge");
		if(!this._charge)
		{
			throw "Crm.EditorDragItemController: The 'charge' parameter is not defined in settings or empty.";
		}

		this._startNotifier = BX.CrmNotifier.create(this);
		this._stopNotifier = BX.CrmNotifier.create(this);

		this._ghostOffset = { x: 0, y: -40 };
	};
	BX.Crm.EditorDragItemController.prototype.addStartListener = function(listener)
	{
		this._startNotifier.addListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.removeStartListener = function(listener)
	{
		this._startNotifier.removeListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.addStopListener = function(listener)
	{
		this._stopNotifier.addListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.removeStopListener = function(listener)
	{
		this._stopNotifier.removeListener(listener);
	};
	BX.Crm.EditorDragItemController.prototype.getCharge = function()
	{
		return this._charge;
	};
	BX.Crm.EditorDragItemController.prototype.createGhostNode = function()
	{
		if(this._ghostNode)
		{
			return this._ghostNode;
		}

		this._ghostNode = this._charge.createGhostNode();
		document.body.appendChild(this._ghostNode);
	};
	BX.Crm.EditorDragItemController.prototype.getGhostNode = function()
	{
		return this._ghostNode;
	};
	BX.Crm.EditorDragItemController.prototype.removeGhostNode = function()
	{
		if(this._ghostNode)
		{
			document.body.removeChild(this._ghostNode);
			this._ghostNode = null;
		}
	};
	BX.Crm.EditorDragItemController.prototype.getContextId = function()
	{
		return this._charge.getContextId();
	};
	BX.Crm.EditorDragItemController.prototype.getContextData = function()
	{
		return ({ contextId: this._charge.getContextId(), charge: this._charge });
	};
	BX.Crm.EditorDragItemController.prototype.processDragStart = function()
	{
		BX.Crm.EditorDragItemController.current = this;
		this._charge.processDragStart();
		BX.Crm.EditorDragContainerController.refresh(this._charge.getContextId());

		this._startNotifier.notify([]);
	};
	BX.Crm.EditorDragItemController.prototype.processDrag = function(x, y)
	{
	};
	BX.Crm.EditorDragItemController.prototype.processDragPositionChange = function(pos)
	{
		this._charge.processDragPositionChange(pos, BX.pos(this.getGhostNode()));
	};
	BX.Crm.EditorDragItemController.prototype.processDragStop = function()
	{
		BX.Crm.EditorDragItemController.current = null;
		this._charge.processDragStop();
		BX.Crm.EditorDragContainerController.refreshAfter(this._charge.getContextId(), 300);

		this._stopNotifier.notify([]);
	};
	BX.Crm.EditorDragItemController.current = null;
	BX.Crm.EditorDragItemController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorDragItemController();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragContainer) === "undefined")
{
	BX.Crm.EditorDragContainer = function()
	{
	};
	BX.Crm.EditorDragContainer.prototype =
	{
		getContextId: function()
		{
			return "";
		},
		getPriority: function()
		{
			return 100;
		},
		hasPlaceHolder: function()
		{
			return false;
		},
		createPlaceHolder: function(index)
		{
			return null;
		},
		getPlaceHolder: function()
		{
			return null;
		},
		removePlaceHolder: function()
		{
		},
		getChildNodes: function()
		{
			return [];
		},
		getChildNodeCount: function()
		{
			return 0;
		}
	}
}

if(typeof(BX.Crm.EditorFieldDragContainer) === "undefined")
{
	BX.Crm.EditorFieldDragContainer = function()
	{
		BX.Crm.EditorFieldDragContainer.superclass.constructor.apply(this);
		this._section = null;
		this._context = "";
	};
	BX.extend(BX.Crm.EditorFieldDragContainer, BX.Crm.EditorDragContainer);
	BX.Crm.EditorFieldDragContainer.prototype.initialize = function(settings)
	{
		this._section = BX.prop.get(settings, "section");
		if(!this._section)
		{
			throw "Crm.EditorSectionDragContainer: The 'section' parameter is not defined in settings or empty.";
		}

		this._context = BX.prop.getString(settings, "context", "");
	};
	BX.Crm.EditorFieldDragContainer.prototype.getSection = function()
	{
		return this._section;
	};
	BX.Crm.EditorFieldDragContainer.prototype.getContextId = function()
	{
		return this._context !== "" ? this._context : BX.Crm.EditorFieldDragItem.contextId;
	};
	BX.Crm.EditorFieldDragContainer.prototype.getPriority = function()
	{
		return 10;
	};
	BX.Crm.EditorFieldDragContainer.prototype.hasPlaceHolder = function()
	{
		return this._section.hasPlaceHolder();
	};
	BX.Crm.EditorFieldDragContainer.prototype.createPlaceHolder = function(index)
	{
		return this._section.createPlaceHolder(index);
	};
	BX.Crm.EditorFieldDragContainer.prototype.getPlaceHolder = function()
	{
		return this._section.getPlaceHolder();
	};
	BX.Crm.EditorFieldDragContainer.prototype.removePlaceHolder = function()
	{
		this._section.removePlaceHolder();
	};
	BX.Crm.EditorFieldDragContainer.prototype.getChildNodes = function()
	{
		var nodes = [];
		var items = this._section.getChildren();
		for(var i = 0, length = items.length; i < length; i++)
		{
			nodes.push(items[i].getWrapper());
		}
		return nodes;
	};
	BX.Crm.EditorFieldDragContainer.prototype.getChildNodeCount = function()
	{
		return this._section.getChildCount();
	};
	BX.Crm.EditorFieldDragContainer.create = function(settings)
	{
		var self = new BX.Crm.EditorFieldDragContainer();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorSectionDragContainer) === "undefined")
{
	BX.Crm.EditorSectionDragContainer = function()
	{
		BX.Crm.EditorSectionDragContainer.superclass.constructor.apply(this);
		this._editor = null;
	};
	BX.extend(BX.Crm.EditorSectionDragContainer, BX.Crm.EditorDragContainer);
	BX.Crm.EditorSectionDragContainer.prototype.initialize = function(settings)
	{
		this._editor = BX.prop.get(settings, "editor");
		if(!this._editor)
		{
			throw "Crm.EditorSectionDragContainer: The 'editor' parameter is not defined in settings or empty.";
		}
	};
	BX.Crm.EditorSectionDragContainer.prototype.getEditor = function()
	{
		return this._editor;
	};
	BX.Crm.EditorSectionDragContainer.prototype.getContextId = function()
	{
		return BX.Crm.EditorSectionDragItem.contextId;
	};
	BX.Crm.EditorSectionDragContainer.prototype.getPriority = function()
	{
		return 20;
	};
	BX.Crm.EditorSectionDragContainer.prototype.hasPlaceHolder = function()
	{
		return this._editor.hasPlaceHolder();
	};
	BX.Crm.EditorSectionDragContainer.prototype.createPlaceHolder = function(index)
	{
		return this._editor.createPlaceHolder(index);
	};
	BX.Crm.EditorSectionDragContainer.prototype.getPlaceHolder = function()
	{
		return this._editor.getPlaceHolder();
	};
	BX.Crm.EditorSectionDragContainer.prototype.removePlaceHolder = function()
	{
		this._editor.removePlaceHolder();
	};
	BX.Crm.EditorSectionDragContainer.prototype.getChildNodes = function()
	{
		var nodes = [];
		var items = this._editor.getControls();
		for(var i = 0, length = items.length; i < length; i++)
		{
			nodes.push(items[i].getWrapper());
		}
		return nodes;
	};
	BX.Crm.EditorSectionDragContainer.prototype.getChildNodeCount = function()
	{
		return this._editor.getControlCount();
	};
	BX.Crm.EditorSectionDragContainer.create = function(settings)
	{
		var self = new BX.Crm.EditorSectionDragContainer();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragContainerController) === "undefined")
{
	BX.Crm.EditorDragContainerController = function()
	{
		BX.Crm.EditorDragContainerController.superclass.constructor.apply(this);
		this._charge = null;
	};
	BX.extend(BX.Crm.EditorDragContainerController, BX.CrmCustomDragContainer);
	BX.Crm.EditorDragContainerController.prototype.doInitialize = function()
	{
		this._charge = this.getSetting("charge");
		if(!this._charge)
		{
			throw "Crm.EditorDragContainerController: The 'charge' parameter is not defined in settings or empty.";
		}
	};
	BX.Crm.EditorDragContainerController.prototype.getCharge = function()
	{
		return this._charge;
	};
	BX.Crm.EditorDragContainerController.prototype.createPlaceHolder = function(pos)
	{
		var ghostRect = BX.pos(BX.Crm.EditorDragItemController.current.getGhostNode());
		var ghostTop = ghostRect.top, ghostBottom = ghostRect.top + 40;
		var ghostMean = Math.floor((ghostTop + ghostBottom) / 2);

		var rect, mean;
		var placeholder = this._charge.getPlaceHolder();
		if(placeholder)
		{
			rect = placeholder.getPosition();
			mean = Math.floor((rect.top + rect.bottom) / 2);
			if(
				(ghostTop <= rect.bottom && ghostTop >= rect.top) ||
				(ghostBottom >= rect.top && ghostBottom <= rect.bottom) ||
				Math.abs(ghostMean - mean) <= 8
			)
			{
				if(!placeholder.isActive())
				{
					placeholder.setActive(true);
				}
				return;
			}
		}

		var nodes = this._charge.getChildNodes();
		for(var i = 0; i < nodes.length; i++)
		{
			rect = BX.pos(nodes[i]);
			mean = Math.floor((rect.top + rect.bottom) / 2);
			if(
				(ghostTop <= rect.bottom && ghostTop >= rect.top) ||
				(ghostBottom >= rect.top && ghostBottom <= rect.bottom) ||
				Math.abs(ghostMean - mean) <= 8
			)
			{
				this._charge.createPlaceHolder((ghostMean - mean) <= 0 ? i : (i + 1)).setActive(true);
				return;
			}
		}

		this._charge.createPlaceHolder(-1).setActive(true);
		this.refresh();
	};
	BX.Crm.EditorDragContainerController.prototype.removePlaceHolder = function()
	{
		if(!this._charge.hasPlaceHolder())
		{
			return;
		}

		if(this._charge.getChildNodeCount() > 0)
		{
			this._charge.removePlaceHolder();
		}
		else
		{
			this._charge.getPlaceHolder().setActive(false);
		}
		this.refresh();
	};
	BX.Crm.EditorDragContainerController.prototype.getContextId = function()
	{
		return this._charge.getContextId();
	};
	BX.Crm.EditorDragContainerController.prototype.getPriority = function()
	{
		return this._charge.getPriority();
	};
	BX.Crm.EditorDragContainerController.prototype.isAllowedContext = function(contextId)
	{
		return contextId === this._charge.getContextId();
	};
	BX.Crm.EditorDragContainerController.refresh = function(contextId)
	{
		for(var k in this.items)
		{
			if(!this.items.hasOwnProperty(k))
			{
				continue;
			}
			var item = this.items[k];
			if(item.getContextId() === contextId)
			{
				item.refresh();
			}
		}
	};
	BX.Crm.EditorDragContainerController.refreshAfter = function(contextId, interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.Crm.EditorDragContainerController.refresh(contextId); }, interval);
		}
		else
		{
			this.refresh(contextId);
		}
	};
	BX.Crm.EditorDragContainerController.refreshAll = function()
	{
		for(var k in this.items)
		{
			if(!this.items.hasOwnProperty(k))
			{
				continue;
			}
			this.items[k].refresh();
		}
	};
	BX.Crm.EditorDragContainerController.enable = function(contextId, enable)
	{
		for(var k in this.items)
		{
			if(!this.items.hasOwnProperty(k))
			{
				continue;
			}
			var item = this.items[k];
			if(item.getContextId() === contextId)
			{
				item.enable(enable);
			}
		}
	};
	BX.Crm.EditorDragContainerController.items = {};
	BX.Crm.EditorDragContainerController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorDragContainerController();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.Crm.EditorDragPlaceholder) === "undefined")
{
	BX.Crm.EditorDragPlaceholder = function()
	{
		this._settings = null;
		this._container = null;
		this._node = null;
		this._isDragOver = false;
		this._isActive = false;
		this._index = -1;
		this._timeoutId = null;
	};
	BX.Crm.EditorDragPlaceholder.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._container = this.getSetting("container", null);

			this._isActive = this.getSetting("isActive", false);
			this._index = parseInt(this.getSetting("index", -1));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		isDragOver: function()
		{
			return this._isDragOver;
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active, interval)
		{
			if(this._timeoutId !== null)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = null;
			}

			interval = parseInt(interval);
			if(interval > 0)
			{
				var self = this;
				window.setTimeout(function(){ if(self._timeoutId === null) return; self._timeoutId = null; self.setActive(active, 0); }, interval);
				return;
			}

			active = !!active;
			if(this._isActive === active)
			{
				return;
			}

			this._isActive = active;
			if(this._node)
			{
				//this._node.className = active ? "crm-lead-header-drag-zone-bd" : "crm-lead-header-drag-zone-bd-inactive";
			}
		},
		getIndex: function()
		{
			return this._index;
		},
		prepareNode: function()
		{
			return null;
		},
		layout: function()
		{
			this._node = this.prepareNode();
			var anchor = this.getSetting("anchor", null);
			if(anchor)
			{
				this._container.insertBefore(this._node, anchor);
			}
			else
			{
				this._container.appendChild(this._node);
			}

			BX.bind(this._node, "dragover", BX.delegate(this._onDragOver, this));
			BX.bind(this._node, "dragleave", BX.delegate(this._onDragLeave, this));
		},
		clearLayout: function()
		{
			if(this._node)
			{
				// this._node = BX.remove(this._node);
				this._node.style.height = 0;
				setTimeout(BX.proxy(function (){this._node = BX.remove(this._node);}, this), 100);
			}
		},
		getPosition: function()
		{
			return BX.pos(this._node);
		},
		_onDragOver: function(e)
		{
			e = e || window.event;
			this._isDragOver = true;
			return BX.eventReturnFalse(e);
		},
		_onDragLeave: function(e)
		{
			e = e || window.event;
			this._isDragOver = false;
			return BX.eventReturnFalse(e);
		}
	}
}

if(typeof(BX.Crm.EditorDragFieldPlaceholder) === "undefined")
{
	BX.Crm.EditorDragFieldPlaceholder = function()
	{
	};

	BX.extend(BX.Crm.EditorDragFieldPlaceholder, BX.Crm.EditorDragPlaceholder);
	BX.Crm.EditorDragFieldPlaceholder.prototype.prepareNode = function()
	{
		return BX.create("div", { attrs: { className: "crm-entity-widget-content-block-place" } });
	};
	BX.Crm.EditorDragFieldPlaceholder.create = function(settings)
	{
		var self = new BX.Crm.EditorDragFieldPlaceholder();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.Crm.EditorDragSectionPlaceholder) === "undefined")
{
	BX.Crm.EditorDragSectionPlaceholder = function()
	{
	};

	BX.extend(BX.Crm.EditorDragSectionPlaceholder, BX.Crm.EditorDragPlaceholder);
	BX.Crm.EditorDragSectionPlaceholder.prototype.prepareNode = function()
	{
		return BX.create("div", { attrs: { className: "crm-entity-card-widget crm-entity-card-widget-place" } });
	};
	BX.Crm.EditorDragSectionPlaceholder.create = function(settings)
	{
		var self = new BX.Crm.EditorDragSectionPlaceholder();
		self.initialize(settings);
		return self;
	};
}

//endregion

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

//endregion

//region DUPLICATE MANAGER
if(typeof BX.Crm.EntityEditorDupManager === "undefined")
{
	BX.Crm.EntityEditorDupManager = function()
	{
		this._id = "";
		this._settings = null;
		this._groupInfos = null;

		this._isEnabled = false;
		this._serviceUrl = "";
		this._entityTypeName = "";
		this._form = null;
		this._controller = null;
	};
	BX.Crm.EntityEditorDupManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._isEnabled = BX.prop.getBoolean(this._settings, "enabled", "");
			if(!this._isEnabled)
			{
				return;
			}

			this._groupInfos = BX.prop.getObject(this._settings, "groups", {});

			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
			this._form = BX.prop.get(this._settings, "form", null);

			this._controller = BX.CrmDupController.create(
				this._id,
				{
					serviceUrl: this._serviceUrl,
					entityTypeName: this._entityTypeName,
					form: this._form,
					searcSummaryPosition: "right"
				}
			);
		},
		isEnabled: function()
		{
			return this._isEnabled;
		},
		search: function()
		{
			this._controller.initialSearch();
		},
		getGroupInfo: function(groupId)
		{
			return this._groupInfos.hasOwnProperty(groupId) ? this._groupInfos[groupId] : null;
		},
		getGroup: function(groupId)
		{
			return this._isEnabled ? this._controller.getGroup(groupId) : null;
		},
		ensureGroupRegistered: function(groupId)
		{
			if(!this._isEnabled)
			{
				return null;
			}

			var group = this.getGroup(groupId);
			if(!group)
			{
				group = this._controller.registerGroup(groupId, this.getGroupInfo(groupId));
			}
			return group;
		},
		registerField: function(config)
		{
			if(!this._isEnabled)
			{
				return null;
			}

			var groupId = BX.prop.getString(config, "groupId", "");
			var field = BX.prop.getObject(config, "field", null);
			if(groupId === "" || !field)
			{
				return null;
			}

			var group = this.ensureGroupRegistered(groupId);
			if(!group)
			{
				return null;
			}

			return group.registerField(field);
		},
		unregisterField: function(config)
		{
			if(!this._isEnabled)
			{
				return;
			}

			var groupId = BX.prop.getString(config, "groupId", "");
			var field = BX.prop.getObject(config, "field", null);
			if(groupId === "" || !field)
			{
				return;
			}

			var group = this.getGroup(groupId);
			if(!group)
			{
				return;
			}

			group.unregisterField(field);
		}
	};
	BX.Crm.EntityEditorDupManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorDupManager();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region HELPERS
if(typeof BX.Crm.EditorTextHelper === "undefined")
{
	BX.Crm.EditorTextHelper = function()
	{
	};
	BX.Crm.EditorTextHelper.prototype =
	{
		selectAll: function(input)
		{
			if(!(BX.type.isElementNode(input) && input.value.length > 0))
			{
				return;
			}

			if(BX.type.isFunction(input.setSelectionRange))
			{
				input.setSelectionRange(0, input.value.length);
			}
			else
			{
				input.select();
			}
		},
		setPositionAtEnd: function(input)
		{
			if(BX.type.isElementNode(input) && input.value.length > 0)
			{
				BX.setCaretPosition(input, input.value.length);
			}
		}
	};
	BX.Crm.EditorTextHelper._current = null;
	BX.Crm.EditorTextHelper.getCurrent = function ()
	{
		if(!this._current)
		{
			this._current = new BX.Crm.EditorTextHelper();
		}
		return this._current;
	}
}
//endregion

//region CONTROL VISIBILITY POLICY
if(typeof BX.Crm.EntityEditorVisibilityPolicy === "undefined")
{
	BX.Crm.EntityEditorVisibilityPolicy =
	{
		always: 0,
		view: 1,
		edit: 2,
		parse: function(str)
		{
			str = str.toLowerCase();
			if(str === "view")
			{
				return this.view;
			}
			else if(str === "edit")
			{
				return this.edit;
			}
			return this.always;
		},
		checkVisibility: function(control)
		{
			var mode = control.getMode();
			var policy = control.getVisibilityPolicy();

			if(policy === this.view)
			{
				return mode === BX.Crm.EntityEditorMode.view;
			}
			else if(policy === this.edit)
			{
				return mode === BX.Crm.EntityEditorMode.edit;
			}
			return true;
		}
	};
}
//endregion

//region CONTROLS (SECTIONS, FIELDS)
if(typeof BX.Crm.EntityEditorControl === "undefined")
{
	BX.Crm.EntityEditorControl = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;
		this._parent = null;

		this._mode = BX.Crm.EntityEditorMode.intermediate;
		this._modeOptions = BX.Crm.EntityEditorModeOptions.none;
		this._model = null;
		this._schemeElement = null;

		this._container = null;
		this._wrapper = null;
		this._dragButton = null;
		this._dragItem = null;
		this._hasLayout = false;
		this._isValidLayout = false;

		this._isVisible = true;
		this._isActive = false;
		this._isChanged = false;
		this._isSchemeChanged = false;
		this._changeHandler = BX.delegate(this.onChange, this);

		this._modeChangeNotifier = null;

		this._contextMenuButton = null;
		this._isContextMenuOpened = false;

		this._draggableContextId = "";
	};
	BX.Crm.EntityEditorControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._editor = BX.prop.get(this._settings, "editor", null);
			this._parent = BX.prop.get(this._settings, "parent", null);

			this._model = BX.prop.get(this._settings, "model", null);

			this._schemeElement = BX.prop.get(this._settings, "schemeElement", null);
			this._container = BX.prop.getElementNode(this._settings, "container", null);

			var mode = BX.prop.getInteger(this._settings, "mode", BX.Crm.EntityEditorMode.view);
			if(mode === BX.Crm.EntityEditorMode.edit && this._schemeElement && !this._schemeElement.isEditable())
			{
				mode = BX.Crm.EntityEditorMode.view;
			}
			this._mode = mode;

			this.doInitialize();
			this.bindModel();
		},
		doInitialize: function()
		{
		},
		bindModel: function()
		{
		},
		unbindModel: function()
		{
		},
		getMessage: function(name)
		{
			var m = BX.Crm.EntityEditorControl.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getId: function()
		{
			return this._id;
		},
		getEditor: function()
		{
			return this._editor;
		},
		setEditor: function(editor)
		{
			this._editor = editor;
		},
		getParentPosition: function()
		{
			var parent = this.getParent();
			return (parent ? parent.getPosition() : { top: 0, right: 0, bottom: 0, left: 0, width: 0, height: 0 });
		},
		getParent: function()
		{
			return this._parent;
		},
		setParent: function(parent)
		{
			this._parent = parent;
		},
		getSiblingByIndex: function (index)
		{
			return this._editor ? this._editor.getControlByIndex(index) : null;
		},
		getChildCount: function()
		{
			return 0;
		},
		getChildById: function(childId)
		{
			return null;
		},
		editChild: function(child)
		{
		},
		removeChild: function(child)
		{
		},
		getChildren: function()
		{
			return [];
		},
		editChildConfiguration: function(child)
		{
		},
		areAttributesEnabled: function()
		{
			return this._schemeElement && this._schemeElement.areAttributesEnabled();
		},
		getType: function()
		{
			return this._schemeElement ? this._schemeElement.getType() : "";
		},
		getName: function()
		{
			return this._schemeElement ? this._schemeElement.getName() : "";
		},
		getTitle: function()
		{
			if(!this._schemeElement)
			{
				return "";
			}

			var title = this._schemeElement.getTitle();
			if(title === "")
			{
				title = this._schemeElement.getName();
			}

			return title;
		},
		setTitle: function(title)
		{
			if(!this._schemeElement)
			{
				return;
			}

			this._schemeElement.setTitle(title);
			this.refreshTitleLayout();
		},
		getOptionFlags: function()
		{
			return(this._schemeElement
				? this._schemeElement.getOptionFlags()
				: BX.Crm.EntityEditorControlOptions.none
			);
		},
		setOptionFlags: function(flags)
		{
			if(this._schemeElement)
			{
				this._schemeElement.setOptionFlags(flags);
			}
		},
		toggleOptionFlag: function(flag)
		{
			var flags = this.getOptionFlags();
			if(BX.Crm.EntityEditorControlOptions.check(flags, flag))
			{
				flags &= ~flag;
			}
			else
			{
				flags |= flag;
			}
			this.setOptionFlags(flags);
		},
		checkOptionFlag: function(flag)
		{
			return BX.Crm.EntityEditorControlOptions.check(this.getOptionFlags(), flag);
		},
		getData: function()
		{
			return this._schemeElement ? this._schemeElement.getData() : {};
		},
		isVisible: function()
		{
			if(!this._isVisible)
			{
				return false;
			}

			if(this.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways))
			{
				return true;
			}
			return BX.Crm.EntityEditorVisibilityPolicy.checkVisibility(this);
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;
			if(this._hasLayout)
			{
				this._wrapper.style.display = this._isVisible ? "" : "none";
			}
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active)
		{
			active = !!active;
			if(this._isActive === active)
			{
				return;
			}

			this._isActive = active;
			this.doSetActive();
		},
		doSetActive: function()
		{
		},
		isEditable: function()
		{
			return this._schemeElement && this._schemeElement.isEditable();
		},
		isRequired: function()
		{
			return this._schemeElement && this._schemeElement.isRequired();
		},
		isRequiredConditionally: function()
		{
			return this._schemeElement && this._schemeElement.isRequiredConditionally();
		},
		isHeading: function()
		{
			return this._schemeElement && this._schemeElement.isHeading();
		},
		getCreationPlaceholder: function()
		{
			return this._schemeElement ? this._schemeElement.getCreationPlaceholder() : "";
		},
		isReadOnly: function()
		{
			return this._editor && this._editor.isReadOnly();
		},
		getVisibilityPolicy: function()
		{
			if(this._editor && !this._editor.isVisibilityPolicyEnabled())
			{
				return BX.Crm.EntityEditorVisibilityPolicy.always;
			}
			return this._schemeElement && this._schemeElement.getVisibilityPolicy();
		},
		getEditPriority: function()
		{
			return BX.Crm.EntityEditorPriority.normal;
		},
		getPosition: function()
		{
			return BX.pos(this._wrapper);
		},
		focus: function()
		{
		},
		save: function()
		{
		},
		validate: function(result)
		{
			return true;
		},
		rollback: function()
		{
		},
		isDragEnabled: function()
		{
			if(!this._editor)
			{
				return false;
			}

			if(!this._editor.canChangeScheme())
			{
				return false;
			}

			return BX.prop.getBoolean(
				BX.prop.getObject(
					this._editor.getDragConfig(this.getDragObjectType()),
					"modes",
					{}
				),
				BX.Crm.EntityEditorMode.getName(this._mode),
				false
			);
		},
		isContextMenuEnabled: function()
		{
			if(this._editor && !(this._editor.isFieldsContextMenuEnabled() && this._editor.canChangeScheme()))
			{
				return false;
			}

			return this._schemeElement.isContextMenuEnabled();
		},
		getMode: function()
		{
			return this._mode;
		},
		setMode: function(mode, options)
		{
			if(!this.canChangeMode(mode))
			{
				return;
			}

			var modeOptions = BX.prop.getInteger(options, "options", BX.Crm.EntityEditorModeOptions.none);
			if(this._mode === mode && this._modeOptions === modeOptions)
			{
				return;
			}

			this.onBeforeModeChange();

			this._mode = mode;
			this._modeOptions = modeOptions;
			this.doSetMode(this._mode);

			this.onAfterModeChange();

			if(BX.prop.getBoolean(options, "notify", false))
			{
				if(this._parent)
				{
					this._parent.processChildControlModeChange(this);
				}
				else if(this._editor)
				{
					this._editor.processControlModeChange(this);
				}
			}

			this._isSchemeChanged = false;
			this._isChanged = false;

			if(this._hasLayout)
			{
				this._isValidLayout = false;
			}
		},
		getModeChangeNotifier: function()
		{
			if(!this._modeChangeNotifier)
			{
				this._modeChangeNotifier = BX.CrmNotifier.create(this);
			}
			return this._modeChangeNotifier;
		},
		onBeforeModeChange: function()
		{
		},
		doSetMode: function(mode)
		{
		},
		onAfterModeChange: function()
		{
			if(this._modeChangeNotifier)
			{
				this._modeChangeNotifier.notify();
			}
		},
		canChangeMode: function(mode)
		{
			if(mode === BX.Crm.EntityEditorMode.edit)
			{
				return this.isEditable();
			}
			return true;
		},
		isModeToggleEnabled: function()
		{
			return this._editor.isModeToggleEnabled();
		},
		toggleMode: function(notify, options)
		{
			if(!this.isModeToggleEnabled())
			{
				return false;
			}

			this.setMode(
				this._mode === BX.Crm.EntityEditorMode.view
					? BX.Crm.EntityEditorMode.edit : BX.Crm.EntityEditorMode.view,
				{ notify: notify }
			);

			if(BX.prop.getBoolean(options, "refreshLayout", true))
			{
				this.refreshLayout();
			}
			return true;
		},
		isEditInViewEnabled: function()
		{
			//"Edit in View" - control value may be changed in view mode
			return(this._editor
				&& this._editor.isEditInViewEnabled()
				&& this.getDataBooleanParam("enableEditInView", false)
			);
		},
		isSingleEditEnabled: function()
		{
			//"Single Edit" - control may be switched to edit mode independently of parent control (section)
			return(
				this.isModeToggleEnabled()
				&& this.isEditable()
				&& !this.getDataBooleanParam("enableEditInView", false)
				&& this.getDataBooleanParam("enableSingleEdit", true)
			);
		},
		isInSingleEditMode: function()
		{
			if(!this.isInEditMode())
			{
				return false;
			}

			return(this.checkModeOption(BX.Crm.EntityEditorModeOptions.exclusive)
				|| this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual)
			);
		},
		isInEditMode: function()
		{
			return this._mode === BX.Crm.EntityEditorMode.edit;
		},
		isInViewMode: function()
		{
			return this._mode === BX.Crm.EntityEditorMode.view;
		},
		checkModeOption: function(option)
		{
			return BX.Crm.EntityEditorModeOptions.check(this._modeOptions, option);
		},
		getContextId: function()
		{
			return this._editor ? this._editor.getContextId() : '';
		},
		getExternalContextId: function()
		{
			return this._editor ? this._editor.getExternalContextId() : '';
		},
		processAvailableSchemeElementsChange: function()
		{
		},
		processChildControlModeChange: function(control)
		{
		},
		processChildControlChange: function(control, params)
		{
		},
		isChanged: function()
		{
			return this._isChanged;
		},
		markAsChanged: function(params)
		{
			if(typeof(params) === "undefined")
			{
				params = {};
			}

			var control = BX.prop.get(params, "control", null);
			if(!(control && control instanceof BX.Crm.EntityEditorControl))
			{
				control = params["control"] = this;
			}

			if(!control.isInEditMode())
			{
				return;
			}

			if(!this._isChanged)
			{
				this._isChanged = true;
			}

			this.notifyChanged(params);
		},
		isSchemeChanged: function()
		{
			return this._isSchemeChanged;
		},
		markSchemeAsChanged: function()
		{
			if(this._isSchemeChanged)
			{
				return;
			}

			this._isSchemeChanged = true;
		},
		saveScheme: function()
		{
			if(!this._isSchemeChanged)
			{
				return;
			}

			this.commitSchemeChanges();
			return this._editor.saveScheme();
		},
		commitSchemeChanges: function()
		{
			if(!this._isSchemeChanged)
			{
				return;
			}

			this._editor.updateSchemeElement(this._schemeElement);
			this._isSchemeChanged = false;
		},
		getRootContainer: function()
		{
			return this._editor ? this._editor.getContainer() : null;
		},
		getRootContainerPosition: function()
		{
			return BX.pos(this.getRootContainer());
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function (container)
		{
			this._container = container;
			if(this._hasLayout)
			{
				this._hasLayout = false;
			}
		},
		getWrapper: function()
		{
			return this._wrapper;
		},
		enablePointerEvents: function(enable)
		{
			if(this._wrapper)
			{
				this._wrapper.style.pointerEvents = enable ? "" : "none";
			}
		},
		getModel: function()
		{
			return this._model;
		},
		getSchemeElement: function()
		{
			return this._schemeElement;
		},
		hasScheme: function()
		{
			return !!this._schemeElement;
		},
		getDataBooleanParam: function(name, defaultval)
		{
			return(this._schemeElement
				? this._schemeElement.getDataBooleanParam(name, defaultval)
				: defaultval
			);
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		layout: function(options)
		{
		},
		registerLayout:  function(options)
		{
			if(!this._wrapper)
			{
				return;
			}

			this._wrapper.setAttribute("data-cid", this.getId());

			//HACK: Fix positions of context menu and drag button for readonly fields in editing section
			if(this.isInViewMode() && this._parent && this._parent.isInEditMode())
			{
				BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-readonly");
			}
			else
			{
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-field-readonly");
			}

			if(typeof options === "undefined")
			{
				options = {};
			}

			if(!BX.prop.getBoolean(options, "preservePosision", false))
			{
				var anchor = BX.prop.getElementNode(options, "anchor", null);
				if (anchor)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-hide");
					this._container.insertBefore(this._wrapper, anchor);
					setTimeout(BX.delegate(function ()
					{
						BX.removeClass(this._wrapper, "crm-entity-widget-content-hide");
						BX.addClass(this._wrapper, "crm-entity-widget-content-show");
					}, this), 1);
					setTimeout(BX.delegate(function ()
					{
						BX.removeClass(this._wrapper, "crm-entity-widget-content-show");
					}, this), 310);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}
			}

			this._isValidLayout = true;
			this.doRegisterLayout();
		},
		doRegisterLayout: function()
		{
		},
		refreshLayout: function(options)
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.closeContextMenu();

			this.clearLayout({ preservePosision: true });
			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			if(BX.prop.getBoolean(options, "reset", false))
			{
				this.reset();
			}

			options["preservePosision"] = true;
			this.layout(options);
		},
		clearLayout: function(options)
		{
		},
		refreshTitleLayout: function()
		{
		},
		releaseLayout: function ()
		{
			this._wrapper = null;
		},
		release: function()
		{
		},
		reset: function()
		{
		},
		hide: function()
		{
			if(this.isRequired() || this.isRequiredConditionally())
			{
				return;
			}

			if(this._parent)
			{
				BX.addClass(this._wrapper, "crm-entity-widget-content-hide");
				setTimeout(BX.delegate(function ()
				{
					this._parent.removeChild(this);
				}, this), 350);
			}
			else
			{
				this.clearLayout();
			}
		},
		showMessageDialog: function(id, title, content)
		{
			if(this._editor)
			{
				this._editor.showMessageDialog(id, title, content);
			}
		},
		prepareSaveData: function(data)
		{
		},
		onBeforeSubmit: function()
		{
		},
		onHideButtonClick: function(e)
		{
			this.hide();
		},
		onContextButtonClick: function(e)
		{
			if(!this._isContextMenuOpened)
			{
				this.openContextMenu();
			}
			else
			{
				this.closeContextMenu();
			}
		},
		openContextMenu: function()
		{
			if(this._isContextMenuOpened)
			{
				return;
			}

			var menu = this.prepareContextMenuItems();
			if(BX.type.isArray(menu) && menu.length > 0)
			{
				var handler = BX.delegate( this.onContextMenuItemSelect, this);
				for(var i = 0, length = menu.length; i < length; i++)
				{
					if(typeof menu[i]["onclick"] === "undefined")
					{
						menu[i]["onclick"] = handler;
					}
				}
				BX.PopupMenu.show(
					this._id,
					this._contextMenuButton,
					menu,
					{
						angle: false,
						events:
							{
								onPopupShow: BX.delegate(this.onContextMenuShow, this),
								onPopupClose: BX.delegate(this.onContextMenuClose, this)
							}
					}
				);
			}
		},
		prepareContextMenuItems: function()
		{
			return [];
		},
		processContextMenuCommand: function(e, command)
		{
		},
		closeContextMenu: function()
		{
			var menu = BX.PopupMenu.getMenuById(this._id);
			if(menu)
			{
				menu.popupWindow.close();
			}
		},
		onContextMenuShow: function()
		{
			this._isContextMenuOpened = true;
		},
		onContextMenuClose: function()
		{
			BX.PopupMenu.destroy(this._id);
			this._isContextMenuOpened = false;
		},
		onContextMenuItemSelect: function(e, item)
		{
			this.processContextMenuCommand(e, BX.prop.getString(item, "value"));
		},
		onChange: function(e)
		{
			this.markAsChanged();
		},
		notifyChanged: function(params)
		{
			if(typeof(params) === "undefined")
			{
				params = {};
			}

			if(this._parent)
			{
				this._parent.processChildControlChange(this, params);
			}
			else if(this._editor)
			{
				this._editor.processControlChange(this, params);
			}
		},
		getDragObjectType: function()
		{
			return BX.Crm.EditorDragObjectType.intermediate;
		},
		getChildDragObjectType: function()
		{
			return BX.Crm.EditorDragObjectType.intermediate;
		},
		getDragScope: function()
		{
			if(this._parent)
			{
				return this._parent.getChildDragScope();
			}

			if(!this._editor)
			{
				return BX.Crm.EditorDragScope.getDefault();
			}

			return BX.prop.getInteger(
				this._editor.getDragConfig(this.getDragObjectType()),
				"scope",
				BX.Crm.EditorDragScope.getDefault()
			);
		},
		getChildDragScope: function()
		{
			if(!this._editor)
			{
				return BX.Crm.EditorDragScope.getDefault();
			}

			return BX.prop.getInteger(
				this._editor.getDragConfig(this.getChildDragObjectType()),
				"scope",
				BX.Crm.EditorDragScope.getDefault()
			);
		},
		getDraggableContextId: function()
		{
			return this._draggableContextId;
		},
		setDraggableContextId: function(contextId)
		{
			this._draggableContextId = contextId;
		},
		createDragButton: function()
		{
			return this._dragButton;
		},
		createHideButton: function()
		{
			var enabled = !this.isRequired() && !this.isRequiredConditionally();
			var button = BX.create(
				"div",
				{
					props:
					{
						className: "crm-entity-widget-content-block-hide-btn",
						title: this.getHideButtonHint(enabled)
					}
				}
			);

			if(enabled)
			{
				BX.bind(button, "click", BX.delegate(this.onHideButtonClick, this));
			}
			return button;
		},
		createContextMenuButton: function()
		{
			this._contextMenuButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-context-menu" },
					events: { click: BX.delegate(this.onContextButtonClick, this) }
				}
			);

			return this._contextMenuButton;
		},
		createGhostNode:function()
		{
			return null;
		},
		getHideButtonHint: function(enabled)
		{
			return "";
		},
		isWaitingForInput: function()
		{
			return false;
		}
	};
	if(typeof(BX.Crm.EntityEditorControl.messages) === "undefined")
	{
		BX.Crm.EntityEditorControl.messages = {};
	}
}

if(typeof BX.Crm.EntityEditorField === "undefined")
{
	BX.Crm.EntityEditorField = function()
	{
		BX.Crm.EntityEditorField.superclass.constructor.apply(this);
		this._titleWrapper = null;

		this._singleEditButton = null;
		this._singleEditButtonHandler = BX.delegate(this.onSingleEditBtnClick, this);
		this._singleEditController = null;
		this._singleEditTimeoutHandle = 0;

		this._viewController = null;

		this._validators = null;
		this._hasError = false;
		this._errorContainer = null;

		this._layoutAttributes = null;
		this._spotlight = null;

		this._dragObjectType = BX.Crm.EditorDragObjectType.field;
	};
	BX.extend(BX.Crm.EntityEditorField, BX.Crm.EntityEditorControl);
	BX.Crm.EntityEditorField.prototype.isNewEntity = function()
	{
		return this._editor && this._editor.isNew();
	};
	BX.Crm.EntityEditorField.prototype.configure = function()
	{
		if(this._parent)
		{
			this._parent.editChildConfiguration(this);
		}
	};
	BX.Crm.EntityEditorField.prototype.hasAttributeConfiguration = function(attributeTypeId)
	{
		return this._schemeElement.hasAttributeConfiguration(attributeTypeId);
	};
	BX.Crm.EntityEditorField.prototype.getAttributeConfiguration = function(attributeTypeId)
	{
		return this._schemeElement.getAttributeConfiguration(attributeTypeId);
	};
	BX.Crm.EntityEditorField.prototype.setAttributeConfiguration = function(configuration)
	{
		return this._schemeElement.setAttributeConfiguration(configuration);
	};
	BX.Crm.EntityEditorField.prototype.removeAttributeConfiguration = function(attributeTypeId)
	{
		return this._schemeElement.removeAttributeConfiguration(attributeTypeId);
	};
	BX.Crm.EntityEditorField.prototype.getDuplicateControlConfig = function()
	{
		return this._schemeElement ? this._schemeElement.getDataObjectParam("duplicateControl", null) : null;
	};
	BX.Crm.EntityEditorField.prototype.markAsChanged = function(params)
	{
		BX.Crm.EntityEditorField.superclass.markAsChanged.apply(this, arguments);
		if(this.hasError())
		{
			this.clearError();
		}

		var validators = this.getValidators();
		for(var i = 0, length = validators.length; i < length; i++)
		{
			validators[i].processFieldChange(this);
		}
	};
	BX.Crm.EntityEditorField.prototype.bindModel = function()
	{
		this._model.addChangeListener(BX.delegate(this.onModelChange, this));
		this._model.addLockListener(BX.delegate(this.onModelLock, this));
	};
	BX.Crm.EntityEditorField.prototype.onBeforeModeChange = function()
	{
		//Enable animation if it is going to view mode
		this._layoutAttributes = null;
		if(this.isInEditMode())
		{
			this._layoutAttributes = { animate: "show" };
		}
	};
	BX.Crm.EntityEditorField.prototype.onModelChange = function(sender, params)
	{
		this.processModelChange(params);
	};
	BX.Crm.EntityEditorField.prototype.onModelLock = function(sender, params)
	{
		this.processModelLock(params);
	};
	BX.Crm.EntityEditorField.prototype.processModelChange = function(params)
	{
	};
	BX.Crm.EntityEditorField.prototype.processModelLock = function(params)
	{
	};
	BX.Crm.EntityEditorField.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorField.messages;
		return (m.hasOwnProperty(name)
			? m[name]
			: BX.Crm.EntityEditorField.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorField.prototype.hasContentWrapper = function()
	{
		return this.getContentWrapper() !== null;
	};
	BX.Crm.EntityEditorField.prototype.getContentWrapper = function()
	{
		return null;
	};
	BX.Crm.EntityEditorField.prototype.getHideButtonHint = function(enabled)
	{
		return this.getMessage(
			enabled ? "hideButtonHint" : "hideButtonDisabledHint"
		);
	};
	BX.Crm.EntityEditorField.prototype.getEditButton = function()
	{
		return this._singleEditButton;
	};
	BX.Crm.EntityEditorField.prototype.ensureWrapperCreated = function(params)
	{
		if(!this._wrapper)
		{
			this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block" } });
		}

		this.createAdditionalWrapperBlock();

		var classNames = BX.prop.getArray(params, "classNames", []);
		for(var i = 0, length = classNames.length;  i < length; i++)
		{
			BX.addClass(this._wrapper, classNames[i]);
		}
		return this._wrapper;
	};
	BX.Crm.EntityEditorField.prototype.createAdditionalWrapperBlock = function()
	{
		if(!this._wrapper)
		{
			return;
		}

		var additionalBlock = BX.create("div", {
			props: { className: "crm-entity-widget-before-action" },
			attrs: { "data-field-tag": this.getId() }
		});

		this._wrapper.appendChild(additionalBlock);

	};
	BX.Crm.EntityEditorField.prototype.adjustWrapper = function()
	{
		if(!this._wrapper)
		{
			return;
		}

		if(this.isInEditMode()
			&& (this.checkModeOption(BX.Crm.EntityEditorModeOptions.exclusive)
				|| this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual)
			)
		)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-edit");
		}
		else
		{
			BX.removeClass(this._wrapper, "crm-entity-widget-content-block-edit");
		}

		//region Applying layout attributes
		/*
		for(var i = this._wrapper.attributes.length - 1; i >= 0; i--)
		{
			this._wrapper.removeAttribute(this._wrapper.attributes[i].name);
		}
		*/
		if(this._layoutAttributes)
		{
			for(var key in this._layoutAttributes)
			{
				if(this._layoutAttributes.hasOwnProperty(key))
				{
					this._wrapper.setAttribute("data-" + key, this._layoutAttributes[key]);
				}
			}
			this._layoutAttributes = null;
		}
		//endregion
	};
	BX.Crm.EntityEditorField.prototype.createTitleNode = function(title)
	{
		this._titleWrapper = BX.create(
			"div",
			{
				attrs: { className: "crm-entity-widget-content-block-title" }
			}
		);

		this.prepareTitleLayout(BX.type.isNotEmptyString(title) ? title : this.getTitle());
		return this._titleWrapper;
	};
	BX.Crm.EntityEditorField.prototype.prepareTitleLayout = function(title)
	{
		if(!this._titleWrapper)
		{
			return;
		}

		var titleNode = BX.create("span",
			{ attrs: { className: "crm-entity-widget-content-block-title-text" }, text: title }
		);

		var marker = this.createTitleMarker();
		if(marker)
		{
			titleNode.appendChild(marker);
		}
		this._titleWrapper.appendChild(titleNode);

		var actionControls = this.createTitleActionControls();
		if(actionControls.length > 0)
		{
			var actionWrapper = BX.create("span", { attrs: { className: "crm-entity-widget-content-block-title-actions" } });
			this._titleWrapper.appendChild(actionWrapper);

			for(var i = 0, length = actionControls.length; i < length; i++)
			{
				actionWrapper.appendChild(actionControls[i]);
			}
		}

		/*
		var editButton = this.createEditButton();
		if(editButton)
		{
			this._titleWrapper.appendChild(editButton);
		}
		*/
	};
	BX.Crm.EntityEditorField.prototype.refreshTitleLayout = function()
	{
		if(!this._titleWrapper)
		{
			return;
		}

		BX.cleanNode(this._titleWrapper);
		this.prepareTitleLayout(this.getTitle());
	};
	BX.Crm.EntityEditorField.prototype.createTitleMarker = function()
	{
		if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			return null;
		}

		if(this.isRequired())
		{
			return BX.create("span", { style: { color: "#f00", verticalAlign: "super" }, text: "*" });
		}
		else if(this.isRequiredConditionally())
		{
			return BX.create("span", { text: "*" });
		}
		return null;
	};
	BX.Crm.EntityEditorField.prototype.createEditButton = function()
	{
		if(!(this.isInViewMode() && this.isSingleEditEnabled()))
		{
			return null;
		}

		if(!this._singleEditButton)
		{
			this._singleEditButton = BX.create(
				"span",
				{
					props: { className: "crm-entity-card-widget-title-edit-icon" }
				}
			);
		}
		return this._singleEditButton;
	};
	BX.Crm.EntityEditorField.prototype.createTitleActionControls = function()
	{
		return [];
	};
	BX.Crm.EntityEditorField.prototype.createDragButton = function()
	{
		if(!this._dragButton)
		{
			this._dragButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-draggable-btn-container" },
					children:
						[
							BX.create(
								"div",
								{
									props: { className: "crm-entity-widget-content-block-draggable-btn" }
								}
							)
						]
				}
			);
		}
		return this._dragButton;
	};
	BX.Crm.EntityEditorField.prototype.createGhostNode = function()
	{
		if(!this._wrapper)
		{
			return null;
		}

		var pos = BX.pos(this._wrapper);
		var node = this._wrapper.cloneNode(true);
		BX.addClass(node, "crm-entity-widget-content-block-drag");
		node.style.width = pos.width + "px";
		node.style.height = pos.height + "px";
		return node;
	};
	BX.Crm.EntityEditorField.prototype.clearLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		this.releaseLightingAbilities();

		BX.Crm.EntityEditorField.superclass.clearLayout.apply(this, arguments);

		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		this.releaseDragDropAbilities();
		this.releaseSwitchingAbilities();

		if(!BX.prop.getBoolean(options, "preservePosision", false))
		{
			this._wrapper = BX.remove(this._wrapper);
		}
		else
		{
			BX.removeClass(this._wrapper, "crm-entity-widget-content-block-click-editable");
			BX.removeClass(this._wrapper, "crm-entity-widget-content-block-click-empty");
			this._wrapper = BX.cleanNode(this._wrapper);
			if(this.hasError())
			{
				this.clearError();
			}
		}

		if(this._singleEditButton)
		{
			this._singleEditButton = null;
		}

		this.doClearLayout(options);

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorField.prototype.doClearLayout = function(options)
	{
	};
	BX.Crm.EntityEditorField.prototype.registerLayout = function(options)
	{
		var isVisible = this.isVisible();
		var isNeedToDisplay = this.isNeedToDisplay();

		this._wrapper.style.display = (isVisible && isNeedToDisplay) ? "" : "none";

		this.initializeSwitchingAbilities();
		if(this.isInEditMode() && this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual))
		{
			window.setTimeout(BX.delegate(this.focus, this), 0);
		}
		BX.Crm.EntityEditorField.superclass.registerLayout.apply(this, arguments);

		var lighting = BX.prop.getObject(options, "lighting", null);
		if(lighting)
		{
			window.setTimeout(
				function(){ this.initializeLightingAbilities(lighting); }.bind(this),
				1000
			)
		}

		if(!isNeedToDisplay && BX.prop.getBoolean(options, "notifyIfNotDisplayed", false))
		{
			BX.UI.Notification.Center.notify(
				{
					content: this.getMessage("hiddenInViewMode").replace(/#TITLE#/gi, this.getTitle()),
					position: "top-center",
					autoHideDelay: 5000
				}
			);
		}
	};
	BX.Crm.EntityEditorField.prototype.raiseLayoutEvent = function()
	{
		BX.onCustomEvent(window, "BX.Crm.EntityEditorField:onLayout", [ this ]);
	};
	BX.Crm.EntityEditorField.prototype.hasContentToDisplay = function()
	{
		return this.hasValue();
	};
	BX.Crm.EntityEditorField.prototype.isNeedToDisplay = function(options)
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit
			|| this.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways)
		)
		{
			return true;
		}

		if(this._editor && BX.prop.getBoolean(options, "enableLayoutResolvers", true))
		{
			return BX.prop.getBoolean(
				this._editor.prepareFieldLayoutOptions(this),
				"isNeedToDisplay",
				true
			);
		}

		return this.hasContentToDisplay();
	};
	BX.Crm.EntityEditorField.prototype.isWaitingForInput = function()
	{
		return this.isInEditMode() && this.isRequired() && !this.hasValue();
	};
	BX.Crm.EntityEditorField.prototype.hide = function()
	{
		if(!(this.isRequired() || this.isRequiredConditionally()))
		{
			BX.Crm.EntityEditorField.superclass.hide.apply(this, arguments);
		}
		else
		{
			this.showMessageDialog(
				"operationDenied",
				this.getMessage("hideDeniedDlgTitle"),
				this.getMessage("hideDeniedDlgContent")
			);
		}
	};
	//region Value
	BX.Crm.EntityEditorField.prototype.getEditPriority = function()
	{
		var hasValue = this.hasValue();
		if(!hasValue && (this.isRequired() || this.isRequiredConditionally()))
		{
			return BX.Crm.EntityEditorPriority.high;
		}

		if(!this._editor.isNew())
		{
			return BX.Crm.EntityEditorPriority.normal;
		}

		return hasValue ? BX.Crm.EntityEditorPriority.high : this.doGetEditPriority();
	};
	BX.Crm.EntityEditorField.prototype.doGetEditPriority = function()
	{
		return BX.Crm.EntityEditorPriority.normal;
	};
	BX.Crm.EntityEditorField.prototype.checkIfNotEmpty = function(value)
	{
		if(BX.type.isString(value))
		{
			return value.trim() !== "";
		}
		return (value !== null && value !== undefined);
	};
	BX.Crm.EntityEditorField.prototype.setupFromModel = function(model, options)
	{
		if(!model)
		{
			model = this._model;
		}

		if(!model)
		{
			return;
		}

		var data = this.getRelatedModelData(model);
		this._model.updateData(data, options);
	};
	BX.Crm.EntityEditorField.prototype.getRelatedModelData = function(model)
	{
		if(!model)
		{
			model = this._model;
		}

		if(!model)
		{
			return {};
		}

		var data = {};
		var keys = this.getRelatedDataKeys();
		for(var i = 0, length = keys.length; i < length; i++)
		{
			var key = keys[i];
			if(key !== "")
			{
				data[key] = model.getField(key, null);
			}
		}
		return data;
	};
	BX.Crm.EntityEditorField.prototype.getRelatedDataKeys = function()
	{
		return [this.getDataKey()];
	};
	BX.Crm.EntityEditorField.prototype.hasValue = function()
	{
		return this.checkIfNotEmpty(this.getValue());
	};
	BX.Crm.EntityEditorField.prototype.getValue = function(defaultValue)
	{
		if(!this._model)
		{
			return "";
		}

		return(
			this._model.getField(
				this.getDataKey(),
				(defaultValue !== undefined ? defaultValue : "")
			)
		);
	};
	BX.Crm.EntityEditorField.prototype.getStringValue = function(defaultValue)
	{
		return this._model ? this._model.getStringField(this.getName(), defaultValue) : "";
	};
	BX.Crm.EntityEditorField.prototype.getRuntimeValue = function()
	{
		return "";
	};
	BX.Crm.EntityEditorField.prototype.getDataKey = function()
	{
		return this.getName();
	};
	BX.Crm.EntityEditorField.prototype.prepareSaveData = function(data)
	{
		data[this.getDataKey()] = this.getValue();
	};
	//endregion
	//region Validators
	BX.Crm.EntityEditorField.prototype.findValidatorIndex = function(validator)
	{
		if(!this._validators)
		{
			return -1;
		}

		for(var i = 0, length = this._validators.length; i < length; i++)
		{
			if(this._validators[i] === validator)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorField.prototype.addValidator = function(validator)
	{
		if(validator && this.findValidatorIndex(validator) < 0)
		{
			if(!this._validators)
			{
				this._validators = [];
			}
			this._validators.push(validator);
		}
	};
	BX.Crm.EntityEditorField.prototype.removeValidator = function(validator)
	{
		if(!this._validators || !validator)
		{
			return;
		}

		var index = this.findValidatorIndex(validator);
		if(index >= 0)
		{
			this._validators.splice(index, 1);
		}
	};
	BX.Crm.EntityEditorField.prototype.getValidators = function()
	{
		return this._validators ? this._validators : [];
	};
	BX.Crm.EntityEditorField.prototype.hasValidators = function()
	{
		return this._validators && this._validators.length > 0;
	};
	BX.Crm.EntityEditorField.prototype.executeValidators = function(result)
	{
		if(!this._validators)
		{
			return true;
		}

		var isValid = true;
		for(var i = 0, length = this._validators.length; i < length; i++)
		{
			if(!this._validators[i].validate(result))
			{
				isValid = false;
			}
		}
		return isValid;
	};
	//endregion
	BX.Crm.EntityEditorField.prototype.hasError =  function()
	{
		return this._hasError;
	};
	BX.Crm.EntityEditorField.prototype.showError =  function(error, anchor)
	{
		if(!this._errorContainer)
		{
			this._errorContainer = BX.create(
				"div",
				{ attrs: { className: "crm-entity-widget-content-error-text" } }
			);
		}

		this._errorContainer.innerHTML = error;
		this._wrapper.appendChild(this._errorContainer);
		BX.addClass(this._wrapper, "crm-entity-widget-content-error");
		this._hasError = true;
	};
	BX.Crm.EntityEditorField.prototype.showRequiredFieldError =  function(anchor)
	{
		this.showError(this.getMessage("requiredFieldError"), anchor);
	};
	BX.Crm.EntityEditorField.prototype.clearError =  function()
	{
		if(!this._hasError)
		{
			return;
		}

		if(this._errorContainer && this._errorContainer.parentNode)
		{
			this._errorContainer.parentNode.removeChild(this._errorContainer);
		}
		BX.removeClass(this._wrapper, "crm-entity-widget-content-error");
		this._hasError = false;
	};
	BX.Crm.EntityEditorField.prototype.scrollAnimate = function()
	{
		var doc = BX.GetDocElement(document);
		var anchor = this._wrapper;
		window.setTimeout(
			function()
			{
				(new BX.easing(
						{
							duration : 300,
							start : { position: doc.scrollTop },
							finish: { position: BX.pos(anchor).top - 10 },
							step: function(state)
							{
								doc.scrollTop = state.position;
							}
						}
					)
				).animate();
			},
			0
		);
	};
	BX.Crm.EntityEditorField.prototype.setDragObjectType = function(type)
	{
		this._dragObjectType = type;
	};
	BX.Crm.EntityEditorField.prototype.getDragObjectType = function()
	{
		return this._dragObjectType;
	};
	BX.Crm.EntityEditorField.prototype.initializeDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			return;
		}

		this._dragItem = BX.Crm.EditorDragItemController.create(
			"field_" +  this.getId(),
			{
				charge: BX.Crm.EditorFieldDragItem.create(
					{
						control: this,
						contextId: this._draggableContextId,
						scope: this.getDragScope()
					}
				),
				node: this.createDragButton(),
				showControlInDragMode: false,
				ghostOffset: { x: 0, y: 0 }
			}
		);
	};
	BX.Crm.EntityEditorField.prototype.releaseDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			this._dragItem.release();
			this._dragItem = null;
		}
	};
	BX.Crm.EntityEditorField.prototype.initializeSwitchingAbilities = function()
	{
		if(this.isInViewMode())
		{
			if(this.isSingleEditEnabled())
			{
				BX.addClass(this._wrapper, "crm-entity-widget-content-block-click-editable");
				if(!this.hasContentToDisplay())
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-click-empty");
				}

				if(this._singleEditButton)
				{
					BX.bind(this._singleEditButton, "click", this._singleEditButtonHandler);
				}
			}

			if(this.hasContentWrapper()
				&& BX.Crm.EntityEditorModeSwitchType.check(
					this.getModeSwitchType(BX.Crm.EntityEditorMode.edit),
					BX.Crm.EntityEditorModeSwitchType.content
				)
			)
			{
				this._viewController = BX.Crm.EditorFieldViewController.create(
					this._id,
					{ field: this, wrapper: this.getContentWrapper() }
				);
			}
		}
		else if(this.checkModeOption(BX.Crm.EntityEditorModeOptions.exclusive))
		{
			this._singleEditController = BX.Crm.EditorFieldSingleEditController.create(
				this._id,
				{ field: this }
			);
		}
	};
	BX.Crm.EntityEditorField.prototype.releaseSwitchingAbilities = function()
	{
		if(this._singleEditButton)
		{
			BX.unbind(this._singleEditButton, "click", this._singleEditButtonHandler);
		}

		if(this._viewController)
		{
			this._viewController.release();
			this._viewController = null;
		}

		if(this._singleEditController)
		{
			this._singleEditController.release();
			this._singleEditController = null;
		}
	};
	BX.Crm.EntityEditorField.prototype.initializeLightingAbilities = function(params)
	{
		var text = BX.prop.getString(params, "text", "");
		if(!BX.type.isNotEmptyString(text))
		{
			return;
		}

		var wrapper = this.getContentWrapper();
		if(!wrapper)
		{
			return;
		}

		this._spotlight = new BX.SpotLight(
			{
				id: BX.prop.getString(params, "id", ""),
				targetElement: wrapper,
				autoSave: true,
				content: text,
				targetVertex: "middle-left",
				zIndex: 200
			}
		);
		this._spotlight.show();

		var events = BX.prop.getObject(params, "events", {});
		for(var key in events)
		{
			if(events.hasOwnProperty(key))
			{
				BX.addCustomEvent(this._spotlight, key, events[key]);
			}
		}
	};
	BX.Crm.EntityEditorField.prototype.releaseLightingAbilities = function()
	{
		if(this._spotlight)
		{
			this._spotlight.close();
			this._spotlight = null;
		}
	};
	BX.Crm.EntityEditorField.prototype.prepareContextMenuItems = function()
	{
		var results = [];
		results.push({ value: "hide", text: this.getMessage("hide") });
		results.push({ value: "configure", text: this.getMessage("configure") });

		if (this._parent && this._parent.hasAdditionalMenu())
		{
			var additionalMenu = this._parent.getAdditionalMenu();
			for (var i=0; i<additionalMenu.length; i++)
			{
				results.push(additionalMenu[i]);
			}
		}

		results.push(
			{
				value: "showAlways",
				text: '<label class="crm-context-menu-item-hide-empty-wrap">' +
				'<input type="checkbox"' +
				(this.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways) ? ' checked = "true"' : '') +
				' class="crm-context-menu-item-hide-empty-input">' +
				'<span class="crm-context-menu-item-hide-empty-text">' +
				this.getMessage("showAlways") +
				'</span></label>'
			}
		);

		this.doPrepareContextMenuItems(results);
		return results;
	};
	BX.Crm.EntityEditorField.prototype.doPrepareContextMenuItems = function(menuItems)
	{
	};
	BX.Crm.EntityEditorField.prototype.processContextMenuCommand = function(e, command)
	{
		if(command === "showAlways")
		{
			var target = BX.getEventTarget(e);
			if(target && target.tagName === "INPUT")
			{
				this.toggleOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
				if(this._parent)
				{
					this._parent.processChildControlSchemeChange(this);
				}

				if(!this.isNeedToDisplay())
				{
					window.setTimeout(BX.delegate(this.clearLayout, this), 500);
					BX.UI.Notification.Center.notify(
						{
							content: this.getMessage("isHiddenDueToShowAlwaysChanged").replace(/#TITLE#/gi, this.getTitle()),
							position: "top-center",
							autoHideDelay: 5000
						}
					);
					this.closeContextMenu();
				}
			}
			return;
		}

		if(command === "hide")
		{
			window.setTimeout(BX.delegate(this.hide, this), 500);
		}
		else if(command === "configure")
		{
			this.configure();
		}
		else if (this._parent && this._parent.hasAdditionalMenu())
		{
			this._parent.processChildAdditionalMenuCommand(this, command);
		}
		this.closeContextMenu();
	};
	BX.Crm.EntityEditorField.prototype.onSingleEditBtnClick = function(e)
	{
		if(!(this.isSingleEditEnabled() && this._editor))
		{
			return;
		}

		if(this._singleEditTimeoutHandle > 0)
		{
			window.clearTimeout(this._singleEditTimeoutHandle);
			this._singleEditTimeoutHandle = 0;
		}

		this._singleEditTimeoutHandle = window.setTimeout(
			BX.delegate(this.switchToSingleEditMode, this),
			250
		);

		BX.eventCancelBubble(e);
	};
	BX.Crm.EntityEditorField.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button;
		}
		return result;
	};
	BX.Crm.EntityEditorField.prototype.switchToSingleEditMode = function(targetNode)
	{
		if(!(this.isSingleEditEnabled() && this._editor))
		{
			return;
		}

		this._singleEditTimeoutHandle = 0;

		if(this._editor)
		{
			this._editor.switchControlMode(
				this,
				BX.Crm.EntityEditorMode.edit,
				BX.Crm.EntityEditorModeOptions.individual
			);
		}
	};
	if(typeof(BX.Crm.EntityEditorField.messages) === "undefined")
	{
		BX.Crm.EntityEditorField.messages = {};
	}
}

if(typeof BX.Crm.EntityEditorSection === "undefined")
{
	BX.Crm.EntityEditorSection = function()
	{
		BX.Crm.EntityEditorSection.superclass.constructor.apply(this);
		this._fields = null;
		this._fieldConfigurator = null;
		this._userFieldConfigurator = null;
		this._mandatoryConfigurator = null;

		this._titleWrapper = null;
		this._titleEditButton = null;
		this._titleEditHandler = BX.delegate(this.onTitleEditButtonClick, this);
		this._titleView = null;
		this._titleInput = null;
		this._titleMode = BX.Crm.EntityEditorMode.intermediate;
		this._titleInputKeyHandler = BX.delegate(this.onTitleInputKeyPress, this);
		this._documentClickHandler = BX.delegate(this.onExternalClick, this);

		this._enableToggling = true;
		this._toggleButton = null;

		this._buttonPanelWrapper = null;

		this._addChildButton = null;
		this._addChildButtonHandler = BX.delegate(this.onAddChildBtnClick, this);

		this._createChildButton = null;
		this._createChildButtonHandler = BX.delegate(this.onCreateUserFieldBtnClick, this);

		this._deleteButton = null;
		this._deleteButtonHandler = BX.delegate(this.onDeleteSectionBtnClick, this);
		this._detetionConfirmDlgId = "section_deletion_confirm";

		this._childSelectMenu = null;
		this._fieldTypeSelectMenu = null;

		this._dragContainerController = null;
		this._dragPlaceHolder = null;
		this._dropHandler = BX.delegate(this.onDrop, this);
		this._titleActions = null;

		this._fieldSelector = null;
		this._stub = null;

		this._detailButton = null;
	};
	BX.extend(BX.Crm.EntityEditorSection, BX.Crm.EntityEditorControl);
	BX.Crm.EntityEditorSection.prototype.doSetActive = function()
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			this._fields[i].setActive(this._isActive);
		}
	};
	//region Initialization
	BX.Crm.EntityEditorSection.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorSection.superclass.initialize.call(this, id, settings);

		this._draggableContextId = BX.Crm.EditorFieldDragItem.contextId;
		if(this.getChildDragScope() === BX.Crm.EditorDragScope.parent)
		{
			this._draggableContextId += "_" + this.getId();
		}

		this.initializeFromModel();
	};
	BX.Crm.EntityEditorSection.prototype.initializeFromModel =  function()
	{
		var i, length;
		if(this._fields)
		{
			for(i = 0, length = this._fields.length; i < length; i++)
			{
				this._fields[i].release();
			}
		}

		this._fields = [];

		var elements = this._schemeElement.getElements();
		for(i = 0, length = elements.length; i < length; i++)
		{
			var element = elements[i];
			var field = this._editor.createControl(
				element.getType(),
				element.getName(),
				{ schemeElement: element, model: this._model, parent: this }
			);

			if(!field)
			{
				continue;
			}

			element.setParent(this._schemeElement);
			field.setMode(this._mode, { notify: false });
			this._fields.push(field);
		}
	};
	//endregion
	//region Layout
	BX.Crm.EntityEditorSection.prototype.createDragButton = function()
	{
		if(!this._dragButton)
		{
			this._dragButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-card-widget-draggable-btn-container" },
					children:
						[
							BX.create(
								"div",
								{
									props: { className: "crm-entity-card-widget-draggable-btn" }
								}
							)
						]
				}
			);
		}
		return this._dragButton;
	};
	BX.Crm.EntityEditorSection.prototype.createGhostNode = function()
	{
		if(!this._wrapper)
		{
			return null;
		}

		var pos = BX.pos(this._wrapper);
		var node =  BX.create("div",
			{
				props: { className: "crm-entity-card-widget-edit" },
				children :
					[
						BX.create("div",
							{
								props: { className: "crm-entity-card-widget-draggable-btn-container" },
								children:
									[
										BX.create(
											"div",
											{
												props: { className: "crm-entity-card-widget-draggable-btn" },
												children:
													[
														BX.create("div",
															{ props: { className: "crm-entity-card-widget-draggable-btn-inner" } }
														)
													]
											}
										)
									]
							}
						),
						BX.create("div",
							{
								props: { className: "crm-entity-card-widget-title" },
								children :
									[
										BX.create("span",
											{
												props: { className: "crm-entity-card-widget-title-text" },
												text: this._schemeElement.getTitle()
											}
										)
									]
							}
						)
					]
			}
		);
		BX.addClass(node, "crm-entity-widget-card-drag");
		node.style.width = pos.width + "px";
		return node;
	};
	BX.Crm.EntityEditorSection.prototype.getEditPriority = function()
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			if(this._fields[i].getEditPriority() === BX.Crm.EntityEditorPriority.high)
			{
				return BX.Crm.EntityEditorPriority.high;
			}
		}
		return BX.Crm.EntityEditorPriority.normal;
	};
	BX.Crm.EntityEditorSection.prototype.layout = function(options)
	{
		var i, length;

		//Create wrapper
		var title = this._schemeElement.getTitle();
		this._contentContainer = BX.create("div", {props: { className: 'crm-entity-widget-content' } });
		var isViewMode = this._mode === BX.Crm.EntityEditorMode.view ;

		var wrapperClassName = isViewMode
			? "crm-entity-card-widget"
			: "crm-entity-card-widget-edit";

		this._enableToggling = this.isModeToggleEnabled() && this._schemeElement.getDataBooleanParam("enableToggling", true);
		this._toggleButton = BX.create("span",
			{
				attrs: { className: "crm-entity-widget-hide-btn" },
				events: { click: BX.delegate(this.onToggleBtnClick, this) },
				text: this.getMessage(isViewMode ? "change" : "cancel")
			}
		);

		var url = BX.prop.getString(this.getEditor()._settings, "entityDetailsUrl", "");
		if (this.getEditor().isEmbedded() && url.length)
		{
			var sections = this.getEditor().getControls().filter(function(control)
			{
				return (control instanceof BX.Crm.EntityEditorSection);
			});

			if (sections.length && sections[0] === this)
			{
				this._detailButton = BX.create("a",
					{
						attrs: {
							className: "crm-entity-widget-detail-btn",
							href: url
						},
						text: this.getMessage("openDetails")
					}
				);
			}
		}

		if(!this._enableToggling)
		{
			this._toggleButton.style.display = "none";
		}

		this._titleMode = BX.Crm.EntityEditorMode.view;

		this._wrapper = BX.create("div", { props: { className: wrapperClassName }});

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._schemeElement.isTitleEnabled())
		{
			this._titleEditButton = BX.create("span",
				{
					props: { className: "crm-entity-card-widget-title-edit-icon" },
					events: { click: this._titleEditHandler }
				}
			);

			if(!this._editor.isSectionEditEnabled() || !this._editor.canChangeScheme())
			{
				this._titleEditButton.style.display = "none";
			}

			this._titleView = BX.create("span",
				{
					props: { className: "crm-entity-card-widget-title-text" },
					text: title
				}
			);
			this._titleInput = BX.create("input",
				{
					props: { className: "crm-entity-card-widget-title-text" },
					style: { display: "none" }
				}
			);
			this._titleActions = BX.create('div',
				{
					props: { className: 'crm-entity-widget-actions-block' },
					children : [ this._toggleButton]
				}
			);
			if (this._detailButton)
			{
				this._titleActions.appendChild(this._detailButton);
			}

			this._titleWrapper = BX.create('div',
				{
					props: { className: 'crm-entity-card-widget-title' },
					children :
						[
							BX.create('div',{
								style: {
									maxWidth: 'calc(100% - 30px)',
									minWidth: 0,
									flex: 1
								},
								children:
								[
									this._titleView,
									this._titleInput,
									this._titleEditButton
								]
							}),
							this._titleActions
						]
				}
			);

			this._wrapper.appendChild(this._titleWrapper);
		}

		this._wrapper.appendChild(this._contentContainer);

		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var anchor = BX.prop.getElementNode(options, "anchor", null);
		if (anchor)
		{
			this._container.insertBefore(this._wrapper, anchor);
		}
		else
		{
			this._container.appendChild(this._wrapper);
		}

		if(isViewMode && this._fields.length === 0)
		{
			this._contentContainer.appendChild(this.createStub());
		}

		var enableReset = BX.prop.getBoolean(options, "reset", false);
		//Layout fields
		var userFieldLoader = BX.prop.get(options, "userFieldLoader", null);
		if(!userFieldLoader)
		{
			userFieldLoader = BX.Crm.EntityUserFieldLayoutLoader.create(
				this._id,
				{ mode: this._mode, enableBatchMode: true, owner: this }
			);
		}

		var lighting = BX.prop.getObject(options, "lighting", null);
		var enableFocusGain = BX.prop.getBoolean(options, "enableFocusGain", true);
		var isLighted = false;
		for(i = 0, length = this._fields.length; i < length; i++)
		{
			var field = this._fields[i];
			field.setContainer(this._contentContainer);
			field.setDraggableContextId(this._draggableContextId);

			//Force layout reset because of animation implementation
			field.releaseLayout();
			if(enableReset)
			{
				field.reset();
			}

			var layoutOptions = { userFieldLoader: userFieldLoader };
			if(!isLighted && lighting && field.isVisible() && field.isNeedToDisplay())
			{
				layoutOptions["lighting"] = lighting;
				isLighted = true;
			}

			field.layout(layoutOptions);
			if(enableFocusGain && !isViewMode && field.isHeading())
			{
				field.focus();
			}
		}

		if(userFieldLoader.getOwner() === this)
		{
			userFieldLoader.runBatch();
		}

		this._addChildButton = this._createChildButton = this._deleteButton = null;
		if(this._editor.canChangeScheme() && this._schemeElement.getDataBooleanParam('showButtonPanel', true))
		{
			this._buttonPanelWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block" } });

			if(this._schemeElement.getDataBooleanParam("isChangeable", true))
			{
				this._addChildButton = BX.create("span",
					{
						props: { className: "crm-entity-widget-content-block-edit-action-btn" },
						text: this.getMessage("selectField"),
						events: { click: this._addChildButtonHandler }
					}
				);
				if(!this._editor.hasAvailableSchemeElements())
				{
					this._addChildButton.style.display = "none";
				}
				this._buttonPanelWrapper.appendChild(this._addChildButton);

				if(this._editor.getUserFieldManager().isCreationEnabled())
				{
					this._createChildButton = BX.create("span",
						{
							props: { className: "crm-entity-widget-content-block-edit-action-btn" },
							text: this.getMessage("createField"),
							events: { click: this._createChildButtonHandler }
						}
					);
					this._buttonPanelWrapper.appendChild(this._createChildButton);
				}
			}

			if(this._schemeElement.getDataBooleanParam("isRemovable", true))
			{
				var deleteClassName = "crm-entity-widget-content-block-edit-remove-btn";
				if (this.isRequired() || this.isRequiredConditionally())
				{
					deleteClassName = "crm-entity-widget-content-block-edit-remove-btn-disabled";
				}

				this._deleteButton = BX.create("span",
					{
						props: { className: deleteClassName },
						text: this.getMessage("deleteSection")
					}
				);
				this._buttonPanelWrapper.appendChild(this._deleteButton);
				BX.bind(this._deleteButton, "click", this._deleteButtonHandler);
			}

			this._contentContainer.appendChild(this._buttonPanelWrapper);
		}

		if(this.isDragEnabled())
		{
			this._dragContainerController = BX.Crm.EditorDragContainerController.create(
				"section_" + this.getId(),
				{
					charge: BX.Crm.EditorFieldDragContainer.create(
						{
							section: this,
							context: this._draggableContextId
						}
					),
					node: this._wrapper
				}
			);
			this._dragContainerController.addDragFinishListener(this._dropHandler);

			this.initializeDragDropAbilities();
		}

		//region Add custom Html
		var serialNumber = this._editor._controls.indexOf(this);
		var eventArgs =  { id: this._id, customNodes: [], serialNumber: serialNumber };
		BX.onCustomEvent(window, "BX.Crm.EntityEditorSection:onLayout", [ this, eventArgs ]);
		if(this._titleActions && BX.type.isArray(eventArgs["customNodes"]))
		{
			for(i = 0, length = eventArgs["customNodes"].length; i < length; i++)
			{
				var node = eventArgs["customNodes"][i];
				if(BX.type.isElementNode(node))
				{
					this._titleActions.appendChild(node);
				}
			}
		}
		//endregion

		this._hasLayout = true;
	};
	BX.Crm.EntityEditorSection.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(this._dragContainerController)
		{
			this._dragContainerController.removeDragFinishListener(this._dropHandler);
			this._dragContainerController.release();
			this._dragContainerController = null;
		}
		this.releaseDragDropAbilities();

		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			var field = this._fields[i];
			field.clearLayout();
			field.setContainer(null);
			field.setDraggableContextId("");
		}

		if(this._addChildButton)
		{
			BX.unbind(this._addChildButton, "click", this._addChildButtonHandler);
			this._addChildButton = BX.remove(this._addChildButton);
		}

		if(this._createChildButton)
		{
			BX.unbind(this._createChildButton, "click", this._createChildButtonHandler);
			this._createChildButton = BX.remove(this._createChildButton);
		}

		if(this._deleteButton)
		{
			BX.unbind(this._deleteButton, "click", this._deleteButtonHandler);
			this._deleteButton = BX.remove(this._deleteButton);
		}

		if(this._buttonPanelWrapper)
		{
			this._buttonPanelWrapper = BX.remove(this._buttonPanelWrapper);
		}

		this._stub = null;
		this._titleWrapper = null;
		this._wrapper = BX.remove(this._wrapper);
		this._hasLayout = false;
	};
	BX.Crm.EntityEditorSection.prototype.refreshLayout = function(options)
	{
		options = BX.type.isPlainObject(options) ? BX.mergeEx({}, options) : {};

		//region CALLBACK
		var callback = BX.prop.getFunction(options, "callback", null);
		delete options["callback"];
		//endregion

		//region ANCHOR
		delete options["anchor"];
		if(this._wrapper && this._wrapper.nextSibling)
		{
			options["anchor"] = this._wrapper.nextSibling;
		}
		//endregion

		//region LAYOUT
		this.clearLayout();
		this.layout(options);
		//endregion

		if(callback)
		{
			callback();
		}
	};
	BX.Crm.EntityEditorSection.prototype.createStub = function()
	{
		this._stub = BX.create(
			"div",
			{
				props: { className: "crm-entity-widget-content-block" },
				children:
					[
						BX.create(
							"div",
							{
								props: { className: "crm-entity-widget-content-nothing-selected" },
								children:
									[
										BX.create(
											"div",
											{
												props: { className: "crm-entity-widget-content-nothing-selected-text" },
												text: this.getMessage("nothingSelected")
											}
										)
									]
							}
						)
					]
			}
		);

		if(this.isModeToggleEnabled())
		{
			BX.bind(this._stub, "click", BX.delegate(this.onStubClick, this));
		}

		return this._stub;
	};
	BX.Crm.EntityEditorSection.prototype.onStubClick = function(e)
	{
		this.toggle();
	};
	BX.Crm.EntityEditorSection.prototype.hasAdditionalMenu = function(e)
	{
		return false;
	};
	BX.Crm.EntityEditorSection.prototype.getAdditionalMenu = function(e)
	{
		return [];
	};
	BX.Crm.EntityEditorSection.prototype.processChildAdditionalMenuCommand = function(child, command)
	{
	};
	BX.Crm.EntityEditorSection.prototype.ensureButtonPanelWrapperCreated = function()
	{
		if(!this._hasLayout)
		{
			throw "EntityEditorSection: Control does not have layout.";
		}

		if(!this._buttonPanelWrapper)
		{
			this._buttonPanelWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block" } });
			this._contentContainer.appendChild(this._buttonPanelWrapper);
		}
		return this._buttonPanelWrapper;
	};
	//endregion
	//region Title Edit
	BX.Crm.EntityEditorSection.prototype.setTitleMode = function(mode)
	{
		if(this._titleMode === mode)
		{
			return;
		}

		this._titleMode = mode;

		if(!this._schemeElement.isTitleEnabled())
		{
			return;
		}

		if(this._titleMode === BX.Crm.EntityEditorMode.view)
		{
			this._titleView.style.display = "";
			this._titleInput.style.display = "none";
			this._titleEditButton.style.display = "";

			var title = this._titleInput.value;
			this._titleView.innerHTML = BX.util.htmlspecialchars(title);

			this._schemeElement.setTitle(title);
			this.markSchemeAsChanged();
			this.saveScheme();

			BX.unbind(this._titleInput, "keyup", this._titleInputKeyHandler);
			BX.unbind(window.document, "click", this._documentClickHandler);
		}
		else
		{
			this._titleView.style.display = "none";
			this._titleInput.style.display = "";
			this._titleEditButton.style.display = "none";

			this._titleInput.value = this._schemeElement.getTitle();

			BX.bind(this._titleInput, "keyup", this._titleInputKeyHandler);
			this._titleInput.focus();

			window.setTimeout(
				BX.delegate(function() { BX.bind(window.document, "click", this._documentClickHandler); }, this),
				100
			);
		}
	};
	BX.Crm.EntityEditorSection.prototype.toggleTitleMode = function()
	{
		this.setTitleMode(
			this._titleMode === BX.Crm.EntityEditorMode.view
				? BX.Crm.EntityEditorMode.edit
				: BX.Crm.EntityEditorMode.view
		);
	};
	BX.Crm.EntityEditorSection.prototype.onTitleEditButtonClick = function(e)
	{
		if(this._editor.isSectionEditEnabled())
		{
			this.toggleTitleMode();
		}
	};
	BX.Crm.EntityEditorSection.prototype.onTitleInputKeyPress = function(e)
	{
			if(!e)
			{
				e = window.event;
			}

			if(e.keyCode === 13)
			{
				this.toggleTitleMode();
			}
	};
	BX.Crm.EntityEditorSection.prototype.onExternalClick = function(e)
	{
		if(!e)
		{
			e = window.event;
		}

		if(this._titleInput !== BX.getEventTarget(e))
		{
			this.toggleTitleMode();
		}
	};
	//endregion
	//region Toggling & Mode control
	BX.Crm.EntityEditorSection.prototype.enableToggling = function(enable)
	{
		enable = !!enable;
		if(this._enableToggling === enable)
		{
			return;
		}

		this._enableToggling = enable;
		if(this._hasLayout)
		{
			this._toggleButton.style.display = this._enableToggling ? "" : "none";
		}
	};
	BX.Crm.EntityEditorSection.prototype.toggle = function()
	{
		if(this._enableToggling && this._editor)
		{
			var isViewMode = (this._mode === BX.Crm.EntityEditorMode.view);
			if (isViewMode)
			{
				this.releaseActiveControls();
			}
			this._editor.switchControlMode(
				this,
				isViewMode ? BX.Crm.EntityEditorMode.edit : BX.Crm.EntityEditorMode.view
			);
		}
	};
	BX.Crm.EntityEditorSection.prototype.releaseActiveControls = function()
	{
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			var control = this._fields[i];
			this._editor.unregisterActiveControl(control);
		}
	};
	BX.Crm.EntityEditorSection.prototype.onToggleBtnClick = function(e)
	{
		this.toggle();
	};
	BX.Crm.EntityEditorSection.prototype.onBeforeModeChange = function()
	{
		this.removeFieldConfigurator();
		this.removeUserFieldConfigurator();
	};
	BX.Crm.EntityEditorSection.prototype.doSetMode = function(mode)
	{
		if(this._titleMode === BX.Crm.EntityEditorMode.edit)
		{
			this.toggleTitleMode();
		}
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			this._fields[i].setMode(mode, { notify: false });
		}
	};
	//endregion
	//region Tracking of Changes, Validation, Saving and Rolling back
	BX.Crm.EntityEditorSection.prototype.processAvailableSchemeElementsChange = function()
	{
		if(this._hasLayout && BX.type.isDomNode(this._addChildButton))
		{
			this._addChildButton.style.display = this._editor.hasAvailableSchemeElements() ? "" : "none";
		}
	};
	BX.Crm.EntityEditorSection.prototype.validate = function(result)
	{
		if(this._mode !== BX.Crm.EntityEditorMode.edit)
		{
			return true;
		}

		var currentResult = BX.Crm.EntityValidationResult.create();
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			var field = this._fields[i];
			if(field.getMode() !== BX.Crm.EntityEditorMode.edit)
			{
				continue;
			}

			field.validate(currentResult);
		}

		result.addResult(currentResult);
		return currentResult.getStatus();
	};
	BX.Crm.EntityEditorSection.prototype.commitSchemeChanges = function()
	{
		if(this._isSchemeChanged)
		{
			var schemeElements = [];
			for(var i = 0, length = this._fields.length; i < length; i++)
			{
				var schemeElement = this._fields[i].getSchemeElement();
				if(schemeElement)
				{
					schemeElements.push(schemeElement);
				}
			}
			this._schemeElement.setElements(schemeElements);
		}
		return BX.Crm.EntityEditorSection.superclass.commitSchemeChanges.call(this);
	};
	BX.Crm.EntityEditorSection.prototype.save = function()
	{
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			this._fields[i].save();
		}
	};
	BX.Crm.EntityEditorSection.prototype.rollback = function()
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			this._fields[i].rollback();
		}

		if(this._isChanged)
		{
			this.initializeFromModel();
			this._isChanged = false;
		}
	};
	BX.Crm.EntityEditorSection.prototype.onBeforeSubmit = function()
	{
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			this._fields[i].onBeforeSubmit();
		}
	};
	//endregion
	//region Children & User Fields
	BX.Crm.EntityEditorSection.prototype.getChildIndex = function(child)
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			if(this._fields[i] === child)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorSection.prototype.addChild = function(child, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var related = null;
		var index = BX.prop.getInteger(options, "index", -1);
		if(index >= 0)
		{
			this._fields.splice(index, 0, child);
			if(index < (this._fields.length - 1))
			{
				related = this._fields[index + 1];
			}
		}
		else
		{
			this._fields.push(child);
			related = BX.prop.get(options, "related", null);
		}

		if(child.getParent() !== this)
		{
			child.setParent(this);
		}

		if(child.hasScheme())
		{
			child.getSchemeElement().setParent(this._schemeElement);
		}

		child.setActive(this._isActive);

		if(this._hasLayout)
		{
			child.setContainer(this._contentContainer);
			child.setDraggableContextId(this._draggableContextId);

			var layoutOpts = BX.prop.getObject(options, "layout", {});

			if(related)
			{
				layoutOpts["anchor"] = related.getWrapper();
			}
			else
			{
				layoutOpts["anchor"] = this._buttonPanelWrapper;
			}

			if(BX.prop.getBoolean(layoutOpts, "forceDisplay", false) &&
				!child.isNeedToDisplay() &&
				!child.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways)
			)
			{
				//Ensure that field will be displayed.
				child.toggleOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
			}

			child.layout(layoutOpts);
		}

		if(child.hasScheme())
		{
			this._editor.processControlAdd(child);
			this.markSchemeAsChanged();

			if(BX.prop.getBoolean(options, "enableSaving", true))
			{
				this.saveScheme();
			}
		}
	};
	BX.Crm.EntityEditorSection.prototype.removeChild = function(child, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var index = this.getChildIndex(child);
		if(index < 0)
		{
			return;
		}

		if(child.isActive())
		{
			child.setActive(false);
		}

		this._fields.splice(index, 1);

		var processScheme = child.hasScheme();

		if(processScheme)
		{
			child.getSchemeElement().setParent(null);
		}

		if(this._hasLayout)
		{
			child.clearLayout();
			child.setContainer(null);
			child.setDraggableContextId("");
		}

		if(processScheme)
		{
			this._editor.processControlRemove(child);
			this.markSchemeAsChanged();

			if(BX.prop.getBoolean(options, "enableSaving", true))
			{
				this.saveScheme();
			}
		}
	};
	BX.Crm.EntityEditorSection.prototype.moveChild = function(child, index, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var qty = this.getChildCount();
		var lastIndex = qty - 1;
		if(index < 0  || index > qty)
		{
			index = lastIndex;
		}

		var currentIndex = this.getChildIndex(child);
		if(currentIndex < 0 || currentIndex === index)
		{
			return false;
		}

		if(this._hasLayout)
		{
			child.clearLayout();
		}
		this._fields.splice(currentIndex, 1);

		qty--;

		var anchor = null;
		if(this._hasLayout)
		{
			anchor = index < qty
				? this._fields[index].getWrapper()
				: this._buttonPanelWrapper;
		}

		if(index < qty)
		{
			this._fields.splice(index, 0, child);
		}
		else
		{
			this._fields.push(child);
		}

		if(this._hasLayout)
		{
			if(anchor)
			{
				child.layout({ anchor: anchor });
			}
			else
			{
				child.layout();
			}
		}

		this._editor.processControlMove(child);
		this.markSchemeAsChanged();

		if(BX.prop.getBoolean(options, "enableSaving", true))
		{
			this.saveScheme();
		}

		return true;
	};
	BX.Crm.EntityEditorSection.prototype.editChild = function(child)
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			child.focus();
		}
		else if(!this.isReadOnly())
		{
			var isHomogeneous = true;
			for(var i = 0, length = this._fields.length; i < length; i++)
			{
				if(this._fields[i].getMode() !== this._mode)
				{
					isHomogeneous = false;
					break;
				}
			}

			if(isHomogeneous)
			{
				this.setMode(BX.Crm.EntityEditorMode.edit, { notify: true });
				this.refreshLayout(
					{
						callback: function(){ child.focus(); }
					}
				);
			}
		}
	};
	BX.Crm.EntityEditorSection.prototype.getChildById = function(childId)
	{
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			var field = this._fields[i];
			if(field.getId() === childId)
			{
				return field;
			}
		}
		return null;
	};
	BX.Crm.EntityEditorSection.prototype.getChildCount = function()
	{
		return this._fields.length;
	};
	BX.Crm.EntityEditorSection.prototype.getChildren = function()
	{
		return this._fields;
	};
	BX.Crm.EntityEditorSection.prototype.processChildControlModeChange = function(child)
	{
		if(!this.isActive() && this._editor)
		{
			this._editor.processControlModeChange(child);
		}
	};
	BX.Crm.EntityEditorSection.prototype.processChildControlChange = function(child, params)
	{
		if(!child.isInEditMode())
		{
			return;
		}

		if(typeof(params) === "undefined")
		{
			params = {};
		}

		if(!BX.prop.get(params, "control", null))
		{
			params["control"] = child;
		}

		this.markAsChanged(params);
		this.enableToggling(false);
	};
	BX.Crm.EntityEditorSection.prototype.processChildControlSchemeChange = function(child)
	{
		this.markSchemeAsChanged();
		this.saveScheme();
	};
	BX.Crm.EntityEditorSection.prototype.openAddChildMenu = function()
	{
		var schemeElements = this._editor.getAvailableSchemeElements();
		var length = schemeElements.length;
		if(length === 0)
		{
			return;
		}

		var menuItems = [];
		for(var i = 0; i < length; i++)
		{
			var schemeElement = schemeElements[i];
			menuItems.push({ text: schemeElement.getTitle(), value: schemeElement.getName() });
		}

		menuItems.push({ delimiter: true });
		menuItems.push({ text: this.getMessage("selectFieldFromOtherSection"), value: "ACTION.TRANSFER" });

		var eventArgs =
			{
				id: this._id,
				menuItems: menuItems,
				button: this._addChildButton,
				cancel: false
			};
		BX.onCustomEvent(window, "BX.Crm.EntityEditorSection:onOpenChildMenu", [ this, eventArgs ]);

		if(eventArgs["cancel"])
		{
			return;
		}

		if(this._childSelectMenu)
		{
			this._childSelectMenu.setupItems(menuItems);
		}
		else
		{
			this._childSelectMenu = BX.CmrSelectorMenu.create(this._id, { items: menuItems });
			this._childSelectMenu.addOnSelectListener(BX.delegate(this.onChildSelect, this));
		}
		this._childSelectMenu.open(this._addChildButton);
	};
	BX.Crm.EntityEditorSection.prototype.onAddChildBtnClick = function(e)
	{
		this.openAddChildMenu();
	};
	BX.Crm.EntityEditorSection.prototype.openTransferDialog = function()
	{
		if(!this._fieldSelector)
		{
			this._fieldSelector = BX.Crm.EntityEditorFieldSelector.create(
				this._id,
				{
					scheme: this._editor.getScheme(),
					excludedNames: [ this.getSchemeElement().getName() ],
					title: this.getMessage("transferDialogTitle")
				}
			);
			this._fieldSelector.addClosingListener(BX.delegate(this.onTransferFieldSelect, this));
		}

		this._fieldSelector.open();
	};
	BX.Crm.EntityEditorSection.prototype.onTransferFieldSelect = function(sender, eventArgs)
	{
		if(BX.prop.getBoolean(eventArgs, "isCanceled"))
		{
			return;
		}

		var items = BX.prop.getArray(eventArgs, "items");
		if(items.length === 0)
		{
			return;
		}

		for(var i = 0, length = items.length; i < length; i++)
		{
			var item = items[i];

			var sectionName = BX.prop.getString(item, "sectionName", "");
			var fieldName = BX.prop.getString(item, "fieldName", "");

			var sourceSection = this._editor.getControlById(sectionName);
			if(!sourceSection)
			{
				continue;
			}

			var sourceField = sourceSection.getChildById(fieldName);
			if(!sourceField)
			{
				continue;
			}

			var schemeElement = sourceField.getSchemeElement();

			sourceSection.removeChild(sourceField, { enableSaving: false });

			var targetField = this._editor.createControl(
				schemeElement.getType(),
				schemeElement.getName(),
				{ schemeElement: schemeElement, model: this._model, parent: this, mode: this._mode }
			);

			//Option "notifyIfNotDisplayed" to enable user notification if field will not be displayed because of settings.
			//Option "forceDisplay" to enable "showAlways" flag if required .
			this.addChild(targetField, { layout: { forceDisplay: true }, enableSaving: false });
		}

		this._editor.saveSchemeChanges();
	};
	BX.Crm.EntityEditorSection.prototype.onChildSelect = function(sender, item)
	{
		var eventArgs =
			{
				id: this._id,
				item: item,
				button: this._addChildButton,
				cancel: false
			};
		BX.onCustomEvent(window, "BX.Crm.EntityEditorSection:onChildMenuItemSelect", [ this, eventArgs ]);

		if(eventArgs["cancel"])
		{
			return;
		}

		var v = item.getValue();
		if(v === "ACTION.TRANSFER")
		{
			this.openTransferDialog();
			return;
		}

		var element = this._editor.getAvailableSchemeElementByName(v);
		if(!element)
		{
			return;
		}

		var field = this._editor.createControl(
			element.getType(),
			element.getName(),
			{ schemeElement: element, model: this._model, parent: this, mode: this._mode }
		);

		if(field)
		{
			//Option "notifyIfNotDisplayed" to enable user notification if field will not be displayed because of settings.
			//Option "forceDisplay" to enable "showAlways" flag if required .
			this.addChild(field, { layout: { forceDisplay: true } });
		}
	};
	BX.Crm.EntityEditorSection.prototype.onCreateUserFieldBtnClick = function(e)
	{
		if(!this._fieldTypeSelectMenu)
		{
			var infos = this._editor.getUserFieldManager().getTypeInfos();
			var items = [];
			for(var i = 0, length = infos.length; i < length; i++)
			{
				var info = infos[i];
				items.push({ value: info.name, text: info.title, legend: info.legend });
			}

			this._fieldTypeSelectMenu = BX.Crm.UserFieldTypeMenu.create(
				this._id,
				{
					items: items,
					callback: BX.delegate(this.onUserFieldTypeSelect, this)
				}
			);
		}
		this._fieldTypeSelectMenu.open(this._createChildButton);
	};
	BX.Crm.EntityEditorSection.prototype.onUserFieldTypeSelect = function(sender, item)
	{
		this._fieldTypeSelectMenu.close();

		var typeId = item.getValue();
		if(typeId === "")
		{
			return;
		}

		if(typeId === "custom")
		{
			window.open(this._editor.getUserFieldManager().getCreationPageUrl());
		}
		else
		{
			this.removeFieldConfigurator();
			this.removeUserFieldConfigurator();
			this.createUserFieldConfigurator({ typeId: typeId });
		}
	};
	BX.Crm.EntityEditorSection.prototype.createUserFieldConfigurator = function(params)
	{
		if(!BX.type.isPlainObject(params))
		{
			throw "EntityEditorSection: The 'params' argument must be object.";
		}

		var typeId = "";
		var field = BX.prop.get(params, "field", null);
		if(field)
		{
			if(!(field instanceof BX.Crm.EntityEditorUserField))
			{
				throw "EntityEditorSection: The 'field' param must be EntityEditorUserField.";
			}

			typeId = field.getFieldType();
			field.setVisible(false);
		}
		else
		{
			typeId = BX.prop.get(params, "typeId", BX.Crm.EntityUserFieldType.string);
		}

		if (typeId === 'resourcebooking')
		{
			var options = {
				editor: this._editor,
				schemeElement: null,
				model: this._model,
				mode: BX.Crm.EntityEditorMode.edit,
				parent: this,
				typeId: typeId,
				field: field,
				showAlways: true
			};

			if (BX.Calendar && BX.type.isFunction(BX.Calendar.ResourcebookingUserfield))
			{
				this._userFieldConfigurator = BX.Calendar.ResourcebookingUserfield.getCrmFieldConfigurator("", options);
			}
			else if (BX.Calendar && BX.Calendar.UserField && BX.Calendar.UserField.EntityEditorUserFieldConfigurator)
			{
				this._userFieldConfigurator = BX.Calendar.UserField.EntityEditorUserFieldConfigurator.create("", options);
			}
		}
		else
		{
			var attrManager = this._editor.getAttributeManager();
			if(attrManager)
			{
				this._mandatoryConfigurator = attrManager.createFieldConfigurator(
					field,
					BX.Crm.EntityFieldAttributeType.required
				);
			}

			this._userFieldConfigurator = BX.Crm.EntityEditorUserFieldConfigurator.create(
				"",
				{
					editor: this._editor,
					schemeElement: null,
					model: this._model,
					mode: BX.Crm.EntityEditorMode.edit,
					parent: this,
					typeId: typeId,
					field: field,
					mandatoryConfigurator: this._mandatoryConfigurator,
					showAlways: true
				}
			);
		}

		this.addChild(this._userFieldConfigurator, { related: field });

		BX.addCustomEvent(this._userFieldConfigurator, "onSave", BX.delegate(this.onUserFieldConfigurationSave, this));
		BX.addCustomEvent(this._userFieldConfigurator, "onCancel", BX.delegate(this.onUserFieldConfigurationCancel, this));
	};
	BX.Crm.EntityEditorSection.prototype.removeUserFieldConfigurator = function()
	{
		if(this._userFieldConfigurator)
		{
			var field = this._userFieldConfigurator.getField();
			if(field)
			{
				field.setVisible(true);
			}
			this.removeChild(this._userFieldConfigurator);
			this._userFieldConfigurator = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.onUserFieldConfigurationSave = function(sender, params)
	{
		if(sender !== this._userFieldConfigurator)
		{
			return;
		}

		this._userFieldConfigurator.setLocked(true);

		var typeId = BX.prop.getString(params, "typeId");
		if(typeId === BX.Crm.EntityUserFieldType.datetime && !BX.prop.getBoolean(params, "enableTime", false))
		{
			typeId = BX.Crm.EntityUserFieldType.date;
		}

		var fieldData = { "USER_TYPE_ID": typeId };

		if(this._mandatoryConfigurator
			&& this._mandatoryConfigurator.isPermitted()
			&& this._mandatoryConfigurator.isEnabled()
			&& this._mandatoryConfigurator.isCustomized()
		)
		{
			if(this._mandatoryConfigurator.isChanged())
			{
				this._mandatoryConfigurator.acceptChanges();
			}

			fieldData["MANDATORY"] = "N";
		}
		else
		{
			fieldData["MANDATORY"] = BX.prop.getBoolean(params, "mandatory", false) ? "Y" : "N";
		}

		var settings = BX.prop.get(params, "settings", null);
		if (settings)
		{
			fieldData["SETTINGS"] = settings;
		}

		var showAlways = BX.prop.getBoolean(params, "showAlways", null);
		var label = BX.prop.getString(params, "label", "");
		var field = BX.prop.get(params, "field", null);

		if(field)
		{
			var previousLabel = field.getTitle();
			if(label !== "" || showAlways !== null)
			{
				field.setTitle(label);
				if(showAlways !== null && showAlways !== field.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways))
				{
					field.toggleOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
				}

				this.markSchemeAsChanged();
				this.saveScheme();
			}

			fieldData["FIELD"] = field.getName();
			fieldData["ENTITY_VALUE_ID"] = field.getEntityValueId();

			if(this._editor.getConfigScope() === BX.Crm.EntityConfigScope.common && previousLabel !== label)
			{
				fieldData["EDIT_FORM_LABEL"] = fieldData["LIST_COLUMN_LABEL"] = fieldData["LIST_FILTER_LABEL"] = label;
			}

			fieldData["VALUE"] = field.getFieldValue();

			if(typeId === BX.Crm.EntityUserFieldType.enumeration)
			{
				fieldData["ENUM"] = BX.prop.getArray(params, "enumeration", []);
			}

			field.adjustFieldParams(fieldData, false);

			this._editor.getUserFieldManager().updateField(
				fieldData,
				field.getMode()
			).then(
				BX.delegate(this.onUserFieldUpdate, this)
			);
		}
		else
		{
			if(showAlways !== null)
			{
				this._editor.setOption("show_always", showAlways ? "Y" : "N");
			}

			fieldData["EDIT_FORM_LABEL"] = fieldData["LIST_COLUMN_LABEL"] = fieldData["LIST_FILTER_LABEL"] = BX.prop.getString(params, "label");
			fieldData["MULTIPLE"] = BX.prop.getBoolean(params, "multiple", false) ? "Y" : "N";

			if(typeId === BX.Crm.EntityUserFieldType.enumeration)
			{
				fieldData["ENUM"] = BX.prop.getArray(params, "enumeration", []);
			}

			this._editor.getUserFieldManager().createField(
				fieldData,
				this._mode
			).then(BX.delegate(this.onUserFieldCreate, this));
		}
	};
	BX.Crm.EntityEditorSection.prototype.onUserFieldConfigurationCancel = function(sender, params)
	{
		if(sender !== this._userFieldConfigurator)
		{
			return;
		}

		this.removeUserFieldConfigurator();

		if(this._mandatoryConfigurator)
		{
			this._mandatoryConfigurator = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.onUserFieldCreate = function(result)
	{
		if(!BX.type.isPlainObject(result))
		{
			return;
		}

		this.removeUserFieldConfigurator();

		var manager = this._editor.getUserFieldManager();
		for(var key in result)
		{
			if(!result.hasOwnProperty(key))
			{
				continue;
			}

			var data = result[key];
			var info = BX.prop.getObject(data, "FIELD", null);
			if(!info)
			{
				continue;
			}

			var element = manager.createSchemeElement(info);
			if(!element)
			{
				continue;
			}

			this._model.registerNewField(
				element.getName(),
				{ "VALUE": "", "SIGNATURE": BX.prop.getString(info, "SIGNATURE", "") }
			);

			var field = this._editor.createControl(
				element.getType(),
				element.getName(),
				{ schemeElement: element, model: this._model, parent: this, mode: this._mode }
			);

			if(this._mandatoryConfigurator
				&& this._mandatoryConfigurator.isPermitted()
				&& this._mandatoryConfigurator.isEnabled()
				&& this._mandatoryConfigurator.isCustomized()
			)
			{
				var attributeConfig = this._mandatoryConfigurator.getConfiguration();
				this._editor.getAttributeManager().saveConfiguration(attributeConfig, element.getName());
				field.setAttributeConfiguration(attributeConfig);
			}

			var showAlways = this._editor.getOption("show_always", "Y") === "Y";
			if(showAlways !== field.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways))
			{
				field.toggleOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
			}

			//Option "notifyIfNotDisplayed" to enable user notification if field will not be displayed because of settings.
			this.addChild(field, { layout: { notifyIfNotDisplayed: true, html: BX.prop.getString(data, "HTML", "") } });

			break;
		}

		if(this._mandatoryConfigurator)
		{
			this._mandatoryConfigurator = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.onUserFieldUpdate = function(result)
	{
		if(!BX.type.isPlainObject(result))
		{
			return;
		}

		this.removeUserFieldConfigurator();

		var manager = this._editor.getUserFieldManager();
		for(var key in result)
		{
			if(!result.hasOwnProperty(key))
			{
				continue;
			}

			var data = result[key];
			var info = BX.prop.getObject(data, "FIELD", null);
			if(!info)
			{
				continue;
			}

			var field = this.getChildById(key);
			if(!field)
			{
				continue;
			}

			var element = field.getSchemeElement();
			if(!element)
			{
				continue;
			}

			if(this._mandatoryConfigurator && this._mandatoryConfigurator.isPermitted())
			{
				if(this._mandatoryConfigurator.isEnabled() && this._mandatoryConfigurator.isCustomized())
				{
					var attributeConfig = this._mandatoryConfigurator.getConfiguration();
					this._editor.getAttributeManager().saveConfiguration(attributeConfig, element.getName());
					field.setAttributeConfiguration(attributeConfig);
				}
				else
				{
					var attributeTypeId = this._mandatoryConfigurator.getTypeId();
					this._editor.getAttributeManager().removeConfiguration(attributeTypeId, element.getName());
					field.removeAttributeConfiguration(attributeTypeId);
				}
			}

			manager.updateSchemeElement(element, info);
			var options = {};
			var html = BX.prop.getString(data, "HTML", "");
			if(html !== "")
			{
				options["html"] = html;
			}

			field.refreshLayout(options);

			break;
		}

		if(this._mandatoryConfigurator)
		{
			this._mandatoryConfigurator = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.editChildConfiguration = function(child)
	{
		this.removeFieldConfigurator();
		this.removeUserFieldConfigurator();

		if(child.getType() === "userField" && this._editor.getUserFieldManager().isModificationEnabled())
		{
			this.createUserFieldConfigurator({ field: child });
		}
		else
		{
			this.createFieldConfigurator(child);
		}
	};
	BX.Crm.EntityEditorSection.prototype.createFieldConfigurator = function(child)
	{
		child.setVisible(false);

		var attrManager = this._editor.getAttributeManager();
		if(attrManager)
		{
			this._mandatoryConfigurator = attrManager.createFieldConfigurator(
				child,
				BX.Crm.EntityFieldAttributeType.required
			);
		}

		this._fieldConfigurator = BX.Crm.EntityEditorFieldConfigurator.create(
			"",
			{
				editor: this._editor,
				schemeElement: null,
				model: this._model,
				mode: BX.Crm.EntityEditorMode.edit,
				parent: this,
				field: child,
				mandatoryConfigurator: this._mandatoryConfigurator
			}
		);
		this.addChild(this._fieldConfigurator, { related: child });

		BX.addCustomEvent(this._fieldConfigurator, "onSave", BX.delegate(this.onFieldConfigurationSave, this));
		BX.addCustomEvent(this._fieldConfigurator, "onCancel", BX.delegate(this.onFieldConfigurationCancel, this));
	};
	BX.Crm.EntityEditorSection.prototype.removeFieldConfigurator = function()
	{
		if(this._fieldConfigurator)
		{
			var field = this._fieldConfigurator.getField();
			if(field)
			{
				field.setVisible(true);
			}
			this.removeChild(this._fieldConfigurator);
			this._fieldConfigurator = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.onFieldConfigurationSave = function(sender, params)
	{
		if(sender !== this._fieldConfigurator)
		{
			return;
		}

		var field = BX.prop.get(params, "field", null);
		if(!field)
		{
			throw "EntityEditorSection. Could not find target field.";
		}

		var label = BX.prop.getString(params, "label", "");
		var showAlways = BX.prop.getBoolean(params, "showAlways", null);
		if(label === "" && showAlways === null)
		{
			this.removeFieldConfigurator();
			if(this._mandatoryConfigurator)
			{
				this._mandatoryConfigurator = null;
			}
			return;
		}

		this._fieldConfigurator.setLocked(true);
		field.setTitle(label);
		if(showAlways !== null && showAlways !== field.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways))
		{
			field.toggleOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
		}

		this.markSchemeAsChanged();
		this.saveScheme().then(
			BX.delegate(
				function()
				{
					if(this._mandatoryConfigurator)
					{
						if(this._mandatoryConfigurator.isPermitted()
							&& field.areAttributesEnabled()
							&& !field.isRequired()
						)
						{
							if(this._mandatoryConfigurator.isEnabled())
							{
								if(this._mandatoryConfigurator.isChanged())
								{
									this._mandatoryConfigurator.acceptChanges();
								}
								var attributeConfig = this._mandatoryConfigurator.getConfiguration();
								this._editor.getAttributeManager().saveConfiguration(attributeConfig, field.getName());
								field.setAttributeConfiguration(attributeConfig);
							}
							else
							{
								var attributeTypeId = this._mandatoryConfigurator.getTypeId();
								this._editor.getAttributeManager().removeConfiguration(attributeTypeId, field.getName());
								field.removeAttributeConfiguration(attributeTypeId);
							}
						}
						this._mandatoryConfigurator = null;
					}
					this.removeFieldConfigurator();
				},
				this
			)
		)
	};
	BX.Crm.EntityEditorSection.prototype.onFieldConfigurationCancel = function(sender, params)
	{
		if(sender !== this._fieldConfigurator)
		{
			return;
		}

		var field = BX.prop.get(params, "field", null);
		if(!field)
		{
			throw "EntityEditorSection. Could not find target field.";
		}

		this.removeFieldConfigurator();
		if(this._mandatoryConfigurator)
		{
			this._mandatoryConfigurator = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.enablePointerEvents = function(enable)
	{
		if(!this._fields)
		{
			return;
		}

		enable = !!enable;
		for(i = 0, length = this._fields.length; i < length; i++)
		{
			this._fields[i].enablePointerEvents(enable);
		}
	};
	//endregion
	//region Create|Delete Section
	BX.Crm.EntityEditorSection.prototype.onDeleteConfirm = function(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this._editor.removeSchemeElement(this.getSchemeElement());
		this._editor.removeControl(this);
		this._editor.saveScheme();
	};
	BX.Crm.EntityEditorSection.prototype.onDeleteSectionBtnClick = function(e)
	{
		if(this.isRequired() || this.isRequiredConditionally())
		{
			this.showMessageDialog(
				"operationDenied",
				this.getMessage("deleteSection"),
				this.getMessage("deleteSectionDenied")
			);
			return;
		}

		var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("deleteSection"),
					content: this.getMessage("deleteSectionConfirm")
				}
			);
		}
		dlg.open().then(BX.delegate(this.onDeleteConfirm, this));
	};
	//endregion
	//region D&D
	BX.Crm.EntityEditorSection.prototype.getDragObjectType = function()
	{
		return BX.Crm.EditorDragObjectType.section;
	};
	BX.Crm.EntityEditorSection.prototype.getChildDragObjectType = function()
	{
		return BX.Crm.EditorDragObjectType.field;
	};
	BX.Crm.EntityEditorSection.prototype.hasPlaceHolder = function()
	{
		return !!this._dragPlaceHolder;
	};
	BX.Crm.EntityEditorSection.prototype.createPlaceHolder = function(index)
	{
		this.enablePointerEvents(false);

		var qty = this.getChildCount();
		if(index < 0 || index > qty)
		{
			index = qty > 0 ? qty : 0;
		}

		if(this._dragPlaceHolder)
		{
			if(this._dragPlaceHolder.getIndex() === index)
			{
				return this._dragPlaceHolder;
			}

			this._dragPlaceHolder.clearLayout();
			this._dragPlaceHolder = null;
		}

		this._dragPlaceHolder = BX.Crm.EditorDragFieldPlaceholder.create(
			{
				container: this._contentContainer,
				anchor: (index < qty) ? this._fields[index].getWrapper() : this._buttonPanelWrapper,
				index: index
			}
		);
		this._dragPlaceHolder.layout();
		return this._dragPlaceHolder;
	};
	BX.Crm.EntityEditorSection.prototype.getPlaceHolder = function()
	{
		return this._dragPlaceHolder;
	};
	BX.Crm.EntityEditorSection.prototype.removePlaceHolder = function()
	{
		this.enablePointerEvents(true);

		if(this._dragPlaceHolder)
		{
			this._dragPlaceHolder.clearLayout();
			this._dragPlaceHolder = null;
		}
	};
	BX.Crm.EntityEditorSection.prototype.processDraggedItemDrop = function(dragContainer, draggedItem)
	{
		var containerCharge = dragContainer.getCharge();
		if(!((containerCharge instanceof BX.Crm.EditorFieldDragContainer) && containerCharge.getSection() === this))
		{
			return;
		}

		var context = draggedItem.getContextData();
		var contextId = BX.type.isNotEmptyString(context["contextId"]) ? context["contextId"] : "";

		if(contextId !== this.getDraggableContextId())
		{
			return;
		}

		var placeholder = this.getPlaceHolder();
		var placeholderIndex = placeholder ? placeholder.getIndex() : -1;
		if(placeholderIndex < 0)
		{
			return;
		}

		var itemCharge = typeof(context["charge"]) !== "undefined" ?  context["charge"] : null;
		if(!(itemCharge instanceof BX.Crm.EditorFieldDragItem))
		{
			return;
		}

		var source = itemCharge.getControl();
		if(!source)
		{
			return;
		}

		var sourceParent = source.getParent();
		if(sourceParent === this)
		{
			var currentIndex = this.getChildIndex(source);
			if(currentIndex < 0)
			{
				return;
			}

			var index = placeholderIndex <= currentIndex ? placeholderIndex : (placeholderIndex - 1);
			if(index === currentIndex)
			{
				return;
			}

			this.moveChild(source, index, { enableSaving: false });
			this._editor.saveSchemeChanges();
		}
		else
		{
			var schemeElement = source.getSchemeElement();
			sourceParent.removeChild(source, { enableSaving: false });

			var target = this._editor.createControl(
				schemeElement.getType(),
				schemeElement.getName(),
				{ schemeElement: schemeElement, model: this._model, parent: this, mode: this._mode }
			);

			if(this._mode === BX.Crm.EntityEditorMode.view
				&& !target.hasContentToDisplay()
				&& !target.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways)
			)
			{
				//Activate 'showAlways' flag for display empty field in view mode.
				target.toggleOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
			}

			this.addChild(target, { index: placeholderIndex, enableSaving: false });
			this._editor.saveSchemeChanges();
		}
	};
	BX.Crm.EntityEditorSection.prototype.onDrop = function(dragContainer, draggedItem, x, y)
	{
		this.processDraggedItemDrop(dragContainer, draggedItem);
	};
	BX.Crm.EntityEditorSection.prototype.initializeDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			return;
		}

		this._dragItem = BX.Crm.EditorDragItemController.create(
			"section_" + this.getId(),
			{
				charge: BX.Crm.EditorSectionDragItem.create({ control: this }),
				node: this.createDragButton(),
				showControlInDragMode: false,
				ghostOffset: { x: 0, y: 0 }
			}
		);
	};
	BX.Crm.EntityEditorSection.prototype.releaseDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			this._dragItem.release();
			this._dragItem = null;
		}
	};
	//endregion
	BX.Crm.EntityEditorSection.prototype.isWaitingForInput = function()
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			if(this._fields[i].isWaitingForInput())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityEditorSection.prototype.isRequired = function()
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			if(this._fields[i].isRequired())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityEditorSection.prototype.isRequiredConditionally = function()
	{
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			if(this._fields[i].isRequiredConditionally())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityEditorSection.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorSection.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.Crm.EntityEditorSection.messages) === "undefined")
	{
		BX.Crm.EntityEditorSection.messages = {};
	}
	BX.Crm.EntityEditorSection.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorSection();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorText === "undefined")
{
	BX.Crm.EntityEditorText = function()
	{
		BX.Crm.EntityEditorText.superclass.constructor.apply(this);
		this._input = null;
		this._innerWrapper = null;
	};

	BX.extend(BX.Crm.EntityEditorText, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorText.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorText.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorText.prototype.focus = function()
	{
		if(!this._input)
		{
			return;
		}

		BX.focus(this._input);
		BX.Crm.EditorTextHelper.getCurrent().setPositionAtEnd(this._input);
	};
	BX.Crm.EntityEditorText.prototype.getLineCount = function()
	{
		return this._schemeElement.getDataIntegerParam("lineCount", 1);
	};
	BX.Crm.EntityEditorText.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-text" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			var lineCount = this.getLineCount();
			if(lineCount > 1)
			{
				this._input = BX.create("textarea",
					{
						props:
						{
							className: "crm-entity-widget-content-textarea",
							name: name,
							rows: lineCount,
							value: value
						}
					}
				);
			}
			else
			{
				this._input = BX.create("input",
					{
						attrs:
						{
							name: name,
							className: "crm-entity-widget-content-input",
							type: "text",
							value: value
						}
					}
				);
			}

			if(this.isNewEntity())
			{
				var placeholder = this.getCreationPlaceholder();
				if(placeholder !== "")
				{
					this._input.setAttribute("placeholder", placeholder);
				}
			}

			BX.bind(this._input, "input", this._changeHandler);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: { className: "crm-entity-widget-content-block-field-container" },
									children: [ this._input ]
								}
							)
						]
				}
			);

			if(this._editor.isDuplicateControlEnabled())
			{
				var dupControlConfig = this.getDuplicateControlConfig();
				if(dupControlConfig)
				{
					if(!BX.type.isPlainObject(dupControlConfig["field"]))
					{
						dupControlConfig["field"] = {};
					}
					dupControlConfig["field"]["id"] = this.getId();
					dupControlConfig["field"]["element"] = this._input;
					this._editor.getDuplicateManager().registerField(dupControlConfig);
				}
			}
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			if(this.hasContentToDisplay())
			{
				if(this.getLineCount() > 1)
				{
					this._innerWrapper = BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-content-block-inner" },
							children:
								[
									BX.create(
										"div",
										{
											props: { className: "crm-entity-widget-content-block-inner-text" },
											html: BX.util.nl2br(BX.util.htmlspecialchars(value))
										}
									)
								]
						}
					);
				}
				else
				{
					this._innerWrapper = BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-content-block-inner" },
							children:
								[
									BX.create(
										"div",
										{
											props: { className: "crm-entity-widget-content-block-inner-text" },
											text: value
										}
									)
								]
						}
					);
				}
			}
			else
			{
				this._innerWrapper = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						text: this.getMessage("isEmpty")
					}
				);
			}
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorText.prototype.doClearLayout = function(options)
	{
		if(this._editor.isDuplicateControlEnabled())
		{
			var dupControlConfig = this.getDuplicateControlConfig();
			if(dupControlConfig)
			{
				if(!BX.type.isPlainObject(dupControlConfig["field"]))
				{
					dupControlConfig["field"] = {};
				}
				dupControlConfig["field"]["id"] = this.getId();
				this._editor.getDuplicateManager().unregisterField(dupControlConfig);
			}
		}

		this._input = null;
		//BX.unbind(this._innerWrapper, "click", this._viewClickHandler);
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorText.prototype.refreshLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorText.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit && this._input)
		{
			this._input.value = this.getValue();
		}
		else if(this._mode === BX.Crm.EntityEditorMode.view && this._innerWrapper)
		{
			this._innerWrapper.innerHTML = BX.util.htmlspecialchars(this.getValue());
		}
	};
	BX.Crm.EntityEditorText.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.Crm.EntityEditorMode.edit && this._input
			? BX.util.trim(this._input.value) : ""
		);
	};
	BX.Crm.EntityEditorText.prototype.validate = function(result)
	{
		if(!(this._mode === BX.Crm.EntityEditorMode.edit && this._input))
		{
			throw "BX.Crm.EntityEditorText. Invalid validation context";
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._input);
		}
		return isValid;
	};
	BX.Crm.EntityEditorText.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorText.superclass.showError.apply(this, arguments);
		if(this._input)
		{
			BX.addClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorText.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorText.superclass.clearError.apply(this);
		if(this._input)
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorText.prototype.save = function()
	{
		if(this._input)
		{
			this._model.setField(this.getName(), this._input.value, { originator: this });
		}
	};
	BX.Crm.EntityEditorText.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorText.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorText();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorNumber === "undefined")
{
	BX.Crm.EntityEditorNumber = function()
	{
		BX.Crm.EntityEditorNumber.superclass.constructor.apply(this);
		this._input = null;
		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorNumber, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorNumber.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorNumber.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorNumber.prototype.focus = function()
	{
		if(!this._input)
		{
			return;
		}

		BX.focus(this._input);
		BX.Crm.EditorTextHelper.getCurrent().selectAll(this._input);
	};
	BX.Crm.EntityEditorNumber.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-number" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._input = null;
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._input = BX.create("input",
				{
					attrs:
					{
						name: name,
						className: "crm-entity-widget-content-input",
						type: "text",
						value: value
					}
				}
			);
			BX.bind(this._input, "input", this._changeHandler);

			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create("div",
				{
					//todo: remove class "crm-entity-widget-content-block-field-half-width" if required
					props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-field-half-width" },
					children:
						[
							BX.create("div",
								{
									props: { className: "crm-entity-widget-content-block-field-container" },
									children: [ this._input ]
								}
							)
						]
				}
			);
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			if(!this.hasContentToDisplay())
			{
				value = this.getMessage("isEmpty");
			}

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: {className: "crm-entity-widget-content-block-inner-text"},
									text: value
								}
							)
						]
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorNumber.prototype.doClearLayout = function(options)
	{
		this._input = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorNumber.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.Crm.EntityEditorMode.edit && this._input
				? BX.util.trim(this._input.value) : ""
		);
	};
	BX.Crm.EntityEditorNumber.prototype.validate = function(result)
	{
		if(!(this._mode === BX.Crm.EntityEditorMode.edit && this._input))
		{
			throw "BX.Crm.EntityEditorNumber. Invalid validation context";
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._input);
		}
		return isValid;
	};
	BX.Crm.EntityEditorNumber.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorNumber.superclass.showError.apply(this, arguments);
		if(this._input)
		{
			BX.addClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorNumber.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorNumber.superclass.clearError.apply(this);
		if(this._input)
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorNumber.prototype.save = function()
	{
		if(this._input)
		{
			this._model.setField(this.getName(), this._input.value);
		}
	};
	BX.Crm.EntityEditorNumber.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorNumber();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorDatetime === "undefined")
{
	BX.Crm.EntityEditorDatetime = function()
	{
		BX.Crm.EntityEditorDatetime.superclass.constructor.apply(this);
		this._input = null;
		this._inputClickHandler = BX.delegate(this.onInputClick, this);
		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorDatetime, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorDatetime.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorDatetime.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorDatetime.prototype.focus = function()
	{
		if(this._input)
		{
			BX.focus(this._input);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._input);
		}
	};
	BX.Crm.EntityEditorDatetime.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-date" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._input = BX.create("input",
				{
					attrs:
					{
						name: name,
						className: "crm-entity-widget-content-input",
						type: "text",
						value: value
					}
				}
			);
			BX.bind(this._input, "click", this._inputClickHandler);
			BX.bind(this._input, "change", this._changeHandler);
			BX.bind(this._input, "input", this._changeHandler);

			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-field-half-width" },
					children:
						[
							BX.create("div",
								{
									props: {className:"crm-entity-widget-content-block-field-container"},
									children: [ this._input ]
								}
							)
						]
				}
			);
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			value = BX.date.format("j F Y", BX.parseDate(value));
			this._wrapper.appendChild(this.createTitleNode(title));
			if(!this.hasContentToDisplay())
			{
				value = this.getMessage("isEmpty");
			}

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: {className: "crm-entity-widget-content-block-inner-text"},
									text: value
								}
							)
						]
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorDatetime.prototype.doRegisterLayout = function()
	{
		if(this.isInEditMode()
			&& this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual)
			&& this._input
		)
		{
			window.setTimeout(BX.delegate(this.showCalendar, this), 100);
		}
	};
	BX.Crm.EntityEditorDatetime.prototype.doClearLayout = function(options)
	{
		this._input = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorDatetime.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.Crm.EntityEditorMode.edit && this._input
				? BX.util.trim(this._input.value) : ""
		);
	};
	BX.Crm.EntityEditorDatetime.prototype.onInputClick = function(e)
	{
		this.showCalendar();
	};
	BX.Crm.EntityEditorDatetime.prototype.showCalendar = function()
	{
		BX.calendar({ node: this._input, field: this._input, bTime: false, bSetFocus: false });
	};
	BX.Crm.EntityEditorDatetime.prototype.validate = function(result)
	{
		if(!(this._mode === BX.Crm.EntityEditorMode.edit && this._input))
		{
			throw "BX.Crm.EntityEditorDatetime. Invalid validation context";
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._input);
		}
		return isValid;
	};
	BX.Crm.EntityEditorDatetime.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorDatetime.superclass.showError.apply(this, arguments);
		if(this._input)
		{
			BX.addClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorDatetime.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorDatetime.superclass.clearError.apply(this);
		if(this._input)
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorDatetime.prototype.save = function()
	{
		if(this._input)
		{
			this._model.setField(this.getName(), this._input.value);
		}
	};
	BX.Crm.EntityEditorDatetime.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorDatetime();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorBoolean === "undefined")
{
	BX.Crm.EntityEditorBoolean = function()
	{
		BX.Crm.EntityEditorBoolean.superclass.constructor.apply(this);
		this._input = null;
		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorBoolean, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorBoolean.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorBoolean.superclass.doInitialize.apply(this);
		this._selectedValue = this._model.getField(this._schemeElement.getName());
	};
	BX.Crm.EntityEditorBoolean.prototype.areAttributesEnabled = function()
	{
		return false;
	};
	BX.Crm.EntityEditorBoolean.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorBoolean.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorBoolean.prototype.hasValue = function()
	{
		return BX.util.trim(this.getValue()) !== "";
	};
	BX.Crm.EntityEditorBoolean.prototype.getValue = function(defaultValue)
	{
		if(!this._model)
		{
			return "";
		}

		if(defaultValue === undefined)
		{
			defaultValue = "N";
		}

		var value = this._model.getStringField(
			this.getName(),
			defaultValue
		);

		if(value !== "Y" && value !== "N")
		{
			value = "N";
		}

		return value;
	};
	BX.Crm.EntityEditorBoolean.prototype.getRuntimeValue = function()
	{
		if (this._mode !== BX.Crm.EntityEditorMode.edit || !this._input)
			return "";

		var value = BX.util.trim(this._input.value);
		if(value !== "Y" && value !== "N")
		{
			value = "N";
		}
		return value;
	};
	BX.Crm.EntityEditorBoolean.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-checkbox" ] });
		this.adjustWrapper();

		/*
		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}
		*/

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(
				BX.create("input", { attrs: { name: name, type: "hidden", value: "N" } })
			);

			this._input = BX.create(
				"input",
				{
					attrs:
					{
						className: "crm-entity-widget-content-checkbox",
						name: name,
						type: "checkbox",
						value: "Y",
						checked: value === "Y"
					}
				}
			);
			BX.bind(this._input, "change", this._changeHandler);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: {className: "crm-entity-widget-content-block-field-container"},
									children:
										[
											BX.create("label",
												{
													attrs: { className: "crm-entity-widget-content-block-checkbox-label" },
													children:
														[
															this._input,
															BX.create("span",
																{
																	props: { className: "crm-entity-widget-content-block-checkbox-description" },
																	text: title
																}
															)
														]
												}
											)
										]
								}
							)
						]
				}
			);
		}
		else//if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: { className: "crm-entity-widget-content-block-inner-text"},
									text: this.getMessage(value === "Y" ? "yes" : "no")
								}
							)
						]
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorBoolean.prototype.doClearLayout = function(options)
	{
		this._input = null;
		this._innerWrapper = null;
		//this._selectContainer = null;
	};
	BX.Crm.EntityEditorBoolean.prototype.validate = function(result)
	{
		if(!(this._mode === BX.Crm.EntityEditorMode.edit && this._input))
		{
			throw "BX.Crm.EntityEditorBoolean. Invalid validation context";
		}

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			BX.addClass(this._input, "crm-entity-widget-content-error");
			this.showRequiredFieldError(this._input);
		}
		else
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
			this.clearError();
		}
		return isValid;
	};
	BX.Crm.EntityEditorBoolean.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorBoolean.superclass.showError.apply(this, arguments);
		if(this._input)
		{
			BX.addClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorBoolean.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorBoolean.superclass.clearError.apply(this);
		if(this._input)
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorBoolean.prototype.save = function()
	{
		if(this._input)
		{
			this._model.setField(this.getName(), this._input.checked ? "Y" : "N", { originator: this });
		}
	};
	BX.Crm.EntityEditorBoolean.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorBoolean.messages;
		return (m.hasOwnProperty(name)
			? m[name]
			: BX.Crm.EntityEditorBoolean.superclass.getMessage.apply(this, arguments)
		);
	};
	if(typeof(BX.Crm.EntityEditorBoolean.messages) === "undefined")
	{
		BX.Crm.EntityEditorBoolean.messages = {};
	}
	BX.Crm.EntityEditorBoolean.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorBoolean();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorList === "undefined")
{
	BX.Crm.EntityEditorList = function()
	{
		BX.Crm.EntityEditorList.superclass.constructor.apply(this);
		this._items = null;
		this._input = null;
		this._selectContainer = null;
		this._selectedValue = "";
		this._selectorClickHandler = BX.delegate(this.onSelectorClick, this);
		this._innerWrapper = null;
		this._isOpened = false;
	};
	BX.extend(BX.Crm.EntityEditorList, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorList.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorList.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorList.prototype.checkIfNotEmpty = function(value)
	{
		if(BX.type.isString(value))
		{
			value = value.trim();
			//0 is value for "Not Selected" item
			return value !== "" && value !== "0";
		}
		return (value !== null && value !== undefined);
	};
	BX.Crm.EntityEditorList.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-select" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();

		var value = this.getValue();
		var item = this.getItemByValue(value);
		var isHtmlOption = this.getDataBooleanParam('isHtml', false);
		var containerProps = {};

		if(!item)
		{
			item = this.getFirstItem();
			if(item)
			{
				value = item["VALUE"];
			}
		}
		this._selectedValue = value;

		this._selectContainer = null;
		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });
			this._wrapper.appendChild(this._input);

			containerProps = {props: { className: "crm-entity-widget-content-select" }};
			if (isHtmlOption)
			{
				containerProps.html = (item ? item["NAME"] : value);
			}
			else
			{
				containerProps.text = (item ? item["NAME"] : value);
			}

			this._selectContainer = BX.create("div", containerProps);
			BX.bind(this._selectContainer, "click", this._selectorClickHandler);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: {className: "crm-entity-widget-content-block-field-container"},
									children :[ this._selectContainer ]
								}
							)
						]
				}
			);
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			var text = "";
			if(!this.hasContentToDisplay())
			{
				text = this.getMessage("isEmpty");
			}
			else if(item)
			{
				text = item["NAME"];
			}
			else
			{
				text = value;
			}

			var containerProps = {props: { className: "crm-entity-widget-content-block-inner-text" }};

			if (isHtmlOption)
			{
				containerProps.html = text;
			}
			else
			{
				containerProps.text = text;
			}

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div", containerProps)
						]
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorList.prototype.doRegisterLayout = function()
	{
		if(this.isInEditMode()
			&& this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual)
			&& this._selectContainer
		)
		{
			window.setTimeout(BX.delegate(this.openMenu, this), 100);
		}
	};
	BX.Crm.EntityEditorList.prototype.doClearLayout = function(options)
	{
		this.closeMenu();

		this._input = null;
		this._selectContainer = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorList.prototype.refreshLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorMoney.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		var value = this.getValue();
		var item = this.getItemByValue(value);
		var text = item ? BX.prop.getString(item, "NAME", value) : value;
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._selectedValue = value;
			if(this._input)
			{
				this._input.value  = value;
			}
			if(this._selectContainer)
			{
				this._selectContainer.innerHTML = this.getDataBooleanParam('isHtml', false) ? text : BX.util.htmlspecialchars(text);
			}
		}
		else if(this._mode === BX.Crm.EntityEditorMode.view && this._innerWrapper)
		{
			this._innerWrapper.innerHTML = this.getDataBooleanParam('isHtml', false) ? text : BX.util.htmlspecialchars(text);
		}
	};
	BX.Crm.EntityEditorList.prototype.validate = function(result)
	{
		if(this._mode !== BX.Crm.EntityEditorMode.edit)
		{
			throw "BX.Crm.EntityEditorList. Invalid validation context";
		}

		if(!this.isEditable())
		{
			return true;
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._input);
		}
		return isValid;
	};
	BX.Crm.EntityEditorList.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorList.superclass.showError.apply(this, arguments);
		if(this._input)
		{
			BX.addClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorList.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorList.superclass.clearError.apply(this);
		if(this._input)
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorList.prototype.onSelectorClick = function (e)
	{
		if(!this._isOpened)
		{
			this.openMenu();
		}
		else
		{
			this.closeMenu();
		}
	};
	BX.Crm.EntityEditorList.prototype.openMenu = function()
	{
		if(this._isOpened)
		{
			return;
		}

		var menu = [];
		var items = this.getItems();
		for(var i = 0, length = items.length; i < length; i++)
		{
			var item = items[i];
			if(!BX.prop.getBoolean(item, "IS_EDITABLE", true))
			{
				continue;
			}

			var value = BX.prop.getString(item, "VALUE", i);
			var name = BX.prop.getString(item, "NAME", value);
			menu.push(
				{
					text: this.getDataBooleanParam('isHtml', false) ? name : BX.util.htmlspecialchars(name),
					value: value,
					onclick: BX.delegate( this.onItemSelect, this)
				}
			);
		}

		BX.PopupMenu.show(
			this._id,
			this._selectContainer,
			menu,
			{
				angle: false, width: this._selectContainer.offsetWidth + 'px',
				events:
					{
						onPopupShow: BX.delegate( this.onMenuShow, this),
						onPopupClose: BX.delegate( this.onMenuClose, this)
					}
			}
		);
		BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorList.prototype.closeMenu = function()
	{
		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorList.prototype.onMenuShow = function()
	{
		BX.addClass(this._selectContainer, "active");
		this._isOpened = true;
	};
	BX.Crm.EntityEditorList.prototype.onMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);

		BX.removeClass(this._selectContainer, "active");
		this._isOpened = false;
	};
	BX.Crm.EntityEditorList.prototype.onItemSelect = function(e, item)
	{
		this.closeMenu();

		this._selectedValue = this._input.value  = item.value;
		var name = BX.prop.getString(
			this.getItemByValue(this._selectedValue),
			"NAME",
			this._selectedValue
		);

		this._selectContainer.innerHTML = this.getDataBooleanParam('isHtml', false) ? name : BX.util.htmlspecialchars(name);
		this.markAsChanged();
		BX.PopupMenu.destroy(this._id);

	};
	BX.Crm.EntityEditorList.prototype.getItems = function()
	{
		if(!this._items)
		{
			this._items = BX.prop.getArray(this._schemeElement.getData(), "items", []);
		}
		return this._items;
	};
	BX.Crm.EntityEditorList.prototype.getItemByValue = function(value)
	{
		var items = this.getItems();
		for(var i = 0, l = items.length; i < l; i++)
		{
			var item = items[i];
			if(value === BX.prop.getString(item, "VALUE", ""))
			{
				return item;
			}
		}
		return null;
	};
	BX.Crm.EntityEditorList.prototype.getFirstItem = function()
	{
		var items = this.getItems();
		return items.length > 0 ? items[0] : null;
	};
	BX.Crm.EntityEditorList.prototype.save = function()
	{
		if(!this.isEditable())
		{
			return;
		}

		this._model.setField(this.getName(), this._selectedValue);
	};
	BX.Crm.EntityEditorList.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorList.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.Crm.EntityEditorMode.edit && this._input
				? this._selectedValue : ""
		);
	};
	BX.Crm.EntityEditorList.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorList();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorHtml === "undefined")
{
	BX.Crm.EntityEditorHtml = function()
	{
		BX.Crm.EntityEditorHtml.superclass.constructor.apply(this);
		this._htmlEditorContainer = null;
		this._htmlEditor = null;
		this._isEditorInitialized = false;
		this._focusOnLoad = false;

		this._input = null;
		this._innerWrapper = null;

		this._editorInitializationHandler = BX.delegate(this.onEditorInitialized, this);
		this._viewClickHandler = BX.delegate(this.onViewClick, this);
	};
	BX.extend(BX.Crm.EntityEditorHtml, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorHtml.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorHtml.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorHtml.prototype.checkIfNotEmpty = function(value)
	{
		return BX.Crm.EntityEditorHtml.isNotEmptyValue(value);
	};
	BX.Crm.EntityEditorHtml.prototype.focus = function()
	{
		if(this._htmlEditor && this._isEditorInitialized)
		{
			this._htmlEditor.Focus(true);
		}
		else
		{
			this._focusOnLoad = true;
		}
	};
	BX.Crm.EntityEditorHtml.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.release();
		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-comment" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			if(!this._editor)
			{
				throw "BX.Crm.EntityEditorHtml: Editor instance is required for create layout.";
			}

			var htmlEditorConfig = this._editor.getHtmlEditorConfig(name);
			if(!htmlEditorConfig)
			{
				throw "BX.Crm.EntityEditorHtml: Could not find HTML editor config.";
			}

			this._htmlEditorContainer = BX(BX.prop.getString(htmlEditorConfig, "containerId"));
			if(!BX.type.isElementNode(this._htmlEditorContainer))
			{
				throw "BX.Crm.EntityEditorHtml: Could not find HTML editor container.";
			}

			this._htmlEditor = BXHtmlEditor.Get(BX.prop.getString(htmlEditorConfig, "id"));
			if(!this._htmlEditor)
			{
				throw "BX.Crm.EntityEditorHtml: Could not find HTML editor instance.";
			}

			this._wrapper.appendChild(this.createTitleNode(title));
			this._input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });
			this._wrapper.appendChild(this._input);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: {className: "crm-entity-widget-content-block-field-container"},
									children: [this._htmlEditorContainer]
								}
							)
						]
				}
			);
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" }
				}
			);

			if(this.hasContentToDisplay())
			{
				this._innerWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-content-block-field-container" },
							children:
							[
								BX.create("div",
									{
										props: { className: "crm-entity-widget-content-block-inner-comment" },
										html: value
									}
								)
							]
						}
					)
				);

				if (value.length > 200)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-comment-collapsed");
					this._innerWrapper.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-widget-content-block-field-comment-expand-btn-container" },
								children:
									[
										BX.create("A",
											{
												attrs:
													{
														className: "crm-entity-widget-content-block-field-comment-expand-btn",
														href: "#"
													},
												events:
													{
														click: BX.delegate(this.onExpandButtonClick, this)
													},
												text: this.getMessage("expand")
											}
										)
									]
							}
						)
					);
					this._isCollapsed = true;
				}
			}
			else
			{
				this._innerWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
			}

			this._wrapper.appendChild(this._innerWrapper);

			BX.bindDelegate(
				this._wrapper,
				"mousedown",
				BX.delegate(this.filterViewNode, this),
				this._viewClickHandler
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

		this.registerLayout(options);

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._isEditorInitialized = !!this._htmlEditor.inited;
			if(this._isEditorInitialized)
			{
				this.prepareEditor();
			}
			else
			{
				BX.addCustomEvent(
					this._htmlEditor,
					"OnCreateIframeAfter",
					this._editorInitializationHandler
				);
				this._htmlEditor.Init();
			}

			window.top.setTimeout(BX.delegate(this.bindChangeEvent, this), 1000);
			this.initializeDragDropAbilities();
		}

		this._hasLayout = true;
	};
	BX.Crm.EntityEditorHtml.prototype.doClearLayout = function(options)
	{
		this.release();
		this._input = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorHtml.prototype.onExpandButtonClick = function(e)
	{
		if (!this._wrapper)
		{
			return BX.PreventDefault(e);
		}

		if (this._hasFiles && BX.type.isDomNode(this._commentWrapper) && !this._textLoaded)
		{
			this._textLoaded = true;
			this.loadContent(this._commentWrapper, "GET_TEXT")
		}
		var eventWrapper = this._wrapper.querySelector(".crm-entity-widget-content-block-inner-comment");
		if (this._isCollapsed)
		{

			BX.defer(
				function() {
					eventWrapper.style.maxHeight = eventWrapper.scrollHeight + 130 + "px";
				}
			)();

			setTimeout(
				BX.delegate(function() {
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-field-comment-collapsed");
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-comment-expand");
					eventWrapper.style.maxHeight = "";
				}, this),
				200
			);
		}
		else
		{
			BX.defer(
				function() {
					eventWrapper.style.maxHeight = eventWrapper.clientHeight + "px";
				}
			)();


			BX.defer(
				function() {
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-field-comment-expand");
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-comment-collapsed");
				},
				this
			)();

			setTimeout(
				function() {
					eventWrapper.style.maxHeight = "";
				},
				200
			);
		}

		this._isCollapsed = !this._isCollapsed;

		var button = this._wrapper.querySelector("a.crm-entity-widget-content-block-field-comment-expand-btn");
		if (button)
		{
			button.innerHTML = this.getMessage(this._isCollapsed ? "expand" : "collapse");
		}
		return BX.PreventDefault(e);
	};
	BX.Crm.EntityEditorHtml.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorHtml.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorHtml.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorHtml.prototype.filterViewNode = function(obj)
	{
		return true;
	};
	BX.Crm.EntityEditorHtml.prototype.onViewClick = function(e)
	{
		var link = null;
		var node = BX.getEventTarget(e);
		if(node.tagName === "A")
		{
			link = node;
		}
		else
		{
			link = BX.findParent(node, { tagName: "a" }, this._wrapper);
		}

		if(link && link.target !== "_blank")
		{
			link.target = "_blank";
		}
	};
	BX.Crm.EntityEditorHtml.prototype.onEditorInitialized = function()
	{
		this._isEditorInitialized = true;
		BX.removeCustomEvent(
			this._htmlEditor,
			"OnCreateIframeAfter",
			this._editorInitializationHandler
		);
		this.prepareEditor();
	};
	BX.Crm.EntityEditorHtml.prototype.prepareEditor = function()
	{
		this._htmlEditorContainer.style.display = "";

		this._htmlEditor.CheckAndReInit();
		this._htmlEditor.ResizeSceleton("100%", 200);
		this._htmlEditor.SetContent(this.getStringValue(""), true);

		if(this._focusOnLoad)
		{
			this._htmlEditor.Focus(true);
			this._focusOnLoad = false;
		}
	};
	BX.Crm.EntityEditorHtml.prototype.release = function()
	{
		if(this._htmlEditorContainer)
		{
			var stub = BX.create("DIV",
				{
					style:
						{
							height: this._htmlEditorContainer.offsetHeight + "px",
							border: "1px solid #bbc4cd",
							boxSizing: "border-box"
						}
				}
			);
			this._htmlEditorContainer.parentNode.insertBefore(stub, this._htmlEditorContainer);

			document.body.appendChild(this._htmlEditorContainer);
			this._htmlEditorContainer.style.display = "none";
			this._htmlEditorContainer = null;
		}

		if(this._htmlEditor)
		{
			this.unbindChangeEvent();
			this._htmlEditor.SetContent("");
			this._htmlEditor = null;
			this._isEditorInitialized = false;
		}

		this._focusOnLoad = false;
	};
	BX.Crm.EntityEditorHtml.prototype.bindChangeEvent = function()
	{
		if(this._htmlEditor)
		{
			BX.addCustomEvent(this._htmlEditor, "OnContentChanged", this._changeHandler);
		}
	};
	BX.Crm.EntityEditorHtml.prototype.unbindChangeEvent = function()
	{
		if(this._htmlEditor)
		{
			BX.removeCustomEvent(this._htmlEditor, "OnContentChanged", this._changeHandler);
		}
	};
	BX.Crm.EntityEditorHtml.prototype.validate = function(result)
	{
		if(!(this._mode === BX.Crm.EntityEditorMode.edit && this._htmlEditor))
		{
			throw "BX.Crm.EntityEditorHtml. Invalid validation context";
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.Crm.EntityEditorHtml.isNotEmptyValue(this._htmlEditor.GetContent());
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._htmlEditorContainer);
		}
		return isValid;
	};
	BX.Crm.EntityEditorHtml.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorHtml.superclass.showError.apply(this, arguments);
		if(this._htmlEditorContainer)
		{
			BX.addClass(this._htmlEditorContainer, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorHtml.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorHtml.superclass.clearError.apply(this);
		if(this._htmlEditorContainer)
		{
			BX.removeClass(this._htmlEditorContainer, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorHtml.prototype.save = function()
	{
		if(this._htmlEditor)
		{
			var value = this._input.value = this._htmlEditor.GetContent();
			this._model.setField(this.getName(), value);
		}
	};
	BX.Crm.EntityEditorHtml.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.Crm.EntityEditorMode.edit && this._input
				? this._htmlEditor.GetContent() : ""
		);
	};
	BX.Crm.EntityEditorHtml.isNotEmptyValue = function(value)
	{
		return BX.util.trim(value.replace(/<br\/?>|&nbsp;/ig, "")) !== "";
	};
	BX.Crm.EntityEditorHtml.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorHtml();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorMoney === "undefined")
{
	BX.Crm.EntityEditorMoney = function()
	{
		BX.Crm.EntityEditorMoney.superclass.constructor.apply(this);
		this._currencyEditor = null;
		this._amountInput = null;
		this._currencyInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
		this._selectedCurrencyValue = "";
		this._selectorClickHandler = BX.delegate(this.onSelectorClick, this);
		this._isCurrencyMenuOpened = false;
	};
	BX.extend(BX.Crm.EntityEditorMoney, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorMoney.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorMoney.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorMoney.prototype.focus = function()
	{
		if(this._amountInput)
		{
			BX.focus(this._amountInput);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._amountInput);
		}
	};
	BX.Crm.EntityEditorMoney.prototype.getValue = function(defaultValue)
	{
		if(!this._model)
		{
			return "";
		}

		return(
			this._model.getStringField(
				this.getAmountFieldName(),
				(defaultValue !== undefined ? defaultValue : "")
			)
		);
	};
	BX.Crm.EntityEditorMoney.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-money" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		//var name = this.getName();
		var title = this.getTitle();
		var data = this.getData();

		var amountInputName = BX.prop.getString(data, "amount");
		var currencyInputName = BX.prop.getString(BX.prop.getObject(data, "currency"), "name");

		var currencyValue = this._model.getField(
			BX.prop.getString(BX.prop.getObject(data, "currency"), "name", "")
		);

		if(!BX.type.isNotEmptyString(currencyValue))
		{
			currencyValue = BX.Currency.Editor.getBaseCurrencyId();
		}

		this._selectedCurrencyValue = currencyValue;

		var currencyName = this._editor.findOption(
			currencyValue,
			BX.prop.getArray(BX.prop.getObject(data, "currency"), "items")
		);

		var amountFieldName = this.getAmountFieldName();
		var currencyFieldName = this.getCurrencyFieldName();
		var amountValue = this._model.getField(amountFieldName, ""); //SET CURRENT SUM VALUE
		var formatted = this._model.getField(BX.prop.getString(data, "formatted"), ""); //SET FORMATTED VALUE

		this._amountValue = null;
		this._amountInput = null;
		this._currencyInput = null;
		this._selectContainer = null;
		this._innerWrapper = null;
		this._sumElement = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._amountValue = BX.create("input",
				{
					attrs:
					{
						name: amountInputName,
						type: "hidden",
						value: amountValue
					}
				}
			);

			this._amountInput = BX.create("input",
				{
					attrs:
					{
						className: "crm-entity-widget-content-input",
						type: "text",
						value: formatted
					}
				}
			);
			BX.bind(this._amountInput, "input", this._changeHandler);

			if(this._model.isFieldLocked(amountFieldName))
			{
				this._amountInput.disabled = true;
			}

			this._currencyInput = BX.create("input",
				{
					attrs:
					{
						name: currencyInputName,
						type: "hidden",
						value: currencyValue
					}
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-select" },
					text: currencyName
				}
			);

			if(this._model.isFieldLocked(currencyFieldName))
			{
				this._selectContainer.disabled = true;
			}
			else
			{
				BX.bind(this._selectContainer, "click", this._selectorClickHandler);
			}

			this._inputWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-container-double" },
					children:
						[
							this._amountValue,
							this._amountInput,
							this._currencyInput,
							BX.create('div',
								{
									props: { className: "crm-entity-widget-content-block-select" + (this._selectContainer.disabled ? '-disabled': '') },
									children: [ this._selectContainer ]
								}
							)
						]
				}
			);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					children: [ this._inputWrapper ]
				}
			);

			this._currencyEditor = new BX.Currency.Editor(
				{
					input: this._amountInput,
					currency: currencyValue,
					callback: BX.delegate(this.onAmountValueChange, this)
				}
			);

			this._currencyEditor.changeValue();
		}
		else //this._mode === BX.Crm.EntityEditorMode.view
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			if(this.hasContentToDisplay())
			{
				this._sumElement = BX.create("span",
					{
						props: { className: "crm-entity-widget-content-block-wallet" }
					}
				);
				this._sumElement.innerHTML = this.renderMoney();
				this._innerWrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						children:
							[
								BX.create("div",
									{
										props: { className: "crm-entity-widget-content-block-inner-text" },
										children: [ this._sumElement ]
									}
								)
							]
					}
				);
			}
			else
			{
				this._innerWrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						text: this.getMessage("isEmpty")
					}
				);
			}
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorMoney.prototype.doClearLayout = function(options)
	{
		BX.PopupMenu.destroy(this._id);

		if(this._currencyEditor)
		{
			this._currencyEditor.clean();
			this._currencyEditor = null;
		}

		this._amountValue = null;
		this._amountInput = null;
		this._currencyInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorMoney.prototype.refreshLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorMoney.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit && this._amountInput)
		{
			var currencyValue = this._currencyEditor
				? this._currencyEditor.currency
				: this._model.getField(this.getCurrencyFieldName());

			if(!BX.type.isNotEmptyString(currencyValue))
			{
				currencyValue = BX.Currency.Editor.getBaseCurrencyId();
			}

			var amountFieldName = this.getAmountFieldName();
			this._amountValue.value = this._model.getField(amountFieldName);
			this._amountInput.value = BX.Currency.Editor.getFormattedValue(
				this._model.getField(amountFieldName, ""),
				currencyValue
			);

			this._amountInput.disabled = this._model.isFieldLocked(amountFieldName);
		}
		else if(this._mode === BX.Crm.EntityEditorMode.view && this._sumElement)
		{
			this._sumElement.innerHTML = this.renderMoney();
		}
	};
	BX.Crm.EntityEditorMoney.prototype.onAmountValueChange = function(v)
	{
		if(this._amountValue)
		{
			this._amountValue.value = v;
		}
	};
	BX.Crm.EntityEditorMoney.prototype.getAmountFieldName = function()
	{
		return this._schemeElement.getDataStringParam("amount", "");
	};
	BX.Crm.EntityEditorMoney.prototype.getCurrencyFieldName = function()
	{
		return BX.prop.getString(
			this._schemeElement.getDataObjectParam("currency", {}),
			"name",
			""
		);
	};
	BX.Crm.EntityEditorMoney.prototype.onSelectorClick = function (e)
	{
		this.openCurrencyMenu();
	};
	BX.Crm.EntityEditorMoney.prototype.openCurrencyMenu = function()
	{
		if(this._isCurrencyMenuOpened)
		{
			return;
		}

		var data = this._schemeElement.getData();
		var currencyList = BX.prop.getArray(BX.prop.getObject(data, "currency"), "items"); //{NAME, VALUE}

		var key = 0;
		var menu = [];
		while (key < currencyList.length)
		{
			menu.push(
				{
					text: currencyList[key]["NAME"],
					value: currencyList[key]["VALUE"],
					onclick: BX.delegate( this.onCurrencySelect, this)
				}
			);
			key++
		}

		BX.PopupMenu.show(
			this._id,
			this._selectContainer,
			menu,
			{
				angle: false, width: this._selectContainer.offsetWidth + 'px',
				events:
					{
						onPopupShow: BX.delegate( this.onCurrencyMenuOpen, this),
						onPopupClose: BX.delegate( this.onCurrencyMenuClose, this)
					}
			}
		);
		// BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorMoney.prototype.closeCurrencyMenu = function()
	{
		if(!this._isCurrencyMenuOpened)
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorMoney.prototype.onCurrencyMenuOpen = function()
	{
		BX.addClass(this._selectContainer, "active");
		this._isCurrencyMenuOpened = true;
	};
	BX.Crm.EntityEditorMoney.prototype.onCurrencyMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);

		BX.removeClass(this._selectContainer, "active");
		this._isCurrencyMenuOpened = false;
	};
	BX.Crm.EntityEditorMoney.prototype.onCurrencySelect = function(e, item)
	{
		this.closeCurrencyMenu();

		this._selectedCurrencyValue = this._currencyInput.value = item.value;
		this._selectContainer.innerHTML = BX.util.htmlspecialchars(item.text);
		if(this._currencyEditor)
		{
			this._currencyEditor.setCurrency(this._selectedCurrencyValue);
		}
		this.markAsChanged(
			{
				fieldName: this.getCurrencyFieldName(),
				fieldValue: this._selectedCurrencyValue
			}
		);
	};
	BX.Crm.EntityEditorMoney.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getAmountFieldName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorMoney.prototype.processModelLock = function(params)
	{
		var name = BX.prop.getString(params, "name", "");
		if(this.getAmountFieldName() === name)
		{
			this.refreshLayout();
		}
	};
	BX.Crm.EntityEditorMoney.prototype.validate = function(result)
	{
		if(!(this._mode === BX.Crm.EntityEditorMode.edit && this._amountInput && this._amountValue))
		{
			throw "BX.Crm.EntityEditorMoney. Invalid validation context";
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._amountValue.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._inputWrapper);
		}
		return isValid;
	};
	BX.Crm.EntityEditorMoney.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorMoney.superclass.showError.apply(this, arguments);
		if(this._amountInput)
		{
			BX.addClass(this._amountInput, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorMoney.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorMoney.superclass.clearError.apply(this);
		if(this._amountInput)
		{
			BX.removeClass(this._amountInput, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorMoney.prototype.getRuntimeValue = function()
	{
		var data = [];
		if (this._mode === BX.Crm.EntityEditorMode.edit)
		{
			if(this._amountValue)
			{
				data[ BX.prop.getString(data, "amount")] = this._amountValue.value;
			}
			data[ BX.prop.getString(data, "currency")] = this._selectedCurrencyValue;

			return data;
		}
		return "";
	};
	BX.Crm.EntityEditorMoney.prototype.save = function()
	{
		var data = this._schemeElement.getData();
		this._model.setField(
			BX.prop.getString(BX.prop.getObject(data, "currency"), "name"),
			this._selectedCurrencyValue,
			{ originator: this }
		);

		if(this._amountValue)
		{
			this._model.setField(
				BX.prop.getString(data, "amount"),
				this._amountValue.value,
				{ originator: this }
			);

			this._model.setField(
				BX.prop.getString(data, "formatted"),
				"",
				{ originator: this }
			);

			this._editor.formatMoney(
				this._amountValue.value,
				this._selectedCurrencyValue,
				BX.delegate(this.onMoneyFormatRequestSuccess, this)
			);
		}
	};
	BX.Crm.EntityEditorMoney.prototype.onMoneyFormatRequestSuccess = function(data)
	{
		var schemeData = this._schemeElement.getData();
		var formattedWithCurrency = BX.type.isNotEmptyString(data["FORMATTED_SUM_WITH_CURRENCY"]) ? data["FORMATTED_SUM_WITH_CURRENCY"] : "";
		this._model.setField(BX.prop.getString(schemeData, "formattedWithCurrency"), formattedWithCurrency);

		var formatted = BX.type.isNotEmptyString(data["FORMATTED_SUM"]) ? data["FORMATTED_SUM"] : "";
		this._model.setField(
			BX.prop.getString(schemeData, "formatted"),
			formatted,
			{ originator: this }
		);

		if(this._sumElement)
		{
			while (this._sumElement.firstChild)
			{
				this._sumElement.removeChild(this._sumElement.firstChild);
			}
			this._sumElement.innerHTML = this.renderMoney();
		}
	};
	BX.Crm.EntityEditorMoney.prototype.renderMoney = function()
	{
		var data = this._schemeElement.getData();
		var formattedWithCurrency = this._model.getField(BX.prop.getString(data, "formattedWithCurrency"), "");
		var formatted = this._model.getField(BX.prop.getString(data, "formatted"), "");
		var result = BX.Currency.Editor.trimTrailingZeros(formatted, this._selectedCurrencyValue);

		return formattedWithCurrency.replace(
			formatted,
			"<span class=\"crm-entity-widget-content-block-colums-right\">" + result + "</span>"
		);
	};
	BX.Crm.EntityEditorMoney.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMoney();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorImage === "undefined")
{
	BX.Crm.EntityEditorImage = function()
	{
		BX.Crm.EntityEditorImage.superclass.constructor.apply(this);
		this._innerWrapper = null;

		this._dialogShowHandler = BX.delegate(this.onDialogShow, this);
		this._dialogCloseHandler = BX.delegate(this.onDialogClose, this);
		this._fileChangeHandler = BX.delegate(this.onFileChange, this);
	};
	BX.extend(BX.Crm.EntityEditorImage, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorImage.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorImage.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorImage.prototype.hasContentToDisplay = function()
	{
		return(this._mode === BX.Crm.EntityEditorMode.edit
			|| this._model.getSchemeField(this._schemeElement, "showUrl", "") !== ""
		);
	};
	BX.Crm.EntityEditorImage.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-file" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" }
				}
			);
			this._editor.loadCustomHtml("RENDER_IMAGE_INPUT", { "FIELD_NAME": name }, BX.delegate(this.onEditorHtmlLoad, this));
		}
		else// if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner" } });

			if(this.hasContentToDisplay())
			{
				this._innerWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-content-block-inner-box" },
							children:
								[
									BX.create(
										"img",
										{
											props:
												{
													className: "crm-entity-widget-content-block-photo",
													src: this._model.getSchemeField(this._schemeElement, "showUrl", "")
												}
										}
									)
								]
						}
					)
				);
			}
			else
			{
				this._innerWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
			}
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorImage.prototype.doClearLayout = function(options)
	{
		if(this._innerWrapper)
		{
			BX.removeCustomEvent(window, "onAfterPopupShow", this._dialogShowHandler);
			BX.removeCustomEvent(window, "onPopupClose", this._dialogCloseHandler);

			BX.cleanNode(this._innerWrapper);
			this._innerWrapper = null;
		}

		this.unbindFileEvents();
	};
	BX.Crm.EntityEditorImage.prototype.onEditorHtmlLoad = function(html)
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit && this._innerWrapper)
		{
			this._innerWrapper.innerHTML = html;

			BX.addCustomEvent(window, "onAfterPopupShow", this._dialogShowHandler);
			BX.addCustomEvent(window, "onPopupClose", this._dialogCloseHandler);

			window.setTimeout(BX.delegate(this.bindFileEvents, this), 500)
		}
	};
	BX.Crm.EntityEditorImage.prototype.bindFileEvents = function()
	{
		var fileControl = BX.MFInput ? BX.MFInput.get(this.getName().toLowerCase() + "_uploader") : null
		if(fileControl)
		{
			BX.addCustomEvent(fileControl, "onAddFile", this._fileChangeHandler);
			BX.addCustomEvent(fileControl, "onDeleteFile", this._fileChangeHandler);
		}
	};
	BX.Crm.EntityEditorImage.prototype.unbindFileEvents = function()
	{
		var fileControl = BX.MFInput ? BX.MFInput.get(this.getName().toLowerCase() + "_uploader") : null
		if(fileControl)
		{
			BX.removeCustomEvent(fileControl, "onAddFile", this._fileChangeHandler);
			BX.removeCustomEvent(fileControl, "onDeleteFile", this._fileChangeHandler);
		}
	};
	BX.Crm.EntityEditorImage.prototype.onDialogShow = function(popup)
	{
		if(popup.uniquePopupId.indexOf("popupavatarEditor") !== 0)
		{
			return;
		}

		BX.addCustomEvent(window, "onApply", this._fileChangeHandler);

		if(this._singleEditController)
		{
			this._singleEditController.setActiveDelayed(false);
		}

		BX.bind(
			popup.popupContainer,
			"click",
			function (e) { BX.eventCancelBubble(e); }
		);
	};
	BX.Crm.EntityEditorImage.prototype.onDialogClose = function(popup)
	{
		if(BX.prop.getString(popup, "uniquePopupId", "").indexOf("popupavatarEditor") !== 0)
		{
			return;
		}

		BX.removeCustomEvent(window, "onApply", this._fileChangeHandler);

		if(this._singleEditController)
		{
			this._singleEditController.setActiveDelayed(true);
		}
	};
	BX.Crm.EntityEditorImage.prototype.onFileChange = function(result)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorImage.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorImage();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorUser === "undefined")
{
	BX.Crm.EntityEditorUser = function()
	{
		BX.Crm.EntityEditorUser.superclass.constructor.apply(this);
		this._input = null;
		this._editButton = null;
		this._photoElement = null;
		this._nameElement = null;
		this._positionElement = null;
		this._userSelector = null;
		this._selectedData = {};
		this._editButtonClickHandler = BX.delegate(this.onEditBtnClick, this);
	};
	BX.extend(BX.Crm.EntityEditorUser, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorUser.prototype.isSingleEditEnabled = function()
	{
		return true;
	};
	BX.Crm.EntityEditorUser.prototype.getRelatedDataKeys = function()
	{
		return (
			[
				this.getDataKey(),
				this._schemeElement.getDataStringParam("formated", ""),
				this._schemeElement.getDataStringParam("position", ""),
				this._schemeElement.getDataStringParam("showUrl", ""),
				this._schemeElement.getDataStringParam("photoUrl", "")
			]
		);
	};
	BX.Crm.EntityEditorUser.prototype.hasContentToDisplay = function()
	{
		return true;
	};
	BX.Crm.EntityEditorUser.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this._schemeElement.getName();
		var title = this._schemeElement.getTitle();
		var value = this._model.getField(name);

		var formattedName = this._model.getSchemeField(this._schemeElement, "formated", "");
		var position = this._model.getSchemeField(this._schemeElement, "position", "");
		var showUrl = this._model.getSchemeField(this._schemeElement, "showUrl", "", "");
		var photoUrl = this._model.getSchemeField(this._schemeElement, "photoUrl", "");

		this._photoElement = BX.create("a",
			{
				props: { className: "crm-widget-employee-avatar-container", target: "_blank" },
				style:
					{
						backgroundImage: BX.type.isNotEmptyString(photoUrl) ? "url('" + photoUrl + "')" : "",
						backgroundSize: BX.type.isNotEmptyString(photoUrl) ? "30px" : ""
					}
			}
		);

		this._nameElement = BX.create("a",
			{
				props: { className: "crm-widget-employee-name", target: "_blank" },
				text: formattedName
			}
		);

		if (showUrl !== "")
		{
			this._photoElement.href = showUrl;
			this._nameElement.href = showUrl;
		}

		this._positionElement = BX.create("SPAN",
			{
				props: { className: "crm-widget-employee-position" },
				text: position
			}
		);

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));

		var userElement = BX.create("div", { props: { className: "crm-widget-employee-container" } });
		this._editButton = null;
		this._input = null;

		if(this._mode === BX.Crm.EntityEditorMode.edit || (this.isEditInViewEnabled() && !this.isReadOnly()))
		{
			this._input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });
			this._wrapper.appendChild(this._input);

			this._editButton = BX.create("span", { props: { className: "crm-widget-employee-change" }, text: this.getMessage("change") });
			BX.bind(this._editButton, "click", this._editButtonClickHandler);
			userElement.appendChild(this._editButton);
		}

		userElement.appendChild(this._photoElement);
		userElement.appendChild(
			BX.create("span",
				{
					props: { className: "crm-widget-employee-info" },
					children: [ this._nameElement, this._positionElement ]
				}
			)
		);

		this._wrapper.appendChild(
			BX.create("div",
				{ props: { className: "crm-entity-widget-content-block-inner" }, children: [ userElement ] }
			)
		);

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorUser.prototype.doRegisterLayout = function()
	{
		if(this.isInEditMode()
			&& this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual)
		)
		{
			window.setTimeout(BX.delegate(this.openSelector, this), 0);
		}
	};
	BX.Crm.EntityEditorUser.prototype.doClearLayout = function(options)
	{
		this._input = null;
		this._editButton = null;
		this._photoElement = null;
		this._nameElement = null;
		this._positionElement = null;
	};
	BX.Crm.EntityEditorUser.prototype.onEditBtnClick = function(e)
	{
		//If any other control has changed try to switch to edit mode.
		if(this._mode === BX.Crm.EntityEditorMode.view && this.isEditInViewEnabled() && this.getEditor().isChanged())
		{
			this.switchToSingleEditMode();
		}
		else
		{
			this.openSelector();
		}
	};
	BX.Crm.EntityEditorUser.prototype.openSelector = function()
	{
		if(!this._userSelector)
		{
			this._userSelector = BX.Crm.EntityEditorUserSelector.create(
				this._id,
				{ callback: BX.delegate(this.processItemSelect, this) }
			);
		}

		this._userSelector.open(this._editButton);
	};
	BX.Crm.EntityEditorUser.prototype.processItemSelect = function(selector, item)
	{
		var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;
		var editInView = this.isEditInViewEnabled();
		if(isViewMode && !editInView)
		{
			return;
		}

		this._selectedData =
			{
				id: BX.prop.getInteger(item, "entityId", 0),
				photoUrl: BX.prop.getString(item, "avatar", ""),
				formattedNameHtml: BX.prop.getString(item, "name", ""),
				positionHtml: BX.prop.getString(item, "desc", "")
			};

		this._input.value = this._selectedData["id"];
		this._photoElement.style.backgroundImage = this._selectedData["photoUrl"] !== ""
			? "url('" + this._selectedData["photoUrl"] + "')" : "";
		this._photoElement.style.backgroundSize = this._selectedData["photoUrl"] !== ""
			? "30px" : "";

		this._nameElement.innerHTML = this._selectedData["formattedNameHtml"];
		this._positionElement.innerHTML = this._selectedData["positionHtml"];
		this._userSelector.close();

		if(!isViewMode)
		{
			this.markAsChanged();
		}
		else
		{
			this._editor.saveControl(this);
		}
	};
	BX.Crm.EntityEditorUser.prototype.save = function()
	{
		var data = this._schemeElement.getData();
		if(this._selectedData["id"] > 0)
		{
			var itemId = this._selectedData["id"];

			this._model.setField(
				BX.prop.getString(data, "formated"),
				BX.util.htmlspecialcharsback(this._selectedData["formattedNameHtml"])
			);

			this._model.setField(
				BX.prop.getString(data, "position"),
				this._selectedData["positionHtml"] !== "&nbsp;"
					? BX.util.htmlspecialcharsback(this._selectedData["positionHtml"]) : ""
			);

			this._model.setField(
				BX.prop.getString(data, "showUrl"),
				BX.prop.getString(data, "pathToProfile").replace(/#user_id#/ig, itemId)
			);

			this._model.setField(
				BX.prop.getString(data, "photoUrl"),
				this._selectedData["photoUrl"]
			);

			this._model.setField(this.getName(), itemId);
		}
	};
	BX.Crm.EntityEditorUser.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorUser.prototype.getRuntimeValue = function()
	{
		if (this._mode === BX.Crm.EntityEditorMode.edit && this._selectedData["id"] > 0)
		{
			return this._selectedData["id"];
		}
		return "";
	};
	BX.Crm.EntityEditorUser.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorUser.messages;
		return (m.hasOwnProperty(name)
			? m[name]
			: BX.Crm.EntityEditorUser.superclass.getMessage.apply(this, arguments)
		);
	};

	if(typeof(BX.Crm.EntityEditorUser.messages) === "undefined")
	{
		BX.Crm.EntityEditorUser.messages = {};
	}
	BX.Crm.EntityEditorUser.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUser();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorAddress === "undefined")
{
	BX.Crm.EntityEditorAddress = function()
	{
		BX.Crm.EntityEditorAddress.superclass.constructor.apply(this);
		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorAddress, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorAddress.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorAddress.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorAddress.prototype.hasContentToDisplay = function()
	{
		return(this._mode === BX.Crm.EntityEditorMode.edit || this.getViewHtml() !== "");
	};
	BX.Crm.EntityEditorAddress.prototype.getViewHtml = function()
	{
		var viewFieldName = this._schemeElement.getDataStringParam("view", "");
		if(viewFieldName === "")
		{
			viewFieldName = this._schemeElement.getName() + "_HML";
		}
		return this._model.getStringField(viewFieldName, "");
	};
	BX.Crm.EntityEditorAddress.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this._schemeElement.getName();
		var title = this.getTitle();
		var fields = this._schemeElement.getDataObjectParam("fields", {});
		var labels = this._schemeElement.getDataObjectParam("labels", {});
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{

			var fieldsContainer = BX.create("div", { attrs: { className: "crm-entity-widget-content-block-inner-address" } } );

			this._innerWrapper = BX.create("div",
				{
					attrs: { className: "crm-entity-widget-content-block-inner" },
					children:
					[
						BX.create("div",
							{
								props: {className: "crm-entity-widget-content-block-field-container"},
								children: [ fieldsContainer ]
							}
						)
					]
				}
			);

			for(var key in fields)
			{
				if(!fields.hasOwnProperty(key))
				{
					return;
				}

				var field = fields[key];
				var label = BX.prop.getString(labels, key, key);
				this.layoutField(key, field, label, fieldsContainer);
			}

			BX.bindDelegate(
				fieldsContainer,
				"bxchange",
				{ tag: [ "input", "textarea" ] },
				this._changeHandler
			);
		}
		else
		{
			if(this.hasContentToDisplay())
			{
				this._innerWrapper = BX.create("div",
					{
						attrs: { className: "crm-entity-widget-content-block-inner" },
						children:
						[
							BX.create("div",
								{
									attrs: { className: "crm-entity-widget-content-block-inner-text" },
									html: this.getViewHtml()
								}
							)
						]
					}
				);
			}
			else
			{
				this._innerWrapper = BX.create(
					"div",
					{
						attrs: { className: "crm-entity-widget-content-block-inner" },
						text: this.getMessage("isEmpty")
					}
				);
			}
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

		this.registerLayout(options);
		this._hasLayout = true
	};
	BX.Crm.EntityEditorAddress.prototype.layoutField = function(name, field, label, container)
	{
		var alias = BX.prop.getString(field, "NAME", name);
		var value = this._model.getStringField(alias, "");

		container.appendChild(
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-title" },
					children: [
						BX.create(
							"span",
							{
								attrs: { className: "crm-entity-widget-content-block-title-text" },
								text: label
							}
						)
					]
				}
			)
		);

		if(BX.prop.getBoolean(field, "IS_MULTILINE", false))
		{
			container.appendChild(
				BX.create(
					"textarea",
					{
						props: { className: "crm-entity-widget-content-input", name: alias, value: value }
					}
				)
			);
		}
		else
		{
			container.appendChild(
				BX.create(
					"input",
					{
						props: { className: "crm-entity-widget-content-input", name: alias, type: "text", value: value }
					}
				)
			);
		}
	};
	BX.Crm.EntityEditorAddress.prototype.doClearLayout = function(options)
	{
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorAddress.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorAddress();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorMultifieldItem === "undefined")
{
	BX.Crm.EntityEditorMultifieldItem = function()
	{
		this._id = "";
		this._settings = {};
		this._parent = null;
		this._editor = null;

		this._mode = BX.Crm.EntityEditorMode.view;
		this._data = null;
		this._typeId = "";
		this._valueTypeItems = null;

		this._container = null;
		this._wrapper = null;
		this._valueInput = null;
		this._valueTypeInput = null;
		this._valueTypeSelector = null;

		this._deleteButton = null;
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);

		this._isJunked = false;

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorMultifieldItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._parent = BX.prop.get(this._settings, "parent", null);
			this._editor = this._parent.getEditor();

			this._mode = BX.prop.getInteger(this._settings, "mode", BX.Crm.EntityEditorMode.view);

			this._typeId = BX.prop.getString(this._settings, "typeId", "");
			this._data = BX.prop.getObject(this._settings, "data", {});
			this._valueTypeItems = BX.prop.getArray(this._settings, "valueTypeItems", []);

			this._container = BX.prop.getElementNode(this._settings, "container", null);
		},
		getId: function()
		{
			return this._id;
		},
		isEmpty: function()
		{
			return BX.util.trim(this.getValue()) === "";
		},
		getTypeId: function()
		{
			return this._typeId;
		},
		getValue: function()
		{
			return BX.prop.getString(this._data, "VALUE", "");
		},
		getValueId: function()
		{
			return BX.prop.getString(this._data, "ID", "");
		},
		getValueTypeId: function()
		{
			var result = BX.prop.getString(this._data, "VALUE_TYPE", "");
			return result !== "" ? result : this.getDefaultValueTypeId();
		},
		getDefaultValueTypeId: function()
		{
			return this._valueTypeItems.length > 0
				? BX.prop.getString(this._valueTypeItems[0], "VALUE") : "";
		},
		getViewData: function()
		{
			return BX.prop.getObject(this._data, "VIEW_DATA", {});
		},
		resolveValueTypeName: function(valueTypeId)
		{
			if(valueTypeId === "")
			{
				return "";
			}

			for(var i = 0, length = this._valueTypeItems.length; i < length; i++)
			{
				var item = this._valueTypeItems[i];
				if(valueTypeId === BX.prop.getString(item, "VALUE", ""))
				{
					return BX.prop.getString(item, "NAME", valueTypeId);
				}
			}
			return valueTypeId;
		},
		prepareControlName: function(name)
		{
			return this.getTypeId() + "[" + this.getValueId() + "]" + "[" + name + "]";
		},
		getMode: function()
		{
			return this._mode;
		},
		setMode: function(mode)
		{
			this._mode = mode;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
			if(this._hasLayout)
			{
				this.clearLayout();
			}
		},
		focus: function()
		{
			if(this._valueInput)
			{
				BX.focus(this._valueInput);
				BX.Crm.EditorTextHelper.getCurrent().selectAll(this._valueInput);
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._valueInput = null;
			this._valueTypeInput = null;
			this._valueTypeSelector = null;
			this._deleteButton = null;
			var valueTypeId = this.getValueTypeId();
			var value = this.getValue();

			this._wrapper = BX.create("div");
			this._container.appendChild(this._wrapper);

			if(this._mode === BX.Crm.EntityEditorMode.edit)
			{
				BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-container-double");

				this._valueInput = BX.create(
					"input",
					{
						attrs:
							{
								className: "crm-entity-widget-content-input",
								name: this.prepareControlName("VALUE"),
								type: "text",
								value: value
							}
					}
				);
				BX.bind(this._valueInput, "input", BX.delegate(this.onValueChange, this));
				this._wrapper.appendChild(this._valueInput);

				this._valueTypeInput = BX.create(
					"input",
					{
						attrs:
							{
								name: this.prepareControlName("VALUE_TYPE"),
								type: "hidden",
								value: valueTypeId
							}
					}
				);
				this._wrapper.appendChild(this._valueTypeInput);

				this._valueTypeSelector = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-select" },
						text: this.resolveValueTypeName(valueTypeId),
						events: { click: BX.delegate(this.onValueTypeSelectorClick, this) }
					}
				);

				this._wrapper.appendChild(
					BX.create(
						"div",
						{
							attrs: { className: "crm-entity-widget-content-block-select" },
							children: [ this._valueTypeSelector ]
						}
					)
				);

				this._deleteButton = BX.create(
					"div",
					{ attrs: { className: "crm-entity-widget-content-remove-block" } }
				);
				this._wrapper.appendChild(this._deleteButton);
				BX.bind(this._deleteButton, "click", this._deleteButtonHandler);

				if(this._editor.isDuplicateControlEnabled())
				{
					var dupControlConfig = this._parent.getDuplicateControlConfig();
					if(dupControlConfig)
					{
						if(!BX.type.isPlainObject(dupControlConfig["field"]))
						{
							dupControlConfig["field"] = {};
						}
						dupControlConfig["field"]["id"] = this.getValueId();
						dupControlConfig["field"]["element"] = this._valueInput;
						this._editor.getDuplicateManager().registerField(dupControlConfig);
					}
				}
			}
			else if(this._mode === BX.Crm.EntityEditorMode.view && !this.isEmpty())
			{
				BX.addClass(this._wrapper, "crm-entity-widget-content-block-mutlifield");

				var viewData = this.getViewData();
				var html = BX.prop.getString(viewData, "value", "");
				if(html === "")
				{
					html = BX.util.htmlspecialchars(value);
				}

				this._wrapper.appendChild(
					BX.create(
						"span",
						{
							attrs: { className: "crm-entity-widget-content-block-mutlifield-type" },
							text: this.resolveValueTypeName(valueTypeId)
						}
					)
				);

				var contentWrapper = BX.create(
					"span",
					{
						attrs: { className: "crm-entity-widget-content-block-mutlifield-value" },
						html: html
					}
				);
				this._wrapper.appendChild(contentWrapper);

				if(this._parent.getMultifieldType() === "EMAIL")
				{
					var emailLink = contentWrapper.querySelector("a.crm-entity-email");
					if(emailLink)
					{
						BX.bind(emailLink, "click", BX.delegate(this.onEmailClick, this));
					}
				}
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._editor.isDuplicateControlEnabled())
			{
				var dupControlConfig = this._parent.getDuplicateControlConfig();
				if(dupControlConfig)
				{
					if(!BX.type.isPlainObject(dupControlConfig["field"]))
					{
						dupControlConfig["field"] = {};
					}
					dupControlConfig["field"]["id"] = this.getValueId();
					this._editor.getDuplicateManager().unregisterField(dupControlConfig);
				}
			}

			this._wrapper = BX.remove(this._wrapper);
			this._hasLayout = false;
		},
		adjust: function()
		{
			if(this._hasLayout)
			{
				this._wrapper.style.display = this._isJunked ? "none" : "";
			}
		},
		onValueChange: function(e)
		{
			this._parent.processItemChange(this);
		},
		onValueTypeSelectorClick: function(e)
		{
			var menu = [];
			for(var i = 0, length = this._valueTypeItems.length; i < length; i++)
			{
				var item = this._valueTypeItems[i];
				menu.push(
					{
						text: item["NAME"],
						value: item["VALUE"],
						onclick: BX.delegate( this.onValueTypeSelect, this)
					}
				);
			}

			BX.addClass(this._valueTypeSelector, "active");

			BX.PopupMenu.destroy(this._id);
			BX.PopupMenu.show(
				this._id,
				this._valueTypeSelector,
				menu,
				{
					angle: false, width: this._valueTypeSelector.offsetWidth + 'px',
					events: { onPopupClose: BX.delegate(this.onValueTypeMenuClose, this) }
				}
			);

			BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._valueTypeSelector)["width"]);
		},
		onValueTypeMenuClose: function(e)
		{
			BX.removeClass(this._valueTypeSelector, "active");
		},
		onValueTypeSelect: function(e, item)
		{
			BX.removeClass(this._valueTypeSelector, "active");

			this._valueTypeInput.value = item.value;
			this._valueTypeSelector.innerHTML = BX.util.htmlspecialchars(item.text);

			this._parent.processItemChange(this);
			BX.PopupMenu.destroy(this._id);
		},
		isJunked: function()
		{
			return this._isJunked;
		},
		markAsJunked: function(junked)
		{
			junked = !!junked;
			if(this._isJunked !== junked)
			{
				this._isJunked = junked;
				if(this._isJunked)
				{
					this._valueInput.value = "";
				}
				this.adjust();
			}
		},
		onEmailClick: function(e)
		{
			if(BX.CrmActivityEditor)
			{
				var ownerInfo = this._editor.getOwnerInfo();
				var settings =
				{
					ownerType: ownerInfo["ownerType"],
					ownerID: ownerInfo["ownerID"],
					communications:
					[
						{
							entityType: ownerInfo["ownerType"],
							entityId: ownerInfo["ownerID"],
							type: "EMAIL",
							value: this.getValue()
						}
					]
				};
				BX.CrmActivityEditor.addEmail(settings);
			}
			return BX.PreventDefault(e);
		},
		onDeleteButtonClick: function(e)
		{
			this._parent.processItemDeletion(this);
		}
	};
	BX.Crm.EntityEditorMultifieldItem.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultifieldItem();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorMultifieldItemPhone ==="undefined")
{
	BX.Crm.EntityEditorMultifieldItemPhone = function()
	{
		BX.Crm.EntityEditorMultifieldItemPhone.superclass.constructor.apply(this);

		this._maskedPhone = null;
		this._maskedValueInput = null;
		this._countryFlagNode = null;
	};

	BX.extend(BX.Crm.EntityEditorMultifieldItemPhone, BX.Crm.EntityEditorMultifieldItem);

	BX.Crm.EntityEditorMultifieldItemPhone.prototype.layout = function ()
	{
		var self = this;
		if (this._hasLayout)
		{
			return;
		}

		this._valueInput = null;
		this._valueTypeInput = null;
		this._valueTypeSelector = null;
		var valueTypeId = this.getValueTypeId();
		var value = this.getValue();

		this._wrapper = BX.create("div");
		this._container.appendChild(this._wrapper);

		if (this._mode === BX.Crm.EntityEditorMode.edit)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-container-double");

			this._valueInput = BX.create(
				"input",
				{
					attrs: {
						name: this.prepareControlName("VALUE"),
						type: "hidden",
						value: value
					}
				}
			);
			this._wrapper.appendChild(this._valueInput);

			this._wrapper.appendChild(BX.create("div", {
				props: {className: "crm-entity-widget-content-input-phone-wrapper"},
				children: [
					this._countryFlagNode = BX.create("span", {
						props: {className: "crm-entity-widget-content-country-flag"}
					}),
					this._maskedValueInput = BX.create(
						"input",
						{
							attrs: {
								className: "crm-entity-widget-content-input crm-entity-widget-content-input-phone",
								type: "text",
								value: value
							}
						}
					)
				]
			}));

			this._maskedPhone = new BX.PhoneNumber.Input({
				node: this._maskedValueInput,
				flagNode: this._countryFlagNode,
				flagSize: 24,
				onChange: function(e)
				{
					self._valueInput.value = e.value;
					self.onValueChange();
				}
			});

			this._valueTypeInput = BX.create(
				"input",
				{
					attrs: {
						name: this.prepareControlName("VALUE_TYPE"),
						type: "hidden",
						value: valueTypeId
					}
				}
			);
			this._wrapper.appendChild(this._valueTypeInput);

			this._valueTypeSelector = BX.create(
				"div",
				{
					props: {className: "crm-entity-widget-content-select"},
					text: this.resolveValueTypeName(valueTypeId),
					events: {click: BX.delegate(this.onValueTypeSelectorClick, this)}
				}
			);

			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						attrs: {className: "crm-entity-widget-content-block-select"},
						children: [this._valueTypeSelector]
					}
				)
			);

			this._deleteButton = BX.create(
				"div",
				{ attrs: { className: "crm-entity-widget-content-remove-block" } }
			);
			this._wrapper.appendChild(this._deleteButton);
			BX.bind(this._deleteButton, "click", this._deleteButtonHandler);

			if (this._editor.isDuplicateControlEnabled())
			{
				var dupControlConfig = this._parent.getDuplicateControlConfig();
				if (dupControlConfig)
				{
					if (!BX.type.isPlainObject(dupControlConfig["field"]))
					{
						dupControlConfig["field"] = {};
					}
					dupControlConfig["field"]["id"] = this.getValueId();
					dupControlConfig["field"]["element"] = this._maskedValueInput;
					this._editor.getDuplicateManager().registerField(dupControlConfig);
				}
			}
		}
		else if (this._mode === BX.Crm.EntityEditorMode.view && !this.isEmpty())
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-mutlifield");

			var viewData = this.getViewData();
			var html = BX.prop.getString(viewData, "value", "");
			if(html === "")
			{
				html = BX.util.htmlspecialchars(value);
			}

			this._wrapper.appendChild(
				BX.create(
					"span",
					{
						attrs: {className: "crm-entity-widget-content-block-mutlifield-type"},
						text: this.resolveValueTypeName(valueTypeId)
					}
				)
			);

			this._wrapper.appendChild(
				BX.create(
					"span",
					{
						attrs: {className: "crm-entity-widget-content-block-mutlifield-value"},
						html: html
					}
				)
			);
		}

		this._hasLayout = true;
	};
	BX.Crm.EntityEditorMultifieldItemPhone.prototype.focus = function()
	{
		if(this._maskedValueInput)
		{
			BX.focus(this._maskedValueInput);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._maskedValueInput);
		}
	};
	BX.Crm.EntityEditorMultifieldItemPhone.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultifieldItemPhone();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorMultifield === "undefined")
{
	BX.Crm.EntityEditorMultifield = function()
	{
		BX.Crm.EntityEditorMultifield.superclass.constructor.apply(this);
		this._items = null;
		this._itemWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorMultifield, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorMultifield.prototype.doInitialize = function()
	{
		this.initializeItems();
	};
	BX.Crm.EntityEditorMultifield.prototype.initializeItems = function()
	{
		var name = this.getName();
		var data = this._model.getField(name, []);
		if(data.length === 0)
		{
			data.push({ "ID": "n0" });
		}

		for(var i = 0, length = data.length; i < length; i++)
		{
			this.addItem(data[i]);
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.findItemIndex = function(item)
	{
		if(!this._items)
		{
			return -1;
		}

		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}

		return -1;
	};
	BX.Crm.EntityEditorMultifield.prototype.resetItems = function()
	{
		if(this._hasLayout)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].clearLayout();
			}
		}

		this._items = [];
	};
	BX.Crm.EntityEditorMultifield.prototype.deleteItem = function(item)
	{
		if(!this._items)
		{
			return;
		}

		var index = this.findItemIndex(item);
		if(index >= 0)
		{
			this._items[index].markAsJunked(true);
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.reset = function()
	{
		this.resetItems();
		this.initializeItems();
	};
	BX.Crm.EntityEditorMultifield.prototype.hasContentToDisplay = function()
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			return true;
		}

		var length = this._items.length;
		if(length === 0)
		{
			return false;
		}

		for(var i = 0; i < length; i++)
		{
			if(!this._items[i].isEmpty())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityEditorMultifield.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorMultifield.prototype.getContentWrapper = function()
	{
		return this._itemWrapper;
	};
	BX.Crm.EntityEditorMultifield.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorMultifield.prototype.prepareItemsLayout = function()
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			item.setMode(this._mode);
			item.setContainer(this._itemWrapper);
			item.layout();
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorMultifield.prototype.getContentWrapper = function()
	{
		return this._itemWrapper;
	};
	BX.Crm.EntityEditorMultifield.prototype.focus = function()
	{
		if(this._items && this._items.length > 0)
		{
			this._items[this._items.length - 1].focus();
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-multifield" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		this._itemWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this.getTitle()));

		this._itemWrapper = BX.create("div", { attrs: { className: "crm-entity-widget-content-block-inner" } });
		this._wrapper.appendChild(this._itemWrapper);

		if(this.hasContentToDisplay())
		{
			this.prepareItemsLayout();
		}
		else if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._itemWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						attrs: { className: "crm-entity-widget-content-block-add-field" },
						children:
						[
							BX.create(
								"span",
								{
									attrs: { className: "crm-entity-widget-content-add-field" },
									text: this.getMessage("add"),
									events: { click: BX.delegate(this.onAddButtonClick, this) }
								}
							)
						]
					}
				)
			);
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorMultifield.prototype.doClearLayout = function(options)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			item.clearLayout();
			item.setContainer(null);
		}
		this._itemWrapper = null;
	};
	BX.Crm.EntityEditorMultifield.prototype.refreshLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorMultifield.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		this.resetItems();
		BX.cleanNode(this._itemWrapper);

		this.initializeItems();
		if(this.hasContentToDisplay())
		{
			this.prepareItemsLayout();
		}
		else if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			this._itemWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.getMultifieldType = function()
	{
		return this._schemeElement.getDataStringParam("type", "");
	};
	BX.Crm.EntityEditorMultifield.prototype.addItem = function(data)
	{
		var item;
		var typeId = this._schemeElement.getName();

		if(typeId === 'PHONE')
		{
			item = BX.Crm.EntityEditorMultifieldItemPhone.create(
				"",
				{
					parent: this,
					typeId: this._schemeElement.getName(),
					valueTypeItems: this._schemeElement.getDataArrayParam("items", []),
					data: data
				}
			);
		}
		else
		{
			item = BX.Crm.EntityEditorMultifieldItem.create(
				"",
				{
					parent: this,
					typeId: this._schemeElement.getName(),
					valueTypeItems: this._schemeElement.getDataArrayParam("items", []),
					data: data
				}
			);
		}

		if(this._items === null)
		{
			this._items = [];
		}

		this._items.push(item);

		if(this._hasLayout)
		{
			item.setMode(this._mode);
			item.setContainer(this._itemWrapper);
			item.layout();
		}

		return item;
	};
	BX.Crm.EntityEditorMultifield.prototype.onAddButtonClick = function(e)
	{
		this.addItem({ "ID": "n" + this._items.length.toString() });
	};
	BX.Crm.EntityEditorMultifield.prototype.processItemChange = function(item)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorMultifield.prototype.processItemDeletion = function(item)
	{
		this.deleteItem(item);
		this.markAsChanged();
	};
	BX.Crm.EntityEditorMultifield.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultifield();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorClientMode === "undefined")
{
	BX.Crm.EntityEditorClientMode =
	{
		undefined: 0,
		select: 1,
		create: 2,
		edit: 3
	};
}

if(typeof BX.Crm.EntityEditorClientSearchBox === "undefined")
{
	BX.Crm.EntityEditorClientSearchBox = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;

		this._container = null;
		this._wrapper = null;

		this._badgeElement = null;
		this._editButton = null;
		this._changeButton = null;
		this._deleteButton = null;

		this._parentField = null;
		this._entityInfo = null;
		this._entityTypeName = "";

		this._externalEditorPages = null;

		this._searchInput = null;
		this._searchControl = null;

		this._loaderConfig = null;

		this._changeNotifier = null;
		this._titleChangeNotifier = null;
		this._resetNotifier = null;
		this._deletionNotifier = null;

		this._enableDeletion = true;

		this._editButtonHandler = BX.delegate(this.onEditButtonClick, this);
		this._changeButtonHandler = BX.delegate(this.onChangeButtonClick, this);
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._inputFocusHandler = BX.delegate(this.onInputFocus, this);
		this._inputBlurHandler = BX.delegate(this.onInputBlur, this);
		this._inputDblClickHandler = BX.delegate(this.onInputDblClick, this);

		this._mode = BX.Crm.EntityEditorClientMode.undefined;
		this._multifieldChangeNotifier = null;

		this._maskedPhone = null;
		this._emailInput = null;

		this._phoneId = "";
		this._emailId = "";

		this._enableQuickEdit = true;

		this._hasFocus = false;
		this._hasLayout = false;
		this._hasMultifieldLayout = false;
	};
	BX.Crm.EntityEditorClientSearchBox.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._editor = BX.prop.get(this._settings, "editor", null);
			this._parentField = BX.prop.get(this._settings, "parentField", null);
			this._container = BX.prop.getElementNode(this._settings, "container", null);

			var entityInfo = BX.prop.get(this._settings, "entityInfo", null);
			if(entityInfo)
			{
				this._entityInfo = entityInfo;
				this._entityTypeName = entityInfo.getTypeName();
			}
			else
			{
				this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
			}

			this._mode = BX.prop.getInteger(this._settings, "mode", BX.Crm.EntityEditorClientMode.select);
			if(this._mode === BX.Crm.EntityEditorClientMode.edit && !(this._entityInfo && this._entityInfo.canUpdate()))
			{
				this._mode = BX.Crm.EntityEditorClientMode.select;
			}

			this._enableQuickEdit = BX.prop.getBoolean(this._settings, "enableQuickEdit", true);
			this._enableDeletion = BX.prop.getBoolean(this._settings, "enableDeletion", true);
			this._loaderConfig = BX.prop.get(this._settings, "loaderConfig", null);

			this._changeNotifier = BX.CrmNotifier.create(this);
			this._titleChangeNotifier = BX.CrmNotifier.create(this);
			this._deletionNotifier = BX.CrmNotifier.create(this);
			this._resetNotifier = BX.CrmNotifier.create(this);

			this._multifieldChangeNotifier = BX.CrmNotifier.create(this);
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.EntityEditorClientSearchBox.messages, name);
		},
		getEntity: function()
		{
			return this._entityInfo;
		},
		setEntityTypeName: function(entityTypeName)
		{
			if(this._entityTypeName !== entityTypeName)
			{
				this._entityTypeName = entityTypeName;
			}
		},
		setEntity: function(entityInfo, enableNotification)
		{
			var previousEntityInfo = this._entityInfo;

			this._entityInfo = entityInfo;

			if(entityInfo)
			{
				this._entityTypeName = entityInfo.getTypeName();
			}

			if(this._entityInfo && this._entityInfo.getId() === 0)
			{
				this.setMode(BX.Crm.EntityEditorClientMode.create);
			}
			else
			{
				this.setMode(BX.Crm.EntityEditorClientMode.select);
			}

			this.clearMultifieldLayout();
			this.adjust();

			if(enableNotification)
			{
				this._changeNotifier.notify([ this._entityInfo , previousEntityInfo ]);
			}
		},
		setupEntity: function(entityTypeName, entityId)
		{
			if(entityId <= 0)
			{
				return;
			}

			this.setEntityTypeName(entityTypeName);
			this.loadEntityInfo(entityId);
		},
		hasEntity: function()
		{
			return !!this._entityInfo;
		},
		isNewEntity: function()
		{
			return this._entityInfo && this._entityInfo.getId() === 0;
		},
		canUpdateEntity: function()
		{
			return this._entityInfo && this._entityInfo.canUpdate();
		},
		getMode: function()
		{
			return this._mode;
		},
		setMode: function(mode)
		{
			if(!BX.type.isNumber(mode))
			{
				mode = parseInt(mode);
				if(!BX.type.isNumber(mode))
				{
					throw "EntityEditorClientSearchBox: Argument must be integer.";
				}
			}

			if(this._mode === mode)
			{
				return;
			}

			this._mode = mode;
		},
		layout: function(options)
		{
			if(this._hasLayout)
			{
				return;
			}

			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-row" } });
			this.innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-inner" } });

			var anchor = BX.prop.getElementNode(options, "anchor", null);
			if(anchor)
			{
				this._container.insertBefore(this._wrapper, anchor);
			}
			else
			{
				this._container.appendChild(this._wrapper);
			}

			this._wrapper.appendChild(this.innerWrapper);

			var boxWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
			this.innerWrapper.appendChild(boxWrapper);

			var icon = BX.create("div", { props: { className: "crm-entity-widget-img-box" } });
			if(this._entityTypeName === BX.CrmEntityType.names.company)
			{
				BX.addClass(icon, "crm-entity-widget-img-company");
			}
			else if(this._entityTypeName === BX.CrmEntityType.names.contact)
			{
				BX.addClass(icon, "crm-entity-widget-img-contact");
			}
			boxWrapper.appendChild(icon);

			this._searchInput = BX.create("input",
				{
					props:
						{
							type: "text",
							placeholder: BX.prop.getString(this._settings, "placeholder", ""),
							className: "crm-entity-widget-content-input crm-entity-widget-content-search-input",
							autocomplete: "nope"
						}
				}
			);
			boxWrapper.appendChild(this._searchInput);
			BX.bind(this._searchInput, "focus", this._inputFocusHandler);
			BX.bind(this._searchInput, "blur", this._inputBlurHandler);
			BX.bind(this._searchInput, "dblclick", this._inputDblClickHandler);

			this._badgeElement = BX.create("div", { props: { className: "crm-entity-widget-badge" } });
			boxWrapper.appendChild(this._badgeElement);

			this._editButton = BX.create("div", { props: { className: "crm-entity-widget-btn-edit" } });
			boxWrapper.appendChild(this._editButton);

			BX.bind(this._editButton, "click", this._editButtonHandler);

			this._changeButton = BX.create(
				"div",
				{
					props:
						{
							className: "crm-entity-widget-btn-select",
							title: this.getMessage(this._entityTypeName.toLowerCase() + "ChangeButtonHint")
						}
				}
			);
			boxWrapper.appendChild(this._changeButton);

			BX.bind(this._changeButton, "click", this._changeButtonHandler);

			if(this._entityInfo)
			{
				//Move it in BX.UI.Dropdown
				this._searchInput.value = this._entityInfo.getTitle();
			}

			this._searchControl = new BX.UI.Dropdown(
				{
					searchAction: "crm.api.entity.search",
					searchOptions: { types: [ this._entityTypeName ], scope: "index" },
					searchResultRenderer: null,
					targetElement: this._searchInput,
					items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
					enableCreation: BX.prop.getBoolean(this._settings, "enableCreation", false),
					enableCreationOnBlur: this._enableQuickEdit,
					context: { origin: "crm.entity.editor", isEmbedded: this._editor.isEmbedded()  },
					messages:
						{
							creationLegend: this.getMessage(this._entityTypeName.toLowerCase() + "ToCreateLegend"),
							notFound: this.getMessage("notFound")
						},
					events:
						{
							onSelect: this.onEntitySelect.bind(this),
							onAdd: this.onEntityAdd.bind(this),
							onReset: this.onEntityReset.bind(this)
						}
				}
			);

			this._deleteButton = BX.create("div", { props: { className: "crm-entity-widget-btn-close" } });
			if(!this._enableDeletion)
			{
				this._deleteButton.style.display = "none";
			}
			this.innerWrapper.appendChild(this._deleteButton);
			BX.bind(this._deleteButton, "click", this._deleteButtonHandler);

			window.setTimeout(function(){ this.adjust(options); }.bind(this), 0);
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.clearMultifieldLayout();

			BX.unbind(this._editButton, "click", this._editButtonHandler);
			BX.unbind(this._deleteButton, "click", this._deleteButtonHandler);
			BX.unbind(this._changeButton, "click", this._changeButtonHandler);

			this._deleteButton = this._changeButton = this._searchControl = this._badgeElement = null;
			this._wrapper = BX.remove(this._wrapper);

			this._hasLayout = false;
		},
		prepareMultifieldLayout: function()
		{
			if(this._hasMultifieldLayout)
			{
				return;
			}

			this._multifieldContainer = BX.create("div", { props: { className: "crm-entity-widget-content-multifield" } });
			this._wrapper.appendChild(this._multifieldContainer);

			this._phoneInput = BX.create("input", { props: { type: "hidden" } });
			this._countryFlagNode = BX.create("span", { props: {className: "crm-entity-widget-content-country-flag"}});
			this._maskedPhoneInput = BX.create("input",
				{
					props:
						{
							type: "text",
							placeholder: BX.message("CRM_EDITOR_PHONE"),
							className: "crm-entity-widget-content-input crm-entity-widget-content-input-phone",
							autocomplete: "nope"
						}
				}
			);

			this._multifieldContainer.appendChild(
				BX.create("div",
					{
						props: { className: "crm-entity-widget-content-multifield-item" },
						children:
							[
								this._countryFlagNode,
								this._maskedPhoneInput,
								this._phoneInput
							]
					}
				)
			);

			this._maskedPhone = new BX.PhoneNumber.Input(
				{
					node: this._maskedPhoneInput,
					flagNode: this._countryFlagNode,
					flagSize: 24,
					onChange: BX.delegate(this.onPhoneChange, this)
				}
			);

			this._emailInput = BX.create("input",
				{
					props:
						{
							type: "text",
							placeholder: BX.message("CRM_EDITOR_EMAIL"),
							className: "crm-entity-widget-content-input",
							autocomplete: "nope"
						}
				}
			);
			BX.bind(this._emailInput, "input", BX.delegate(this.onEmailChange, this));

			this._multifieldContainer.appendChild(
				BX.create("div",
					{
						props: { className: "crm-entity-widget-content-multifield-item" },
						children: [ this._emailInput ]
					}
				)
			);

			var emailId = "", phoneId = "", fieldCounter = 0;
			this._phoneId = this._emailId = "";
			if(this._entityInfo)
			{
				var phones = this._entityInfo.getPhones();
				if(phones.length === 0)
				{
					this._maskedPhone.setValue((this._phoneInput.value = ""));
				}
				else
				{
					this._phoneId = BX.prop.getString(phones[0], "ID", "");
					phoneId = this.parseMultifieldPseudoId(this._phoneId);
					if(phoneId >= 0)
					{
						fieldCounter = phoneId + 1;
					}
					this._maskedPhone.setValue((this._phoneInput.value = BX.prop.getString(phones[0], "VALUE", "")));
				}

				var emails = this._entityInfo.getEmails();
				if(emails.length === 0)
				{
					this._emailInput.value = "";
				}
				else
				{
					this._emailId = BX.prop.getString(emails[0], "ID", "");
					emailId = this.parseMultifieldPseudoId(this._emailId);
					if(emailId >= 0)
					{
						fieldCounter = emailId + 1;
					}
					this._emailInput.value = BX.prop.getString(emails[0], "VALUE", "");
				}
			}
			else
			{
				this._emailInput.value = "";
				this._maskedPhone.setValue((this._phoneInput.value = ""));
			}

			if(this._phoneId === "")
			{
				this._phoneId = this.prepareMultifieldPseudoId(fieldCounter);
				fieldCounter++;
			}

			if(this._emailId === "")
			{
				this._emailId = this.prepareMultifieldPseudoId(fieldCounter);
				//fieldCounter++;
			}

			this._hasMultifieldLayout = true;
		},
		clearMultifieldLayout: function()
		{
			if(!this._hasMultifieldLayout)
			{
				return;
			}

			this._multifieldContainer = BX.remove(this._multifieldContainer);

			this._phoneInput = this._maskedPhone = this._emailInput = null;
			this._phoneId = this._emailId = "";

			this._hasMultifieldLayout = false;
		},
		prepareMultifieldPseudoId: function(num)
		{
			return ("n" + num.toString());
		},
		parseMultifieldPseudoId: function(pseudoId)
		{
			var m = pseudoId.match(/^n(\d+)/);
			return BX.type.isArray(m) && m.length > 1 ? parseInt(m[1]) : -1;
		},
		isNeedToSave: function()
		{
			return (this._mode === BX.Crm.EntityEditorClientMode.create
				|| this._mode === BX.Crm.EntityEditorClientMode.edit
			);
		},
		save: function()
		{
			if(this._mode !== BX.Crm.EntityEditorClientMode.create && this._mode !== BX.Crm.EntityEditorClientMode.edit)
			{
				return;
			}

			if(!this._entityInfo)
			{
				return;
			}

			if(this._searchInput && this._searchInput.value !== this._entityInfo.getTitle())
			{
				this._entityInfo.setTitle(this._searchInput.value);
			}

			if(this._phoneInput)
			{
				this._entityInfo.setMultifieldById(
					{ "ID": this._phoneId, "TYPE_ID": "PHONE", "VALUE": this._phoneInput.value },
					this._phoneId
				);
			}

			if(this._emailInput)
			{
				this._entityInfo.setMultifieldById(
					{ "ID": this._emailId, "TYPE_ID": "EMAIL", "VALUE": this._emailInput.value },
					this._emailId
				);
			}
		},
		focus: function()
		{
			if(this._searchInput)
			{
				this._searchInput.focus();
			}
		},
		hasValue: function()
		{
			return !!this._entityInfo;
		},
		addMultifieldChangeListener: function(listener)
		{
			this._multifieldChangeNotifier.addListener(listener);
		},
		removeMultifieldChangeListener: function(listener)
		{
			this._multifieldChangeNotifier.removeListener(listener);
		},
		addTitleChangeListener: function(listener)
		{
			this._titleChangeNotifier.addListener(listener);
		},
		removeTitleChangeListener: function(listener)
		{
			this._titleChangeNotifier.removeListener(listener);
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		addResetListener: function(listener)
		{
			this._resetNotifier.addListener(listener);
		},
		removeResetListener: function(listener)
		{
			this._resetNotifier.removeListener(listener);
		},
		isQuickEditEnabled: function()
		{
			return this._enableQuickEdit;
		},
		enableQuickEdit: function(enable)
		{
			enable = !!enable;
			if(this._enableQuickEdit === enable)
			{
				return;
			}

			this._enableQuickEdit = enable;

			if(this._searchControl)
			{
				this._searchControl.enableCreationOnBlur = this._enableQuickEdit;
			}
		},
		enableDeletion: function(enable)
		{
			enable = !!enable;
			if(this._enableDeletion === enable)
			{
				return;
			}

			this._enableDeletion = enable;

			if(this._hasLayout)
			{
				this._deleteButton.style.display = enable ? "" : "none";
			}
		},
		adjust: function(options)
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			if(this._hasFocus)
			{
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-complete");
				BX.addClass(this._wrapper, "crm-entity-widget-content-block-inprogress");
			}
			else
			{
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-inprogress");
				BX.addClass(this._wrapper, "crm-entity-widget-content-block-complete");
			}

			if(this.hasEntity())
			{
				if(this._mode === BX.Crm.EntityEditorClientMode.create
					|| this._mode === BX.Crm.EntityEditorClientMode.edit
				)
				{
					this._badgeElement.innerHTML = this.getMessage(
						this._mode === BX.Crm.EntityEditorClientMode.create
							? this._entityTypeName.toLowerCase() + "ToCreateTag"
							: "entityEditTag"
					);

					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-selection-mode");

					BX.addClass(
						this._wrapper,
						this._mode === BX.Crm.EntityEditorClientMode.create
							? "crm-entity-widget-content-block-new-mode"
							: "crm-entity-widget-content-block-edit-mode"
					);

					if(this._searchInput.value.length < 0)
					{
						BX.removeClass(this._wrapper, "crm-entity-widget-content-block-textreset");
					}
					else
					{
						BX.addClass(this._wrapper, "crm-entity-widget-content-block-textreset");
					}

					this.prepareMultifieldLayout();

					if(this._searchControl)
					{
						this._searchControl.isDisabled = true;
					}
				}
				else if(this._mode === BX.Crm.EntityEditorClientMode.select)
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-badge");
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-selection-mode");

					this.clearMultifieldLayout();

					if(this._searchControl)
					{
						this._searchControl.isDisabled = false;
					}
				}

				if(this._searchInput.value.length > 0)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-textreset");
				}
				else
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-textreset");
				}
			}
			else
			{
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-new-mode");
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-edit-mode");
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-selection-mode");
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-textreset");
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-complete");
				BX.removeClass(this._wrapper, "crm-entity-widget-content-block-inprogress");

				this.clearMultifieldLayout();

				if(this._searchControl)
				{
					this._searchControl.isDisabled = false;
				}
			}
		},
		getParentContextId: function()
		{
			return this._parentField.getContextId();
		},
		getEntityCreateUrl: function(entityTypeName)
		{
			return this._parentField.getEntityCreateUrl(entityTypeName);
		},
		getEntityEditUrl: function(entityTypeName, entityId)
		{
			return this._parentField.getEntityEditUrl(entityTypeName, entityId);
		},
		openEntityCreatePage: function(params)
		{
			var url = this.getEntityCreateUrl(this._entityTypeName);
			if(url === "")
			{
				return;
			}

			var contextId = this.getParentContextId() + "_" + BX.util.getRandomString(6).toUpperCase();

			var urlParams = BX.prop.getObject(params, "urlParams", {});
			urlParams["external_context_id"] = contextId;
			url = BX.util.add_url_param(url, urlParams);

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			if(!this._externalEditorPages)
			{
				this._externalEditorPages = {};
			}
			this._externalEditorPages[contextId] = url;
			BX.Crm.Page.open(url);
		},
		openEntityEditPage: function(params)
		{
			var url = this.getEntityEditUrl(this._entityTypeName, BX.prop.getInteger(params, "entityId", 0));
			if(url === "")
			{
				return;
			}

			var contextId = this.getParentContextId() + "_" + BX.util.getRandomString(6).toUpperCase();

			var urlParams = BX.prop.getObject(params, "urlParams", {});
			urlParams["external_context_id"] = contextId;
			url = BX.util.add_url_param(url, urlParams);

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			if(!this._externalEditorPages)
			{
				this._externalEditorPages = {};
			}
			this._externalEditorPages[contextId] = url;
			BX.Crm.Page.open(url);
		},
		onExternalEvent: function(params)
		{
			var eventName = BX.prop.getString(params, "key", "");

			if(eventName !== "onCrmEntityCreate" && eventName !== "onCrmEntityUpdate")
			{
				return;
			}

			var value = BX.prop.getObject(params, "value", {});
			var contextId = BX.prop.getString(value, "context", "");

			if(BX.prop.getString(this._externalEditorPages, contextId, "") === "")
			{
				return;
			}

			var entityTypeName = BX.prop.getString(value, "entityTypeName", "");
			var entityId = BX.prop.getInteger(value, "entityId", 0);

			if(this._entityTypeName !== entityTypeName)
			{
				return;
			}

			if(eventName === "onCrmEntityUpdate" && !(this._entityInfo && this._entityInfo.getId() === entityId))
			{
				return;
			}

			this.setupEntity(this._entityTypeName, entityId);

			window.setTimeout(
				function()
				{
					BX.Crm.Page.close(
						this._externalEditorPages[contextId],
						{ identity: { key: "external_context_id", value: contextId } }
					);
					delete this._externalEditorPages[contextId];
				}.bind(this),
				100
			);
		},
		onPhoneChange: function(e)
		{
			if(!this._phoneInput)
			{
				return;
			}

			if(this._phoneInput.value !== e.value)
			{
				this._phoneInput.value = e.value;
				this._multifieldChangeNotifier.notify();
			}
		},
		onEmailChange: function(e)
		{
			this._multifieldChangeNotifier.notify();
		},
		onEditButtonClick: function()
		{
			if(this.isNewEntity()
				|| !this.canUpdateEntity()
				|| this.getMode() === BX.Crm.EntityEditorClientMode.edit
			)
			{
				return;
			}

			if(this._searchControl)
			{
				this._searchControl.destroyPopupWindow();
			}

			if(!this.isQuickEditEnabled())
			{
				this.openEntityEditPage(
					{
						entityId: this._entityInfo.getId(),
						urlParams: { init_mode: "edit" }
					}
				);
				return;
			}

			this.setMode(BX.Crm.EntityEditorClientMode.edit);
			this.clearMultifieldLayout();
			this.adjust();
		},
		onChangeButtonClick: function(e)
		{
			this.setMode(BX.Crm.EntityEditorClientMode.select);

			if(this._searchInput)
			{
				this._searchInput.focus();
			}

			if(this._searchControl)
			{
				this._searchControl.getPopupWindow().show();
			}
		},
		onDeleteButtonClick: function(e)
		{
			if(this._enableDeletion)
			{
				this._deletionNotifier.notify([ this._entityInfo ]);
			}
		},
		onInputFocus: function(e)
		{
			this._hasFocus = true;
			window.setTimeout(BX.delegate(this.adjust, this), 150);
		},
		onInputBlur: function(e)
		{
			this._hasFocus = false;
			window.setTimeout(BX.delegate(this.adjust, this), 300);

			if(this._mode === BX.Crm.EntityEditorClientMode.edit && this._searchInput.value !== this._entityInfo.getTitle())
			{
				this._titleChangeNotifier.notify([]);
			}
		},
		onInputDblClick: function(e)
		{
		},
		onEntityAdd: function(sender, item)
		{
			var title = BX.prop.getString(item, "title", "");
			if(title === "")
			{
				return;
			}

			if(this._searchControl)
			{
				this._searchControl.destroyPopupWindow();
			}

			if(!this.isQuickEditEnabled())
			{
				this.openEntityCreatePage({ urlParams: { title: title } });
				return;
			}

			var entityData = { typeName: this._entityTypeName, title: title };
			if(BX.validation.checkIfEmail(title))
			{
				entityData["title"] = this.getMessage(
					this._entityTypeName === BX.CrmEntityType.names.contact ? "unnamed" : "untitled"
				);
				entityData["advancedInfo"] =
					{
						"multiFields": [ { "ID": this.prepareMultifieldPseudoId(0), "TYPE_ID": "EMAIL", "VALUE": title } ]
					};
			}
			else if(BX.validation.checkIfPhone(title))
			{
				entityData["title"] = this.getMessage(
					this._entityTypeName === BX.CrmEntityType.names.contact ? "unnamed" : "untitled"
				);
				entityData["advancedInfo"] =
					{
						"multiFields": [ { "ID": this.prepareMultifieldPseudoId(0), "TYPE_ID": "PHONE", "VALUE": title } ]
					};
			}

			if(this._searchInput.value !== entityData["title"])
			{
				this._searchInput.value = entityData["title"];
			}

			this.setEntity(BX.CrmEntityInfo.create(entityData), true);

			this._searchControl.destroyPopupWindow();
		},
		onEntityReset: function()
		{
			this.reset();
			this._searchControl.destroyPopupWindow();
		},
		onEntitySelect: function(sender, item)
		{
			var entityTypeName = BX.prop.getString(item, "type", "");
			var entityId = BX.prop.getInteger(item, "id", 0);
			var title = BX.prop.getString(item, "title", "");

			this.setEntityTypeName(entityTypeName);
			if(entityId <= 0)
			{
				return;
			}

			this.loadEntityInfo(entityId);

			this._searchInput.value = title;
			this._searchControl.destroyPopupWindow();
		},
		onEntityInfoLoad: function(sender, result)
		{
			var entityData = BX.prop.getObject(result, "DATA", null);
			if(entityData)
			{
				this.setEntity(BX.CrmEntityInfo.create(entityData), true);
				if(this._hasLayout)
				{
					var anchor = this._wrapper.nextSibling;
					this.clearLayout();
					this.layout({ anchor: anchor });
				}
			}
		},
		reset: function()
		{
			this._searchInput.value = "";

			var previousEntityInfo = this._entityInfo;
			this._entityInfo = null;
			this._resetNotifier.notify([ previousEntityInfo ]);

			window.setTimeout(BX.delegate(this.adjust, this), 150);
		},
		loadEntityInfo: function(entityId)
		{
			var loader = BX.prop.getObject(this._loaderConfig, this._entityTypeName, null);
			if(!loader)
			{
				return;
			}

			BX.CrmDataLoader.create(
				this._id,
				{
					serviceUrl: loader["url"],
					action: loader["action"],
					params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": entityId, "NORMALIZE_MULTIFIELDS": "Y" }
				}
			).load(BX.delegate(this.onEntityInfoLoad, this));
		}
	};
	if(typeof(BX.Crm.EntityEditorClientSearchBox.messages) === "undefined")
	{
		BX.Crm.EntityEditorClientSearchBox.messages = {};
	}
	BX.Crm.EntityEditorClientSearchBox.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorClientSearchBox();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorClientLayoutType === "undefined")
{
	BX.Crm.EntityEditorClientLayoutType =
	{
		undefined: 0,
		contactCompany: 1,
		companyContact: 2,
		contact: 3,
		company: 4,

		names:
		{
			contactCompany: "CONTACT_COMPANY",
			companyContact: "COMPANY_CONTACT",
			contact: "CONTACT",
			company: "COMPANY"
		},

		resolveId: function(name)
		{
			name = name.toUpperCase();
			if(this.names.contactCompany === name)
			{
				return this.contactCompany;
			}
			else if(this.names.companyContact === name)
			{
				return this.companyContact;
			}
			else if(this.names.contact === name)
			{
				return this.contact;
			}
			else if(this.names.company === name)
			{
				return this.company;
			}

			return this.undefined;
		}
	};
}

if(typeof BX.Crm.EntityEditorClientLight === "undefined")
{
	BX.Crm.EntityEditorClientLight = function()
	{
		BX.Crm.EntityEditorClientLight.superclass.constructor.apply(this);
		this._map = null;
		this._info = null;

		this._primaryLoaderConfig = null;
		this._secondaryLoaderConfig = null;

		this._dataElements = null;

		this._companyInfos = null;
		this._contactInfos = null;

		this._enableCompanyMultiplicity = false;

		this._companyTitleWrapper = null;
		this._contactTitleWrapper = null;

		this._companySearchBoxes = null;
		this._contactSearchBoxes = null;

		this._companyPanels = null;
		this._contactPanels = null;

		this._companyWrapper = null;
		this._contactWrapper = null;

		this._addCompanyButton = null;
		this._addContactButton = null;

		this._innerWrapper = null;

		this._layoutType = BX.Crm.EntityEditorClientLayoutType.undefined;
		this._enableLayoutTypeChange = false;
		this._enableQuickEdit = null;

		this._companyNameChangeHandler = BX.delegate(this.onCompanyNameChange, this);
		this._companyChangeHandler = BX.delegate(this.onCompanyChange, this);
		this._companyDeletionHandler = BX.delegate(this.onCompanyDelete, this);
		this._companyResetHandler = BX.delegate(this.onCompanyReset, this);
		this._contactNameChangeHandler = BX.delegate(this.onContactNameChange, this);
		this._contactChangeHandler = BX.delegate(this.onContactChange, this);
		this._contactDeletionHandler = BX.delegate(this.onContactDelete, this);
		this._contactResetHandler = BX.delegate(this.onContactReset, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);
		this._multifieldChangeHandler = BX.delegate(this.onMultifieldChange, this);
	};
	BX.extend(BX.Crm.EntityEditorClientLight, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorClientLight.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorClientLight.superclass.doInitialize.apply(this);
		this._map = this._schemeElement.getDataObjectParam("map", {});

		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClientLight.prototype.initializeFromModel = function()
	{
		this._companyInfos = BX.Collection.create();
		this._contactInfos = BX.Collection.create();

		this._info = this._model.getSchemeField(this._schemeElement, "info", {});
		this.initializeEntityInfos(BX.prop.getArray(this._info, "COMPANY_DATA", []), this._companyInfos);
		this.initializeEntityInfos(BX.prop.getArray(this._info, "CONTACT_DATA", []), this._contactInfos);

		this._enableCompanyMultiplicity = this._schemeElement.getDataBooleanParam("enableCompanyMultiplicity", false);

		var loaders = this._schemeElement.getDataObjectParam("loaders", {});
		this._primaryLoaderConfig = BX.prop.getObject(loaders, "primary", {});
		this._secondaryLoaderConfig = BX.prop.getObject(loaders, "secondary", {});

		//region Layout Type
		this._enableLayoutTypeChange = true;

		var fixedLayoutTypeName = this._schemeElement.getDataStringParam("fixedLayoutType", "");
		if(fixedLayoutTypeName !== "")
		{
			var fixedLayoutType = BX.Crm.EntityEditorClientLayoutType.resolveId(fixedLayoutTypeName);
			if(fixedLayoutType !== BX.Crm.EntityEditorClientLayoutType.undefined)
			{
				this._layoutType = fixedLayoutType;
				this._enableLayoutTypeChange = false;
			}
		}
		//endregion
	};
	BX.Crm.EntityEditorClientLight.prototype.initializeEntityInfos = function(sourceData, collection)
	{
		for(var i = 0, length = sourceData.length; i < length; i++)
		{
			var info = BX.CrmEntityInfo.create(sourceData[i]);
			if(info.getId() > 0)
			{
				collection.add(info);
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.createDataElement = function(key, value)
	{
		var name = BX.prop.getString(this._map, key, "");

		if(name === "")
		{
			return;
		}

		var input = BX.create("input", { attrs: { name: name, type: "hidden" } });
		if(BX.type.isNotEmptyString(value))
		{
			input.value = value;
		}

		if(!this._dataElements)
		{
			this._dataElements = {};
		}

		this._dataElements[key] = input;
		if(this._wrapper)
		{
			this._wrapper.appendChild(input);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorClientLight.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorClientLight.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.getOwnerTypeName = function()
	{
		return this._editor.getEntityTypeName();
	};
	BX.Crm.EntityEditorClientLight.prototype.getOwnerTypeId = function()
	{
		return this._editor.getEntityTypeId();
	};
	BX.Crm.EntityEditorClientLight.prototype.getOwnerId = function()
	{
		return this._editor.getEntityId();
	};
	BX.Crm.EntityEditorClientLight.prototype.hasCompanies = function()
	{
		return this._companyInfos !== null && this._companyInfos.length() > 0;
	};
	BX.Crm.EntityEditorClientLight.prototype.hasContacts = function()
	{
		return this._contactInfos !== null && this._contactInfos.length() > 0;
	};
	BX.Crm.EntityEditorClientLight.prototype.addCompany = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			if(!this._companyInfos)
			{
				this._companyInfos = BX.Collection.create();
			}

			this._companyInfos.add(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.removeCompany = function(entityInfo)
	{
		if(this._companyInfos && (entityInfo instanceof BX.CrmEntityInfo))
		{
			this._companyInfos.remove(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.addContact = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			if(!this._contactInfos)
			{
				this._contactInfos = BX.Collection.create();
			}

			this._contactInfos.add(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContact = function(entityInfo)
	{
		if(this._contactInfos && (entityInfo instanceof BX.CrmEntityInfo))
		{
			this._contactInfos.remove(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.hasContentToDisplay = function()
	{
		return(
			this.hasCompanies()
			|| (this._contactInfos !== null && this._contactInfos.length() > 0)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorClientLight.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorClientLight.prototype.reset = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClientLight.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.initializeFromModel();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.getEntityCreateUrl = function(entityTypeName)
	{
		return this._editor.getEntityCreateUrl(entityTypeName);
	};
	BX.Crm.EntityEditorClientLight.prototype.getEntityEditUrl = function(entityTypeName, entityId)
	{
		return this._editor.getEntityEditUrl(entityTypeName, entityId);
	};
	BX.Crm.EntityEditorClientLight.prototype.doSetMode = function(mode)
	{
		this.rollback();
	};
	BX.Crm.EntityEditorClientLight.prototype.doPrepareContextMenuItems = function(menuItems)
	{
		menuItems.push({ delimiter: true });

		if(this._enableLayoutTypeChange)
		{
			var layoutType = this.getLayoutType();
			if(layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact
				|| layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany
			)
			{
				menuItems.push(
					{
						value: "set_layout_contact",
						text: this.getMessage("disableCompany")
					}
				);

				menuItems.push(
					{
						value: "set_layout_company",
						text: this.getMessage("disableContact")
					}
				);
			}
			else if(layoutType === BX.Crm.EntityEditorClientLayoutType.company)
			{
				menuItems.push(
					{
						value: "set_layout_company_contact",
						text: this.getMessage("enableContact")
					}
				);
			}
			else if(layoutType === BX.Crm.EntityEditorClientLayoutType.contact)
			{
				menuItems.push(
					{
						value: "set_layout_contact_company",
						text: this.getMessage("enableCompany")
					}
				);
			}

			if(layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact)
			{
				menuItems.push({ delimiter: true });
				menuItems.push(
					{
						value: "set_layout_contact_company",
						text: this.getMessage("displayContactAtFirst")
					}
				);
			}
			else if(layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany)
			{
				menuItems.push({ delimiter: true });
				menuItems.push(
					{
						value: "set_layout_company_contact",
						text: this.getMessage("displayCompanyAtFirst")
					}
				);
			}

			menuItems.push({ delimiter: true });
		}

		if(this.isQuickEditEnabled())
		{
			menuItems.push(
				{
					value: "disable_quick_edit",
					text: this.getMessage("disableQuickEdit")
				}
			);
		}
		else
		{
			menuItems.push(
				{
					value: "enable_quick_edit",
					text: this.getMessage("enableQuickEdit")
				}
			);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.processContextMenuCommand = function(e, command)
	{
		if(command === "set_layout_contact_company")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.contactCompany) }.bind(this),
				100
			);
		}
		else if(command === "set_layout_company_contact")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.companyContact) }.bind(this),
				100
			);
		}
		else if(command === "set_layout_contact")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.contact) }.bind(this),
				100
			);
		}
		else if(command === "set_layout_company")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.company) }.bind(this),
				100
			);
		}
		else if(command === "disable_quick_edit")
		{
			this.enableQuickEdit(false);
		}
		else if(command === "enable_quick_edit")
		{
			this.enableQuickEdit(true);
		}
		BX.Crm.EntityEditorClientLight.superclass.processContextMenuCommand.apply(this, arguments)
	};
	//region Quick Edit
	BX.Crm.EntityEditorClientLight.prototype.isQuickEditEnabled = function()
	{
		if(this._enableQuickEdit === null)
		{
			this._enableQuickEdit = this._editor.getConfigOption("enableQuickEdit", "Y") === "Y";
		}
		return this._enableQuickEdit;
	};
	BX.Crm.EntityEditorClientLight.prototype.enableQuickEdit = function(enable)
	{
		enable = !!enable;

		if(this._enableQuickEdit === null)
		{
			this._enableQuickEdit = this._editor.getConfigOption("enableQuickEdit", "Y") === "Y";
		}

		if(this._enableQuickEdit === enable)
		{
			return;
		}

		this._enableQuickEdit = enable;
		this._editor.setConfigOption("enableQuickEdit", enable ? "Y" : "N");

		var i, length;
		if(this._companySearchBoxes)
		{
			for(i = 0, length = this._companySearchBoxes.length; i < length; i++)
			{
				this._companySearchBoxes[i].enableQuickEdit(enable);
			}
		}

		if(this._contactSearchBoxes)
		{
			for(i = 0, length = this._contactSearchBoxes.length; i < length; i++)
			{
				this._contactSearchBoxes[i].enableQuickEdit(enable);
			}
		}
	};
	//endregion
	//region Layout Type
	BX.Crm.EntityEditorClientLight.prototype.isCompanyEnabled = function()
	{
		var layoutType = this.getLayoutType();
		return (
			layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.company
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.isContactEnabled = function()
	{
		var layoutType = this.getLayoutType();
		return (
			layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.contact
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.getLayoutType = function()
	{
		if(this._layoutType <= 0)
		{
			var str = this._editor.getConfigOption("client_layout", "");
			var num = parseInt(str);
			if(isNaN(num) || num <= 0)
			{
				num = BX.Crm.EntityEditorClientLayoutType.companyContact;
			}
			this._layoutType = num;
		}
		return this._layoutType;
	};
	BX.Crm.EntityEditorClientLight.prototype.setLayoutType = function(layoutType)
	{
		if(!BX.type.isNumber(layoutType))
		{
			layoutType = parseInt(layoutType);
		}

		if(isNaN(layoutType) || layoutType <= 0)
		{
			return;
		}

		if(layoutType === this._layoutType)
		{
			return;
		}

		this._layoutType = layoutType;

		this._editor.setConfigOption("client_layout", layoutType);
		this.refreshLayout();
	};
	//endregion
	BX.Crm.EntityEditorClientLight.prototype.layout = function(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this.getTitle()));

		if(!this.hasContentToDisplay() && this.isInViewMode())
		{
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-inner" },
					text: this.getMessage("isEmpty")
				}
			);
			this._wrapper.appendChild(this._innerWrapper);
		}
		else
		{
			this._innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner" } });
			this._wrapper.appendChild(this._innerWrapper);

			var layoutType = this.getLayoutType();

			if(this.isInEditMode())
			{
				var fieldContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container" } });
				this._innerWrapper.appendChild(fieldContainer);
				this._innerContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container-inner" } });
				fieldContainer.appendChild(this._innerContainer);
			}
			else
			{
				BX.addClass(this._wrapper, "crm-entity-widget-participants-block");
				BX.addClass(this._innerWrapper, "crm-entity-widget-inner");
			}

			if(this.isContactEnabled() && this.isCompanyEnabled())
			{
				if(layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany)
				{
					this.renderContact();
					this.renderCompany();
				}
				else if(layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact)
				{
					this.renderCompany();
					this.renderContact();
				}
			}
			else
			{
				if(this.isContactEnabled())
				{
					this.renderContact();
				}

				if(this.isCompanyEnabled())
				{
					this.renderCompany();
				}
			}
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);

		this._entityEditParams = {};
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorClientLight.prototype.createAdditionalWrapperBlock = function()
	{
	};
	BX.Crm.EntityEditorClientLight.prototype.switchToSingleEditMode = function(targetNode)
	{
		this._entityEditParams = {};

		if(this.isInViewMode() && this.isQuickEditEnabled() && BX.type.isElementNode(targetNode))
		{
			var isFound = false;

			if(BX.isParentForNode(this._companyTitleWrapper, targetNode))
			{
				isFound = true;

				this._entityEditParams["enableCompany"] = true;
				this._entityEditParams["companyIndex"] = 0;
			}

			if(!isFound && BX.isParentForNode(this._contactTitleWrapper, targetNode))
			{
				isFound = true;

				this._entityEditParams["enableContact"] = true;
				this._entityEditParams["contactIndex"] = 0;
			}

			var i, length;
			if(!isFound && this._companyPanels !== null)
			{
				for(i = 0, length = this._companyPanels.length; i < length; i++)
				{
					if(this._companyPanels[i].checkOwership(targetNode))
					{
						isFound = true;

						this._entityEditParams["enableCompany"] = true;
						this._entityEditParams["companyIndex"] = i;

						break;
					}
				}
			}

			if(!isFound && this._contactPanels !== null)
			{
				for(i = 0, length = this._contactPanels.length; i < length; i++)
				{
					if(this._contactPanels[i].checkOwership(targetNode))
					{
						isFound = true;

						this._entityEditParams["enableContact"] = true;
						this._entityEditParams["contactIndex"] = i;

						break;
					}
				}
			}

			if(!BX.prop.getBoolean(this._entityEditParams, "enableCompany", false)
				&& !BX.prop.getBoolean(this._entityEditParams, "enableContact", false)
			)
			{
				var layoutType = this.getLayoutType();
				if(layoutType === BX.Crm.EntityEditorClientLayoutType.contact
					|| layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany
				)
				{
					this._entityEditParams["enableContact"] = true;
					this._entityEditParams["contactIndex"] = 0;
				}
				else if(layoutType === BX.Crm.EntityEditorClientLayoutType.company
					|| layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact
				)
				{
					this._entityEditParams["enableCompany"] = true;
				}
			}
		}
		BX.Crm.EntityEditorClientLight.superclass.switchToSingleEditMode.apply(this, arguments);
	};
	BX.Crm.EntityEditorClientLight.prototype.getEntityInitialMode = function(entityTypeId)
	{
		if(!this.isQuickEditEnabled())
		{
			return BX.Crm.EntityEditorClientMode.select;
		}

		if(!this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual))
		{
			return BX.Crm.EntityEditorClientMode.edit;
		}

		return BX.prop.getBoolean(
			this._entityEditParams,
			entityTypeId === BX.CrmEntityType.enumeration.contact ? "enableContact" : "enableCompany",
			false
		) ? BX.Crm.EntityEditorClientMode.edit : BX.Crm.EntityEditorClientMode.select;
	};
	BX.Crm.EntityEditorClientLight.prototype.resolveDataTagName = function(entityTypeName)
	{
		var compoundInfos = this._schemeElement.getDataArrayParam("compound", null);
		if(BX.type.isArray(compoundInfos))
		{
			for(var i = 0, length = compoundInfos.length; i < length; i++)
			{
				if(BX.prop.getString(compoundInfos[i], "entityTypeName", "") === entityTypeName)
				{
					return BX.prop.getString(compoundInfos[i], "tagName", "");
				}
			}
		}
		return "";
	};
	BX.Crm.EntityEditorClientLight.prototype.renderContact = function()
	{
		var caption = this._schemeElement.getDataStringParam("contactLegend", "");
		if(caption === "")
		{
			caption = BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.contact);
		}

		if(this.isInEditMode())
		{
			this._contactWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-inner-row" } });
			this._innerContainer.appendChild(this._contactWrapper);

			this._contactTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" },
					children:
						[
							BX.create("span",
								{
									props: { className: "crm-entity-widget-content-block-title-text" },
									text: caption
								}
							)
						]
				}
			);
			this._contactWrapper.appendChild(this._contactTitleWrapper);

			this._addContactButton = BX.create(
				"span",
				{
					props: { className: "crm-entity-widget-actions-btn-add" },
					text: this.getMessage("addParticipant")
				}
			);
			this._contactWrapper.appendChild(this._addContactButton);
			BX.bind(this._addContactButton, "click", BX.delegate(this.onContactAddButtonClick, this));

			this._contactSearchBoxes = [];
			if(this._contactInfos.length() > 0)
			{
				var mode = this.getEntityInitialMode(BX.CrmEntityType.enumeration.contact);
				var editIndex = mode === BX.Crm.EntityEditorClientMode.edit
					? BX.prop.getInteger(this._entityEditParams, "contactIndex", -1) : -1;

				for(var i = 0, length = this._contactInfos.length(); i < length; i++)
				{
					var currentMode = mode;
					if(currentMode === BX.Crm.EntityEditorClientMode.edit && !(editIndex === i || editIndex === -1))
					{
						currentMode = BX.Crm.EntityEditorClientMode.select
					}

					this.addContactSearchBox(
						this.createContactSearchBox({ entityInfo: this._contactInfos.get(i), mode: currentMode })
					);
				}
			}
			else
			{
				this.addContactSearchBox(this.createContactSearchBox());
			}
		}
		else if(this._contactInfos.length() > 0 && this.isContactEnabled())
		{
			this._contactTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" }
				}
			);

			var innerTitleWrapper = BX.create("span",
				{
					props: { className: "crm-entity-widget-content-subtitle-text" },
					children: [ BX.create("span", { text: caption }) ]
				}
			);
			this._contactTitleWrapper.appendChild(innerTitleWrapper);


			if(!this.isReadOnly())
			{
				innerTitleWrapper.appendChild(
					BX.create("span",
						{
							props: { className: "crm-entity-card-widget-title-edit-icon" }
						}
					)
				);
			}

			var innerWrapperContainer = BX.create("div", {
				props: { className: "crm-entity-widget-content-block-inner-container" }
			});

			this._innerWrapper.appendChild(innerWrapperContainer);
			innerWrapperContainer.appendChild(this._contactTitleWrapper);


			var dataTagName = this.resolveDataTagName(BX.CrmEntityType.names.contact);
			if(dataTagName === "")
			{
				dataTagName = "CONTACT_IDS";
			}
			
			var additionalBlock = BX.create("div", {
				props: { className: "crm-entity-widget-before-action" },
				attrs: { "data-field-tag": dataTagName }
			});
			innerWrapperContainer.appendChild(additionalBlock);


			this._contactPanels = [];
			for(i = 0, length = this._contactInfos.length(); i < length; i++)
			{
				var contactInfo = this._contactInfos.get(i);

				var contactSettings =
					{
						editor: this,
						entityInfo: contactInfo,
						enableEntityTypeCaption: false,
						enableRequisite: false,
						enableCommunications: this._editor.areCommunicationControlsEnabled(),
						mode: BX.Crm.EntityEditorMode.view
					};

				//HACK: Enable requisite selection due to editor is not support it.
				var enableRequisite = i === 0 && !(this.isCompanyEnabled() && this.hasCompanies());
				if(enableRequisite)
				{
					contactSettings['enableRequisite'] = true;
					contactSettings['requisiteBinding'] = this._model.getField("REQUISITE_BINDING", {});
					contactSettings['requisiteSelectUrl'] = this._editor.getEntityRequisiteSelectUrl(
						BX.CrmEntityType.names.contact,
						contactInfo.getId()
					);
					contactSettings['requisiteMode'] = BX.Crm.EntityEditorMode.edit;
				}

				var contactPanel = BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + contactInfo.getId().toString(),
					contactSettings
				);

				this._contactPanels.push(contactPanel);
				contactPanel.setContainer(innerWrapperContainer);
				contactPanel.layout();

				if(enableRequisite)
				{
					contactPanel.addRequisiteChangeListener(this._requisiteChangeHandler);
				}
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.renderCompany = function()
	{
		var caption = this._schemeElement.getDataStringParam("companyLegend", "");
		if(caption === "")
		{
			caption = BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.company);
		}

		if(this.isInEditMode())
		{
			this._companyWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-inner-row" } });
			this._innerContainer.appendChild(this._companyWrapper);

			this._companyTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" },
					children:
						[
							BX.create("span",
								{
									props: { className: "crm-entity-widget-content-block-title-text" },
									text: caption
								}
							)
						]
				}
			);
			this._companyWrapper.appendChild(this._companyTitleWrapper);

			if(this._enableCompanyMultiplicity)
			{
				this._addCompanyButton = BX.create(
					"span",
					{
						props: { className: "crm-entity-widget-actions-btn-add" },
						text: this.getMessage("addParticipant")
					}
				);
				this._companyWrapper.appendChild(this._addCompanyButton);
				BX.bind(this._addCompanyButton, "click", BX.delegate(this.onCompanyAddButtonClick, this));
			}

			this._companySearchBoxes = [];
			if(this._companyInfos.length() > 0)
			{
				var mode = this.getEntityInitialMode(BX.CrmEntityType.enumeration.company);
				var editIndex = mode === BX.Crm.EntityEditorClientMode.edit
					? BX.prop.getInteger(this._entityEditParams, "companyIndex", -1) : -1;

				for(var i = 0, length = this._companyInfos.length(); i < length; i++)
				{
					var currentMode = mode;
					if(currentMode === BX.Crm.EntityEditorClientMode.edit && !(editIndex === i || editIndex === -1))
					{
						currentMode = BX.Crm.EntityEditorClientMode.select
					}

					this.addCompanySearchBox(
						this.createCompanySearchBox({ entityInfo: this._companyInfos.get(i), mode: currentMode })
					);
				}
			}
			else
			{
				this.addCompanySearchBox(this.createCompanySearchBox());
			}
		}
		else if(this.isCompanyEnabled() && this._companyInfos.length() > 0)
		{
			this._companyTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" }
				}
			);

			var innerTitleWrapper = BX.create("span",
				{
					props: { className: "crm-entity-widget-content-subtitle-text" },
					children: [ BX.create("span", { text: caption }) ]
				}
			);
			this._companyTitleWrapper.appendChild(innerTitleWrapper);
			if(!this.isReadOnly())
			{
				innerTitleWrapper.appendChild(
					BX.create("span",
						{
							props: { className: "crm-entity-card-widget-title-edit-icon" }
						}
					)
				);
			}



			var innerWrapperContainer = BX.create("div", {
				props: { className: "crm-entity-widget-content-block-inner-container" }
			});

			this._innerWrapper.appendChild(innerWrapperContainer);
			innerWrapperContainer.appendChild(this._companyTitleWrapper);

			var dataTagName = this.resolveDataTagName(BX.CrmEntityType.names.company);
			if(dataTagName === "")
			{
				dataTagName = this._enableCompanyMultiplicity ? "COMPANY_IDS" : "COMPANY_ID";
			}

			var additionalBlock = BX.create("div", {
				props: { className: "crm-entity-widget-before-action" },
				attrs: { "data-field-tag": dataTagName }
			});
			innerWrapperContainer.appendChild(additionalBlock);

			this._companyPanels = [];
			for(i = 0, length = this._companyInfos.length(); i < length; i++)
			{
				var companyInfo = this._companyInfos.get(i);

				var companySettings =
					{
						editor: this,
						entityInfo: companyInfo,
						enableEntityTypeCaption: false,
						enableRequisite: false,
						enableCommunications: this._editor.areCommunicationControlsEnabled(),
						mode: BX.Crm.EntityEditorMode.view
					};

				//HACK: Enable requisite selection due to editor is not support it.
				var enableRequisite = i === 0;
				if(enableRequisite)
				{
					companySettings['enableRequisite'] = true;
					companySettings['requisiteBinding'] = this._model.getField("REQUISITE_BINDING", {});
					companySettings['requisiteSelectUrl'] = this._editor.getEntityRequisiteSelectUrl(
						BX.CrmEntityType.names.company,
						companyInfo.getId()
					);
					companySettings['requisiteMode'] = BX.Crm.EntityEditorMode.edit;
				}

				var companyPanel = BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + companyInfo.getId().toString(),
					companySettings
				);

				this._companyPanels.push(companyPanel);
				companyPanel.setContainer(innerWrapperContainer);
				companyPanel.layout();

				if(enableRequisite)
				{
					companyPanel.addRequisiteChangeListener(this._requisiteChangeHandler);
				}
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.createCompanySearchBox = function(params)
	{
		var entityInfo = BX.prop.get(params, "entityInfo", null);
		if(entityInfo !== null && !(entityInfo instanceof BX.CrmEntityInfo))
		{
			entityInfo = null;
		}

		var enableCreation = this._editor.canCreateCompany();
		if(enableCreation)
		{
			//Check if creation of company is disabled by configuration.
			enableCreation = BX.prop.getBoolean(
				this._schemeElement.getDataObjectParam("creation", {}),
				BX.CrmEntityType.names.company.toLowerCase(),
				true
			);
		}

		return(
			BX.Crm.EntityEditorClientSearchBox.create(
				this._id,
				{
					entityTypeName: BX.CrmEntityType.names.company,
					entityInfo: entityInfo,
					enableCreation: enableCreation,
					enableDeletion: false,
					enableQuickEdit: this.isQuickEditEnabled(),
					mode: BX.prop.getInteger(params, "mode", BX.Crm.EntityEditorClientMode.select),
					editor: this._editor,
					loaderConfig: this._primaryLoaderConfig,
					lastEntityInfos: this._model.getSchemeField(this._schemeElement, "lastCompanyInfos", []),
					container: this._companyWrapper,
					placeholder: this.getMessage("companySearchPlaceholder"),
					parentField: this
				}
			)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.addCompanySearchBox = function(searchBox, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		this._companySearchBoxes.push(searchBox);

		var layoutOptions = BX.prop.getObject(options, "layoutOptions", {});
		if(this._addCompanyButton)
		{
			layoutOptions["anchor"] = this._addCompanyButton;
		}

		searchBox.layout(layoutOptions);

		searchBox.addResetListener(this._companyResetHandler);
		searchBox.addTitleChangeListener(this._companyNameChangeHandler);
		searchBox.addChangeListener(this._companyChangeHandler);
		searchBox.addDeletionListener(this._companyDeletionHandler);
		searchBox.addMultifieldChangeListener(this._multifieldChangeHandler);

		var enableDeletion = this._companySearchBoxes.length > 1;
		for(var i = 0, length = this._companySearchBoxes.length; i < length; i++)
		{
			this._companySearchBoxes[i].enableDeletion(enableDeletion);
		}

		return searchBox;
	};
	BX.Crm.EntityEditorClientLight.prototype.removeCompanySearchBox = function(searchBox)
	{
		var index = this.findCompanySearchBoxIndex(searchBox);
		if(index < 0)
		{
			return;
		}

		searchBox.removeResetListener(this._companyResetHandler);
		searchBox.removeTitleChangeListener(this._companyNameChangeHandler);
		searchBox.removeChangeListener(this._companyChangeHandler);
		searchBox.removeDeletionListener(this._companyDeletionHandler);
		searchBox.removeMultifieldChangeListener(this._multifieldChangeHandler);

		searchBox.clearLayout();

		this._companySearchBoxes.splice(index, 1);

		var enableDeletion = this._companySearchBoxes.length > 1;
		for(var i = 0, length = this._companySearchBoxes.length; i < length; i++)
		{
			this._companySearchBoxes[i].enableDeletion(enableDeletion);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.findCompanySearchBoxIndex = function(companySearchBox)
	{
		for(var i = 0, length = this._companySearchBoxes.length; i < length; i++)
		{
			if(companySearchBox === this._companySearchBoxes[i])
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorClientLight.prototype.createContactSearchBox = function(params)
	{
		var entityInfo = BX.prop.get(params, "entityInfo", null);
		if(entityInfo !== null && !(entityInfo instanceof BX.CrmEntityInfo))
		{
			entityInfo = null;
		}

		var enableCreation = this._editor.canCreateContact();
		if(enableCreation)
		{
			//Check if creation of contact is disabled by configuration.
			enableCreation = BX.prop.getBoolean(
				this._schemeElement.getDataObjectParam("creation", {}),
				BX.CrmEntityType.names.contact.toLowerCase(),
				true
			);
		}

		return(
			BX.Crm.EntityEditorClientSearchBox.create(
				this._id,
				{
					entityTypeName: BX.CrmEntityType.names.contact,
					entityInfo: entityInfo,
					enableCreation: enableCreation,
					enableDeletion: BX.prop.getBoolean(params, "enableDeletion", true),
					enableQuickEdit: this.isQuickEditEnabled(),
					mode: BX.prop.getInteger(params, "mode", BX.Crm.EntityEditorClientMode.select),
					editor: this._editor,
					loaderConfig: this._primaryLoaderConfig,
					lastEntityInfos: this._model.getSchemeField(this._schemeElement, "lastContactInfos", []),
					container: this._contactWrapper,
					placeholder: this.getMessage("contactSearchPlaceholder"),
					parentField: this
				}
			)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.addContactSearchBox = function(searchBox, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		this._contactSearchBoxes.push(searchBox);

		var layoutOptions = BX.prop.getObject(options, "layoutOptions", {});
		if(this._addContactButton)
		{
			layoutOptions["anchor"] = this._addContactButton;
		}

		searchBox.layout(layoutOptions);

		searchBox.addResetListener(this._contactResetHandler);
		searchBox.addTitleChangeListener(this._contactNameChangeHandler);
		searchBox.addChangeListener(this._contactChangeHandler);
		searchBox.addDeletionListener(this._contactDeletionHandler);
		searchBox.addMultifieldChangeListener(this._multifieldChangeHandler);

		var enableDeletion = this._contactSearchBoxes.length > 1;
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			this._contactSearchBoxes[i].enableDeletion(enableDeletion);
		}

		return searchBox;
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContactSearchBox = function(searchBox)
	{
		var index = this.findContactSearchBoxIndex(searchBox);
		if(index < 0)
		{
			return;
		}

		searchBox.removeResetListener(this._contactResetHandler);
		searchBox.removeTitleChangeListener(this._contactNameChangeHandler);
		searchBox.removeChangeListener(this._contactChangeHandler);
		searchBox.removeDeletionListener(this._contactDeletionHandler);
		searchBox.removeMultifieldChangeListener(this._multifieldChangeHandler);

		searchBox.clearLayout();

		this._contactSearchBoxes.splice(index, 1);

		var enableDeletion = this._contactSearchBoxes.length > 1;
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			this._contactSearchBoxes[i].enableDeletion(enableDeletion);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContactAllSearchBoxes = function()
	{
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			var searchBox = this._contactSearchBoxes[i];

			searchBox.removeResetListener(this._contactResetHandler);
			searchBox.removeChangeListener(this._contactChangeHandler);
			searchBox.removeDeletionListener(this._contactDeletionHandler);
			searchBox.clearLayout();
		}

		this._contactSearchBoxes = [];
	};
	BX.Crm.EntityEditorClientLight.prototype.findContactSearchBoxIndex = function(contactSearchBox)
	{
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			if(contactSearchBox === this._contactSearchBoxes[i])
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorClientLight.prototype.save = function()
	{
		this._info["COMPANY_DATA"] = this.saveEntityInfos(this._companySearchBoxes, this._companyInfos);
		this._info["CONTACT_DATA"] = this.saveEntityInfos(this._contactSearchBoxes, this._contactInfos);
	};
	BX.Crm.EntityEditorClientLight.prototype.saveEntityInfos = function(searchBoxes, entityInfos)
	{
		var i, length;

		if(searchBoxes !== null)
		{
			for(i = 0, length = searchBoxes.length; i < length; i++)
			{
				if(searchBoxes[i].isNeedToSave())
				{
					searchBoxes[i].save();
				}
			}
		}

		var data = [];
		if(entityInfos !== null)
		{
			var infoItems = entityInfos.getItems();
			for(i = 0, length = infoItems.length; i < length; i++)
			{
				data.push(infoItems[i].getSettings());
			}
		}
		return data;
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactAddButtonClick = function(e)
	{
		this.addContactSearchBox(this.createContactSearchBox()).focus();
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyAddButtonClick = function(e)
	{
		if(this._enableCompanyMultiplicity)
		{
			this.addCompanySearchBox(this.createCompanySearchBox()).focus();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyReset = function(sender, previousEntityInfo)
	{
		if(previousEntityInfo)
		{
			this.removeCompany(previousEntityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyNameChange = function(sender)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
		var isChanged = false;

		if(previousEntityInfo)
		{
			this.removeCompany(previousEntityInfo);
			isChanged = true;
		}

		if(currentEntityInfo)
		{
			this.addCompany(currentEntityInfo);
			isChanged = true;
		}

		if(!isChanged)
		{
			return;
		}

		this.markAsChanged();

		if(!this._enableCompanyMultiplicity)
		{
			if(currentEntityInfo.getId() > 0)
			{
				var entityLoader = BX.prop.getObject(
					this._secondaryLoaderConfig,
					BX.CrmEntityType.names.company,
					null
				);

				if(entityLoader)
				{
					BX.CrmDataLoader.create(
						this._id,
						{
							serviceUrl: entityLoader["url"],
							action: entityLoader["action"],
							params:
								{
									"PRIMARY_TYPE_NAME": BX.CrmEntityType.names.company,
									"PRIMARY_ID": currentEntityInfo.getId(),
									"SECONDARY_TYPE_NAME": BX.CrmEntityType.names.contact,
									"OWNER_TYPE_NAME": this.getOwnerTypeName()
								}
						}
					).load(BX.delegate(this.onContactInfosLoad, this));
				}
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyDelete = function(sender, currentEntityInfo)
	{
		if(currentEntityInfo)
		{
			this._companyInfos.remove(currentEntityInfo);
			this.markAsChanged();
		}

		this.removeCompanySearchBox(sender);
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
		var isChanged = false;

		if(previousEntityInfo)
		{
			this.removeContact(previousEntityInfo);
			isChanged = true;
		}

		if(currentEntityInfo)
		{
			this.addContact(currentEntityInfo);
			isChanged = true;
		}

		if(isChanged)
		{
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactNameChange = function(sender)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactDelete = function(sender, currentEntityInfo)
	{
		if(currentEntityInfo)
		{
			this._contactInfos.remove(currentEntityInfo);
			this.markAsChanged();
		}

		this.removeContactSearchBox(sender);
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactReset = function(sender, previousEntityInfo)
	{
		if(previousEntityInfo)
		{
			this.removeContact(previousEntityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactInfosLoad = function(sender, result)
	{
		var i, length;
		var entityInfos = [];
		var entityData = BX.type.isArray(result['ENTITY_INFOS']) ? result['ENTITY_INFOS'] : [];
		for(i = 0, length = entityData.length; i < length; i++)
		{
			entityInfos.push(BX.CrmEntityInfo.create(entityData[i]));
		}

		this._contactInfos.removeAll();
		for(i = 0, length = entityInfos.length; i < length; i++)
		{
			this._contactInfos.add(entityInfos[i]);
		}
		this.markAsChanged();

		this.removeContactAllSearchBoxes();
		if(entityInfos.length > 0)
		{
			for(i = 0, length = entityInfos.length; i < length; i++)
			{
				this.addContactSearchBox(
					this.createContactSearchBox(
						{ entityInfo: entityInfos[i] }
					)
				);
			}
		}
		else
		{
			this.addContactSearchBox(this.createContactSearchBox());
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onRequisiteChange = function(sender, eventArgs)
	{
		if(this.isInEditMode())
		{
			this.markAsChanged();
		}
		else
		{
			//Save immediately
			this._editor.saveData(
				{
					'REQUISITE_ID': BX.prop.getInteger(eventArgs, "requisiteId", 0),
					'BANK_DETAIL_ID': BX.prop.getInteger(eventArgs, "bankDetailId", 0)
				}
			);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onMultifieldChange = function(sender)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClientLight.prototype.prepareEntitySubmitData = function(searchBoxes)
	{
		if(!BX.type.isArray(searchBoxes))
		{
			return [];
		}

		var results = [];
		for(var i = 0, length = searchBoxes.length; i < length; i++)
		{
			var entity = searchBoxes[i].getEntity();
			if(!entity)
			{
				continue;
			}

			var data = {};

			var mode = searchBoxes[i].getMode();
			if(mode === BX.Crm.EntityEditorClientMode.select
				|| (mode === BX.Crm.EntityEditorClientMode.edit && entity.getTitle() !== "")
			)
			{
				data["id"] = entity.getId();
			}
			if(mode === BX.Crm.EntityEditorClientMode.create
				|| (mode === BX.Crm.EntityEditorClientMode.edit && entity.getTitle() !== "")
			)
			{
				data["title"] = entity.getTitle();
				data["multifields"] = entity.getMultifields();
			}

			results.push(data);
		}
		return results;
	};
	BX.Crm.EntityEditorClientLight.prototype.onBeforeSubmit = function()
	{
		var data = {};
		if(this.isCompanyEnabled())
		{
			data["COMPANY_DATA"] = this.prepareEntitySubmitData(this._companySearchBoxes);
		}
		if(this.isContactEnabled())
		{
			data["CONTACT_DATA"] = this.prepareEntitySubmitData(this._contactSearchBoxes);
		}

		this.createDataElement("data", JSON.stringify(data));
	};
	if(typeof(BX.Crm.EntityEditorClientLight.messages) === "undefined")
	{
		BX.Crm.EntityEditorClientLight.messages = {};
	}
	BX.Crm.EntityEditorClientLight.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorClientLight();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorClient === "undefined")
{
	BX.Crm.EntityEditorClient = function()
	{
		BX.Crm.EntityEditorClient.superclass.constructor.apply(this);
		this._info = null;

		this._enablePrimaryEntity = true;
		this._primaryEntityTypeName = "";
		this._primaryEntityInfo = null;
		this._primaryEntityBindingInfos = null;
		this._primaryEntityEditor = null;

		this._secondaryEntityTypeName = "";
		this._secondaryEntityInfos = null;

		this._secondaryEntityEditor = null;
		this._dataElements = null;
		this._map = null;
		this._bindingTracker = null;

		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorClient, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorClient.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorClient.superclass.doInitialize.apply(this);
		this._map = this._schemeElement.getDataObjectParam("map", {});
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClient.prototype.initializeFromModel = function()
	{
		this._info = this._model.getSchemeField(this._schemeElement, "info", {});

		this._enablePrimaryEntity = this._schemeElement.getDataBooleanParam(
			"enablePrimaryEntity",
			true
		);

		if(this._enablePrimaryEntity)
		{
			var primaryEntityData = BX.prop.getObject(this._info, "PRIMARY_ENTITY_DATA", null);
			var primaryEntityInfo = primaryEntityData ? BX.CrmEntityInfo.create(primaryEntityData) : null;

			if(primaryEntityInfo)
			{
				this.setPrimaryEntity(primaryEntityInfo);
			}
			else
			{
				this.setPrimaryEntityTypeName(
					this._schemeElement.getDataStringParam(
						"primaryEntityTypeName",
						BX.CrmEntityType.names.company
					)
				);
			}
		}

		this.setSecondaryEntityTypeName(
			this._schemeElement.getDataStringParam(
				"secondaryEntityTypeName",
				BX.CrmEntityType.names.contact
			)
		);

		var secondaryEntityData = null;
		var secondaryEntityDataKey =  this._schemeElement.getDataStringParam("secondaryEntityInfo", "");
		if(secondaryEntityDataKey !== "")
		{
			secondaryEntityData = this._model.getField(secondaryEntityDataKey, [])
		}
		else
		{
			secondaryEntityData = BX.prop.getArray(this._info, "SECONDARY_ENTITY_DATA", []);
		}

		this._secondaryEntityInfos = BX.Collection.create();
		this._primaryEntityBindingInfos = BX.Collection.create();
		var companyEntityId = primaryEntityInfo && primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.company
			? primaryEntityInfo.getId() : 0;
		var i, length, info;
		for(i = 0, length = secondaryEntityData.length; i < length; i++)
		{
			info = BX.CrmEntityInfo.create(secondaryEntityData[i]);
			if(info.getId() <= 0)
			{
				continue;
			}

			if(companyEntityId > 0 && info.checkEntityBinding(BX.CrmEntityType.names.company, companyEntityId))
			{
				this._primaryEntityBindingInfos.add(info);
			}
			else
			{
				this._secondaryEntityInfos.add(info);
			}
		}
		this._bindingTracker = BX.Crm.EntityBindingTracker.create();
	};
	BX.Crm.EntityEditorClient.prototype.hasContentToDisplay = function()
	{
		return(this._primaryEntityInfo !== null
			|| (this._secondaryEntityInfos !== null && this._secondaryEntityInfos.length() > 0)
		);
	};
	BX.Crm.EntityEditorClient.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorClient.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorClient.prototype.getEntityCreateUrl = function(entityTypeName)
	{
		return this._editor.getEntityCreateUrl(entityTypeName);
	};
	BX.Crm.EntityEditorClient.prototype.getEntityRequisiteSelectUrl = function(entityTypeName, entityId)
	{
		return this._editor.getEntityRequisiteSelectUrl(entityTypeName, entityId);
	};
	BX.Crm.EntityEditorClient.prototype.reset = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClient.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.initializeFromModel();
		}
	};
	BX.Crm.EntityEditorClient.prototype.doSetMode = function(mode)
	{
		this.rollback();
	};
	BX.Crm.EntityEditorClient.prototype.createDataElement = function(key, value)
	{
		var name = BX.prop.getString(this._map, key, "");

		if(name === "")
		{
			return;
		}

		var input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });

		if(!this._dataElements)
		{
			this._dataElements = {};
		}

		this._dataElements[key] = input;
		if(this._wrapper)
		{
			this._wrapper.appendChild(input);
		}
	};
	BX.Crm.EntityEditorClient.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var title = this._schemeElement.getTitle();

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}


		this._dataElements = {};

		if(!this.hasContentToDisplay() && this.isInViewMode())
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					text: this.getMessage("isEmpty")
				}
			);
		}
		else
		{
			this._innerWrapper = BX.create("div",{ props: { className: "crm-entity-widget-clients-block" } });
			this._innerWrapper.appendChild(this.createTitleNode(title));

			if(this.isInEditMode())
			{
				if(this._enablePrimaryEntity)
				{
					this.createDataElement("primaryEntityType", this.getPrimaryEntityTypeName());
					this.createDataElement("primaryEntityId", this.getPrimaryEntityId());

					this.createDataElement("unboundSecondaryEntityIds", "");
					this.createDataElement("boundSecondaryEntityIds", "");
				}

				this.createDataElement("secondaryEntityType", this.getSecondaryEntityTypeName());
				this.createDataElement("secondaryEntityIds", this.getAllSecondaryEntityIds().join(","));
			}

			var editorWrapper = BX.create(
				"div",
				{
					props: { className: this.isInEditMode() ? "crm-entity-widget-content-block-clients" : "" }
				}
			);
			this._innerWrapper.appendChild(editorWrapper);

			var primaryEntityAnchor = BX.create("div", {});
			editorWrapper.appendChild(primaryEntityAnchor);

			var loaders = this._schemeElement.getDataObjectParam("loaders", {});
			var primaryLoader = BX.prop.getObject(loaders, "primary", {});
			var secondaryLoader = BX.prop.getObject(loaders, "secondary", {});

			if(this._enablePrimaryEntity)
			{
				this._primaryEntityEditor = BX.Crm.PrimaryClientEditor.create(
					this._id + "_PRIMARY",
					{
						"entityInfo": this._primaryEntityInfo,
						"entityTypeName": this._primaryEntityTypeName,
						"lastEntityInfos":	this._model.getSchemeField(
							this._schemeElement,
							"lastPrimaryEntityInfos",
							[]
						),
						"loaderConfig": primaryLoader,
						"requisiteBinding": this._model.getField("REQUISITE_BINDING", {}),
						"editor": this,
						"mode": this._mode,
						"onChange": BX.delegate(this.onPrimaryEntityChange, this),
						"onDelete": BX.delegate(this.onPrimaryEntityDelete, this),
						"onBindingAdd": BX.delegate(this.onPrimaryEntityBindingAdd, this),
						"onBindingDelete": BX.delegate(this.onPrimaryEntityBindingDelete, this),
						"onBindingRelease": BX.delegate(this.onPrimaryEntityBindingRelease, this),
						"container": editorWrapper,
						"achor": primaryEntityAnchor
					}
				);
				this._primaryEntityEditor.layout();
			}

			var secondaryEntityWrapper = BX.create("div", { props: { className: "crm-entity-widget-participants-container" } });
			editorWrapper.appendChild(secondaryEntityWrapper);
			this._secondaryEntityEditor = BX.Crm.SecondaryClientEditor.create(
				this._id + "_SECONDARY",
				{
					"entityInfos":     this._secondaryEntityInfos.getItems(),
					"entityTypeName":  this._secondaryEntityTypeName,
					"entityLegend":    this._schemeElement.getDataStringParam("secondaryEntityLegend", ""),
					"lastEntityInfos":	this._model.getSchemeField(
						this._schemeElement,
						"lastSecondaryEntityInfos",
						[]
					),
					"primaryLoader":   primaryLoader,
					"secondaryLoader": secondaryLoader,
					"mode":            this._mode,
					"onAdd":           BX.delegate(this.onSecondaryEntityAdd, this),
					"onDelete":        BX.delegate(this.onSecondaryEntityDelete, this),
					"onBeforeAdd":     BX.delegate(this.onSecondaryEntityBeforeAdd, this),
					"editor":          this,
					"container":       secondaryEntityWrapper
				}
			);
			this._secondaryEntityEditor.layout();

			if(this._primaryEntityEditor)
			{
				if(this.isInEditMode())
				{
					this._secondaryEntityEditor.setVisible(this._primaryEntityInfo !== null);
				}
				else
				{
					this._secondaryEntityEditor.setVisible(this._secondaryEntityInfos.length() > 0);
				}
			}
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

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorClient.prototype.doClearLayout = function(options)
	{
		if(this._primaryEntityEditor)
		{
			this._primaryEntityEditor.clearLayout();
			this._primaryEntityEditor = null;
		}

		if(this._secondaryEntityEditor)
		{
			this._secondaryEntityEditor.clearLayout();
			this._secondaryEntityEditor = null;
		}

		for(var key in this._dataElements)
		{
			if(this._dataElements.hasOwnProperty(key))
			{
				BX.remove(this._dataElements[key]);
			}
		}
		this._dataElements = null;

		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorClient.prototype.getOwnerTypeName = function()
	{
		return this._editor.getEntityTypeName();
	};
	BX.Crm.EntityEditorClient.prototype.getOwnerTypeId = function()
	{
		return this._editor.getEntityTypeId();
	};
	BX.Crm.EntityEditorClient.prototype.getOwnerId = function()
	{
		return this._editor.getEntityId();
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityTypeName = function()
	{
		return this._primaryEntityTypeName;
	};
	BX.Crm.EntityEditorClient.prototype.setPrimaryEntityTypeName = function(entityType)
	{
		if(this._primaryEntityTypeName !== entityType)
		{
			this._primaryEntityTypeName = entityType;
		}
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityId = function()
	{
		return this._primaryEntityInfo ? this._primaryEntityInfo.getId() : 0;
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntity = function()
	{
		return this._primaryEntityInfo;
	};
	BX.Crm.EntityEditorClient.prototype.setPrimaryEntity = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			this._primaryEntityInfo = entityInfo;
			this.setPrimaryEntityTypeName(entityInfo.getTypeName());
		}
		else
		{
			this._primaryEntityInfo = null;
		}
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityBindings = function()
	{
		return this._primaryEntityBindingInfos;
	};
	BX.Crm.EntityEditorClient.prototype.getSecondaryEntityTypeName = function()
	{
		return this._secondaryEntityTypeName;
	};
	BX.Crm.EntityEditorClient.prototype.setSecondaryEntityTypeName = function(entityType)
	{
		if(this._secondaryEntityTypeName !== entityType)
		{
			this._secondaryEntityTypeName = entityType;
		}
	};
	//region SecondaryEntities
	BX.Crm.EntityEditorClient.prototype.getSecondaryEntities = function()
	{
		return this._secondaryEntityInfos.getItems();
	};
	BX.Crm.EntityEditorClient.prototype.getSecondaryEntityById = function(id)
	{
		if(!this._secondaryEntityInfos)
		{
			return null;
		}
		return this._secondaryEntityInfos.search(function(item){ return item.getId() === id; });
	};
	BX.Crm.EntityEditorClient.prototype.removeSecondaryEntity = function(entityInfo)
	{
		if(this._secondaryEntityInfos)
		{
			this._secondaryEntityInfos.remove(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.addSecondaryEntity = function(entityInfo)
	{
		if(this._secondaryEntityInfos)
		{
			this._secondaryEntityInfos.add(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityDelete = function(editor, entityInfo)
	{
		this.removeSecondaryEntity(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityBeforeAdd = function(editor, entityInfo, eventArgs)
	{
		if(this._primaryEntityEditor && this._primaryEntityInfo && this._primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.company)
		{
			var primaryEntityId = this._primaryEntityInfo.getId();
			if(entityInfo.checkEntityBinding(BX.CrmEntityType.names.company, primaryEntityId)
				&& !this._bindingTracker.isUnbound(entityInfo))
			{
				this._primaryEntityEditor.addBinding(
					this._primaryEntityEditor.createBinding(entityInfo)
				);
				eventArgs["cancel"] = true;
			}
		}
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityAdd = function(editor, entityInfo)
	{
		this.addSecondaryEntity(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityBind = function(editor, entityInfo)
	{
		this._secondaryEntityEditor.removeItem(
			this._secondaryEntityEditor.getItemById(entityInfo.getId())
		);

		if(this._primaryEntityEditor)
		{
			this._primaryEntityEditor.addBinding(this._primaryEntityEditor.createBinding(entityInfo));
		}

		this._bindingTracker.bind(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.getAllSecondaryEntityIds = function()
	{
		var entityInfos = this.getAllSecondaryEntityInfos();
		var results = [];
		for(var i = 0, length = entityInfos.length; i < length; i++)
		{
			results.push(entityInfos[i].getId());
		}
		return results;
	};
	BX.Crm.EntityEditorClient.prototype.getAllSecondaryEntityInfos = function()
	{
		return (
			[].concat(
				this._primaryEntityBindingInfos.getItems(),
				this._secondaryEntityInfos.getItems()
			)
		);
	};
	//endregion
	//region PrimaryEntityBindings
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityBindings = function()
	{
		return this._primaryEntityBindingInfos.getItems();
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityBindingById = function(id)
	{
		if(!this._primaryEntityBindingInfos)
		{
			return null;
		}
		return this._primaryEntityBindingInfos.search(function(item){ return item.getId() === id; });
	};
	BX.Crm.EntityEditorClient.prototype.addPrimaryEntityBinding = function(entityInfo)
	{
		if(this._primaryEntityBindingInfos)
		{
			this._primaryEntityBindingInfos.add(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.removePrimaryEntityBinding = function(entityInfo)
	{
		if(this._primaryEntityBindingInfos)
		{
			this._primaryEntityBindingInfos.remove(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityBindingAdd = function(editor, entityInfo)
	{
		this.addPrimaryEntityBinding(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityBindingDelete = function(editor, entityInfo)
	{
		this.removePrimaryEntityBinding(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityBindingRelease = function(editor, entityInfo)
	{
		this._bindingTracker.unbind(entityInfo);
		this._secondaryEntityEditor.addItem(this._secondaryEntityEditor.createItem(entityInfo));
	};
	//endregion
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityDelete = function(editor, entityInfo)
	{
		var secondaryEntityInfos = [].concat(this._primaryEntityBindingInfos.getItems(), this._secondaryEntityInfos.getItems());

		this._secondaryEntityInfos = BX.Collection.create();
		this._primaryEntityBindingInfos = BX.Collection.create();

		var primaryEntityInfo = null;
		if(secondaryEntityInfos.length > 0)
		{
			primaryEntityInfo = secondaryEntityInfos.shift();
		}

		this.setPrimaryEntity(primaryEntityInfo);
		this._primaryEntityEditor.setEntity(primaryEntityInfo);

		this._secondaryEntityEditor.setEntities(secondaryEntityInfos);
		this._secondaryEntityEditor.setVisible(primaryEntityInfo !== null);
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityChange = function(editor, entityInfo)
	{
		this.setPrimaryEntity(entityInfo);

		if(this._primaryEntityTypeName === BX.CrmEntityType.names.company)
		{
			this._bindingTracker.reset();
			this._primaryEntityBindingInfos = BX.Collection.create();

			this._secondaryEntityInfos = BX.Collection.create();
			this._secondaryEntityEditor.clearItems();
			this._secondaryEntityEditor.reloadEntities();
		}

		this._secondaryEntityEditor.setVisible(true);
	};
	BX.Crm.EntityEditorClient.prototype.save = function()
	{
		var i, length, entityInfo;
		var map = this._schemeElement.getDataObjectParam("map", {});

		if(this._enablePrimaryEntity)
		{
			this._model.setMappedField(map, "primaryEntityType", this._primaryEntityTypeName);
			var primaryEntityId = this._primaryEntityInfo ? this._primaryEntityInfo.getId() : 0;
			this._model.setMappedField(map, "primaryEntityId", primaryEntityId);

			if(this._primaryEntityInfo)
			{
				this._info["PRIMARY_ENTITY_DATA"] = this._primaryEntityInfo.getSettings();
			}
			else
			{
				delete  this._info["PRIMARY_ENTITY_DATA"];
			}

			if(primaryEntityId > 0)
			{
				var unboundSecondaryEntities = this._bindingTracker.getUnboundEntities();
				var unboundSecondaryEntityIds = [];
				for(i = 0, length = unboundSecondaryEntities.length; i < length; i++)
				{
					unboundSecondaryEntityIds.push(unboundSecondaryEntities[i].getId());
				}
				if(unboundSecondaryEntityIds.length > 0)
				{
					for(i = 0, length = unboundSecondaryEntityIds.length; i < length; i++)
					{
						entityInfo = this.getSecondaryEntityById(unboundSecondaryEntityIds[i]);
						if(entityInfo)
						{
							entityInfo.removeEntityBinding(this._primaryEntityTypeName, primaryEntityId);
						}
					}
				}
				this._model.setMappedField(map, "unboundSecondaryEntityIds", unboundSecondaryEntityIds.join(","));

				var boundSecondaryEntities = this._bindingTracker.getBoundEntities();
				var boundSecondaryEntityIds = [];
				for(i = 0, length = boundSecondaryEntities.length; i < length; i++)
				{
					boundSecondaryEntityIds.push(boundSecondaryEntities[i].getId());
				}
				if(boundSecondaryEntityIds.length > 0)
				{
					for(i = 0, length = boundSecondaryEntityIds.length; i < length; i++)
					{
						entityInfo = this.getPrimaryEntityBindingById(boundSecondaryEntityIds[i]);
						if(entityInfo)
						{
							entityInfo.addEntityBinding(this._primaryEntityTypeName, primaryEntityId);
						}
					}
				}
				this._model.setMappedField(map, "boundSecondaryEntityIds", boundSecondaryEntityIds.join(","));

				this._bindingTracker.reset();
			}
		}

		this._model.setMappedField(map, "secondaryEntityType", this._secondaryEntityTypeName);
		var secondaryEntityInfos = this.getAllSecondaryEntityInfos();
		var secondaryEntityData = [];
		var secondaryEntityIds = [];
		for(i = 0, length = secondaryEntityInfos.length; i < length; i++)
		{
			entityInfo = secondaryEntityInfos[i];
			secondaryEntityData.push(entityInfo.getSettings());
			secondaryEntityIds.push(entityInfo.getId());
		}
		this._model.setMappedField(map, "secondaryEntityIds", secondaryEntityIds.join(","));
		this._info["SECONDARY_ENTITY_DATA"] = secondaryEntityData;
	};
	BX.Crm.EntityEditorClient.prototype.onBeforeSubmit = function()
	{
		if(!this._dataElements)
		{
			return;
		}

		for(var key in this._dataElements)
		{
			if(!this._dataElements.hasOwnProperty(key))
			{
				continue;
			}
			var name = BX.prop.getString(this._map, key, "");
			if(name !== "")
			{
				this._dataElements[key].value = this._model.getField(name, "");
			}
		}
	};
	BX.Crm.EntityEditorClient.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorClient();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.PrimaryClientEditor === "undefined")
{
	BX.Crm.PrimaryClientEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._editor = null;
		this._mode = BX.Crm.EntityEditorMode.intermediate;
		this._entityInfo = null;
		this._entityTypeName = "";
		this._container = null;
		this._wrapper = null;

		this._bindingWrapper = null;

		this._externalEventHandler = null;
		this._externalContext = null;

		this._entityBindSelector = null;

		this._searchWrapper = null;
		this._searchInput = null;
		this._searchControl = null;

		this._item = null;
		this._itemBindings = null;
		this._skeleton = null;
		this._loaderConfig = null;
		this._hasLayout = false;
	};
	BX.Crm.PrimaryClientEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._editor = BX.prop.get(this._settings, "editor");
			this._mode = BX.prop.getInteger(this._settings, "mode", 0);
			this._container = BX.prop.getElementNode(this._settings, "container", null);
			this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);

			if(this._entityInfo)
			{
				this.setEntity(this._entityInfo);
			}
			else
			{
				this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
			}

			this._loaderConfig = BX.prop.getObject(this._settings, "loaderConfig", {});
		},
		layout: function()
		{
			var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;

			this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-clients-container" } });
			this._bindingWrapper = null;

			if(!isViewMode)
			{
				//region Search
				this._searchWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
				this._wrapper.appendChild(this._searchWrapper);

				this.prepareSearchLayout();
				this.adjustSearchLayout();
				//endregion
			}
			this._wrapper.appendChild(BX.create("div", { style: { clear: "both" } }));

			if(this._item)
			{
				this._item.setContainer(this._wrapper);
				this._item.layout();

				this._bindingWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-block-children" } });
				this._wrapper.appendChild(this._bindingWrapper);

				var bindingInfos = this._editor.getPrimaryEntityBindings();
				this._itemBindings = [];
				var i, length;
				for(i = 0, length = bindingInfos.length; i < length; i++)
				{
					var bindingInfo = bindingInfos[i];
					var binding = BX.Crm.ClientEditorEntityBindingPanel.create(
						this._id +  "_" + bindingInfo.getId().toString(),
						{
							entityInfo: bindingInfo,
							editor: this._editor,
							mode: this._mode,
							container: this._bindingWrapper,
							onChange: BX.delegate(this.onItemBindingChange, this)
						}
					);
					binding.layout();
					this._itemBindings.push(binding);
				}
			}

			var anchor = BX.prop.getElementNode(this._settings, "achor", null);
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
		adjustLayout: function()
		{
		},
		clearLayout: function()
		{
			if(this._item)
			{
				this._item.clearLayout();
			}

			if(this._itemBindings)
			{
				for(var i = 0, length = this._itemBindings.length; i < length; i++)
				{
					this._itemBindings[i].clearLayout();
				}
				this._itemBindings = null;
			}

			this._wrapper = BX.remove(this._wrapper);
			this._searchWrapper = null;
			this._bindingWrapper = null;
			this._entityCreateButton = null;

			this._hasLayout = false;
		},
		//region Search
		prepareSearchLayout: function()
		{
			this._searchInput = BX.create(
				"input",
				{
					props:
						{
							id: "dropdown-input",
							className: "crm-entity-widget-content-input crm-entity-widget-content-search-input"
						},
					attrs: { autocomplete: "nope" }
				}
			);
			this._searchWrapper.appendChild(this._searchInput);
			this._searchControl = new BX.UI.Dropdown(
				{
					searchAction: "crm.api.entity.search",
					searchOptions: { types: [ BX.CrmEntityType.names.contact, BX.CrmEntityType.names.company ] },
					//TODO: Implement CRM renderer
					searchResultRenderer: null,
					targetElement: this._searchInput,
					items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
					footerItems:
						[
							{
								caption: this.getMessage("create"),
								buttons:
									[
										{
											type: "create",
											caption: BX.CrmEntityType.getCaption(BX.CrmEntityType.enumeration.contact),
											events:
												{
													click: BX.delegate(
														function()
														{
															this.createEntity(BX.CrmEntityType.names.contact);
															this._searchControl.destroyPopupWindow();
														},
														this
													)
												}
										},
										{
											type: "create",
											caption: BX.CrmEntityType.getCaption(BX.CrmEntityType.enumeration.company),
											events:
												{
													click: BX.delegate(
														function()
														{
															this.createEntity(BX.CrmEntityType.names.company);
															this._searchControl.destroyPopupWindow();
														},
														this
													)
												}
										}
									]
							}
						],
					events:
						{
							onSelect: this.onEntitySelect.bind(this),
							onSearch: function(word) {}
						}
				}
			);
		},
		adjustSearchLayout: function()
		{
			if(this._searchWrapper)
			{
				this._searchWrapper.style.display = this._item ? "none" : "";
			}
		},
		//endregion
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		setEntityTypeName: function(entityType)
		{
			if(this._entityTypeName === entityType)
			{
				return;
			}

			this._entityTypeName = entityType;
		},
		setEntity: function(entityInfo)
		{
			if(this._item)
			{
				if(this._hasLayout)
				{
					this._item.clearLayout();
				}
				this._item = null;
			}

			if(!(entityInfo instanceof BX.CrmEntityInfo))
			{
				this._entityInfo = null;
			}
			else
			{
				this._entityInfo = entityInfo;
				this.setEntityTypeName(this._entityInfo.getTypeName());
				this._item = BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + this._entityInfo.getId().toString(),
					{
						editor: this._editor,
						entityInfo: this._entityInfo,
						enableEntityTypeCaption: true,
						enableRequisite: true,
						requisiteBinding: BX.prop.getObject(this._settings, "requisiteBinding", {}),
						mode: this._mode,
						onDelete: BX.delegate(this.onItemDelete, this)
					}
				);

				if(this._hasLayout)
				{
					this._item.setContainer(this._wrapper);
					this._item.layout();
				}
			}

			if(this._itemBindings)
			{
				for(var i = 0, length = this._itemBindings.length; i < length; i++)
				{
					this._itemBindings[i].clearLayout();
				}
				this._itemBindings = null;
			}

			this.adjustSearchLayout();
		},
		setupEntity: function(entityId)
		{
			if(this._entityInfo && this._entityInfo.getId() === entityId)
			{
				return;
			}

			this.setEntity(null);

			var callback = BX.prop.getFunction(this._settings, "onChange");
			if(callback)
			{
				callback(this, this._entityInfo);
			}

			var entityLoader = BX.prop.getObject(this._loaderConfig, this._entityTypeName, null);
			if(entityLoader)
			{
				this.showSkeleton();

				BX.CrmDataLoader.create(
					this._id,
					{
						serviceUrl: entityLoader["url"],
						action: entityLoader["action"],
						params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": entityId }
					}
				).load(BX.delegate(this.onEntityInfoLoad, this));
			}
		},
		showSkeleton: function()
		{
			if(!this._skeleton)
			{
				this._skeleton = BX.Crm.ClientEditorEntitySkeleton.create(this._id, { container: this._wrapper });
			}
			this._skeleton.layout();
		},
		hideSkeleton: function()
		{
			if(this._skeleton)
			{
				this._skeleton.clearLayout();
			}
		},
		onEntityInfoLoad: function(sender, result)
		{
			var entityData = BX.prop.getObject(result, "DATA", null);
			if(entityData)
			{
				var hasLayout = this._hasLayout;
				if(hasLayout)
				{
					this.clearLayout();
				}

				this.hideSkeleton();

				var entityInfo = BX.CrmEntityInfo.create(entityData);
				this.setEntity(entityInfo);

				var callback = BX.prop.getFunction(this._settings, "onChange");
				if(callback)
				{
					callback(this, this._entityInfo);
				}

				if(hasLayout)
				{
					this.layout();
				}
			}
		},
		getEntityCreateUrl: function(entityTypeName)
		{
			return this._editor.getEntityCreateUrl(entityTypeName);
		},
		createEntity: function(entityTypeName)
		{
			var url = this.getEntityCreateUrl(entityTypeName);
			if(url === "")
			{
				return "";
			}

			var contextId = this._editor.getContextId();
			url = BX.util.add_url_param(url, { external_context_id: contextId });

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			if(!this._externalContext)
			{
				this._externalContext = {};
			}
			this._externalContext[contextId] = url;
			BX.Crm.Page.open(url);
		},
		onEntitySelect: function(sender, item)
		{
			this._entityTypeName = BX.prop.getString(item, "type", "");
			var entityId = BX.prop.getInteger(item, "id", 0);
			if(entityId > 0)
			{
				this.setupEntity(entityId);
				this._searchControl.destroyPopupWindow();
			}
		},
		onExternalEvent: function(params)
		{
			if(BX.prop.getString(params, "key", "") !== "onCrmEntityCreate")
			{
				return;
			}

			var value = BX.prop.getObject(params, "value", {});
			var context = BX.prop.getString(value, "context", "");

			if(this._externalContext && typeof(this._externalContext[context]) !== "undefined")
			{
				var entityTypeName = BX.prop.getString(value, "entityTypeName", "");
				var entityId = BX.prop.getInteger(value, "entityId", 0);

				if(this._entityTypeName !== entityTypeName)
				{
					this._entityTypeName = entityTypeName;
				}
				this.setupEntity(entityId);

				BX.Crm.Page.close(this._externalContext[context]);
				delete this._externalContext[context];
			}
		},
		onItemBindingChange: function(item, action)
		{
			if(action === "unbind")
			{
				var callback = BX.prop.getFunction(this._settings, "onBindingRelease");
				if(callback)
				{
					callback(this, item.getEntity());
				}

				this.removeBinding(item);
			}
			else if(action === "delete")
			{
				this.removeBinding(item);
			}
		},
		onItemDelete: function(item)
		{
			var entityInfo = this._entityInfo;

			var hasLayout = this._hasLayout;
			if(hasLayout)
			{
				this.clearLayout();
			}
			this.setEntity(null);

			if(hasLayout)
			{
				this.layout();
			}

			var callback = BX.prop.getFunction(this._settings, "onDelete");
			if(callback)
			{
				callback(this, entityInfo);
			}
		},
		getBindings: function()
		{
			return this._itemBindings;
		},
		createBinding: function(entityInfo)
		{
			return(
				BX.Crm.ClientEditorEntityBindingPanel.create(
					this._id +  "_" + entityInfo.getId().toString(),
					{
						entityInfo: entityInfo,
						editor: this._editor,
						mode: this._mode,
						onChange: BX.delegate(this.onItemBindingChange, this)
					}
				)
			);
		},
		findBindingById: function(entityId)
		{
			for(var i = 0, length = this._itemBindings.length; i < length; i++)
			{
				var item = this._itemBindings[i];
				if(item.getEntity().getId() === entityId)
				{
					return item;
				}
			}

			return null;
		},
		getBindingIndex: function(binding)
		{
			for(var i = 0, length = this._itemBindings.length; i < length; i++)
			{
				if(this._itemBindings[i] === binding)
				{
					return i;
				}
			}

			return -1;
		},
		addBinding: function(item)
		{
			this._itemBindings.push(item);

			if(this._hasLayout)
			{
				item.setContainer(this._bindingWrapper);
				item.layout();
			}

			var callback = BX.prop.getFunction(this._settings, "onBindingAdd");
			if(callback)
			{
				callback(this, item.getEntity());
			}
		},
		removeBinding: function(item)
		{
			var index = this.getBindingIndex(item);
			if(index < 0)
			{
				return;
			}

			item.clearLayout();
			this._itemBindings.splice(index, 1);

			var callback = BX.prop.getFunction(this._settings, "onBindingDelete");
			if(callback)
			{
				callback(this, item.getEntity());
			}
		},
		getBindingEntities: function()
		{
			var results = [];
			if(this._itemBindings)
			{
				for(var i = 0, length = this._itemBindings.length; i < length; i++)
				{
					results.push(this._itemBindings[i].getEntity());
				}
			}
			return results;
		}
	};
	BX.Crm.PrimaryClientEditor.prototype.getMessage = function(name)
	{
		var m = BX.Crm.PrimaryClientEditor.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	if(typeof(BX.Crm.PrimaryClientEditor.messages) === "undefined")
	{
		BX.Crm.PrimaryClientEditor.messages = {};
	}
	BX.Crm.PrimaryClientEditor.create = function(id, settings)
	{
		var self = new BX.Crm.PrimaryClientEditor();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.SecondaryClientEditor === "undefined")
{
	BX.Crm.SecondaryClientEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._mode = BX.Crm.EntityEditorMode.intermediate;
		this._container = null;
		this._wrapper = null;
		this._entityTypeName = "";
		this._entityInfos = null;
		this._items = null;

		this._externalEventHandler = null;
		this._externalContext = null;

		this._isMultiple = true;

		this._primaryLoaderConfig = null;
		this._secondaryLoaderConfig = null;

		this._editor = null;

		this._searchWrapper = null;
		this._searchInput = null;
		this._searchControl = null;

		this._addButton = null;
		this._addButtonHandler = BX.delegate(this.onAddButtonClick, this);

		this._bindButton = null;
		this._bindButtonClickHandler = BX.delegate(this.onBindButtonClick, this);
		this._bindingSelector = null;
		this._bindingSelectHandler = BX.delegate(this.onBindingSelect, this);

		this._isVisible = true;
		this._hasLayout = false;
	};

	BX.Crm.SecondaryClientEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._mode = BX.prop.getInteger(this._settings, "mode", 0);
			this._editor = BX.prop.get(this._settings, "editor", null);

			this._container = BX.prop.getElementNode(this._settings, "container", null);
			this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
			this._entityInfos = BX.prop.getArray(this._settings, "entityInfos", "");
			this._isMultiple = BX.prop.getBoolean(this._settings, "isMultiple", true);

			this._items = [];
			var itemCount = this._entityInfos.length;
			if(!this._isMultiple && itemCount > 1)
			{
				itemCount = 1;
			}
			for(var i = 0; i < itemCount; i++)
			{
				var item = this.createItem(this._entityInfos[i]);
				this._items.push(item);
			}

			this._primaryLoaderConfig = BX.prop.getObject(this._settings, "primaryLoader", {});
			this._secondaryLoaderConfig = BX.prop.getObject(this._settings, "secondaryLoader", {});
		},
		getMessage: function(name)
		{
			var m = BX.Crm.SecondaryClientEditor.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getEntities: function()
		{
			return this._entityInfos;
		},
		setEntities: function(entityInfos)
		{
			this._entityInfos = entityInfos;
			this.clearItems();
			var itemCount = this._entityInfos.length;
			if(!this._isMultiple && itemCount > 1)
			{
				itemCount = 1;
			}
			for(var i = 0; i < itemCount; i++)
			{
				this.addItem(this.createItem(this._entityInfos[i]));
			}
		},
		findItemIndex: function(item)
		{
			for(var i = 0, j = this._items.length; i < j; i++)
			{
				if(this._items[i] === item)
				{
					return i;
				}
			}
			return -1;
		},
		getFirstItem: function()
		{
			return this._items.length > 0 ? this._items[0] : null;
		},
		getItemById: function(id)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(item.getEntity().getId() === id)
				{
					return item;
				}
			}
			return null;
		},
		getItems: function()
		{
			return this._items;
		},
		getItemCount: function()
		{
			return this._items.length;
		},
		createItem: function(entityInfo)
		{
			return (
				BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + entityInfo.getId().toString(),
					{
						editor: this._editor,
						entityInfo: entityInfo,
						mode: this._mode,
						onDelete: BX.delegate(this.onItemDelete, this)
					}
				)
			);
		},
		clearItems: function()
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				item.clearLayout();
				item.setContainer(null);
			}
			this._items = [];
		},
		addItemById: function(id)
		{
			var entityLoader = BX.prop.getObject(this._primaryLoaderConfig, this._entityTypeName, null);
			if(!entityLoader)
			{
				return;
			}

			BX.CrmDataLoader.create(
				this._id,
				{
					serviceUrl: entityLoader["url"],
					action: entityLoader["action"],
					params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": id }
				}
			).load(BX.delegate(this.onEntityInfoLoad, this));
		},
		addItem: function(item)
		{
			var beforeCallback = BX.prop.getFunction(this._settings, "onBeforeAdd");
			if(beforeCallback)
			{
				var eventArgs = { cancel: false };
				beforeCallback(this, item.getEntity(), eventArgs);
				if(eventArgs["cancel"])
				{
					return false;
				}
			}

			if(!this._isMultiple)
			{
				this.clearItems();
			}

			this._items.push(item);
			if(this._hasLayout)
			{
				item.setContainer(this._itemsWrapper);
				item.layout();
			}

			var afterCallback = BX.prop.getFunction(this._settings, "onAdd");
			if(afterCallback)
			{
				afterCallback(this, item.getEntity());
			}

			this.adjustLayout();

			return true;
		},
		removeItem: function(item)
		{
			var index = this.findItemIndex(item);
			if(index < 0)
			{
				return;
			}

			this._items.splice(index, 1);
			if(this._hasLayout)
			{
				item.clearLayout();
				item.setContainer(null);
			}

			var callback = BX.prop.getFunction(this._settings, "onDelete");
			if(callback)
			{
				callback(this, item.getEntity());
			}

			this.adjustLayout();
		},
		reloadEntities: function()
		{
			if(!this._editor)
			{
				return;
			}

			var primaryEntity = this._editor.getPrimaryEntity();
			if(!primaryEntity)
			{
				return;
			}

			var entityLoader = BX.prop.getObject(this._secondaryLoaderConfig, primaryEntity.getTypeName(), null);
			if(entityLoader)
			{
				BX.CrmDataLoader.create(
					this._id,
					{
						serviceUrl: entityLoader["url"],
						action: entityLoader["action"],
						params:
							{
								"PRIMARY_TYPE_NAME": primaryEntity.getTypeName(),
								"PRIMARY_ID": primaryEntity.getId(),
								"SECONDARY_TYPE_NAME": this._entityTypeName,
								"OWNER_TYPE_NAME": this._editor.getOwnerTypeName()
							}
					}
				).load(BX.delegate(this.onEntityInfosReload, this));
			}
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;
			if(this._wrapper)
			{
				this._wrapper.style.display = visible ? "" : "none";
			}
		},
		layout: function()
		{
			var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;

			this._wrapper = BX.create("div", {});
			if(!this._isVisible)
			{
				this._wrapper.style.display = "none";
			}
			this._container.appendChild(this._wrapper);

			var legendText = BX.prop.getString(this._settings, "entityLegend", "");

			this._addButton = null;
			this._bindButton = null;

			if(isViewMode)
			{
				this._wrapper.appendChild(
					BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-content-block-title" },
							children: [
								BX.create(
									"span",
									{
										attrs: { className: "crm-entity-widget-content-block-title-text" },
										text: legendText
									}
								)
							]
						}
					)
				);
			}
			else
			{
				this._bindButton = BX.create('span',
					{
						props: { className: 'crm-entity-widget-actions-btn-bind' },
						text: this.getMessage('bind'),
						events: { click: this._bindButtonClickHandler }
					}
				);

				this._wrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-participants-title" },
							children:
								[
									BX.create("div",
										{
											props: { className: "crm-entity-widget-clients-actions-block" },
											children:
												[
													BX.create("span",
														{
															props: { className: "crm-entity-widget-actions-btn-participants" },
															children:
																[
																	BX.create("span",
																		{
																			props: { className: "crm-entity-widget-participants-title-text" },
																			text: legendText
																		}
																	)
																]
														}
													),
													this._bindButton
												]
										}
									)
								]
						}
					)
				);
			}

			//region Search
			this._searchWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
			this._searchWrapper.style.display = "none";

			this._itemsWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-item-container" } });

			this._wrapper.appendChild(this._searchWrapper);

			this._wrapper.appendChild(this._itemsWrapper);

			if(!isViewMode)
			{
				this._addButton = BX.create("span",
					{
						props: { className: "crm-entity-widget-actions-btn-add" },
						text: this.getMessage('addParticipant'),
						events: { click: this._addButtonHandler }
					}
				);
				this._wrapper.appendChild(this._addButton);
			}

			this.prepareSearchLayout();
			//endregion

			for(var i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].setContainer(this._itemsWrapper);
				this._items[i].layout();
			}

			this.adjustLayout();
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			for(var i = 0, j = this._items.length; i < j; i++)
			{
				this._items[i].clearLayout();
				this._items[i].setContainer(null);
			}

			this._addButton = null;
			this._bindButton = null;

			this._wrapper = BX.remove(this._wrapper);
			this._hasLayout = false;
		},
		adjustLayout: function()
		{
			if(!this._bindButton)
			{
				return;
			}

			this._bindButton.style.display =
				this._editor.getPrimaryEntityTypeName() === BX.CrmEntityType.names.company
				&& BX.util.array_diff(
					this._editor.getSecondaryEntities(),
					this._editor.getPrimaryEntityBindings(),
					BX.CrmEntityInfo.getHashCode
				).length > 0 ? "" : "none";
		},
		//region Search
		prepareSearchLayout: function()
		{
			var entityTypeId = BX.CrmEntityType.resolveId(this._entityTypeName);

			this._searchInput = BX.create(
				"input",
				{
					props:
						{
							id: "dropdown-input",
							className: "crm-entity-widget-content-input crm-entity-widget-content-search-input"
						},
					attrs: { autocomplete: "nope" }
				}
			);
			this._searchWrapper.appendChild(this._searchInput);
			this._searchControl = new BX.UI.Dropdown(
				{
					searchAction: "crm.api.entity.search",
					searchOptions: { types: [ this._entityTypeName ] },
					//TODO: Implement CRM renderer
					searchResultRenderer: null,
					targetElement: this._searchInput,
					items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
					footerItems:
						[
							{
								caption: this.getMessage("create"),
								buttons:
									[
										{
											type: "create",
											caption: BX.CrmEntityType.getCaption(entityTypeId),
											events:
												{
													click: BX.delegate(
														function()
														{
															this.createEntity();
															this._searchControl.destroyPopupWindow();
														},
														this
													)
												}
										}
									]
							}
						],
					events:
						{
							onSelect: this.onItemSelect.bind(this),
							onSearch: function(word) {}
						}
				}
			);
		},
		//endregion
		onAddButtonClick: function(e)
		{
			this._searchWrapper.style.display = this._searchWrapper.style.display === "none" ? "" : "none";
		},
		onBindButtonClick: function(e)
		{
			if(this._bindingSelector && this._bindingSelector.isOpened())
			{
				this._bindingSelector.close();
				return;
			}

			if(!this._bindingSelector)
			{
				this._bindingSelector = BX.CmrSelectorMenu.create(this._id, { items: [] });
				this._bindingSelector.addOnSelectListener(this._bindingSelectHandler);
			}

			var bindings = this._editor.getPrimaryEntityBindings();
			var bindingInfos = [];
			var i, length;
			for(i = 0, length = bindings.length; i < length; i++)
			{
				bindingInfos.push(bindings[i]);
			}

			var unboundEntities = BX.util.array_diff(
				this._editor.getSecondaryEntities(),
				bindingInfos,
				BX.CrmEntityInfo.getHashCode
			);

			var items = [];
			for(i = 0, length = unboundEntities.length; i < length; i++)
			{
				var entityInfo = unboundEntities[i];
				items.push({ text: entityInfo.getTitle(), value: entityInfo.getId() });
			}

			this._bindingSelector.setupItems(items);
			this._bindingSelector.open(this._bindButton);
		},
		onBindingSelect: function(sender, item)
		{
			this._editor.onSecondaryEntityBind(this, this._editor.getSecondaryEntityById(item.getValue()));
		},
		onItemSelect: function(sender, item)
		{
			var entityId = BX.prop.getInteger(item, "id", 0);
			if(entityId > 0)
			{
				this.addItemById(entityId);
				this._searchWrapper.style.display = "none";
				this._searchControl.destroyPopupWindow();
			}
		},
		onItemDelete: function(item)
		{
			this.removeItem(item);
		},
		onEntityInfoLoad: function(sender, result)
		{
			var entityData = BX.prop.getObject(result, "DATA", null);
			if(!entityData)
			{
				return;
			}

			var entityInfo = BX.CrmEntityInfo.create(entityData);
			if(this.getItemById(entityInfo.getId()) !== null)
			{
				return;
			}

			this.addItem(this.createItem(entityInfo));
		},
		onEntityInfosReload: function(sender, result)
		{
			var entityData = BX.type.isArray(result['ENTITY_INFOS']) ? result['ENTITY_INFOS'] : [];
			var entityInfos = [];
			for(var i = 0; i < entityData.length; i++)
			{
				entityInfos.push(BX.CrmEntityInfo.create(entityData[i]));
			}
			this.setEntities(entityInfos);
		},
		getEntityCreateUrl: function(entityTypeName)
		{
			return this._editor.getEntityCreateUrl(entityTypeName);
		},
		createEntity: function()
		{
			var url = this.getEntityCreateUrl(this.getEntityTypeName());
			if(url === "")
			{
				return;
			}

			var contextId = this._editor.getContextId();
			url = BX.util.add_url_param(url, { external_context_id: contextId });

			//region add company binding if required
			var ownerTypeName = this._editor.getOwnerTypeName();
			var ownerId = this._editor.getOwnerId();

			if(ownerId > 0 && ownerTypeName === BX.CrmEntityType.names.company)
			{
				url = BX.util.add_url_param(url, { company_id: ownerId });
			}
			//endregion

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			if(!this._externalContext)
			{
				this._externalContext = {};
			}
			this._externalContext[contextId] = url;
			BX.Crm.Page.open(url);
		},
		onExternalEvent: function(params)
		{
			if(!this._externalContext)
			{
				return;
			}

			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			if(key !== "onCrmEntityCreate")
			{
				return;
			}

			var value = BX.prop.getObject(params, "value", {});
			if(BX.prop.getString(value, "entityTypeName", "") !== this.getEntityTypeName())
			{
				return;
			}

			var entityId = BX.prop.getInteger(value, "entityId", 0);
			var context = BX.prop.getString(value, "context", "");

			if(typeof(this._externalContext[context]) !== "undefined")
			{
				this.addItemById(entityId);
				BX.Crm.Page.close(this._externalContext[context]);
				delete this._externalContext[context];
			}
		}
	};
	if(typeof(BX.Crm.SecondaryClientEditor.messages) === "undefined")
	{
		BX.Crm.SecondaryClientEditor.messages = {};
	}
	BX.Crm.SecondaryClientEditor.create = function(id, settings)
	{
		var self = new BX.Crm.SecondaryClientEditor();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorEntity === "undefined")
{
	BX.Crm.EntityEditorEntity = function()
	{
		BX.Crm.EntityEditorEntity.superclass.constructor.apply(this);

		this._entityTypeName = "";
		this._entityInfo = null;

		this._entitySelectClickHandler = BX.delegate(this.onEntitySelectClick, this);
		this._entitySelectButton = null;
		this._entitySelector = null;

		this._entityWrapper = null;
		this._dataInput = null;
		this._skeleton = null;
	};
	BX.extend(BX.Crm.EntityEditorEntity, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorEntity.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorEntity.superclass.doInitialize.apply(this);

		this._loaderConfig = this._schemeElement.getDataObjectParam("loader", {});
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorEntity.prototype.initializeFromModel = function()
	{
		var entityInfo = this._model.getSchemeField(this._schemeElement, "info", null);
		if(entityInfo)
		{
			this.setEntity(BX.CrmEntityInfo.create(entityInfo));
		}
		else
		{
			this.setEntityTypeName(this._schemeElement.getDataStringParam("entityTypeName", ""));
		}
	};
	BX.Crm.EntityEditorEntity.prototype.getOwnerTypeName = function()
	{
		return this._editor.getEntityTypeName();
	};
	BX.Crm.EntityEditorEntity.prototype.getOwnerTypeId = function()
	{
		return this._editor.getEntityTypeId();
	};
	BX.Crm.EntityEditorEntity.prototype.getOwnerId = function()
	{
		return this._editor.getEntityId();
	};
	BX.Crm.EntityEditorEntity.prototype.getEntityTypeName = function()
	{
		return this._entityTypeName;
	};
	BX.Crm.EntityEditorEntity.prototype.setEntityTypeName = function(entityType)
	{
		if(this._entityTypeName === entityType)
		{
			return;
		}

		this._entityTypeName = entityType;
		if(this._entitySelector)
		{
			this._entitySelector = null;
		}
	};
	BX.Crm.EntityEditorEntity.prototype.setEntity = function(entityInfo)
	{
		if(this._item)
		{
			if(this._hasLayout)
			{
				this._item.clearLayout();
			}
			this._item = null;
		}

		if(!(entityInfo instanceof BX.CrmEntityInfo))
		{
			this._entityInfo = null;
		}
		else
		{
			this._entityInfo = entityInfo;
			this.setEntityTypeName(this._entityInfo.getTypeName());
			this._item = BX.Crm.ClientEditorEntityPanel.create(
				this._id +  "_" + this._entityInfo.getId().toString(),
				{
					editor: this,
					entityInfo: this._entityInfo,
					enableEntityTypeCaption: false,
					enableRequisite: true,
					//requisiteBinding: BX.prop.getObject(this._settings, "requisiteBinding", {}),
					mode: this._mode,
					onDelete: BX.delegate(this.onItemDelete, this)
				}
			);

			if(this._hasLayout)
			{
				this._item.setContainer(this._entityWrapper);
				this._item.layout();
			}
		}
	};
	BX.Crm.EntityEditorEntity.prototype.setupEntity = function(entityId)
	{
		if(this._entityInfo && this._entityInfo.getId() === entityId)
		{
			return;
		}

		this.setEntity(null);

		var entityLoader = BX.prop.getObject(this._loaderConfig, this._entityTypeName, null);
		if(entityLoader)
		{
			this.showSkeleton();

			BX.CrmDataLoader.create(
				this._id,
				{
					serviceUrl: entityLoader["url"],
					action: entityLoader["action"],
					params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": entityId }
				}
			).load(BX.delegate(this.onEntityInfoLoad, this));
		}
	};
	BX.Crm.EntityEditorEntity.prototype.showSkeleton = function()
	{
		if(!this._skeleton)
		{
			this._skeleton = BX.Crm.ClientEditorEntitySkeleton.create(this._id, { container: this._entityWrapper });
		}
		this._skeleton.layout();
	};
	BX.Crm.EntityEditorEntity.prototype.hideSkeleton = function()
	{
		if(this._skeleton)
		{
			this._skeleton.clearLayout();
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onEntityInfoLoad = function(sender, result)
	{
		var entityData = BX.prop.getObject(result, "DATA", null);
		if(entityData)
		{
			this.hideSkeleton();

			var entityInfo = BX.CrmEntityInfo.create(entityData);
			this.setEntity(entityInfo);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onItemDelete = function(item)
	{
		this.setEntity(null);
	};
	BX.Crm.EntityEditorEntity.prototype.reset = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorEntity.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.initializeFromModel();
		}
	};
	BX.Crm.EntityEditorEntity.prototype.doSetMode = function(mode)
	{
		this.rollback();
		if(this._item)
		{
			this._item.setMode(mode);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var name = this.getName();
		var title = this._schemeElement.getTitle();
		var value = this.getValue();

		var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;

		this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block" } });

		if(isViewMode && !this._item)
		{
			//There is nothing to show
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var innerWrapper = BX.create("div",{ props: { className: "crm-entity-widget-clients-block" } });
		this._wrapper.appendChild(innerWrapper);
		innerWrapper.appendChild(this.createTitleNode(title));

		var editorWrapper = BX.create("div",{ props: { className: !isViewMode ? "crm-entity-widget-content-block-clients" : "" } });
		innerWrapper.appendChild(editorWrapper);

		this._entityWrapper = BX.create("div", { props: { className: "crm-entity-widget-clients-container" } });
		editorWrapper.appendChild(this._entityWrapper);
		if(!isViewMode)
		{
			this._entitySelectButton = BX.create("span",
				{
					props: { className: "crm-entity-widget-actions-btn-select" },
					text: this.getMessage("select"),
					events: { click: this._entitySelectClickHandler }
				}
			);

			var actionWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-clients-actions-block" },
					children: [ this._entitySelectButton ]
				}
			);

			this._entityWrapper.appendChild(actionWrapper);

			this._dataInput = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });
			this._entityWrapper.appendChild(this._dataInput);
		}

		if(this._item)
		{
			this._item.setContainer(this._entityWrapper);
			this._item.layout();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorEntity.prototype.clearLayout = function()
	{
		if(this._item)
		{
			this._item.clearLayout();
		}

		this._wrapper = BX.remove(this._wrapper);
		this._entityWrapper = null;
		this._dataInput = null;

		if(this._entitySelector)
		{
			if(this._entitySelector.isOpened())
			{
				this._entitySelector.close();
			}
			this._entitySelector = null;
		}

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorEntity.prototype.onEntitySelectClick = function(e)
	{
		if(this._entitySelector && this._entitySelector.isOpened())
		{
			this._entitySelector.close();
			return;
		}

		if(!this._entitySelector)
		{
			this._entitySelector = BX.Crm.EntityEditorCrmSelector.create(
				this._id,
				{
					entityTypeIds: [ BX.CrmEntityType.resolveId(this._entityTypeName) ],
					enableMyCompanyOnly: this._schemeElement.getDataBooleanParam("enableMyCompanyOnly", false),
					callback: BX.delegate(this.onEntitySelect, this)
				}
			);
		}
		this._entitySelector.open(this._entitySelectButton);
	};
	BX.Crm.EntityEditorEntity.prototype.onEntitySelect = function(sender, item)
	{
		var id = BX.prop.getInteger(item, "entityId", 0);
		if(this._entityInfo && this._entityInfo.getId() === id)
		{
			return;
		}

		this._entitySelector.close();
		this.setupEntity(id);
	};
	BX.Crm.EntityEditorEntity.prototype.save = function()
	{
		this._model.setField(this.getName(), this._entityInfo ? this._entityInfo.getId() : 0);
	};
	BX.Crm.EntityEditorEntity.prototype.onBeforeSubmit = function()
	{
		if(this._dataInput)
		{
			this._dataInput.value = this._model.getField(this.getName(), "");
		}
	};
	BX.Crm.EntityEditorEntity.prototype.getMessage = function(name)
	{
		return BX.prop.getString(BX.Crm.EntityEditorEntity.messages, name, name);
	};
	if(typeof(BX.Crm.EntityEditorEntity.messages) === "undefined")
	{
		BX.Crm.EntityEditorEntity.messages = {};
	}
	BX.Crm.EntityEditorEntity.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorEntity();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorFieldConfigurator === "undefined")
{
	BX.Crm.EntityEditorFieldConfigurator = function()
	{
		BX.Crm.EntityEditorFieldConfigurator.superclass.constructor.apply(this);
		this._field = null;
		this._name = null;
		this._isLocked = false;

		this._labelInput = null;
		this._isRequiredCheckBox = null;
		this._showAlwaysCheckBox = null;
		this._saveButton = null;
		this._cancelButton = null;
		this._optionWrapper = null;

		this._mandatoryConfigurator = null;
	};
	BX.extend(BX.Crm.EntityEditorFieldConfigurator, BX.Crm.EntityEditorControl);
	BX.Crm.EntityEditorFieldConfigurator.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorFieldConfigurator.superclass.doInitialize.apply(this);
		this._field = BX.prop.get(this._settings, "field", null);
		this._name = BX.prop.getString(this._fieldData, "name", "");

		this._mandatoryConfigurator = BX.prop.get(this._settings, "mandatoryConfigurator", null);
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorFieldConfigurator.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		if(this._mode === BX.Crm.EntityEditorMode.view)
		{
			throw "EntityEditorFieldConfigurator. View mode is not supported by this control type.";
		}

		this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-new-fields" } });
		this._labelInput = BX.create(
			"input",
			{
				attrs:
				{
					className: "crm-entity-widget-content-input",
					type: "text",
					value: this._field.getTitle()
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
											text: this.getMessage("labelField")
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

		if(this._field.areAttributesEnabled() && !this._field.isRequired() && this._mandatoryConfigurator)
		{
			this._isRequiredCheckBox = this.createOption(
				{
					caption: this._mandatoryConfigurator.getTitle() + ":",
					labelSettings: { props: { className: "crm-entity-new-field-addiction-label" } },
					containerSettings: { style: { alignItems: "center" } },
					elements: this._mandatoryConfigurator.getButton().prepareLayout()
				}
			);
			this._isRequiredCheckBox.checked = !this._mandatoryConfigurator.isEmpty();

			this._mandatoryConfigurator.setSwitchCheckBox(this._isRequiredCheckBox);
			this._mandatoryConfigurator.setLabel(this._isRequiredCheckBox.nextSibling);

			this._mandatoryConfigurator.setEnabled(this._isRequiredCheckBox.checked);
			this._mandatoryConfigurator.adjust();
		}

		//region Show Always
		this._showAlwaysCheckBox = this.createOption(
			{ caption: this.getMessage("showAlways"), help: { code: "7046149" } }
		);
		this._showAlwaysCheckBox.checked = this._field.checkOptionFlag(BX.Crm.EntityEditorControlOptions.showAlways);
		//endregion

		this._wrapper.appendChild(
			BX.create("hr", { props: { className: "crm-entity-widget-hr" } })
		);

		this._wrapper.appendChild(
			BX.create (
				"div",
				{
					props: {
						className: "crm-entity-widget-content-block-new-fields-btn-container"
					},
					children:
						[
							this._saveButton,
							this._cancelButton
						]
				}
			)
		);

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._wrapper = BX.remove(this._wrapper);

		this._labelInput = null;
		this._isRequiredCheckBox = null;
		this._showAlwaysCheckBox = null;
		this._saveButton = null;
		this._cancelButton = null;
		this._optionWrapper = null;

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.onSaveButtonClick = function(e)
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
				field: this._field,
				label: this._labelInput.value,
				showAlways: this._showAlwaysCheckBox.checked
			};

		BX.onCustomEvent(this, "onSave", [ this, params ]);
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.onCancelButtonClick = function(e)
	{
		if(this._isLocked)
		{
			return;
		}

		var params = { field: this._field };
		BX.onCustomEvent(this, "onCancel", [ this, params ]);
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.setLocked = function(locked)
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
	BX.Crm.EntityEditorFieldConfigurator.prototype.getField = function()
	{
		return this._field;
	};
	BX.Crm.EntityEditorFieldConfigurator.prototype.createOption = function(params)
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

		var container = BX.create(
			"div",
			{
				props: { className: "crm-entity-widget-content-block-field-container" },
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
	if(typeof(BX.Crm.EntityEditorFieldConfigurator.messages) === "undefined")
	{
		BX.Crm.EntityEditorFieldConfigurator.messages = {};
	}
	BX.Crm.EntityEditorFieldConfigurator.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorFieldConfigurator();
		self.initialize(id, settings);
		return self;
	}
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
		this._isMultipleCheckBox = null;
		this._showAlwaysCheckBox = null;
		this._enumItemWrapper = null;
		this._enumItemContainer = null;
		this._enumButtonWrapper = null;
		this._optionWrapper = null;

		this._enumItems = null;

		this._mandatoryConfigurator = null;
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

		var container = BX.create(
			"div",
			{
				props: { className: "crm-entity-widget-content-block-field-container" },
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

		//HACK: Try to resolve UserFieldEmployee object
		var match = button.id.match(/^add_user_([a-z_0-9-]+)/i);
		if(BX.type.isArray(match) && match.length > 1)
		{
			var selector = BX.Intranet.UserFieldEmployee.instance(match[1]);
			if(selector)
			{
				BX.addCustomEvent(selector, 'onUpdateValue', this._changeHandler);
			}
		}
	};
	BX.Crm.EntityEditorUserField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserField();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorProductRowSummary === "undefined")
{
	BX.Crm.EntityEditorProductRowSummary = function()
	{
		BX.Crm.EntityEditorProductRowSummary.superclass.constructor.apply(this);
		this._loader = null;
		this._table = null;

		this._itemCount = 0;
		this._totalCount = 0;

		this._moreButton = null;
		this._moreButtonRow = null;
		this._moreButtonClickHandler = BX.delegate(this._onMoreButtonClick, this);
	};
	BX.extend(BX.Crm.EntityEditorProductRowSummary, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorProductRowSummary.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorProductRowSummary.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorProductRowSummary.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({});
		this.adjustWrapper();

		var data = this.getValue();

		if(!BX.type.isPlainObject(data))
		{
			return;
		}

		var title = this.getTitle();
		var items = BX.prop.getArray(data, "items", []);
		this._totalCount = BX.prop.getInteger(data, "count", 0);

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));

		this._table = BX.create("table", { props: { className: "crm-entity-widget-content-block-products-list" } });

		var length = this._itemCount = items.length;
		var restLength = 0;
		if(length > 5)
		{
			restLength = this._totalCount - 5;
			length = 5;
		}

		for(var i = 0; i < length; i++)
		{
			this.addProductRow(items[i], -1);
		}

		var row, cell;
		this._moreButton = null;
		if(restLength > 0)
		{
			row = this._moreButtonRow = this._table.insertRow(-1);
			row.className = "crm-entity-widget-content-block-products-item";
			cell = row.insertCell(-1);
			cell.className = "crm-entity-widget-content-block-products-item-name";

			this._moreButton = BX.create(
				"span",
				{
					attrs: { className: "crm-entity-widget-content-block-products-show-more" },
					events: { click: this._moreButtonClickHandler },
					text: this.getMessage("notShown").replace(/#COUNT#/gi, restLength.toString())
				}
			);

			cell.appendChild(this._moreButton);
			cell = row.insertCell(-1);
			cell.className = "crm-entity-widget-content-block-products-price";
		}

		row = this._table.insertRow(-1);
		row.className = "crm-entity-widget-content-block-products-item";
		cell = row.insertCell(-1);
		cell.className = "crm-entity-widget-content-block-products-item-name";
		cell.innerHTML = this.getMessage("total");

		cell = row.insertCell(-1);
		cell.className = "crm-entity-widget-content-block-products-price";
		cell.appendChild(
			BX.create(
				"div",
				{
					attrs: { className: "crm-entity-widget-content-block-products-price-value" },
					html: data["total"]
				}
			)
		);

		this._wrapper.appendChild(
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-products" },
					children: [ this._table ]
				}
			)
		);

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorProductRowSummary.prototype._onMoreButtonClick = function(e)
	{
		if(this._totalCount > 10)
		{
			BX.onCustomEvent(window, "OpenEntityDetailTab", ["tab_products"]);
			return;
		}

		this._moreButtonRow.style.display = "none";
		var data = this.getValue();
		var items = BX.prop.getArray(data, "items", []);
		for(var i = 5; i < this._itemCount; i++)
		{
			this.addProductRow(items[i], i);
		}
	};
	BX.Crm.EntityEditorProductRowSummary.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._table = null;
		this._moreButton = null;
		this._moreButtonRow = null;
		this._wrapper = BX.remove(this._wrapper);
		this._hasLayout = false;
	};
	BX.Crm.EntityEditorProductRowSummary.prototype.addProductRow = function(data, index)
	{
		if(typeof(index) === "undefined")
		{
			index = -1;
		}

		var row, cell;
		row = this._table.insertRow(index);
		row.className = "crm-entity-widget-content-block-products-item";
		cell = row.insertCell(-1);
		cell.className = "crm-entity-widget-content-block-products-item-name";

		var url = BX.prop.getString(data, "URL", "");
		if(url !== "")
		{
			cell.appendChild(
				BX.create("a", { attrs: { target: "_blank", href: url }, text: data["PRODUCT_NAME"] })
			);
		}
		else
		{
			cell.innerHTML = BX.util.htmlspecialchars(data["PRODUCT_NAME"]);
		}

		cell = row.insertCell(-1);
		cell.className = "crm-entity-widget-content-block-products-price";
		cell.appendChild(
			BX.create(
				"div",
				{
					attrs: { className: "crm-entity-widget-content-block-products-price-value" },
					html: data["SUM"]
				}
			)
		);
	};

	if(typeof(BX.Crm.EntityEditorProductRowSummary.messages) === "undefined")
	{
		BX.Crm.EntityEditorProductRowSummary.messages = {};
	}

	BX.Crm.EntityEditorProductRowSummary.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorProductRowSummary();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRequisiteSelector === "undefined")
{
	BX.Crm.EntityEditorRequisiteSelector = function()
	{
		BX.Crm.EntityEditorRequisiteSelector.superclass.constructor.apply(this);
		this._requisiteId = 0;
		this._bankDetailId = 0;

		this._itemWrappers = {};
		this._itemButtons = {};
		this._itemBankDetailButtons = {};
	};
	BX.extend(BX.Crm.EntityEditorRequisiteSelector, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorRequisiteSelector.prototype.doInitialize = function()
	{
		this._requisiteId = this._model.getIntegerField("REQUISITE_ID", 0);
		this._bankDetailId = this._model.getIntegerField("BANK_DETAIL_ID", 0);
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRequisiteSelector.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.getPrefix = function()
	{
		return this._id.toLowerCase() + "_";
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var data = this.getData();
		this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
			{
				requisiteId: this._requisiteId,
				bankDetailId: this._bankDetailId,
				data: BX.prop.getArray(data, "data", {})
			}
		);

		var items = this._requisiteInfo.getItems();

		this._wrapper = BX.create("div", { props: { className: "crm-entity-requisites-slider-wrapper" } });
		var contentWrapper = BX.create("div", { props: { className: "crm-entity-requisites-slider-content" } });
		this._wrapper.appendChild(contentWrapper);

		var innerContentWrapper = BX.create("div", { props: { className: "crm-entity-requisites-slider-widget-content" } });
		contentWrapper.appendChild(innerContentWrapper);

		var selectContainer = BX.create("div", { props: { className: "crm-entity-requisites-select-container" } });
		innerContentWrapper.appendChild(selectContainer);

		for(var i = 0, length = items.length; i < length; i++)
		{
			selectContainer.appendChild(this.prepareItemLayout(items[i]));
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.getItemData = function(itemId)
	{
		var items = this._requisiteInfo.getItems();
		for(var i = 0, length = items.length; i < length; i++)
		{
			var itemData = items[i];
			if(itemId === BX.prop.getInteger(itemData, "requisiteId", 0))
			{
				return itemData;
			}
		}
		return null;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.prepareItemLayout = function(itemData)
	{
		var viewData = BX.prop.getObject(itemData, "viewData", null);
		if(!viewData)
		{
			return;
		}

		var isSelected = BX.prop.getBoolean(itemData, "selected", false);

		var prefix  = this.getPrefix();
		var itemId = BX.prop.getInteger(itemData, "requisiteId", 0);

		var wrapper = BX.create("label", { props: { className: "crm-entity-requisites-select-item" } });
		wrapper.appendChild(BX.create("strong", { text: BX.prop.getString(viewData, "title", "") }));
		if(isSelected)
		{
			BX.addClass(wrapper, "crm-entity-requisites-select-item-selected");
		}
		this._itemWrappers[itemId] = wrapper;

		var i, length;

		var fields = BX.prop.getArray(viewData, "fields", []);
		for(i = 0, length = fields.length; i < length; i++)
		{
			var field = fields[i];

			var fieldTitle = BX.prop.getString(field, "title", "");
			var fieldValue = BX.prop.getString(field, "textValue", "");

			if(fieldTitle !== "" && fieldValue !== "")
			{
				wrapper.appendChild(BX.create("br"));
				wrapper.appendChild(BX.create("span", { text: fieldTitle + ": " + fieldValue }));
			}
		}

		var button = BX.create("input",
			{
				props:
					{
						type: "radio",
						name: prefix + "requisite",
						checked: isSelected,
						className: "crm-entity-requisites-select-item-field"
					},
				attrs: { "data-requisiteid": itemId }
			}
		);
		wrapper.appendChild(button);
		this._itemButtons[itemId] = button;
		BX.bind(button, "change", BX.delegate(this.onItemChange, this));

		var bankDetailList = BX.prop.getArray(itemData, "bankDetailViewDataList", []);

		if(bankDetailList.length > 0)
		{
			var bankDetailWrapper = BX.create("span",
				{
					props: { className: "crm-entity-requisites-select-item-bank-requisites-container" }
				}
			);
			wrapper.appendChild(bankDetailWrapper);
			bankDetailWrapper.appendChild(
				BX.create("span",
					{
						props: { className: "crm-entity-requisites-select-item-bank-requisites-title" },
						html: this.getMessage("bankDetails")
					}
				)
			);

			var bankDetailContainer = BX.create("span",
				{
					props: { className: "crm-entity-requisites-select-item-bank-requisites-field-container" }
				}
			);
			bankDetailWrapper.appendChild(bankDetailContainer);

			this._itemBankDetailButtons[itemId] = {};
			for(i = 0, length = bankDetailList.length; i < length; i++)
			{
				var bankDetailItem = bankDetailList[i];
				var bankDetailItemId = BX.prop.getInteger(bankDetailItem, "pseudoId", 0);

				var bankDetailViewData = BX.prop.getObject(bankDetailItem, "viewData", null);
				if(!bankDetailViewData)
				{
					continue;
				}

				var isBankDetailItemSelected = isSelected && BX.prop.getBoolean(bankDetailItem, "selected", false);

				var bankDetailItemWrapper = BX.create("label",
					{
						props: { className: "crm-entity-requisites-select-item-bank-requisites-field-item" }
					}
				);
				bankDetailContainer.appendChild(bankDetailItemWrapper);

				var bankDetailButton = BX.create("input",
					{
						props:
							{
								type: "radio",
								name: prefix + "bankrequisite" + itemId,
								checked: isBankDetailItemSelected,
								className: "crm-entity-requisites-select-item-bank-requisites-field"
							},
						attrs:
							{
								"data-requisiteid": itemId,
								"data-bankdetailid": bankDetailItemId
							}
					}
				);
				bankDetailItemWrapper.appendChild(bankDetailButton);
				BX.bind(bankDetailButton, "change", BX.delegate(this.onItemBankDetailChange, this));
				this._itemBankDetailButtons[itemId][bankDetailItemId] = bankDetailButton;

				bankDetailItemWrapper.appendChild(
					document.createTextNode(BX.prop.getString(bankDetailViewData, "title", ""))
				);
			}

			wrapper.appendChild(
				BX.create("span", { style: { display: "block", clear: "both" } })
			);
		}

		return wrapper;

	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._wrapper = BX.remove(this._wrapper);
		this._itemWrappers = {};
		this._itemButtons = {};
		this._itemBankDetailButtons = {};

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.save = function()
	{
		this._model.setField("REQUISITE_ID", this._requisiteId, { originator: this });
		this._model.setField("BANK_DETAIL_ID", this._bankDetailId, { originator: this });
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.onItemChange = function(e)
	{
		var button = BX.getEventTarget(e);
		if(!button.checked)
		{
			return;
		}

		var requisiteId = parseInt(button.getAttribute("data-requisiteid"));
		if(isNaN(requisiteId) || requisiteId <= 0)
		{
			return;
		}

		this._requisiteId = requisiteId;
		this._bankDetailId = 0;

		var itemData = this.getItemData(this._requisiteId);
		var itemBankDetailList = BX.prop.getArray(itemData, "bankDetailViewDataList", []);
		for(var i = 0, length = itemBankDetailList.length; i < length; i++)
		{
			var itemBankDetailItem = itemBankDetailList[i];
			var itemBankDetailItemId = BX.prop.getInteger(itemBankDetailItem, "pseudoId", 0);
			if(itemBankDetailItemId > 0 && BX.prop.getBoolean(itemBankDetailItem, "selected", false))
			{
				this._bankDetailId = itemBankDetailItemId;
				break;
			}
		}

		for(var key in this._itemWrappers)
		{
			if(!this._itemWrappers.hasOwnProperty(key))
			{
				continue;
			}

			var itemWrapper = this._itemWrappers[key];
			var isSelected = this._requisiteId === parseInt(key);
			if(isSelected)
			{
				BX.addClass(itemWrapper, "crm-entity-requisites-select-item-selected");
			}
			else
			{
				BX.removeClass(itemWrapper, "crm-entity-requisites-select-item-selected");
			}

			if(this._itemButtons.hasOwnProperty(key))
			{
				var itemButton = this._itemButtons[key];
				if(itemButton.checked !== isSelected)
				{
					itemButton.checked = isSelected;
				}
			}

			if(this._itemBankDetailButtons.hasOwnProperty(key))
			{
				var itemBankDetailButtons = this._itemBankDetailButtons[key];
				for(var bankDetailItemId in itemBankDetailButtons)
				{
					if(!itemBankDetailButtons.hasOwnProperty(bankDetailItemId))
					{
						continue;
					}

					var isBankDetailItemSelected = isSelected && this._bankDetailId === parseInt(bankDetailItemId);
					var itemBankDetailButton = itemBankDetailButtons[bankDetailItemId];
					if(itemBankDetailButton.checked !== isBankDetailItemSelected)
					{
						itemBankDetailButton.checked = isBankDetailItemSelected;
					}
				}
			}
		}

		this.markAsChanged();
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.onItemBankDetailChange = function(e)
	{
		var button = BX.getEventTarget(e);
		if(!button.checked)
		{
			return;
		}

		var requisiteId = parseInt(button.getAttribute("data-requisiteid"));
		if(isNaN(requisiteId) || requisiteId <= 0)
		{
			return;
		}

		if(this._requisiteId !== requisiteId)
		{
			return;
		}

		var bankdetailId = parseInt(button.getAttribute("data-bankdetailid"));
		if(isNaN(bankdetailId) || bankdetailId <= 0)
		{
			return;
		}

		this._bankDetailId = bankdetailId;

	};
	if(typeof(BX.Crm.EntityEditorRequisiteSelector.messages) === "undefined")
	{
		BX.Crm.EntityEditorRequisiteSelector.messages = {};
	}
	BX.Crm.EntityEditorRequisiteSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRequisiteSelector();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRequisiteListItem === "undefined")
{
	BX.Crm.EntityEditorRequisiteListItem = function()
	{
		this._id = "";
		this._settings = null;
		this._owner = null;
		this._mode = BX.Crm.EntityEditorMode.intermediate;

		this._data = null;
		this._requisiteId = 0;

		this._container = null;
		this._wrapper = null;
		this._innerWrapper = null;
		this._editButton = null;
		this._deleteButton = null;

		this._hasLayout = false;
	};

	BX.Crm.EntityEditorRequisiteListItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = BX.type.isPlainObject(settings) ? settings : {};

			this._owner = BX.prop.get(this._settings, "owner", null);
			this._mode = BX.prop.getInteger(this._settings, "mode", BX.Crm.EntityEditorMode.intermediate);

			this._data = BX.prop.getObject(this._settings, "data", {});
			this._requisiteId = BX.prop.getInteger(this._data, "requisiteId", 0);

			this._container = BX.prop.getElementNode(this._settings, "container");
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.EntityEditorRequisiteListItem.messages, name, name);
		},
		getRequisiteId: function()
		{
			return this._requisiteId;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		layout: function(options)
		{
			if(this._hasLayout)
			{
				return;
			}

			var viewData = BX.prop.getObject(this._data, "viewData", null);
			if(!viewData)
			{
				viewData = {};
			}

			var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;

			this._wrapper = BX.create(
				"div",
				{ props: { className: "crm-entity-widget-client-requisites-container crm-entity-widget-client-requisites-container-opened" } }
			);

			this._innerWrapper = BX.create("dl", { props: { className: "crm-entity-widget-client-requisites-list" } });

			this.prepareViewLayout(viewData, [ "RQ_ADDR" ]);
			this.prepareFieldViewLayout(viewData, "RQ_ADDR");

			var bankDetails = BX.prop.getArray(this._data, "bankDetailViewDataList", []);
			for(var i = 0, length = bankDetails.length; i < length; i++)
			{
				var bankDetail = bankDetails[i];
				if(!BX.prop.getBoolean(bankDetail, "isDeleted", false))
				{
					this.prepareViewLayout(BX.prop.getObject(bankDetail, "viewData", null), []);
				}
			}

			if(!isViewMode)
			{
				this._deleteButton = BX.create(
					"span",
					{
						props: { className: "crm-entity-widget-client-requisites-remove-icon" },
						events: { click: BX.delegate(this.onRemoveButtonClick, this) }
					}
				);

				this._editButton = BX.create(
					"span",
					{
						props: { className: "crm-entity-widget-client-requisites-edit-icon" },
						events: { click: BX.delegate(this.onEditButtonClick, this) }
					}
				);
			}

			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-client-requisites-inner-container" },
						children: [ this._deleteButton, this._editButton, this._innerWrapper ]
					}
				)
			);

			var anchor = BX.prop.getElementNode(options, "anchor", null);
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
		prepareViewLayout: function(viewData, skipFields)
		{
			if(!viewData)
			{
				return;
			}

			var title = BX.prop.getString(viewData, "title", "");
			if(title !== "")
			{
				this._innerWrapper.appendChild(
					BX.create("dt",
						{
							props: { className: "crm-entity-widget-client-requisites-name" },
							text: title
						}
					)
				);
			}

			var i, length;
			var skipMap = {};
			if(BX.type.isArray(skipFields))
			{
				for(i = 0, length = skipFields.length; i < length; i++)
				{
					skipMap[skipFields[i]] = true;
				}
			}

			var fieldContent = [];
			var fields = BX.prop.getArray(viewData, "fields", []);
			for(i = 0, length = fields.length; i < length; i++)
			{
				var field = fields[i];
				var name = BX.prop.getString(field, "name", "");
				if(skipMap.hasOwnProperty(name))
				{
					continue;
				}

				var fieldTitle = BX.prop.getString(field, "title", "");
				var fieldValue = BX.prop.getString(field, "textValue", "");
				if(fieldTitle !== "" && fieldValue !== "")
				{
					fieldContent.push(fieldTitle + ": " + fieldValue);
				}
			}

			this._innerWrapper.appendChild(
				BX.create("dd",
					{
						props: { className: "crm-entity-widget-client-requisites-value" },
						text: fieldContent.join(", ")
					}
				)
			);
		},
		prepareFieldViewLayout: function(viewData, fieldName)
		{
			if(!viewData)
			{
				return;
			}

			var fields = BX.prop.getArray(viewData, "fields", []);
			for(var i = 0, length = fields.length; i < length; i++)
			{
				var field = fields[i];
				var name = BX.prop.getString(field, "name", "");

				if(name !== fieldName)
				{
					continue;
				}

				var title = BX.prop.getString(field, "title", "");
				var text = BX.prop.getString(field, "textValue", "");
				if(title === "" || text === "")
				{
					continue;
				}

				this._innerWrapper.appendChild(
					BX.create("dt",
						{
							props: { className: "crm-entity-widget-client-requisites-name" },
							text: title
						}
					)
				);

				this._innerWrapper.appendChild(
					BX.create("dd",
						{
							props: { className: "crm-entity-widget-client-requisites-value" },
							text: text
						}
					)
				);
			}
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.remove(this._wrapper);
			this._innerWrapper = null;
			this._editButton = null;
			this._deleteButton = null;

			this._hasLayout = false;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		getWrapper: function()
		{
			return this._wrapper;
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
		onEditButtonClick: function(e)
		{
			this._owner.onEditItem(this);
		},
		onRemoveButtonClick: function(e)
		{
			var dlg = BX.Crm.EditorAuxiliaryDialog.create(
				this._id,
				{
					title: this.getMessage("deleteTitle"),
					content: this.getMessage("deleteConfirm"),
					buttons:
					[
						{
							id: "accept",
							type: BX.Crm.DialogButtonType.accept,
							text: BX.message("CRM_EDITOR_DELETE"),
							callback: BX.delegate(this.onRemovalConfirmationDialogButtonClick, this)
						},
						{
							id: "cancel",
							type: BX.Crm.DialogButtonType.cancel,
							text: BX.message("CRM_EDITOR_CANCEL"),
							callback: BX.delegate(this.onRemovalConfirmationDialogButtonClick, this)
						}
					]
				}
			);
			dlg.open();
			this._owner.onOpenItemRemovalConfirmation(this);
		},
		onRemovalConfirmationDialogButtonClick: function(button)
		{
			var dlg = button.getDialog();
			if(button.getId() === "accept")
			{
				this._owner.onRemoveItem(this);
			}
			dlg.close();
			this._owner.onCloseItemRemovalConfirmation(this);
		}
	};
	if(typeof(BX.Crm.EntityEditorRequisiteListItem.messages) === "undefined")
	{
		BX.Crm.EntityEditorRequisiteListItem.messages = {};
	}
	BX.Crm.EntityEditorRequisiteListItem.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRequisiteListItem();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRequisiteList === "undefined")
{
	BX.Crm.EntityEditorRequisiteList = function()
	{
		BX.Crm.EntityEditorRequisiteList.superclass.constructor.apply(this);
		this._items = null;

		this._data = null;
		this._externalContext = null;
		this._externalEventHandler = null;

		this._createButton = null;

		this._dataInputs = {};
		this._dataSignInputs = {};

		this._itemWrapper = null;
		this._dataWrapper = null;

		this._isPresetMenuOpened = false;
		this._newItemIndex = -1;
		this._sliderUrls = {};
	};
	BX.extend(BX.Crm.EntityEditorRequisiteList, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorRequisiteList.prototype.doInitialize = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.initializeFromModel = function()
	{
		var value = this.getValue();
		this._data = BX.type.isArray(value) ? BX.clone(value, true) : [];
		var i, length;
		for(i = 0, length = this._data.length; i < length; i++)
		{
			this.prepareRequisiteData(this._data[i]);
		}

		this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
			{
				requisiteId: 0,
				bankDetailId: 0,
				data: this._data
			}
		);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.initializeFromModel();
		this.refreshLayout();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.reset = function()
	{
		this.initializeFromModel();

		//Destroy cached requisite sliders
		for(var key in this._sliderUrls)
		{
			if(this._sliderUrls.hasOwnProperty(key))
			{
				BX.Crm.Page.removeSlider(this._sliderUrls[key]);
			}
		}
		this._sliderUrls = {};
	};
	BX.Crm.EntityEditorRequisiteList.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.reset();
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.doSetMode = function(mode)
	{
		this.rollback();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRequisiteList.messages;
		return (m.hasOwnProperty(name)
			? m[name]
			: BX.Crm.EntityEditorRequisiteList.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.prepareDataInputName = function(requisiteKey, fieldName)
	{
		return this.getName() + "[" + requisiteKey.toString() + "]" + "[" + fieldName + "]";
	};
	BX.Crm.EntityEditorRequisiteList.prototype.prepareRequisiteData = function(data)
	{
		var id = BX.prop.getInteger(data, "requisiteId", 0);
		var pseudoId = BX.prop.getString(data, "pseudoId", "");

		if(id > 0)
		{
			data["key"] = id.toString();
			data["isNew"] = false;
			data["isChanged"] = BX.prop.getBoolean(data, "isChanged", false);
		}
		else
		{
			data["key"] = pseudoId;
			data["isNew"] = true;
			data["isChanged"] = BX.prop.getBoolean(data, "isChanged", true);
		}
		data["isDeleted"] = false;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.findRequisiteDataIndexByKey = function(key)
	{
		for(var i = 0, length = this._data.length; i < length; i++)
		{
			if(BX.prop.getString(this._data[i], "key", 0) === key)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getRequisiteDataByKey = function(key)
	{
		var index = this.findRequisiteDataIndexByKey(key);
		return index >= 0 ? this._data[index] : null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.setupRequisiteData = function(data)
	{
		var key = BX.prop.getString(data, "key", "");
		if(key === "")
		{
			return;
		}

		var index = this.findRequisiteDataIndexByKey(key);
		if(index >= 0)
		{
			this._data[index] = data;
		}
		else
		{
			this._data.push(data);
		}

		this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
			{
				requisiteId: 0,
				bankDetailId: 0,
				data: this._data
			}
		);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.refreshRequisiteDataInputs = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		BX.cleanNode(this._dataWrapper);
		for(var i = 0, length = this._data.length; i < length; i++)
		{
			var item = this._data[i];

			var key = BX.prop.getString(item, "key", "");
			if(key === "")
			{
				continue;
			}

			var isChanged = BX.prop.getBoolean(item, "isChanged", false);
			var isDeleted = BX.prop.getBoolean(item, "isDeleted", false);
			if(!isChanged && !isDeleted)
			{
				continue;
			}

			if(isDeleted)
			{
					this._dataWrapper.appendChild(
						BX.create(
							"input",
							{
								props:
								{
									type: "hidden",
									name: this.prepareDataInputName(key, "DELETED"),
									value: "Y"
								}
							}
						)
					);
			}
			else
			{
				var requisiteDataSign = BX.prop.getString(item, "requisiteDataSign", "");
				if(requisiteDataSign !== "")
				{
					this._dataWrapper.appendChild(
						BX.create(
							"input",
							{
								props:
								{
									type: "hidden",
									name: this.prepareDataInputName(key, "SIGN"),
									value: requisiteDataSign
								}
							}
						)
					);
				}

				var requisiteData = BX.prop.getString(item, "requisiteData", "");
				if(requisiteData !== "")
				{
					this._dataWrapper.appendChild(
						BX.create(
							"input",
							{
								props:
								{
									type: "hidden",
									name: this.prepareDataInputName(key, "DATA"),
									value: requisiteData
								}
							}
						)
					);
				}
			}
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.hasContentToDisplay = function()
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			return true;
		}
		return this._requisiteInfo && this._requisiteInfo.getItems().length > 0;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.layout = function(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		this._items = [];

		if (!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		var i, length;
		var itemInfos = this._requisiteInfo.getItems();
		for(i = 0, length = itemInfos.length; i < length; i++)
		{
			var  data = itemInfos[i];
			var item = BX.Crm.EntityEditorRequisiteListItem.create(
				BX.prop.getString(data, "key", ""),
				{
					owner: this,
					mode: this._mode,
					data: data
				}
			);
			this._items.push(item);
		}

		if(this.isInEditMode())
		{
			this._dataWrapper = BX.create("div");
			this._wrapper.appendChild(this._dataWrapper);

			this._wrapper.appendChild(this.createTitleNode(this.getTitle()));
			this._itemWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-requisites" } });
			this._wrapper.appendChild(this._itemWrapper);
			for(i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].setContainer(this._itemWrapper);
				this._items[i].layout();
			}

			this._createButton = BX.create(
				"span",
				{
					props: { className: "crm-entity-widget-client-requisites-add-btn" },
					text: BX.message("CRM_EDITOR_ADD")
				}
			);
			this._itemWrapper.appendChild(this._createButton);
			BX.bind(this._createButton, "click", BX.delegate(this.onCreateButtonClick, this));
		}
		else
		{
			this._wrapper.appendChild(this.createTitleNode(this.getTitle()));
			this._itemWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-colums-block" } });
			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						children: [ this._itemWrapper ]
					}
				)
			);

			this._wrapper.appendChild(this._itemWrapper);
			for(i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].setContainer(this._itemWrapper);
				this._items[i].layout();
			}
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.doClearLayout = function(options)
	{
		if(this._items)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].clearLayout();
			}
		}
		this._items = [];

		this._itemWrapper = null;
		this._createButton = null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemByIndex = function(index)
	{
		return index >= 0 && index <= (this._items.length - 1) ? this._items[index] : null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemById = function(requisiteId)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			if(item.getId() === requisiteId)
			{
				return item;
			}
		}
		return null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemCount = function()
	{
		return this._items.length;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemIndex = function(item)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.removeItemByIndex = function(index)
	{
		if(index < this._items.length)
		{
			this._items.splice(index, 1);
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.removeItem = function(item)
	{
		var index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		var data = this.getRequisiteDataByKey(item.getId());
		if(data)
		{
			data["isDeleted"] = true;
		}
		item.clearLayout();
		this.removeItemByIndex(index);

		this.refreshRequisiteDataInputs();
		this.markAsChanged();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.openEditor = function(params)
	{
		var requisiteId = BX.prop.getInteger(params, "requisiteId", 0);
		var contextId = this._editor.getContextId();

		var urlParams =
			{
				etype: this._editor.getEntityTypeId(),
				eid: this._editor.getEntityId(),
				external_context_id: contextId
			};

		var presetId = BX.prop.getInteger(params, "presetId", 0);
		if(presetId > 0)
		{
			urlParams["pid"] = presetId;
		}

		var pseudoId = "";
		if(requisiteId <= 0)
		{
			this._newItemIndex++;
			pseudoId = "n" + this._newItemIndex.toString();
			urlParams["pseudo_id"] = pseudoId;
		}

		var url = BX.util.add_url_param(
			this._editor.getRequisiteEditUrl(requisiteId),
			urlParams
		);

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}

		if(!this._externalContext)
		{
			this._externalContext = {};
		}

		if(requisiteId > 0)
		{
			this._externalContext[requisiteId] = { requisiteId: requisiteId, url: url };
		}
		else
		{
			this._externalContext[pseudoId] = { pseudoId: pseudoId, url: url };
		}

		if(requisiteId > 0)
		{
			this._sliderUrls[requisiteId] = url;
		}

		BX.Crm.Page.openSlider(url, { width: 950 });
	};

	/*
	BX.Crm.EntityEditorRequisiteList.prototype.loadEditor = function(params)
	{
		var requisiteId = BX.prop.getInteger(params, "requisiteId", 0);
		var contextId = this._editor.getContextId();

		var urlParams =
			{
				etype: this._editor.getEntityTypeId(),
				eid: this._editor.getEntityId(),
				external_context_id: contextId
			};

		var presetId = BX.prop.getInteger(params, "presetId", 0);
		if(presetId > 0)
		{
			urlParams["pid"] = presetId;
		}

		var pseudoId = "";
		if(requisiteId <= 0)
		{
			this._newItemIndex++;
			pseudoId = "n" + this._newItemIndex.toString();
			urlParams["pseudo_id"] = pseudoId;
		}

		var url = BX.util.add_url_param(
			this._editor.getRequisiteEditUrl(requisiteId),
			urlParams
		);

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}

		if(!this._externalContext)
		{
			this._externalContext = {};
		}

		if(requisiteId > 0)
		{
			this._externalContext[requisiteId] = { requisiteId: requisiteId, url: url };
		}
		else
		{
			this._externalContext[pseudoId] = { pseudoId: pseudoId, url: url };
		}

		var promise = new top.BX.Promise();
		var onEditorLoad = function(data)
		{
			var node = top.document.createElement("div");
			node.innerHTML = data;
			promise.fulfill(node);
		};
		BX.ajax(
			{
				'method': 'POST',
				'dataType': 'html',
				'url': url,
				'processData': false,
				'data':  {},
				'onsuccess': onEditorLoad
			}
		);

		return promise;
	};
	*/
	BX.Crm.EntityEditorRequisiteList.prototype.onEditItem = function(item)
	{
		this.openEditor( { requisiteId: item.getRequisiteId() });
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onRemoveItem = function(item)
	{
		this.removeItem(item);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onOpenItemRemovalConfirmation = function(item)
	{
		if(this._singleEditController)
		{
			this._singleEditController.setActiveDelayed(false);
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onCloseItemRemovalConfirmation = function(item)
	{
		if(this._singleEditController)
		{
			this._singleEditController.setActiveDelayed(true);
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onExternalEvent = function(params)
	{
		var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
		if(key !== "BX.Crm.RequisiteSliderEditor:onSave")
		{
			return;
		}

		var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
		var contextId = BX.prop.getString(value, "context", "");
		if(contextId !== this._editor.getContextId())
		{
			return;
		}

		var presetId = BX.prop.getInteger(value, "presetId", 0);
		var pseudoId = BX.prop.getString(value, "pseudoId", "");
		var requisiteId = BX.prop.getInteger(value, "requisiteId", 0);
		var requisiteDataSign = BX.prop.getString(value, "requisiteDataSign", "");
		var requisiteData = BX.prop.getString(value, "requisiteData", "");

		var itemData =
		{
			entityTypeId: this._editor.getEntityTypeId(),
			entityId: this._editor.getEntityId(),
			presetId: presetId,
			pseudoId: pseudoId,
			requisiteId: requisiteId,
			requisiteData: requisiteData,
			requisiteDataSign: requisiteDataSign,
			isChanged: true
		};

		this.prepareRequisiteData(itemData);
		this.setupRequisiteData(itemData);
		this.refreshRequisiteDataInputs();
		this.markAsChanged();

		var requisiteKey = BX.prop.getString(itemData, "key", "");
		var contextData = BX.prop.getObject(this._externalContext, requisiteKey, null);
		if(!contextData)
		{
			return;
		}

		var item = this.getItemById(requisiteKey);
		var layoutOptions;
		if(item)
		{
			item.setData(itemData);
			item.clearLayout();
			layoutOptions = {};
			var itemIndex = this.getItemIndex(item);
			if(itemIndex < (this.getItemCount() - 1))
			{
				layoutOptions["anchor"] = this.getItemByIndex(itemIndex + 1).getWrapper();
			}
			else if(this._createButton)
			{
				layoutOptions["anchor"] = this._createButton;
			}
			item.layout(layoutOptions);
		}
		else
		{
			item = BX.Crm.EntityEditorRequisiteListItem.create(
				requisiteKey,
				{
					owner: this,
					mode: this._mode,
					data: itemData,
					container: this._itemWrapper
				}
			);
			this._items.push(item);
			layoutOptions = {};
			if(this._createButton)
			{
				layoutOptions["anchor"] = this._createButton;
			}
			item.layout(layoutOptions);
		}

		var url = BX.prop.getString(contextData, "url", "");
		if(url !== "")
		{
			BX.Crm.Page.closeSlider(url, true);
		}

		delete this._externalContext[requisiteId];
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onCreateButtonClick = function(e)
	{
		this.togglePresetMenu();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.togglePresetMenu = function()
	{
		if(this._isPresetMenuOpened)
		{
			this.closePresetMenu();
		}
		else
		{
			this.openPresetMenu();
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.openPresetMenu = function()
	{
		if(this._isPresetMenuOpened)
		{
			return;
		}

		var menu = [];
		var items = BX.prop.getArray(this._schemeElement.getData(), "presets");
		for(var i = 0, length = items.length; i < length; i++)
		{
			var item = items[i];
			var value = BX.prop.getString(item, "VALUE", i);
			var name = BX.prop.getString(item, "NAME", value);
			menu.push(
				{
					text: name,
					value: value,
					onclick: BX.delegate( this.onPresetSelect, this)
				}
			);
		}

		BX.PopupMenu.show(
			this._id,
			this._createButton,
			menu,
			{
				angle: false,
				events:
					{
						onPopupShow: BX.delegate( this.onPresetMenuShow, this),
						onPopupClose: BX.delegate( this.onPresetMenuClose, this)
					}
			}
		);
		//BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.closePresetMenu = function()
	{
		if(!this._isPresetMenuOpened)
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onPresetMenuShow = function()
	{
		this._isPresetMenuOpened = true;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onPresetMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);
		this._isPresetMenuOpened = false;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onPresetSelect = function(e, item)
	{
		this.openEditor({ presetId: item.value });
		this.closePresetMenu();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.save = function()
	{
	};
	if(typeof(BX.Crm.EntityEditorRequisiteList.messages) === "undefined")
	{
		BX.Crm.EntityEditorRequisiteList.messages = {};
	}
	BX.Crm.EntityEditorRequisiteList.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRequisiteList();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.ClientEditorEntityRequisitePanel === "undefined")
{
	BX.Crm.ClientEditorEntityRequisitePanel = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;

		this._entityInfo = null;
		this._requisiteInfo = null;

		this._mode = BX.Crm.EntityEditorMode.intermediate;

		this._selectedRequisiteId = 0;
		this._selectedBankDetailId = 0;

		this._container = null;
		this._wrapper = null;
		this._contentWrapper = null;

		this._requisiteInput = null;
		this._bankDetailInput = null;

		this._toggleButton = null;
		this._editButton = null;

		this._toggleButtonHandler = BX.delegate(this.onToggleButtonClick, this);
		this._editButtonHandler = BX.delegate(this.onEditButtonClick, this);

		this._isExpanded = false;
		this._hasLayout = false;

		this._externalEventHandler = BX.delegate(this.onExternalEvent, this);

		this._changeNotifier = null;
	};
	BX.Crm.ClientEditorEntityRequisitePanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._editor = BX.prop.get(this._settings, "editor");

			this._container = BX.prop.getElementNode(this._settings, "container", null);
			this._mode = BX.prop.getInteger(this._settings, "mode", 0);

			this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);
			this._requisiteInfo = BX.prop.get(this._settings, "requisiteInfo", null);

			this._selectedRequisiteId = this._requisiteInfo.getRequisiteId();
			this._selectedBankDetailId = this._requisiteInfo.getBankDetailId();

			this._changeNotifier = BX.CrmNotifier.create(this);

			if(BX.Crm.ClientEditorEntityRequisitePanel.options.hasOwnProperty(this._id))
			{
				this._isExpanded = BX.prop.getBoolean(
					BX.Crm.ClientEditorEntityRequisitePanel.options[this._id],
					"expanded",
					false
				);
			}
		},
		getMessage: function(name)
		{
			var m = BX.Crm.ClientEditorEntityRequisitePanel.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		isExpanded: function()
		{
			return this._isExpanded;
		},
		setExpanded: function(expand)
		{
			expand = !!expand;
			if(this._isExpanded === expand)
			{
				return;
			}
			this._isExpanded = expand;

			if(!BX.Crm.ClientEditorEntityRequisitePanel.options.hasOwnProperty(this._id))
			{
				BX.Crm.ClientEditorEntityRequisitePanel.options[this._id] = {};
			}
			BX.Crm.ClientEditorEntityRequisitePanel.options[this._id]["expanded"] = this._isExpanded;

			if(expand)
			{
				BX.addClass(this._wrapper, "crm-entity-widget-client-requisites-container-opened");
			}
			else
			{
				BX.removeClass(this._wrapper, "crm-entity-widget-client-requisites-container-opened");
			}
		},
		toggle: function()
		{
			this.setExpanded(!this._isExpanded);
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var requisite = null;
			var bankDetail = null;

			var requisiteId = this._selectedRequisiteId;
			var bankDetailId = this._selectedBankDetailId;

			if(requisiteId > 0)
			{
				requisite = this._requisiteInfo.getItemById(requisiteId);
			}

			if(!requisite)
			{
				requisite = this._requisiteInfo.getSelectedItem();
			}

			if(!requisite)
			{
				requisite = this._requisiteInfo.getFirstItem();
			}

			if(requisite)
			{
				if(bankDetailId > 0)
				{
					bankDetail = this._requisiteInfo.getItemBankDetailById(requisiteId, bankDetailId);
				}
				if(!bankDetail)
				{
					bankDetail = this._requisiteInfo.getSelectedItemBankDetail(requisiteId);
				}
				if(!bankDetail)
				{
					bankDetail = this._requisiteInfo.getFirstItemBankDetail(requisiteId);
				}
			}

			var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;

			this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-container" } });
			this._container.appendChild(this._wrapper);

			if(this._isExpanded)
			{
				BX.addClass(this._wrapper, "crm-entity-widget-client-requisites-container-opened");
			}

			if(!isViewMode)
			{
				this._requisiteInput = BX.create("input", { props: { type: "hidden", name: "REQUISITE_ID", value: requisiteId } });
				this._wrapper.appendChild(this._requisiteInput);

				this._bankDetailInput = BX.create("input", { props: { type: "hidden", name: "BANK_DETAIL_ID", value: bankDetailId } });
				this._wrapper.appendChild(this._bankDetailInput);
			}

			if(requisite)
			{
				this._toggleButton = BX.create("a",
					{
						props: { className: "crm-entity-widget-client-requisites-show-btn" },
						text: this.getMessage("toggle").toLowerCase()
					}
				);
				this._wrapper.appendChild(this._toggleButton);
				BX.bind(this._toggleButton, "click", this._toggleButtonHandler);

				var innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-inner-container" } });
				this._wrapper.appendChild(innerWrapper);

				if(!isViewMode)
				{
					this._editButton = BX.create("span",
						{ props: { className: "crm-entity-widget-client-requisites-edit-icon" } }
					);
					this._editButton.setAttribute("data-editor-control-type", "button");

					innerWrapper.appendChild(this._editButton);
					BX.bind(this._editButton, "click", this._editButtonHandler);
				}

				this._contentWrapper = BX.create("dl", { props: { className: "crm-entity-widget-client-requisites-list" } });
				innerWrapper.appendChild(this._contentWrapper);

				//HACK: addresses must be rendered as separate items
				var requisiteView = BX.prop.getObject(requisite, "viewData", null);
				this.prepareItemView(requisiteView, ["RQ_ADDR"]);
				this.prepareItemFieldView(requisiteView, "RQ_ADDR");

				if(bankDetail)
				{
					this.prepareItemView(BX.prop.getObject(bankDetail, "viewData", null));
				}
			}

			this._hasLayout = true;
		},
		prepareItemView: function(viewData, skipFields)
		{
			if(!viewData)
			{
				return;
			}

			var fieldTitle = BX.prop.getString(viewData, "title", "");
			if(fieldTitle !== "")
			{
				this._contentWrapper.appendChild(
					BX.create("dt",
						{
							props: { className: "crm-entity-widget-client-requisites-name" },
							text: fieldTitle
						}
					)
				);
			}

			var i, length;
			var skipMap = {};
			if(BX.type.isArray(skipFields))
			{
				for(i = 0, length = skipFields.length; i < length; i++)
				{
					skipMap[skipFields[i]] = true;
				}
			}

			var fieldContent = [];
			var fields = BX.prop.getArray(viewData, "fields", []);
			for(i = 0, length = fields.length; i < length; i++)
			{
				var field = fields[i];
				var name = BX.prop.getString(field, "name", "");
				if(skipMap.hasOwnProperty(name))
				{
					continue;
				}

				var title = BX.prop.getString(field, "title", "");
				var text = BX.prop.getString(field, "textValue", "");
				if(title !== "" && text !== "")
				{
					fieldContent.push(title + ": " + text);
				}
			}

			this._contentWrapper.appendChild(
				BX.create("dd",
					{
						props: { className: "crm-entity-widget-client-requisites-value" },
						text: fieldContent.join(", ")
					}
				)
			);
		},
		prepareItemFieldView: function(viewData, fieldName)
		{
			if(!viewData)
			{
				return;
			}

			var fields = BX.prop.getArray(viewData, "fields", []);
			for(var i = 0, length = fields.length; i < length; i++)
			{
				var field = fields[i];
				var name = BX.prop.getString(field, "name", "");

				if(name !== fieldName)
				{
					continue;
				}

				var title = BX.prop.getString(field, "title", "");
				var text = BX.prop.getString(field, "textValue", "");
				if(title === "" || text === "")
				{
					continue;
				}

				this._contentWrapper.appendChild(
					BX.create("dt",
						{
							props: { className: "crm-entity-widget-client-requisites-name" },
							text: title
						}
					)
				);

				this._contentWrapper.appendChild(
					BX.create("dd",
						{
							props: { className: "crm-entity-widget-client-requisites-value" },
							text: text
						}
					)
				);
			}
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._toggleButton)
			{
				BX.unbind(this._toggleButton, "click", this._toggleButtonHandler);
				this._toggleButton = null;
			}

			if(this._editButton)
			{
				BX.unbind(this._editButton, "click", this._editButtonHandler);
				this._editButton = null;
			}

			this._isExpanded = false;
			this._requisiteInput = null;
			this._bankDetailInput = null;
			this._contentWrapper = null;
			this._wrapper = BX.remove(this._wrapper);
			this._hasLayout = false;
		},
		refreshLayout: function()
		{
			var expanded = this.isExpanded();
			this.clearLayout();
			this.layout();
			this.setExpanded(expanded);
		},
		getRuntimeValue: function()
		{
			return {
				REQUISITE_ID: this._selectedRequisiteId,
				BANK_DETAIL_ID: this._selectedBankDetailId
			}
		},
		onToggleButtonClick: function(e)
		{
			this.toggle();
			return BX.eventReturnFalse(e);
		},
		onEditButtonClick: function(e)
		{
			if(!this._editor)
			{
				return;
			}

			var url = BX.prop.getString(this._settings, "requisiteSelectUrl", "");
			if(url === "" && BX.type.isFunction(this._editor.getEntityRequisiteSelectUrl))
			{
				url = this._editor.getEntityRequisiteSelectUrl(
					this._entityInfo.getTypeName(),
					this._entityInfo.getId()
				);
			}

			if(url !== "")
			{
				url = BX.util.add_url_param(
					url,
					{
						external_context_id: this._editor.getContextId(),
						requisite_id: this._selectedRequisiteId,
						bank_detail_id: this._selectedBankDetailId
					}
				);

				BX.Crm.Page.openSlider(url);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			BX.eventCancelBubble(e);
		},
		onExternalEvent: function(params)
		{
			if(this._mode === BX.Crm.EntityEditorMode.view)
			{
				return;
			}

			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};

			if(!(this._editor && this._editor.getContextId() === BX.prop.getString(value, "context")))
			{
				return;
			}

			if(key === "BX.Crm.EntityRequisiteSelector:onCancel")
			{
				BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
			else if(key === "BX.Crm.EntityRequisiteSelector:onSave")
			{
				BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);

				var requisiteId = BX.prop.getInteger(value, "requisiteId");
				if(requisiteId > 0)
				{
					this._selectedRequisiteId = requisiteId;
					if(this._requisiteInput)
					{
						this._requisiteInput.value = this._selectedRequisiteId;
					}
				}

				var bankDetailId = BX.prop.getInteger(value, "bankDetailId");
				if(bankDetailId)
				{
					this._selectedBankDetailId = bankDetailId;
					if(this._bankDetailInput)
					{
						this._bankDetailInput.value = this._selectedBankDetailId;
					}
				}

				this._changeNotifier.notify(
					[
						{
							requisiteId: this._selectedRequisiteId,
							bankDetailId: this._selectedBankDetailId
						}
					]
				);

				this.refreshLayout();
			}
		}
	};
	if(typeof(BX.Crm.ClientEditorEntityRequisitePanel.messages) === "undefined")
	{
		BX.Crm.ClientEditorEntityRequisitePanel.messages = {};
	}
	BX.Crm.ClientEditorEntityRequisitePanel.options = {};
	BX.Crm.ClientEditorEntityRequisitePanel.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntityRequisitePanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.RequisiteNavigator) === "undefined")
{
	BX.Crm.RequisiteNavigator = function()
	{
		this._id = null;
		this._settings = {};

		this._requisite = null;
		this._bankDetail = null;
		this._bankDetailList = null;

		this._closingNotifier = null;

		this._nextButton = null;
		this._nextButtonHandler = BX.delegate(this.onNextButtonClick, this);

		this._wrapper = null;
		this._innerWrapper = null;
		this._titleContainer = null;
		this._contentContainer = null;
		this._bankDetailContainer = null;
		this._popup = null;

		this._isOpened = false;
		this._isExpanded = true;
		this._hasLayout = false;
	};

	BX.Crm.RequisiteNavigator.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._requisiteInfo = BX.prop.get(settings, "requisiteInfo");

			var requisiteId = this._requisiteInfo.getRequisiteId();
			var bankDetailId = this._requisiteInfo.getBankDetailId();

			this._requisite = requisiteId > 0 ? this._requisiteInfo.getItemById(requisiteId) : null;
			if(!this._requisite)
			{
				this._requisite = this._requisiteInfo.getSelectedItem();
			}
			if(!this._requisite)
			{
				this._requisite = this._requisiteInfo.getFirstItem();
			}

			if(this._requisite)
			{
				this._bankDetailList = this._requisiteInfo.getItemBankDetailList(requisiteId);
				if(this._bankDetailList)
				{
					if(bankDetailId > 0)
					{
						this._bankDetail = this._bankDetailList.getItemById(bankDetailId);
					}
					if(!this._bankDetail)
					{
						this._bankDetail = this._bankDetailList.getSelectedItem();
					}
					if(!this._bankDetail)
					{
						this._bankDetail = this._bankDetailList.getFirstItem();
					}
				}
			}

			this._closingNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.RequisiteNavigator.messages, name, name);
		},
		addClosingListener: function(listener)
		{
			this._closingNotifier.addListener(listener);
		},
		removeClosingListener: function(listener)
		{
			this._closingNotifier.removeListener(listener);
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function(anchor)
		{
			if(this._isOpened)
			{
				return;
			}

			var offsetLeft = 0, offsetTop = 0;
			if(BX.type.isElementNode(anchor))
			{
				offsetLeft = anchor.offsetWidth + 15;
				offsetTop = -(anchor.offsetHeight + 30);
			}

			this._popup = new BX.PopupWindow(
				this._id,
				anchor,
				{
					autoHide: true,
					draggable: false,
					offsetLeft: offsetLeft,
					offsetTop: offsetTop,
					noAllPaddings: true,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					events:
						{
							onPopupShow: BX.delegate(this.onPopupShow, this),
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						},
					content: this.prepareContent()
				}
			);
			//this._popup.setAngle({ position: "left" });
			this._popup.show();
		},
		close: function()
		{
			if(!this._isOpened)
			{
				return;
			}

			if(this._popup)
			{
				this._popup.close();
			}
		},
		prepareContent: function()
		{
			this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-wrap" } });
			this._titleContainer = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-info-box" } });
			this._wrapper.appendChild(this._titleContainer);

			this._requisiteTitleWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-info-wrapper" } });
			this._titleContainer.appendChild(this._requisiteTitleWrapper);

			this._nextButton = BX.create("div",
				{
					props: { className: "crm-entity-widget-client-requisites-arrow-right" },
					children:
						[
							BX.create("div",
								{ props: { className: "crm-entity-widget-client-requisites-arrow-right-item" } }
							)
						]
				}
			);
			this._titleContainer.appendChild(this._nextButton);
			BX.bind(this._nextButton, "click", this._nextButtonHandler);

			this._contentWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-box crm-entity-widget-client-requisites-box-active" } });
			this._wrapper.appendChild(this._contentWrapper);

			this._contentInnerWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-box-inner" } });
			this._contentWrapper.appendChild(this._contentInnerWrapper);

			this._requisiteWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-list-container" } });
			this._contentInnerWrapper.appendChild(this._requisiteWrapper);

			this._bankDetailWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-list-container" } });
			this._contentInnerWrapper.appendChild(this._bankDetailWrapper);

			this.renderRequisites();

			return this._wrapper;
		},
		renderTitleFields: function(fields, container)
		{
			for(var i = 0, length = fields.length; i < length; i++)
			{
				var field = fields[i];

				var title = BX.prop.getString(field, "title", "");
				var text = BX.prop.getString(field, "textValue", "");
				if(title === "" || text === "")
				{
					continue;
				}

				container.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-requisites-info-desc" },
							text: title
						}
					)
				);

				container.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-requisites-info-content" },
							children:
								[
									BX.create("div",
										{
											props: { className: "crm-entity-widget-client-requisites-info-content-item" },
											text: text
										}
									)
								]
						}
					)
				);
			}
		},
		renderContentFields: function(fields, caption, container)
		{
			var wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-list" } });
			container.appendChild(wrapper);

			var innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-client-requisites-item" }
				}
			);
			wrapper.appendChild(innerWrapper);

			innerWrapper.appendChild(
				BX.create("div",
					{
						props: { className: "crm-entity-widget-client-requisites-name" },
						text: caption
					}
				)
			);

			var values = [];
			for(var i = 0, length = fields.length; i < length; i++)
			{
				var field = fields[i];

				var title = BX.prop.getString(field, "title", "");
				var text = BX.prop.getString(field, "textValue", "");

				if(title !== "" && text !== "")
				{
					values.push(title + ": " + text);
				}
			}

			if(values.length > 0)
			{
				innerWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-requisites-value" },
							text: values.join(", ")
						}
					)
				);
			}
			else
			{
				BX.addClass(wrapper, "crm-entity-widget-client-requisites-empty-value");
				innerWrapper.appendChild(document.createTextNode(this.getMessage("stub")));
			}
		},
		renderRequisites: function()
		{
			BX.cleanNode(this._requisiteTitleWrapper);
			BX.cleanNode(this._requisiteWrapper);

			this._nextButton.style.display = this._requisiteInfo.getItemCount() > 1 ? "" : "none";

			if(this._requisite)
			{
				var viewData = BX.prop.getObject(this._requisite, "viewData", {});
				var fields = BX.prop.getArray(viewData, "fields", []);
				var titleFields = [];
				var contentFields = [];
				for(var i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];
					var fieldName = BX.prop.getString(field, "name", "");
					if(fieldName === "RQ_ADDR")
					{
						titleFields.push(field);
					}
					else
					{
						contentFields.push(field);
					}
				}

				this._requisiteTitleWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-requisites-info-title" },
							text: BX.prop.getString(viewData, "title", "")
						}
					)
				);

				this.renderTitleFields(titleFields, this._requisiteTitleWrapper);
				this.renderContentFields(contentFields, "", this._requisiteWrapper);

				this.renderBankDetails();
			}
		},
		renderBankDetails: function()
		{
			BX.cleanNode(this._bankDetailWrapper);

			if(this._bankDetailList && this._bankDetail)
			{
				var viewData = BX.prop.getObject(this._bankDetail, "viewData", {});
				this.renderContentFields(
					BX.prop.getArray(viewData, "fields", []),
					BX.prop.getString(viewData, "title", ""),
					this._bankDetailWrapper
				);

				var bankDetailQty = this._bankDetailList.getItemCount();
				if(bankDetailQty > 1)
				{
					var bankDetailControlContainer = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-control-box" } });
					this._bankDetailWrapper.appendChild(bankDetailControlContainer);

					bankDetailControlContainer.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-requisites-control-value" },
								text: this.getMessage("legend")
									.replace(/#NUMBER#/gi, this._bankDetailList.getItemIndex(this._bankDetail) + 1).toString()
									.replace(/#TOTAL#/gi, bankDetailQty.toString())
							}
						)
					);
					bankDetailControlContainer.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-requisites-control-btn" },
								html: this.getMessage("next") + "&rarr;",
								events: { click: BX.delegate(this.onNextBankDetailButtonClick, this) }
							}
						)
					);
				}
			}
		},
		getSelectedItemId: function()
		{
			return this._requisite ? BX.CrmEntityRequisiteInfo.resolveItemId(this._requisite) : 0;
		},
		getSelectedBankDetailId: function()
		{
			return this._bankDetail ? BX.CrmEntityBankDetailList.resolveItemId(this._bankDetail) : 0;
		},
		showNextItem: function()
		{
			if(!(this._requisiteInfo && this._requisite))
			{
				return;
			}

			var count = this._requisiteInfo.getItemCount();
			if(count === 0)
			{
				return;
			}

			var index = this._requisiteInfo.getItemIndex(this._requisite);
			if(index < 0)
			{
				index = 0;
			}

			index++;
			if(index === count)
			{
				index = 0;
			}

			this._requisite = this._requisiteInfo.getItemByIndex(index);

			if(this._requisite)
			{
				var requisiteId = BX.CrmEntityRequisiteInfo.resolveItemId(this._requisite);
				this._bankDetailList = this._requisiteInfo.getItemBankDetailList(requisiteId);
				if(this._bankDetailList)
				{
					this._bankDetail = this._bankDetailList.getSelectedItem();
					if(!this._bankDetail)
					{
						this._bankDetail = this._bankDetailList.getFirstItem();
					}
				}
			}

			this.renderRequisites();
		},
		showNextBankDetail: function()
		{
			if(!(this._bankDetailList && this._bankDetail))
			{
				return;
			}

			var count = this._bankDetailList.getItemCount();
			if(count === 0)
			{
				return;
			}

			var index = this._bankDetailList.getItemIndex(this._bankDetail);
			if(index < 0)
			{
				index = 0;
			}

			index++;
			if(index === count)
			{
				index = 0;
			}

			this._bankDetail = this._bankDetailList.getItemByIndex(index);
			this.renderBankDetails();
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}

			this._closingNotifier.notify(
				[
					{
						requisiteId: this.getSelectedItemId(),
						bankDetailId: this.getSelectedBankDetailId()
					}
				]
			);
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;

			this._wrapper = null;
			this._innerWrapper = null;

			this._popup = null;
		},
		onNextButtonClick: function(e)
		{
			this.showNextItem();
		},
		onNextBankDetailButtonClick: function(e)
		{
			this.showNextBankDetail();
		}
	};
	BX.Crm.RequisiteNavigator.options = {};
	if(typeof(BX.Crm.RequisiteNavigator.messages) === "undefined")
	{
		BX.Crm.RequisiteNavigator.messages = {};
	}
	BX.Crm.RequisiteNavigator.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteNavigator();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorFileStorage === "undefined")
{
	BX.Crm.EntityEditorFileStorage = function()
	{
		BX.Crm.EntityEditorFileStorage.superclass.constructor.apply(this);
		this._uploaderName = "entity_editor_storage_" + this._id.toLowerCase();
		this._dataContainer = null;
		this._uploaderContainer = null;
	};

	BX.extend(BX.Crm.EntityEditorFileStorage, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorFileStorage.prototype.getStorageTypeId = function()
	{
		return this._model.getIntegerField("STORAGE_TYPE_ID", BX.Crm.EditorFileStorageType.undefined);
	};
	BX.Crm.EntityEditorFileStorage.prototype.getStorageElementInfos = function()
	{
		var storageTypeId = this.getStorageTypeId();
		if(storageTypeId === BX.Crm.EditorFileStorageType.diskfile)
		{
			return this._model.getArrayField(
				this._schemeElement.getDataStringParam("diskFileInfo", "DISK_FILES"),
				[]
			);
		}

		return [];
	};
	BX.Crm.EntityEditorFileStorage.prototype.hasContentToDisplay = function()
	{
		return(this.getStorageElementInfos().length > 0);
	};
	BX.Crm.EntityEditorFileStorage.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-filestorage" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this.getTitle()));
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._dataContainer = BX.create("DIV", {});
			this._wrapper.appendChild(this._dataContainer);
		}

		this._uploaderContainer = BX.create(
			"DIV",
			{ attrs: { className: "bx-crm-dialog-activity-webdav-container" } }
		);
		this._wrapper.appendChild(this._uploaderContainer);

		var storageTypeId = this.getStorageTypeId();
		if(storageTypeId === BX.Crm.EditorFileStorageType.diskfile)
		{
			var uploader = this.prepareDiskUploader();

			uploader.setMode(this._mode);
			uploader.clearValues();
			uploader.setValues(this.getStorageElementInfos());
			uploader.layout(this._uploaderContainer);
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorFileStorage.prototype.doClearLayout = function(options)
	{
		this._dataContainer = this._uploaderContainer = null;
	};
	BX.Crm.EntityEditorFileStorage.prototype.prepareDiskUploader = function()
	{
		var uploader = null;
		if(typeof(BX.CrmDiskUploader) !== "undefined" &&
			typeof(BX.CrmDiskUploader.items[this._uploaderName]) !== "undefined"
		)
		{
			uploader = BX.CrmDiskUploader.items[this._uploaderName];
		}

		if(uploader)
		{
			uploader.cleanLayout();
		}
		else
		{
			uploader = BX.CrmDiskUploader.create(
				this._uploaderName,
				{
					msg :
						{
							diskAttachFiles : this.getMessage('diskAttachFiles'),
							diskAttachedFiles : this.getMessage('diskAttachedFiles'),
							diskSelectFile : this.getMessage('diskSelectFile'),
							diskSelectFileLegend : this.getMessage('diskSelectFileLegend'),
							diskUploadFile : this.getMessage('diskUploadFile'),
							diskUploadFileLegend : this.getMessage('diskUploadFileLegend')
						}
				}
			)
		}

		return uploader;
	};
	BX.Crm.EntityEditorFileStorage.prototype.getDiskUploaderValues = function()
	{
		var uploader = BX.CrmDiskUploader.items[this._uploaderName];
		return uploader ? uploader.getFileIds() : [];
	};
	BX.Crm.EntityEditorFileStorage.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorFileStorage.messages;
		return m.hasOwnProperty(name) ? m[name] : BX.Crm.EntityEditorFileStorage.superclass.getMessage.apply(this, arguments);
	};
	BX.Crm.EntityEditorFileStorage.prototype.save = function()
	{
		var storageTypeId = this.getStorageTypeId();
		if(storageTypeId === BX.Crm.EditorFileStorageType.diskfile)
		{
			this._model.setField(
				this._schemeElement.getDataStringParam("storageElementIds", "STORAGE_ELEMENT_IDS"),
				this.getDiskUploaderValues()
			);
		}
	};
	BX.Crm.EntityEditorFileStorage.prototype.onBeforeSubmit = function()
	{
		if(!this._dataContainer)
		{
			return;
		}

		BX.cleanNode(this._dataContainer, false);

		this._dataContainer.appendChild(
			BX.create(
				"INPUT",
				{
					attrs:
					{
						type: "hidden",
						name: this._schemeElement.getDataStringParam("storageTypeId", "STORAGE_TYPE_ID"),
						value: this.getStorageTypeId()
					}
				}
			)
		);

		var elementFieldName = this._schemeElement.getDataStringParam("storageElementIds", "STORAGE_ELEMENT_IDS");

		var values = this._model.getArrayField(elementFieldName, []);
		if(values.length > 0)
		{
			for(var i = 0, length = values.length; i < length; i++)
			{
				this._dataContainer.appendChild(
					BX.create("INPUT", { attrs: { type: "hidden", name: elementFieldName + "[]", value: values[i] } })
				);
			}
		}
		else
		{
			this._dataContainer.appendChild(
				BX.create("INPUT", { attrs: { type: "hidden", name: elementFieldName, value: "" } })
			);
		}
	};
	if(typeof(BX.Crm.EntityEditorFileStorage.messages) === "undefined")
	{
		BX.Crm.EntityEditorFileStorage.messages = {};
	}
	BX.Crm.EntityEditorFileStorage.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorFileStorage();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorCustom === "undefined")
{
	BX.Crm.EntityEditorCustom = function()
	{
		BX.Crm.EntityEditorCustom.superclass.constructor.apply(this);
		this._innerWrapper = null;
		this._runtimeValue = null;
	};

	BX.extend(BX.Crm.EntityEditorCustom, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorCustom.prototype.hasContentToDisplay = function()
	{
		return this.getHtmlContent() !== "";
	};
	BX.Crm.EntityEditorCustom.prototype.doClearLayout = function(options)
	{
		this.setRuntimeValue(this.getValue());
	};
	BX.Crm.EntityEditorCustom.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorCustom.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var classNames = this._schemeElement.getDataArrayParam("classNames", []);
		classNames.push("crm-entity-widget-content-block-field-custom");

		this.ensureWrapperCreated({ classNames: classNames });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this.getTitle()));
		this._innerWrapper = BX.create("div",
			{
				props: { className: "crm-entity-widget-content-block-inner" }
			}
		);
		this._wrapper.appendChild(this._innerWrapper);
		if (this._mode === BX.Crm.EntityEditorMode.edit)
		{
			BX.addClass(this._innerWrapper, "crm-entity-widget-content-block-inner-edit-mode");
		}

		var html = this.getHtmlContent();
		if(this._mode !== BX.Crm.EntityEditorMode.edit && !BX.type.isNotEmptyString(html))
		{
			html = this._model.getSchemeField(this._schemeElement, "empty",	"");
		}

		setTimeout(
			BX.delegate(function(){
				BX.html(this._innerWrapper, html);
				if (this._mode === BX.Crm.EntityEditorMode.edit)
				{
					BX.bindDelegate(
						this._innerWrapper,
						"bxchange",
						{ tag: [ "input", "select", "textarea" ] },
						this._changeHandler
					);
				}

			}, this),
			0
		);

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorCustom.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorCustom.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorCustom.prototype.getHtmlContent = function()
	{
		return(
			this._model.getSchemeField(
				this._schemeElement,
				this.isInEditMode() ? "edit" : "view",
				""
			)
		);
	};

	BX.Crm.EntityEditorCustom.prototype.setRuntimeValue = function(value)
	{
		this._runtimeValue = value;
	};

	BX.Crm.EntityEditorCustom.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.Crm.EntityEditorMode.edit ? this._runtimeValue : "");
	};

	BX.Crm.EntityEditorCustom.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorCustom();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorHidden === "undefined")
{
	BX.Crm.EntityEditorHidden = function()
	{
		BX.Crm.EntityEditorHidden.superclass.constructor.apply(this);
		this._input = null;
		this._view = null;
	};

	BX.extend(BX.Crm.EntityEditorHidden, BX.Crm.EntityEditorText);

	BX.Crm.EntityEditorHidden.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-text" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));

		if(this.hasContentToDisplay())
		{
			if(this.getLineCount() > 1)
			{
				this._innerWrapper = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						html: BX.util.nl2br(BX.util.htmlspecialchars(value))
					}
				);
			}
			else
			{
				this._innerWrapper = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						text: value
					}
				);
			}
		}
		else
		{
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					text: this.getMessage("isEmpty")
				}
			);
		}

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._input = BX.create("input", {
				props: {
					id: 'crm-entity-widget-content-input',
					name: name,
					type: 'hidden',
					value: value
				}
			});
			this._innerWrapper.appendChild(this._input);
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

		this.registerLayout(options);
		this._hasLayout = true;
	};

	BX.Crm.EntityEditorHidden.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorHidden();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityBindingTracker === "undefined")
{
	BX.Crm.EntityBindingTracker = function()
	{
		this._id = "";
		this._settings = {};
		this._boundEntityInfos = null;
		this._unboundEntityInfos = null;
	};

	BX.Crm.EntityBindingTracker.prototype =
	{
		initialize: function()
		{
			this._boundEntityInfos = [];
			this._unboundEntityInfos = [];
		},
		bind: function(entityInfo)
		{
			if(this.findIndex(entityInfo, this._boundEntityInfos) >= 0)
			{
				return;
			}

			var index = this.findIndex(entityInfo, this._unboundEntityInfos);
			if(index >= 0)
			{
				this._unboundEntityInfos.splice(index, 1);
			}
			else
			{
				this._boundEntityInfos.push(entityInfo);
			}
		},
		unbind: function(entityInfo)
		{
			if(this.findIndex(entityInfo, this._unboundEntityInfos) >= 0)
			{
				return;
			}

			var index = this.findIndex(entityInfo, this._boundEntityInfos);
			if(index >= 0)
			{
				this._boundEntityInfos.splice(index, 1);
			}
			else
			{
				this._unboundEntityInfos.push(entityInfo);
			}
		},
		getBoundEntities: function()
		{
			return this._boundEntityInfos;
		},
		getUnboundEntities: function()
		{
			return this._unboundEntityInfos;
		},
		isBound: function(entityInfo)
		{
			return this.findIndex(entityInfo, this._boundEntityInfos) >= 0;
		},
		isUnbound: function(entityInfo)
		{
			return this.findIndex(entityInfo, this._unboundEntityInfos) >= 0;
		},
		reset: function()
		{
			this._boundEntityInfos = [];
			this._unboundEntityInfos = [];
		},
		findIndex: function(item, collection)
		{
			var id = item.getId();
			for(var i = 0, length = collection.length; i < length; i++)
			{
				if(id === collection[i].getId())
				{
					return i;
				}
			}
			return -1;
		}
	};
	BX.Crm.EntityBindingTracker.create = function()
	{
		var self = new BX.Crm.EntityBindingTracker();
		self.initialize();
		return self;
	};
}

if(typeof BX.Crm.ClientEditorEntitySkeleton === "undefined")
{
	BX.Crm.ClientEditorEntitySkeleton = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._hasLayout = false;
	};
	BX.Crm.ClientEditorEntitySkeleton.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._container = BX.prop.getElementNode(this._settings, "container", null);
		},
		layout: function()
		{
			this._wrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-client-block crm-entity-widget-client-block-skeleton" },
					children: [ BX.create("div", { props: { className: "crm-entity-widget-client-box" } }) ]
				}
			);
			this._container.appendChild(this._wrapper);
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			this._wrapper = BX.remove(this._wrapper);
			this._hasLayout = false;
		}
	};
	BX.Crm.ClientEditorEntitySkeleton.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntitySkeleton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorEntityPanel === "undefined")
{
	BX.Crm.ClientEditorEntityPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._editor = null;
		this._entityInfo = null;
		this._enableCommunications = true;
		this._isRequisiteEnabled = true;
		this._requisiteInfo = null;
		this._requisiteNavigator = null;

		this._mode = BX.Crm.EntityEditorMode.intermediate;
		this._communicationButtons = null;
		this._deleteButton = null;

		this._container = null;
		this._wrapper = null;

		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);
		this._requisiteChangeNotifier = null;
		this._hasLayout = false;
	};
	BX.Crm.ClientEditorEntityPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._container = BX.prop.getElementNode(this._settings, "container", null);
			this._editor = BX.prop.get(this._settings, "editor");
			this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);
			this._mode = BX.prop.getInteger(this._settings, "mode", 0);

			this._enableCommunications = BX.prop.getBoolean(this._settings, "enableCommunications", true);
			this._isRequisiteEnabled = (this._entityInfo.hasRequisites()
				&& BX.prop.getBoolean(this._settings, "enableRequisite", false)
			);

			this._requisiteChangeNotifier = BX.CrmNotifier.create(this);
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		getEntity: function()
		{
			return this._entityInfo;
		},
		getMode: function()
		{
			return this._mode;
		},
		setMode: function(mode)
		{
			this._mode = mode;
		},
		isRequisiteEnabled: function()
		{
			return this._isRequisiteEnabled;
		},
		addRequisiteChangeListener: function(listener)
		{
			this._requisiteChangeNotifier.addListener(listener);
		},
		removeRequisiteChangeListener: function(listener)
		{
			this._requisiteChangeNotifier.removeListener(listener);
		},
		layout: function()
		{
			var isViewMode = this._mode === BX.Crm.EntityEditorMode.view;

			this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-block" } });
			this._container.appendChild(this._wrapper);

			var innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-box" } });
			this._wrapper.appendChild(innerWrapper);

			if(BX.prop.getBoolean(this._settings, "enableEntityTypeCaption", false))
			{
				innerWrapper.appendChild(
					BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-client-box-type" },
							text: this._entityInfo.getTypeCaption()
						}
					)
				);
			}

			this._deleteButton = null;
			if(!isViewMode)
			{
				this._deleteButton = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-client-block-remove" },
						events: { click: this._deleteButtonHandler }
					}
				);
				innerWrapper.appendChild(this._deleteButton);
			}


			var titleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-client-box-name-container" }
				}
			);
			innerWrapper.appendChild(titleWrapper);

			var buttonWrapper = BX.create("div",
				{ props: { className: "crm-entity-widget-client-actions-container" } }
			);

			var showUrl = this._entityInfo.getShowUrl();
			if(showUrl !== "")
			{
				var titleLink = BX.create("a",
					{
						props:
							{
								className: "crm-entity-widget-client-box-name",
								href: this._entityInfo.getShowUrl()
							},
						text: this._entityInfo.getTitle()
					}
				);

				if(this.isRequisiteEnabled())
				{
					BX.bind(titleLink, "mouseover", BX.debounce(this.onMouseOver, 300, this));
					BX.bind(titleLink, "mouseout", BX.debounce(this.onMouseOut, 300, this));
				}

				titleWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-box-name-row" },
							children: [ titleLink, buttonWrapper ]
						}
					)
				);
			}
			else
			{
				var titleNone = BX.create("span",
					{
						props:{ className: "crm-entity-widget-client-box-name" },
						text: this._entityInfo.getTitle()
					}
				);

				titleWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-box-name-row" },
							children: [ titleNone, buttonWrapper ]
						}
					)
				);
			}

			if(this._enableCommunications)
			{
				this._communicationButtons = [];
				var commTypes = [ "PHONE", "EMAIL", "IM" ];
				for(var i = 0, j = commTypes.length; i < j; i++)
				{
					var commType = commTypes[i];
					var button = BX.Crm.ClientEditorCommunicationButton.create(
						this._id +  "_" + commType,
						{
							entityInfo: this._entityInfo,
							type: commType,
							ownerTypeId: this._editor.getOwnerTypeId(),
							ownerId: this._editor.getOwnerId(),
							container: buttonWrapper
						}
					);
					button.layout();
					this._communicationButtons.push(button);
				}
			}

			var description = this._entityInfo.getDescription();
			if(description !== "")
			{
				innerWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-box-position" },
							text: description
						}
					)
				);
			}

			var phones = this._entityInfo.getPhones();
			var emails = this._entityInfo.getEmails();
			if(phones.length > 0 || emails.length > 0)
			{
				var communicationContainer = BX.create("div", { props: { className: "crm-entity-widget-client-contact" } });
				innerWrapper.appendChild(communicationContainer);

				if(phones.length > 0)
				{
					communicationContainer.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-contact-item crm-entity-widget-client-contact-phone" },
								//HACK: Disable autodetection of phone number for Microsoft Edge
								attrs: { "x-ms-format-detection": "none" },
								text: phones[0]["VALUE_FORMATTED"]
							}
						)
					);
				}

				if(emails.length > 0)
				{
					communicationContainer.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-contact-item crm-entity-widget-client-contact-email" },
								text: emails[0]["VALUE_FORMATTED"]
							}
						)
					);
				}
			}

			var callback = BX.prop.getFunction(this._settings, "onLayout", null);
			if(callback)
			{
				callback(this, this._wrapper);
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(this._requisiteNavigator)
			{
				this._requisiteNavigator.removeClosingListener(this._requisiteChangeHandler);
				this._requisiteNavigator.close();
				this._requisiteNavigator = null;
			}

			this._communicationButtons = null;
			this._wrapper = BX.remove(this._wrapper);
			this._hasLayout = false;
		},
		checkOwership: function(element)
		{
			return this._wrapper && BX.isParentForNode(this._wrapper, element);
		},
		onMouseOver: function(e)
		{
			if(this._requisiteHandle > 0)
			{
				window.clearTimeout(this._requisiteHandle);
				this._requisiteHandle = 0;
			}

			this._requisiteHandle = window.setTimeout(
				BX.delegate(this.openRequisiteNavigator, this),
				300
			);
		},
		onMouseOut: function(e)
		{
			if(this._requisiteHandle > 0)
			{
				window.clearTimeout(this._requisiteHandle);
				this._requisiteHandle = 0;
			}
		},
		openRequisiteNavigator: function()
		{
			if(!this.isRequisiteEnabled())
			{
				return;
			}

			if(this._requisiteHandle === 0)
			{
				return;
			}
			this._requisiteHandle = 0;

			if(!this._requisiteNavigator)
			{
				if(!this._requisiteInfo)
				{
					var requisiteBinding = BX.prop.getObject(this._settings, "requisiteBinding", {});
					this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
						{
							requisiteId: BX.prop.getInteger(requisiteBinding, "REQUISITE_ID", 0),
							bankDetailId: BX.prop.getInteger(requisiteBinding, "BANK_DETAIL_ID", 0),
							data: this._entityInfo.getRequisites()
						}
					);
				}

				this._requisiteNavigator = BX.Crm.RequisiteNavigator.create(this._id, { requisiteInfo: this._requisiteInfo });
				this._requisiteNavigator.addClosingListener(this._requisiteChangeHandler);
			}
			this._requisiteNavigator.open(this._wrapper);
		},
		closeRequisiteNavigator: function()
		{
			if(this._requisiteHandle === 0)
			{
				return;
			}
			this._requisiteHandle = 0;

			if(this._requisiteNavigator)
			{
				this._requisiteNavigator.close();
			}
		},
		onDeleteButtonClick: function(e)
		{
			var callback = BX.prop.getFunction(this._settings, "onDelete");
			if(callback)
			{
				callback(this);
			}
		},
		onRequisiteChange: function(sender, eventArgs)
		{
			var requisiteId = BX.prop.getInteger(eventArgs, "requisiteId", 0);
			var bankDetailId = BX.prop.getInteger(eventArgs, "bankDetailId", 0);

			if(!this._requisiteInfo
				|| this._requisiteInfo.getRequisiteId() !== requisiteId
				|| this._requisiteInfo.getBankDetailId() !== bankDetailId
			)
			{
				this._requisiteChangeNotifier.notify([ eventArgs ]);
			}
		}
	};
	BX.Crm.ClientEditorEntityPanel.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntityPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorEntityBindingPanel === "undefined")
{
	BX.Crm.ClientEditorEntityBindingPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._entityInfo = null;
		this._editor = null;
		this._mode = BX.Crm.EntityEditorMode.intermediate;
		this._item = null;
	};
	BX.Crm.ClientEditorEntityBindingPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._container = BX.prop.getElementNode(this._settings, "container", null);
			this._editor = BX.prop.get(this._settings, "editor");
			this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);

			this._mode = BX.prop.getInteger(this._settings, "mode", 0);
			this._item = BX.Crm.ClientEditorEntityPanel.create(
				this._id +  "_" + this._entityInfo.getId().toString(),
				{
					editor: this._editor,
					entityInfo: this._entityInfo,
					mode: this._mode,
					onLayout: BX.delegate(this.onItemLayout, this),
					onDelete: BX.delegate(this.onItemDelete, this)
				}
			);
		},
		getEntity: function()
		{
			return this._entityInfo;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		layout: function()
		{
			this._button = BX.create("div",
				{
					props: { className: "crm-entity-widget-client-child-link" },
					events: { click: BX.delegate(this.onButtonClick, this) }
				}
			);

			this._item.setContainer(this._container);
			this._item.layout();
		},
		onItemLayout: function(item, wrapper)
		{
			BX.addClass(wrapper, "crm-entity-widget-client-block-child");
			var anchor = wrapper.firstChild;
			if(anchor)
			{
				wrapper.insertBefore(this._button, anchor);
			}
			else
			{
				wrapper.appendChild(this._button);
			}
		},
		clearLayout: function()
		{
			this._item.clearLayout();
		},
		onItemDelete: function(item)
		{
			if(this._mode !== BX.Crm.EntityEditorMode.edit)
			{
				return;
			}
			var callback = BX.prop.getFunction(this._settings, "onChange", null);
			if(callback)
			{
				callback(this, "delete");
			}
		},
		onButtonClick: function(e)
		{
			if(this._mode !== BX.Crm.EntityEditorMode.edit)
			{
				return;
			}
			var callback = BX.prop.getFunction(this._settings, "onChange", null);
			if(callback)
			{
				callback(this, "unbind");
			}
		}
	};
	BX.Crm.ClientEditorEntityBindingPanel.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntityBindingPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorCommunicationButton === "undefined")
{
	BX.Crm.ClientEditorCommunicationButton = function()
	{
		this._id = "";
		this._settings = {};
		this._entityInfo = null;
		this._type = "";

		this._items = null;

		this._container = null;
		this._wrapper = null;
		this._menu = null;
	};
	BX.Crm.ClientEditorCommunicationButton.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);
			this._type = BX.prop.getString(this._settings, "type", "");

			this._container = BX.prop.getElementNode(this._settings, "container", "");
			if(this._type === "")
			{
				this._type = "PHONE";
			}

			this._items = this._entityInfo.getMultiFieldsByType(this._type);
		},
		layout: function()
		{
			var className = "";
			if(this._type === "EMAIL")
			{
				className = "crm-entity-widget-client-action-mail";
			}
			else if(this._type === "IM")
			{
				className = "crm-entity-widget-client-action-im";
			}
			else// if(this._type === "PHONE")
			{
				className = "crm-entity-widget-client-action-call";
			}

			if(this._items.length > 0)
			{
				className += " crm-entity-widget-client-action-available";
			}

			this._wrapper = BX.create("a", { props: { className: className } });
			BX.bind(this._wrapper, "click", BX.delegate(this.onClick, this));
			this._container.appendChild(this._wrapper);
		},
		onClick: function(e)
		{
			if(this._items.length === 0)
			{
				return BX.eventReturnFalse(e);
			}

			if(this._items.length === 1)
			{
				var item = this._items[0];
				var value = BX.prop.getString(item, "VALUE");
				if(value !== "")
				{
					if(this._type === "PHONE")
					{
						this.addCall(value);
					}
					else if(this._type === "EMAIL")
					{
						this.addEmail(value);
					}
					else if(this._type === "IM")
					{
						this.openChat(value);
					}
				}
				return BX.eventReturnFalse(e);
			}

			this.toggleMenu();
			BX.eventReturnFalse(e);
		},
		toggleMenu: function()
		{
			if(!this._menu)
			{
				var menuItems = [];
				for(var i = 0, l = this._items.length; i < l; i++)
				{
					var value = BX.prop.getString(this._items[i], "VALUE");
					var formattedValue = BX.prop.getString(this._items[i], "VALUE_FORMATTED");
					var complexName = BX.prop.getString(this._items[i], "COMPLEX_NAME");
					var itemText = (complexName ? complexName + ': ' : '') + (formattedValue ? formattedValue : value);

					if(value !== "")
					{
						menuItems.push({ id: value, text:  itemText });
					}
				}

				this._menu = BX.Crm.ClientEditorMenu.create(
					this._id.toLowerCase() + "_menu",
					{
						anchor: this._wrapper,
						items: menuItems,
						callback: BX.delegate(this.onMenuItemSelect, this)
					}
				);
			}
			this._menu.toggle();
		},
		onMenuItemSelect: function(menu, item)
		{
			if(this._type === "EMAIL")
			{
				this.addEmail(item["id"])
			}
			else if(this._type === "IM")
			{
				this.openChat(item["id"]);
			}
			else// if(this._type === "PHONE")
			{
				this.addCall(item["id"])
			}

			this._menu.close();
		},
		addCall: function(phone)
		{
			if(typeof(window.top['BXIM']) === 'undefined')
			{
				window.alert(this.getMessage("telephonyNotSupported"));
				return;
			}

			var params =
			{
				"ENTITY_TYPE_NAME": this._entityInfo.getTypeName(),
				"ENTITY_ID": this._entityInfo.getId(),
				"AUTO_FOLD": true
			};

			var ownerTypeId = BX.prop.getInteger(this._settings, "ownerTypeId", 0);
			var ownerId = BX.prop.getInteger(this._settings, "ownerId", 0);
			if(ownerTypeId !== this._entityInfo.getTypeId() || ownerId !== this._entityInfo.getId())
			{
				 params["BINDINGS"] = [ { "OWNER_TYPE_NAME": BX.CrmEntityType.resolveName(ownerTypeId), "OWNER_ID": ownerId } ];
			}

			window.top['BXIM'].phoneTo(phone, params);
		},
		addEmail: function(email)
		{
			BX.CrmActivityEditor.addEmail(
				{
					communicationsLoaded: true,
					communications:
						[
							{
								type: "EMAIL",
								entityType: this._entityInfo.getTypeName(),
								entityId: this._entityInfo.getId(),
								value: email
							}
						]
				}
			);
		},
		openChat: function (messengerValue)
		{
			if(typeof(window.top["BXIM"]) === "undefined")
			{
				window.alert(this.getMessage("messagingNotSupported"));
				return;
			}
			window.top["BXIM"].openMessengerSlider(messengerValue, {RECENT: 'N', MENU: 'N'});
		}
	};
	BX.Crm.ClientEditorCommunicationButton.prototype.getMessage = function(name)
	{
		var m = BX.Crm.ClientEditorCommunicationButton.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	if(typeof(BX.Crm.ClientEditorCommunicationButton.messages) === "undefined")
	{
		BX.Crm.ClientEditorCommunicationButton.messages = {};
	}
	BX.Crm.ClientEditorCommunicationButton.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorCommunicationButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorMenu === "undefined")
{
	BX.Crm.ClientEditorMenu = function()
	{
		this._id = null;
		this._settings = {};
		this._items = null;
		this._isOpened = false;
		this._popup = null;
	};

	BX.Crm.ClientEditorMenu.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._items = BX.prop.getArray(this._settings, "items", []);
			for(var i = 0, l = this._items.length; i < l; i++)
			{
				this._items[i]["onclick"] = BX.delegate(this.onItemSelect, this);
			}
		},
		onItemSelect: function(e, item)
		{
			var callback = BX.prop.getFunction(this._settings, "callback", null);
			if(callback)
			{
				callback(this, item);
			}
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function()
		{
			if(this._isOpened)
			{
				return;
			}

			BX.PopupMenu.show(
				this._id,
				BX.prop.getElementNode(this._settings, "anchor", null),
				this._items,
				{
					offsetTop: 0,
					offsetLeft: 0,
					events:
						{
							onPopupShow: BX.delegate(this.onPopupShow, this),
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
			this._popup = BX.PopupMenu.currentItem;
		},
		close: function()
		{
			if(!this._isOpened)
			{
				return;
			}

			if(this._popup)
			{
				if(this._popup.popupWindow)
				{
					this._popup.popupWindow.close();
				}
			}
		},
		toggle: function()
		{
			if(!this._isOpened)
			{
				this.open();
			}
			else
			{
				this.close();
			}
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup && this._popup.popupWindow)
			{
				this._popup.popupWindow.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		}
	};
	BX.Crm.ClientEditorMenu.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorMenu();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.UserFieldTypeMenu) === "undefined")
{
	BX.Crm.UserFieldTypeMenu = function()
	{
		this._id = null;
		this._settings = {};
		this._items = null;
		this._isOpened = false;

		this._wrapper = null;
		this._innerWrapper = null;

		this._topScrollButton = null;
		this._bottomScrollButton = null;

		this._bottomButtonMouseOverHandler = BX.delegate(this.onBottomButtonMouseOver, this);
		this._bottomButtonMouseOutHandler = BX.delegate(this.onBottomButtonMouseOut, this);

		this._topButtonMouseOverHandler = BX.delegate(this.onTopButtonMouseOver, this);
		this._topButtonMouseOutHandler = BX.delegate(this.onTopButtonMouseOut, this);

		this._scrollHandler = BX.throttle(this.onScroll, 100, this);

		this._enableScrollToBottom = false;
		this._enableScrollToTop = false;

		this._popup = null;
	};

	BX.Crm.UserFieldTypeMenu.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._items = [];
			var itemData = BX.prop.getArray(settings, "items");
			for(var i = 0, length = itemData.length; i < length; i++)
			{
				var data = itemData[i];
				data["menu"] = this;
				this._items.push(
					BX.Crm.UserFieldTypeMenuItem.create(
						BX.prop.getString(data, "value"),
						data
					)
				);
			}
		},
		getId: function()
		{
			return this._id;
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function(anchor)
		{
			if(this._isOpened)
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				anchor,
				{
					autoHide: true,
					draggable: false,
					offsetLeft: 0,
					offsetTop: 0,
					noAllPaddings: true,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					content: this.prepareContent()
				}
			);
			this._popup.show();
		},
		close: function()
		{
			if(!this._isOpened)
			{
				return;
			}

			if(this._popup)
			{
				this._popup.close();
			}
		},
		prepareContent: function()
		{
			this._wrapper = BX.create("div", { props: { className: "crm-entity-card-widget-create-field-popup" } });

			var scrollIcon = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"42\" height=\"13\" viewBox=\"0 0 42 13\">\n" +
				"  <polyline fill=\"none\" stroke=\"#CACDD1\" stroke-width=\"2\" points=\"274 98 284 78.614 274 59\" transform=\"rotate(90 186 -86.5)\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>\n" +
				"</svg>\n";

			this._topScrollButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-card-widget-popup-scroll-control-top" },
					html: scrollIcon
				}
			);
			this._wrapper.appendChild(this._topScrollButton);

			this._bottomScrollButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-card-widget-popup-scroll-control-bottom" },
					html: scrollIcon
				}
			);
			this._wrapper.appendChild(this._bottomScrollButton);

			this._innerWrapper = BX.create("div", { props: { className: "crm-entity-card-widget-create-field-list" } });
			this._wrapper.appendChild(this._innerWrapper);

			for(var i = 0, length = this._items.length; i < length; i++)
			{
				this._innerWrapper.appendChild(this._items[i].prepareContent());
			}
			return this._wrapper;
		},
		adjust: function()
		{
			var height = this._innerWrapper.offsetHeight;
			var scrollTop = this._innerWrapper.scrollTop;
			var scrollHeight = this._innerWrapper.scrollHeight;

			if(scrollTop === 0)
			{
				BX.addClass(this._topScrollButton, "control-hide");
			}
			else
			{
				BX.removeClass(this._topScrollButton, "control-hide");
			}

			if((scrollTop + height) === scrollHeight)
			{
				BX.addClass(this._bottomScrollButton, "control-hide");
			}
			else
			{
				BX.removeClass(this._bottomScrollButton, "control-hide");
			}
		},
		onItemSelect: function(item)
		{
			var callback = BX.prop.getFunction(this._settings, "callback", null);
			if(callback)
			{
				callback(this, item);
			}
		},
		onPopupShow: function()
		{
			this._isOpened = true;

			BX.bind(this._bottomScrollButton, "mouseover", this._bottomButtonMouseOverHandler);
			BX.bind(this._bottomScrollButton, "mouseout", this._bottomButtonMouseOutHandler);

			BX.bind(this._topScrollButton, "mouseover", this._topButtonMouseOverHandler);
			BX.bind(this._topScrollButton, "mouseout", this._topButtonMouseOutHandler);

			BX.bind(this._innerWrapper, "scroll", this._scrollHandler);

			window.setTimeout(this.adjust.bind(this), 100);
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;

			BX.unbind(this._bottomScrollButton, "mouseover", this._bottomButtonMouseOverHandler);
			BX.unbind(this._bottomScrollButton, "mouseout", this._bottomButtonMouseOutHandler);

			BX.unbind(this._topScrollButton, "mouseover", this._topButtonMouseOverHandler);
			BX.unbind(this._topScrollButton, "mouseout", this._topButtonMouseOutHandler);

			BX.unbind(this._innerWrapper, "scroll", this._scrollHandler);

			this._wrapper = null;
			this._innerWrapper = null;
			this._topScrollButton = null;
			this._bottomScrollButton = null;

			this._popup = null;
		},
		onBottomButtonMouseOver: function(e)
		{
			if(this._enableScrollToBottom)
			{
				return;
			}

			this._enableScrollToBottom = true;
			this._enableScrollToTop = false;

			(function scroll()
			{
				if(!this._enableScrollToBottom)
				{
					return;
				}

				var el = this._innerWrapper;
				if((el.scrollTop + el.offsetHeight) !== el.scrollHeight)
				{
					el.scrollTop += 3;
				}

				if((el.scrollTop + el.offsetHeight) === el.scrollHeight)
				{
					this._enableScrollToBottom = false;
					//console.log("scrollToBottom: completed");
				}
				else
				{
					window.setTimeout(scroll.bind(this), 20);
				}
			}).bind(this)();
		},
		onBottomButtonMouseOut: function()
		{
			this._enableScrollToBottom = false;
		},
		onTopButtonMouseOver: function(e)
		{
			if(this._enableScrollToTop)
			{
				return;
			}

			this._enableScrollToBottom = false;
			this._enableScrollToTop = true;

			(function scroll()
			{
				if(!this._enableScrollToTop)
				{
					return;
				}

				var el = this._innerWrapper;
				if(el.scrollTop > 0)
				{
					el.scrollTop -= 3;
				}

				if(el.scrollTop === 0)
				{
					this._enableScrollToTop = false;
					//console.log("scrollToTop: completed");
				}
				else
				{
					window.setTimeout(scroll.bind(this), 20);
				}
			}).bind(this)();
		},
		onTopButtonMouseOut: function()
		{
			this._enableScrollToTop = false;
		},
		onScroll: function(e)
		{
			this.adjust();
		}
	};
	BX.Crm.UserFieldTypeMenu.create = function(id, settings)
	{
		var self = new BX.Crm.UserFieldTypeMenu();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.UserFieldTypeMenuItem) === "undefined")
{
	BX.Crm.UserFieldTypeMenuItem = function()
	{
		this._id = "";
		this._settings = null;
		this._menu = "";
		this._value = "";
		this._text = "";
		this._legend = "";
	};
	BX.Crm.UserFieldTypeMenuItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._menu = BX.prop.get(settings, "menu");
			this._value = BX.prop.getString(settings, "value");
			this._text = BX.prop.getString(settings, "text");
			this._legend = BX.prop.getString(settings, "legend");
		},
		getId: function()
		{
			return this._id;
		},
		getValue: function()
		{
			return this._value;
		},
		getText: function()
		{
			return this._text;
		},
		getLegend: function()
		{
			return this._legend;
		},
		prepareContent: function()
		{
			var wrapper = BX.create(
				"span",
				{
					props: { className: "crm-entity-card-widget-create-field-item" },
					events: { click: BX.delegate(this.onClick, this) }
				}
			);

			wrapper.appendChild(
				BX.create(
					"span",
					{
						props: { className: "crm-entity-card-widget-create-field-item-title" },
						text: this._text
					}
				)
			);

			wrapper.appendChild(
				BX.create(
					"span",
					{
						props: { className: "crm-entity-card-widget-create-field-item-desc" },
						text: this._legend
					}
				)
			);

			return wrapper;
		},
		onClick: function(e)
		{
			this._menu.onItemSelect(this);
		}
	};
	BX.Crm.UserFieldTypeMenuItem.create = function(id, settings)
	{
		var self = new BX.Crm.UserFieldTypeMenuItem();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorSubsection === "undefined")
{
	BX.Crm.EntityEditorSubsection = function()
	{
		BX.Crm.EntityEditorSubsection.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorSubsection, BX.Crm.EntityEditorSection);
	BX.Crm.EntityEditorSubsection.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorSubsection.superclass.initialize.call(this, id, settings);
		this.initializeFromModel();
	};

	BX.Crm.EntityEditorSubsection.prototype.ensureWrapperCreated = function(params)
	{
		if(!this._wrapper)
		{
			this._wrapper = BX.create("div");
		}

		return this._wrapper;
	};
	BX.Crm.EntityEditorSubsection.prototype.layout = function(options)
	{
		//Create wrapper
		this._contentContainer = BX.create("div");
		var isViewMode = this._mode === BX.Crm.EntityEditorMode.view ;
		this.ensureWrapperCreated();
		this.layoutTitle();

		this._wrapper.appendChild(this._contentContainer);

		//Layout fields
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			this.layoutChild(this._fields[i]);
		}

		this._addChildButton = this._createChildButton = null;

		if (this.isDragEnabled())
		{
			this._dragContainerController = BX.Crm.EditorDragContainerController.create(
				"section_" + this.getId(),
				{
					charge: BX.Crm.EditorFieldDragContainer.create(
						{
							section: this,
							context: this._draggableContextId
						}
					),
					node: this._wrapper
				}
			);
			this._dragContainerController.addDragFinishListener(this._dropHandler);

			this.initializeDragDropAbilities();
		}

		this._addChildButton = this._createChildButton = null;

		if(!isViewMode)
		{
			this.createButtonPanel();
			this._contentContainer.appendChild(this._buttonPanelWrapper);

		}

		this._hasLayout = true;
		this.registerLayout(options);
	};
	BX.Crm.EntityEditorSubsection.prototype.getChildDragScope = function()
	{
		return BX.Crm.EditorDragScope.parent;
	};
	BX.Crm.EntityEditorSubsection.prototype.createButtonPanel = function()
	{
		this._buttonPanelWrapper = BX.create("div", {
			props: { className: "crm-entity-widget-content-block" }
		});
	};

	BX.Crm.EntityEditorSubsection.prototype.layoutChild = function(field)
	{
		field.setContainer(this._contentContainer);
		field.setDraggableContextId(this._draggableContextId);
		this.setChildVisible(field);
		//Force layout reset because of animation implementation
		field.releaseLayout();
		field.layout();
		if(this._mode !== BX.Crm.EntityEditorMode.view && field.isHeading())
		{
			field.focus();
		}
	};

	BX.Crm.EntityEditorSubsection.prototype.setChildVisible = function(field)
	{
		field.setVisible(BX.prop.getBoolean(field._schemeElement._settings, "isVisible", true));
	};

	BX.Crm.EntityEditorSubsection.prototype.isDragEnabled = function()
	{
		return false;
	};

	BX.Crm.EntityEditorSubsection.prototype.layoutTitle = function()
	{
	};

	BX.Crm.EntityEditorSubsection.prototype.isCreationEnabled = function()
	{
		return false;
	};

	BX.Crm.EntityEditorSubsection.prototype.isContextMenuEnabled = function()
	{
		return false;
	};

	BX.Crm.EntityEditorSubsection.prototype.isRequired = function()
	{
		return true;
	};

	BX.Crm.EntityEditorSubsection.prototype.getRuntimeValue = function()
	{
		var data = [];

		for (var i=0; i < this.getChildCount();i++)
		{
			var fieldValue = this._fields[i].getRuntimeValue();

			if (BX.type.isArray(fieldValue))
			{
				for (var key in fieldValue)
				{
					if(fieldValue.hasOwnProperty(key))
					{
						data[key] = fieldValue[key];
					}
				}
			}
			else
			{
				data[this._fields[i].getName()] = fieldValue
			}
		}
		return data;
	};
	BX.Crm.EntityEditorSubsection.prototype.createDragButton = function()
	{
		if(!this._dragButton)
		{
			this._dragButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-draggable-btn-container" },
					children:
						[
							BX.create(
								"div",
								{
									props: { className: "crm-entity-widget-content-block-draggable-btn" }
								}
							)
						]
				}
			);
		}
		return this._dragButton;
	};
	BX.Crm.EntityEditorSubsection.prototype.initializeDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			return;
		}

		this._dragItem = BX.Crm.EditorDragItemController.create(
			"field_" +  this.getId(),
			{
				charge: BX.Crm.EditorFieldDragItem.create(
					{
						control: this,
						contextId: this._draggableContextId,
						scope: this.getDragScope()
					}
				),
				node: this.createDragButton(),
				showControlInDragMode: false,
				ghostOffset: { x: 0, y: 0 }
			}
		);
	};
	BX.Crm.EntityEditorSubsection.prototype.processChildControlChange = function(child, params)
	{
		if(this._isChanged)
		{
			return;
		}

		this.markAsChanged(params);
	};
	BX.Crm.EntityEditorSubsection.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorSubsection();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorRecurring === "undefined")
{
	BX.Crm.EntityEditorRecurring = function()
	{
		BX.Crm.EntityEditorRecurring.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorRecurring, BX.Crm.EntityEditorSubsection);
	BX.Crm.EntityEditorRecurring.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorRecurring.superclass.initialize.call(this, id, settings);
		var data = this._schemeElement.getData();
		this._schemeFieldData = BX.prop.getObject(data, 'fieldData', {});
		this._enableRecurring = BX.prop.getBoolean(this._schemeElement._settings, "enableRecurring", true);
		this._recurringModel = this._model.getField(this.getName());
	};

	BX.Crm.EntityEditorRecurring.prototype.initializeFromModel =  function()
	{
		BX.Crm.EntityEditorRecurring.superclass.initializeFromModel.call(this);
		var _this = this;
		for (var i = 0, length = this._fields.length; i < length; i++)
		{
			this._fields[i].getValue = function(name){
				if (!BX.type.isNotEmptyString(name))
				{
					name = this.getName();
				}
				return _this.getRecurringFieldValue(name);
			};
		}
	};

	BX.Crm.EntityEditorRecurring.prototype.getRecurringModel =  function()
	{
		var parent = this.getParent();
		if (parent instanceof BX.Crm.EntityEditorRecurring)
		{
			return parent.getRecurringModel();
		}

		return this._recurringModel;
	};
	BX.Crm.EntityEditorRecurring.prototype.isContextMenuEnabled = function()
	{
		return BX.Crm.EntityEditorSubsection.superclass.isContextMenuEnabled.call(this);
	};
	BX.Crm.EntityEditorRecurring.prototype.isNeedToDisplay = function()
	{
		return false;
	};
	BX.Crm.EntityEditorRecurring.prototype.isRequired = function()
	{
		return this._schemeElement && this._schemeElement.isRequired();
	};
	BX.Crm.EntityEditorRecurring.prototype.prepareContextMenuItems = function()
	{
		var results = [];
		results.push({ value: "hide", text: this.getMessage("hide") });

		return results;
	};
	BX.Crm.EntityEditorRecurring.prototype.processContextMenuCommand = function(e, command)
	{
		if(command === "hide")
		{
			window.setTimeout(BX.delegate(this.hide, this), 500);
		}
		else if (this._parent && this._parent.hasAdditionalMenu())
		{
			this._parent.processChildAdditionalMenuCommand(this, command);
		}
		this.closeContextMenu();
	};
	BX.Crm.EntityEditorRecurring.prototype.isDragEnabled = function()
	{
		return BX.Crm.EntityEditorSubsection.superclass.isDragEnabled.call(this);
	};
	BX.Crm.EntityEditorRecurring.prototype.getDragObjectType = function()
	{
		return BX.Crm.EditorDragObjectType.field;
	};
	BX.Crm.EntityEditorRecurring.prototype.hasContentToDisplay = function()
	{
		return true;
	};
	BX.Crm.EntityEditorRecurring.prototype.getRecurringMode =  function()
	{
		var parent = this.getParent();
		if (parent instanceof BX.Crm.EntityEditorRecurring)
		{
			return parent.getRecurringMode();
		}

		return this.getRecurringFieldValue('RECURRING[MODE]');
	};

	BX.Crm.EntityEditorRecurring.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRecurring.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorRecurring.prototype.processChildControlChange = function(child, params)
	{
		var childName = child.getName();
		var refreshLayout = false;
		var previousValue = child.getValue();
		var changedValue = child.getRuntimeValue();
		if (previousValue !== changedValue)
		{
			switch (childName)
			{
				case 'RECURRING[MODE]':
				case 'RECURRING[MULTIPLE_TYPE_LIMIT]':
				case 'RECURRING[BEGINDATE_TYPE]':
				case 'RECURRING[CLOSEDATE_TYPE]':
					refreshLayout = true;
					break;
				case 'RECURRING[MULTIPLE_TYPE]':
					if (
						previousValue === this.getSchemeFieldValue('MULTIPLE_CUSTOM')
						|| changedValue === this.getSchemeFieldValue('MULTIPLE_CUSTOM')
					)
					{
						refreshLayout = true;
					}
			}
		}
		var recurringModel = this.getRecurringModel();
		this.setChangedValue(childName, changedValue, recurringModel);
		BX.Crm.EntityEditorRecurring.superclass.processChildControlChange.call(this, child, params);
		if (refreshLayout)
		{
			this.refreshLayout();
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.setChangedValue = function(childName, value, model)
	{
		if (typeof value === "object")
		{
			for (var key in value)
			{
				if(value.hasOwnProperty(key))
				{
					this.setChangedValue(key, value[key], model);
				}
			}
		}
		else
		{
			model[childName] = value;
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.layout = function(options)
	{
		//Create wrapper
		this._contentContainer = BX.create("div");

		if (this.isMainSubsection())
		{
			this._contentContainer.classList.add("crm-entity-widget-content");
		}

		var isViewMode = this._mode === BX.Crm.EntityEditorMode.view ;
		this.ensureWrapperCreated();
		this.layoutTitle();

		this._wrapper.appendChild(this._contentContainer);

		if (isViewMode)
		{
			var viewNode = BX.create("div", {
				props:{
					className: "crm-entity-widget-content-block crm-entity-widget-content-block-click-editable"
				},
				children: [this.createTitleNode(this.getTitle())]
			});
			this._contentContainer.appendChild(viewNode);

			var textNode = BX.create("div");
			var layoutData = this._schemeElement.getData();
			if (this._schemeElement._promise instanceof BX.Promise)
			{
				this.loadViewText();
				this._schemeElement._promise.then(
					BX.proxy(function() {
						textNode.classList = "crm-entity-widget-content-block-inner";
						textNode.innerHTML = BX.util.htmlspecialchars(layoutData.view.text);
						viewNode.innerHTML = '';
						viewNode.appendChild(textNode);
						this._schemeElement._promise = null;
					}, this)
				);
			}
			else if (BX.type.isNotEmptyString(layoutData.view.text))
			{
				textNode.classList = "crm-entity-widget-content-block-inner";
				textNode.innerHTML = layoutData.view.text;
				viewNode.appendChild(textNode)
			}
			if (this._enableRecurring)
			{
				BX.bind(textNode, "click", BX.delegate(this.toggle, this));
			}

			if(this.isContextMenuEnabled())
			{
				viewNode.appendChild(this.createContextMenuButton());
			}
			if(this.isDragEnabled())
			{
				viewNode.appendChild(this.createDragButton());
				this.initializeDragDropAbilities();
			}
		}
		else if(!this._enableRecurring)
		{
			var viewNode = BX.create("div", {
				props:{
					className: "crm-entity-widget-content-block"
				},
				children: [this.createTitleNode(this.getMessage('modeTitle'))]
			});

			var disabledField = BX.create("div",{
				props: {
					className:'crm-entity-widget-content-block-inner'
				},
				children:[
					BX.create("div",{
						type:"text",
						props: {
							className:'crm-entity-widget-content-input',
							disabled: "disabled"
						},
						text: this.getMessage('notRepeat'),
						events: {
							click: BX.delegate(this.showLicencePopup,this)
						}
					})
				]

			});
			viewNode.appendChild(disabledField);
			var lock = BX.create("button",{
				props: {
					className:'crm-entity-widget-content-block-locked-icon'
				},
				events: {
					click: BX.delegate(this.showLicencePopup,this)
				}
			});
			viewNode.appendChild(lock);
			this._contentContainer.appendChild(viewNode);
		}
		else
		{
			for(var i = 0, l = this._fields.length; i < l; i++)
			{
				this._fields[i].isDragEnabled = function(){
					return false;
				};
				this.layoutChild(this._fields[i]);
			}
		}
		//Layout fields

		this._addChildButton = this._createChildButton = null;
		this._hasLayout = true;
		this.registerLayout(options);
	};
	BX.Crm.EntityEditorRecurring.prototype.createTitleNode = function(title)
	{
		var titleNode = BX.create(
			"div",
			{
				attrs: { className: "crm-entity-widget-content-block-title" },
				children: [
					BX.create(
						"span",
						{
							attrs: { className: "crm-entity-widget-content-block-title-text" },
							text: title
						}
					)
				]
			}
		);

		return titleNode;
	};
	BX.Crm.EntityEditorRecurring.prototype.setChildVisible = function(field)
	{
		var value = false;
		var name = field.getName();
		var mode = this.getRecurringMode();
		if (name === 'RECURRING[MODE]')
		{
			value = true;
		}
		else if (mode === this.getSchemeFieldValue('SINGLE_EXECUTION'))
		{
			switch (name)
			{
				case 'SINGLE_PARAMS':
				case 'RECURRING[BEGINDATE_TYPE]':
				case 'RECURRING[CLOSEDATE_TYPE]':
				case 'SUBTITLE_NEW_ORDER_PARAMS':
				case 'NEW_BEGINDATE':
				case 'NEW_CLOSEDATE':
				case 'RECURRING[CATEGORY_ID]':
					value = true;
					break;
				case 'OFFSET_BEGINDATE':
					if (this.getRecurringFieldValue('RECURRING[BEGINDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
				case 'OFFSET_CLOSEDATE':
					if (this.getRecurringFieldValue('RECURRING[CLOSEDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
			}
		}
		else if (mode === this.getSchemeFieldValue('MULTIPLE_EXECUTION'))
		{
			switch (name)
			{
				case 'MULTIPLE_PARAMS':
				case 'RECURRING[MULTIPLE_TYPE]':
				case 'RECURRING[CATEGORY_ID]':
				case 'RECURRING[MULTIPLE_DATE_START]':
				case 'MULTIPLE_LIMIT':
				case 'RECURRING[MULTIPLE_TYPE_LIMIT]':
				case 'SUBTITLE_NEW_ORDER_PARAMS':
				case 'NEW_BEGINDATE':
				case 'NEW_CLOSEDATE':
				case 'RECURRING[BEGINDATE_TYPE]':
				case 'RECURRING[CLOSEDATE_TYPE]':
					value = true;
					break;
				case 'MULTIPLE_CUSTOM':
					if (this.getRecurringFieldValue('RECURRING[MULTIPLE_TYPE]') === this.getSchemeFieldValue('MULTIPLE_CUSTOM'))
					{
						value = true;
					}
					break;
				case 'RECURRING[MULTIPLE_DATE_LIMIT]':
					if (this.getRecurringFieldValue('RECURRING[MULTIPLE_TYPE_LIMIT]') === this.getSchemeFieldValue('LIMITED_BY_DATE'))
					{
						value = true;
					}
					break;
				case 'RECURRING[MULTIPLE_TIMES_LIMIT]':
					if (this.getRecurringFieldValue('RECURRING[MULTIPLE_TYPE_LIMIT]') === this.getSchemeFieldValue('LIMITED_BY_TIMES'))
					{
						value = true;
					}
					break;
				case 'OFFSET_BEGINDATE':
					if (this.getRecurringFieldValue('RECURRING[BEGINDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
				case 'OFFSET_CLOSEDATE':
					if (this.getRecurringFieldValue('RECURRING[CLOSEDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
			}
		}
		field.setVisible(value);
	};
	BX.Crm.EntityEditorRecurring.prototype.getRecurringFieldValue = function(name)
	{
		return BX.prop.get(this.getRecurringModel(), name)
	};
	BX.Crm.EntityEditorRecurring.prototype.getSchemeFieldValue = function(name)
	{
		return BX.prop.get(this._schemeFieldData, name, "")
	};
	BX.Crm.EntityEditorRecurring.prototype.isMainSubsection = function()
	{
		return !(this.getParent() instanceof BX.Crm.EntityEditorRecurring);
	};
	BX.Crm.EntityEditorRecurring.prototype.onBeforeSubmit = function()
	{
		if (this.isMainSubsection())
		{
			this._wrapper.appendChild(
				BX.create('input',{
					props:{
						type: 'hidden',
						name: 'IS_RECURRING',
						value: (this._model.getStringField('IS_RECURRING') === 'Y') ? 'Y' : 'N'
					}
				})
			);
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.save = function()
	{
		if (this.isMainSubsection())
		{
			this._schemeElement._promise = new BX.Promise();
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.loadViewText = function()
	{
		var data = this._schemeElement.getData();
		if (
			BX.type.isPlainObject(data.loaders)
			&& BX.type.isNotEmptyString(data.loaders["url"])
			&& BX.type.isNotEmptyString(data.loaders["action"])
		)
		{
			BX.ajax(
				{
					url: data.loaders["url"],
					method: "POST",
					dataType: "json",
					data: {
						ACTION: data.loaders["action"],
						PARAMS: {ID:this._model.getField('ID')}
					},
					onsuccess: BX.delegate(this.onEntityHintLoad, this)
				}
			);
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.onEntityHintLoad = function(result)
	{
		var entityData = BX.prop.getObject(result, "DATA", null);

		if(!entityData)
		{
			return;
		}
		if (BX.type.isNotEmptyString(entityData.HINT))
		{
			this._schemeElement._data.view.text = entityData.HINT;
		}

		if (this._schemeElement._promise instanceof BX.Promise)
		{
			this._schemeElement._promise.fulfill();
			this._schemeElement._promise = null;
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.showLicencePopup = function(e)
	{
		e.preventDefault();

		if(!B24 || !B24['licenseInfoPopup'])
		{
			return;
		}

		var layoutData = this._schemeElement.getData();
		var restrictionScript = layoutData.restrictScript;
		if (BX.type.isNotEmptyString(restrictionScript))
		{
			eval(restrictionScript);
		}
	};
	BX.Crm.EntityEditorRecurring.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRecurring();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorRecurringCustomRowField === "undefined")
{
	BX.Crm.EntityEditorRecurringCustomRowField = function()
	{
		BX.Crm.EntityEditorRecurringCustomRowField.superclass.constructor.apply(this);
		// this._currencyEditor = null;
		this._amountInput = null;
		this._selectInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
		this._selectedValue = "";
		this._selectClickHandler = BX.delegate(this.onSelectorClick, this);
		this._isMesureMenuOpened = false;
	};
	BX.extend(BX.Crm.EntityEditorRecurringCustomRowField, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.Crm.EntityEditorModeSwitchType.common;
		if(mode === BX.Crm.EntityEditorMode.edit)
		{
			result |= BX.Crm.EntityEditorModeSwitchType.button|BX.Crm.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.focus = function()
	{
		if(this._amountInput)
		{
			BX.focus(this._amountInput);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._amountInput);
		}
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getValue = function(defaultValue)
	{
		if(!this._model)
		{
			return "";
		}

		return(
			this._model.getStringField(
				this.getAmountFieldName(),
				(defaultValue !== undefined ? defaultValue : "")
			)
		);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-recurring-custom" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var title = this.getTitle();
		var data = this.getData();

		var selectInputName = this.getSelectFieldName();
		this._selectedValue = this.getValue(selectInputName);
		var selectItems = BX.prop.getArray(BX.prop.getObject(data, "select"), "items");
		var selectName = '';
		if(!this._selectedValue)
		{
			var firstItem =  selectItems.length > 0 ? selectItems[0] : null;
			if(firstItem)
			{
				this._selectedValue = firstItem["VALUE"];
				selectName = firstItem["NAME"];
			}
		}
		else
		{
			selectName = this._editor.findOption(
				this._selectedValue,
				selectItems
			);
		}

		var amountInputName = this.getAmountFieldName();
		var amountValue = this.getValue(amountInputName);

		// this._amountValue = null;
		this._amountInput = null;
		this._selectInput = null;
		this._selectContainer = null;
		this._innerWrapper = null;
		this._sumElement = null;

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._amountInput = BX.create("input",
				{
					attrs:
						{
							className: "crm-entity-widget-content-input",
							name: amountInputName,
							type: "text",
							value: amountValue
						}
				}
			);
			BX.bind(this._amountInput, "input", this._changeHandler);

			this._selectInput = BX.create("input",
				{
					attrs:
						{
							name: selectInputName,
							type: "hidden",
							value: this._selectedValue
						}
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-select" },
					text: selectName
				}
			);
			BX.bind(this._selectContainer, "click", this._selectClickHandler);

			this._inputWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-input-wrapper" },
					children:
						[
							this._amountInput,
							this._selectInput,
							BX.create('div',
								{
									props: { className: "crm-entity-widget-content-block-select" },
									children: [ this._selectContainer ]
								}
							)
						]
				}
			);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input" },
					children: [ this._inputWrapper ]
				}
			);
		}

		this._wrapper.appendChild(this._innerWrapper);

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.doClearLayout = function(options)
	{
		BX.PopupMenu.destroy(this._id);
		this._amountInput = null;
		this._selectInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getAmountFieldName = function()
	{
		return this._schemeElement.getDataStringParam("amount", "");
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getSelectFieldName = function()
	{
		return BX.prop.getString(
			this._schemeElement.getDataObjectParam("select", {}),
			"name",
			""
		);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onSelectorClick = function (e)
	{
		this.openListMenu();
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.openListMenu = function()
	{
		if(this._isListMenuOpened)
		{
			return;
		}

		var data = this._schemeElement.getData();
		var selectList = BX.prop.getArray(BX.prop.getObject(data, "select"), "items"); //{NAME, VALUE}

		var key = 0;
		var menu = [];
		while (key < selectList.length)
		{
			menu.push(
				{
					text: selectList[key]["NAME"],
					value: selectList[key]["VALUE"],
					onclick: BX.delegate( this.onSelectItem, this)
				}
			);
			key++
		}

		BX.PopupMenu.show(
			this._id,
			this._selectContainer,
			menu,
			{
				angle: false, width: this._selectContainer.offsetWidth + 'px',
				events:
					{
						onPopupShow: BX.delegate( this.onListMenuOpen, this),
						onPopupClose: BX.delegate( this.onListMenuClose, this)
					}
			}
		);
		BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.closeListMenu = function()
	{
		if(!this._isListMenuOpened)
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onListMenuOpen = function()
	{
		BX.addClass(this._selectContainer, "active");
		this._isListMenuOpened = true;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onListMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);

		BX.removeClass(this._selectContainer, "active");
		this._isListMenuOpened = false;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onSelectItem = function(e, item)
	{
		this.closeListMenu();

		this._selectedValue = this._selectInput.value = item.value;
		this._selectContainer.innerHTML = BX.util.htmlspecialchars(item.text);

		this.markAsChanged(
			{
				fieldName: this.getSelectFieldName(),
				fieldValue: this._selectedValue
			}
		);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getRuntimeValue = function()
	{
		var data = [];
		if (this._mode === BX.Crm.EntityEditorMode.edit)
		{
			if(this._amountInput)
			{
				data[this.getAmountFieldName()] = this._amountInput.value;
			}
			data[this.getSelectFieldName()] = this._selectedValue;

			return data;
		}
		return "";
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.save = function()
	{
		this._model.setField(
			this.getSelectFieldName(),
			this._selectedValue
		);

		if(this._amountInput)
		{
			this._model.setField(this.getAmountFieldName(), this._amountInput.value);
		}
	};
	BX.Crm.EntityEditorRecurringCustomRowField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRecurringCustomRowField();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRecurringSingleField === "undefined")
{
	BX.Crm.EntityEditorRecurringSingleField = function()
	{
		BX.Crm.EntityEditorRecurringSingleField.superclass.constructor.apply(this);
		this._dateInput = null;
	};
	BX.extend(BX.Crm.EntityEditorRecurringSingleField, BX.Crm.EntityEditorRecurringCustomRowField);

	BX.Crm.EntityEditorRecurringSingleField.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-recurring-single" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var title = this.getTitle();
		var data = this.getData();

		var amountInputName = this.getAmountFieldName();
		var amountValue = this.getValue(amountInputName);
		var selectInputName = this.getSelectFieldName();
		this._selectedValue = this.getValue(selectInputName);
		var dateInputName = this.getDateFieldName();
		this._dateValue = this.getValue(dateInputName);

		var selectItems = BX.prop.getArray(BX.prop.getObject(data, "select"), "items");
		var selectName = '';
		if(!this._selectedValue)
		{
			var firstItem =  selectItems.length > 0 ? selectItems[0] : null;
			if(firstItem)
			{
				this._selectedValue = firstItem["VALUE"];
				selectName = firstItem["NAME"];
			}
		}
		else
		{
			selectName = this._editor.findOption(
				this._selectedValue,
				selectItems
			);
		}
		this._amountInput = null;
		this._selectInput = null;
		this._selectContainer = null;
		this._innerWrapper = null;
		this._sumElement = null;

		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._amountInput = BX.create("input",
				{
					attrs:
						{
							className: "crm-entity-widget-content-input",
							name: amountInputName,
							type: "text",
							value: amountValue
						}
				}
			);
			BX.bind(this._amountInput, "input", this._changeHandler);

			this._selectInput = BX.create("input",
				{
					attrs:
						{
							name: selectInputName,
							type: "hidden",
							value: this._selectedValue
						}
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-select" },
					text: selectName
				}
			);

			this._dateInput = BX.create('input',{
				style:{
					display:'inline-block'
				},
				props:{
					name: dateInputName,
					className:'crm-entity-widget-content-input crm-entity-widget-content-input-date',
					value: this._dateValue
				},
				events: {
					click: function(){
						BX.calendar({node: this, field: this, bTime: false})
					},
					change: BX.delegate(
						function(e){
							this.markAsChanged();
						}, this)
				}
			});

			BX.bind(this._selectContainer, "click", this._selectClickHandler);

			this._inputWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-input-wrapper" },
					children:
						[
							this._amountInput,
							this._selectInput,
							BX.create('div',
								{
									props: { className: "crm-entity-widget-content-block-select" },
									children: [ this._selectContainer ]
								}
							),
							BX.create('span',{ text: this.getMessage('until')}),
							this._dateInput
						]
				}
			);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input" },
					children: [ this._inputWrapper ]
				}
			);
		}

		this._wrapper.appendChild(this._innerWrapper);

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getDateFieldName = function()
	{
		return this._schemeElement.getDataStringParam("date", "");
	};
	BX.Crm.EntityEditorRecurringSingleField.prototype.getRuntimeValue = function()
	{
		var data = [];
		if (this._mode === BX.Crm.EntityEditorMode.edit)
		{
			if(this._amountInput)
			{
				data[this.getAmountFieldName()] = this._amountInput.value;
			}
			data[this.getSelectFieldName()] = this._selectedValue;
			data[this.getDateFieldName()] = this._dateInput.value;

			return data;
		}
		return "";
	};
	BX.Crm.EntityEditorRecurringSingleField.prototype.save = function()
	{
		var data = this._schemeElement.getData();
		this._model.setField(
			BX.prop.getString(BX.prop.getObject(data, "select"), "name"),
			this._selectedValue
		);

		if(this._amountInput)
		{
			this._model.setField(BX.prop.getString(data, "amount"), this._amountInput.value);
		}
		if(this._dateInput)
		{
			this._model.setField(BX.prop.getString(data, "date"), this._dateInput.value);
		}
	};
	BX.Crm.EntityEditorRecurringSingleField.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRecurringSingleField.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorRecurringSingleField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRecurringSingleField();
		self.initialize(id, settings);
		return self;
	}
}
//region CONTROLLERS
if(typeof BX.Crm.EditorFieldSingleEditController === "undefined")
{
	BX.Crm.EditorFieldSingleEditController = function()
	{
		this._id = "";
		this._settings = null;
		this._field = null;
		this._wrapper = null;

		this._fieldWrapperHandler = BX.delegate(this.onFieldWrapperClick, this);
		this._documentHandler = BX.delegate(this.onDocumentClick, this);
		this._documentTimeoutHandle = 0;

		this._isInitialized = false;
		this._isActive = false;
	};
	BX.Crm.EditorFieldSingleEditController.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._time = (new Date()).toString();

				this._field = BX.prop.get(this._settings, "field");
				if(!(this._field instanceof BX.Crm.EntityEditorField))
				{
					throw "EditorFieldSingleEditController: The 'field' param must be EntityEditorField.";
				}

				this._wrapper = this._field.getWrapper();
				if(!BX.type.isElementNode(this._wrapper))
				{
					throw "EditorFieldSingleEditController: Could not find the wrapper element.";
				}

				window.setTimeout(BX.delegate(this.bind, this), 100);
				this._isActive = this._isInitialized = true;
			},
			isActive: function()
			{
				return this._isActive;
			},
			setActive: function(active)
			{
				this._isActive = !!active;
			},
			setActiveDelayed: function(active, delay)
			{
				if(typeof(delay) === "undefined")
				{
					delay = 0;
				}

				window.setTimeout(
					BX.delegate(function(){ this.setActive(active); }, this),
					delay
				);
			},
			release: function()
			{
				this._isActive = this._isInitialized = false;
				this.unbind();
			},
			bind: function()
			{
				if(this._isInitialized)
				{
					BX.bind(this._wrapper, "click", this._fieldWrapperHandler);
					BX.bind(document, "click", this._documentHandler);
				}
			},
			unbind: function()
			{
				BX.unbind(this._wrapper, "click", this._fieldWrapperHandler);
				BX.unbind(document, "click", this._documentHandler);
			},
			saveControl: function()
			{
				if(!this._isActive)
				{
					return;
				}

				var editor = this._field.getEditor();
				if(editor)
				{
					editor.switchControlMode(this._field, BX.Crm.EntityEditorMode.view, BX.Crm.EntityEditorModeOptions.none);
					//Is not supported by the all controls
					//editor.saveControl(this._field);
				}

				this._isActive = false;
			},
			onFieldWrapperClick: function(e)
			{
				//The call of "preventDefault" is not allowed because of the checkbox controls
				BX.eventCancelBubble(e);
			},
			onDocumentClick: function(e)
			{
				if(this._documentTimeoutHandle > 0)
				{
					window.clearTimeout(this._documentTimeoutHandle);
					this._documentTimeoutHandle = 0;
				}

				this._documentTimeoutHandle = window.setTimeout(
					BX.delegate(this.saveControl, this),
					400
				);
			}
		};
	BX.Crm.EditorFieldSingleEditController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorFieldSingleEditController();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EditorFieldViewController === "undefined")
{
	BX.Crm.EditorFieldViewController = function()
	{
		this._id = "";
		this._settings = null;
		this._field = null;
		this._wrapper = null;

		this._timeoutHandle = 0;
		this._time = 0;
		this._pos = { x: 0, y: 0 };

		this._mouseDownHandler = BX.delegate(this.onMouseDown, this);
		this._mouseUpHandler = BX.delegate(this.onMouseUp, this);

		this._isInitialized = false;
		this._isActive = false;
	};
	BX.Crm.EditorFieldViewController.prototype =
		{
			initialize: function (id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._field = BX.prop.get(this._settings, "field");
				if (!(this._field instanceof BX.Crm.EntityEditorField)) {
					throw "EditorFieldViewController: The 'field' param must be EntityEditorField.";
				}

				this._wrapper = BX.prop.getElementNode(this._settings, "wrapper");
				if (!BX.type.isElementNode(this._wrapper)) {
					throw "EditorFieldSingleEditController: Could not find the wrapper element.";
				}

				window.setTimeout(BX.delegate(this.bind, this), 100);
				this._isActive = this._isInitialized = true;
			},
			release: function()
			{
				this._isActive = this._isInitialized = false;
				this.unbind();
			},
			bind: function()
			{
				if(this._isInitialized)
				{
					BX.bind(this._wrapper, "mousedown", this._mouseDownHandler);
					BX.bind(this._wrapper, "mouseup", this._mouseUpHandler);
				}
			},
			unbind: function()
			{
				BX.unbind(this._wrapper, "mousedown", this._mouseDownHandler);
				BX.unbind(this._wrapper, "mouseup", this._mouseUpHandler);
			},
			onMouseDown: function(e)
			{
				if(this._timeoutHandle > 0)
				{
					window.clearTimeout(this._timeoutHandle);
					this._timeoutHandle = 0;
				}

				if(!this.isHandleableEvent(e))
				{
					return;
				}

				this._time = new Date().valueOf();
				this._pos = { x: e.clientX, y: e.clientY };
			},
			onMouseUp: function(e)
			{
				if(this._timeoutHandle > 0)
				{
					window.clearTimeout(this._timeoutHandle);
					this._timeoutHandle = 0;
				}

				if(!this.isHandleableEvent(e))
				{
					return;
				}

				//console.log(new Date().valueOf() - this._time);
				//console.log(Math.abs(this._pos.x - e.clientX));
				if((new Date().valueOf() - this._time) < 400 || Math.abs(this._pos.x - e.clientX) < 2)
				{
					this._timeoutHandle = window.setTimeout(
						function()
						{
							this.switchTo(BX.getEventTarget(e));
						}.bind(this),
						0
					);
				}

				this._time = 0;
			},
			isHandleableEvent: function(e)
			{
				var node = BX.getEventTarget(e);
				if(node.tagName === "A")
				{
					return false;
				}

				if(node.getAttribute("data-editor-control-type") === "button")
				{
					return false;
				}

				return !BX.findParent(node, { tagName: "a" }, this._wrapper);
			},
			switchTo: function(targetNode)
			{
				this._field.switchToSingleEditMode(targetNode);
			}
		};
	BX.Crm.EditorFieldViewController.create = function(id, settings)
	{
		var self = new BX.Crm.EditorFieldViewController();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorController === "undefined")
{
	BX.Crm.EntityEditorController = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;
		this._model = null;
		this._config = null;

		this._isChanged = false;
	};
	BX.Crm.EntityEditorController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._editor = BX.prop.get(this._settings, "editor", null);
			this._model = BX.prop.get(this._settings, "model", null);
			this._config = BX.prop.getObject(this._settings, "config", {});

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getConfig: function()
		{
			return this._config;
		},
		getConfigStringParam: function(name, defaultValue)
		{
			return BX.prop.getString(this._config, name, defaultValue);
		},
		isChanged: function()
		{
			return this._isChanged;
		},
		markAsChanged: function()
		{
			if(this._isChanged)
			{
				return;
			}

			this._isChanged = true;
			if(this._editor)
			{
				this._editor.processControllerChange(this);
			}
		},
		rollback: function()
		{
		},
		innerCancel: function()
		{
		},
		onBeforeSubmit: function()
		{
		},
		onAfterSave: function()
		{
			if(this._isChanged)
			{
				this._isChanged = false;
			}
		},
		onBeforesSaveControl: function(data)
		{
			return data;
		}
	};
}

if(typeof BX.Crm.EntityEditorProductRowProxy === "undefined")
{
	BX.Crm.EntityEditorProductRowProxy = function()
	{
		BX.Crm.EntityEditorProductRowProxy.superclass.constructor.apply(this);
		this._externalEditor = null;
		this._editorCreateHandler = null;
		this._sumTotalChangeHandler = null;
		this._productAddHandler = null;
		this._productChangeHandler = null;
		this._productRemoveHandler = null;
		this._editorModeChangeHandler = BX.delegate(this.onEditorModeChange, this);
		this._editorControlChangeHandler = BX.delegate(this.onEditorControlChange, this);

		this._currencyId = "";
	};
	BX.extend(BX.Crm.EntityEditorProductRowProxy, BX.Crm.EntityEditorController);
	BX.Crm.EntityEditorProductRowProxy.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorProductRowProxy.superclass.doInitialize.apply(this);

		this._sumTotalChangeHandler = BX.delegate(this.onSumTotalChange, this);
		this._productAddHandler = BX.delegate(this.onProductAdd, this);
		this._productChangeHandler = BX.delegate(this.onProductChange, this);
		this._productRemoveHandler = BX.delegate(this.onProductRemove, this);

		var externalEditor = typeof BX.CrmProductEditor !== "undefined"
			? BX.CrmProductEditor.get(this.getExternalEditorId()) : null;
		if(externalEditor)
		{
			this.setExternalEditor(externalEditor);
		}
		else
		{
			this._editorCreateHandler = BX.delegate(this.onEditorCreate, this);
			BX.addCustomEvent(window, "ProductRowEditorCreated", this._editorCreateHandler);
		}

		this._editor.addModeChangeListener(this._editorModeChangeHandler);

		BX.addCustomEvent(window, "onEntityDetailsTabShow", BX.delegate(this.onTabShow, this));

	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onTabShow = function(tab)
	{
		if(tab.getId() !== "tab_products")
		{
			return;
		}

		if(this._externalEditor && !this._externalEditor.hasLayout())
		{
			this._externalEditor.layout();
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.getExternalEditorId = function()
	{
		return this.getConfigStringParam("editorId", "");
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.setExternalEditor = function(editor)
	{
		if(this._externalEditor === editor)
		{
			return;
		}

		if(this._externalEditor)
		{
			this._externalEditor.setForm(null);
			BX.removeCustomEvent(this._externalEditor, "sumTotalChange", this._sumTotalChangeHandler);
			BX.removeCustomEvent(this._externalEditor, "productAdd", this._productAddHandler);
			BX.removeCustomEvent(this._externalEditor, "productChange", this._productChangeHandler);
			BX.removeCustomEvent(this._externalEditor, "productRemove", this._productRemoveHandler);
		}

		this._externalEditor = editor;

		if(this._externalEditor)
		{
			this._externalEditor.setForm(this._editor.getFormElement());
			BX.addCustomEvent(this._externalEditor, "sumTotalChange", this._sumTotalChangeHandler);
			BX.addCustomEvent(this._externalEditor, "productAdd", this._productAddHandler);
			BX.addCustomEvent(this._externalEditor, "productChange", this._productChangeHandler);
			BX.addCustomEvent(this._externalEditor, "productRemove", this._productRemoveHandler);

			this.adjustLocks();
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.adjustLocks = function()
	{
		if(!this._externalEditor)
		{
			return;
		}

		if(this._externalEditor.getProductCount() > 0)
		{
			this._model.lockField("OPPORTUNITY");
		}
		else
		{
			this._model.unlockField("OPPORTUNITY");
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.adjustTotals = function(totals)
	{
		this._model.setField(
			"FORMATTED_OPPORTUNITY",
			totals["FORMATTED_SUM"],
			{ enableNotification: false }
		);

		this._model.setField(
			"FORMATTED_OPPORTUNITY_WITH_CURRENCY",
			totals["FORMATTED_SUM_WITH_CURRENCY"],
			{ enableNotification: false }
		);

		this._model.setField(
			"OPPORTUNITY",
			totals["SUM"],
			{ enableNotification: true }
		);
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onEditorCreate = function(sender)
	{
		if(sender.getId() !== this.getExternalEditorId())
		{
			return;
		}

		BX.removeCustomEvent(window, "ProductRowEditorCreated", this._editorCreateHandler);
		delete(this._editorCreateHandler);
		this.setExternalEditor(sender);
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onEditorModeChange = function(sender)
	{
		if(this._editor.getMode() === BX.Crm.EntityEditorMode.edit)
		{
			this._editor.addControlChangeListener(this._editorControlChangeHandler);
		}
		else
		{
			this._editor.removeControlChangeListener(this._editorControlChangeHandler);
		}

		this._isChanged = false;
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onEditorControlChange = function(sender, params)
	{
		if(!this._externalEditor)
		{
			return;
		}

		var name = BX.prop.getString(params, "fieldName", "");
		if(name !== "CURRENCY_ID")
		{
			return;
		}

		var currencyId = BX.prop.getString(params, "fieldValue", "");
		if(currencyId !== "")
		{
			this._currencyId = currencyId;
			this._externalEditor.setCurrencyId(this._currencyId);
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onProductAdd = function(product)
	{
		this.adjustLocks();
		this.markAsChanged();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onProductChange = function(product)
	{
		this.adjustLocks();
		this.markAsChanged();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onProductRemove = function(product)
	{
		this.adjustLocks();
		this.markAsChanged();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onSumTotalChange = function(totalSum, allTotals)
	{
		this.adjustTotals(
			{
				"FORMATTED_SUM_WITH_CURRENCY": allTotals["TOTAL_SUM_FORMATTED"],
				"FORMATTED_SUM": allTotals["TOTAL_SUM_FORMATTED_SHORT"],
				"SUM": allTotals["TOTAL_SUM"]
			}
		);
		this.markAsChanged();
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.rollback = function()
	{
		var currencyId = this._model.getField("CURRENCY_ID", "");
		if(this._currencyId !== currencyId)
		{
			this._currencyId = currencyId;
			if(this._externalEditor)
			{
				this._externalEditor.setCurrencyId(this._currencyId);
			}
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onBeforeSubmit = function()
	{
		if(this._externalEditor)
		{
			this._externalEditor.handleFormSubmit();
		}
	};
	BX.Crm.EntityEditorProductRowProxy.prototype.onBeforesSaveControl = function(data)
	{
		if(this._externalEditor)
		{
			data = this._externalEditor.handleControlSave(data);
		}
		return data;
	};
	BX.Crm.EntityEditorProductRowProxy.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorProductRowProxy();
		self.initialize(id, settings);
		return self;
	}
}

//endregion

//region TOOL PANEL
if(typeof BX.Crm.EntityEditorToolPanel === "undefined")
{
	BX.Crm.EntityEditorToolPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._editor = null;
		this._isVisible = false;
		this._isLocked = false;
		this._hasLayout = false;
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
	};

	BX.Crm.EntityEditorToolPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._container = BX.prop.getElementNode(this._settings, "container", null);
			this._editor = BX.prop.get(this._settings, "editor", null);
			this._isVisible = BX.prop.getBoolean(this._settings, "visible", false);
		},
		getId: function()
		{
			return this._id;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function (container)
		{
			this._container = container;
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;
			this.adjustLayout();
		},
		isLocked: function()
		{
			return this._isLocked;
		},
		setLocked: function(locked)
		{
			locked = !!locked;
			if(this._isLocked === locked)
			{
				return;
			}

			this._isLocked = locked;

			if(locked)
			{
				BX.addClass(this._editButton, "ui-btn-clock");
			}
			else
			{
				BX.removeClass(this._editButton, "ui-btn-clock");
			}
		},
		disableSaveButton: function()
		{
			if(!this._editButton)
			{
				return;
			}

			this._editButton.disabled = true;
			BX.addClass(this._editButton, 'ui-btn-disabled');
		},
		enableSaveButton: function()
		{
			if(!this._editButton)
			{
				return;
			}

			this._editButton.disabled = false;
			BX.removeClass(this._editButton, 'ui-btn-disabled');
		},
		isSaveButtonEnabled: function()
		{
			return this._editButton && !this._editButton.disabled;
		},
		layout: function()
		{
			this._editButton = BX.create("button",
				{
					props: { className: "ui-btn ui-btn-success", title: "[Ctrl+Enter]" },
					text: BX.message("CRM_EDITOR_SAVE"),
					events: { click: BX.delegate(this.onSaveButtonClick, this) }
				}
			);

			this._cancelButton = BX.create("a",
				{
					props:  { className: "ui-btn ui-btn-link", title: "[Esc]" },
					text: BX.message("CRM_EDITOR_CANCEL"),
					attrs:  { href: "#" },
					events: { click: BX.delegate(this.onCancelButtonClick, this) }
				}
			);

			this._errorContainer = BX.create("DIV", { props: { className: "crm-entity-section-control-error-block" } });
			this._errorContainer.style.maxHeight = "0";

			this._wrapper = BX.create("DIV",
				{
					props: { className: "crm-entity-wrap" },
					children :
						[
							BX.create("DIV",
								{
									props: { className: "crm-entity-section crm-entity-section-control" },
									children : [ this._editButton, this._cancelButton, this._errorContainer ]
								}
							)
						]
				}
			);

			this._container.appendChild(this._wrapper);

			this._hasLayout = true;
			this.adjustLayout();
		},
		adjustLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(!this._isVisible)
			{
				BX.removeClass(this._wrapper, "crm-section-control-active");
				BX.unbind(document, "keydown", this._keyPressHandler);
			}
			else
			{
				BX.addClass(this._wrapper, "crm-section-control-active");
				BX.bind(document, "keydown", this._keyPressHandler);
			}
		},
		getPosition: function()
		{
			return this._hasLayout ? BX.pos(this._wrapper) : null;
		}
	};
	BX.Crm.EntityEditorToolPanel.prototype.onSaveButtonClick = function(e)
	{
		if(!this._isLocked)
		{
			this._editor.saveChanged();
		}
	};
	BX.Crm.EntityEditorToolPanel.prototype.onCancelButtonClick = function(e)
	{
		if(!this._isLocked)
		{
			this._editor.cancel();
		}
		return BX.eventReturnFalse(e);
	};
	BX.Crm.EntityEditorToolPanel.prototype.onKeyPress = function(e)
	{
		if(!this._isVisible)
		{
			return;
		}

		//Emulation of dialog modal mode
		if(BX.Crm.EditorAuxiliaryDialog.hasOpenItems())
		{
			return;
		}

		if(BX.type.isFunction(BX.PopupWindowManager.isAnyPopupShown) && BX.PopupWindowManager.isAnyPopupShown())
		{
			return;
		}

		e = e || window.event;
		if (e.keyCode == 27)
		{
			//Esc pressed
			this._editor.cancel();
			BX.eventCancelBubble(e);
		}
		else if (e.keyCode == 13 && e.ctrlKey)
		{
			//Ctrl+Enter pressed
			this._editor.saveChanged();
			BX.eventCancelBubble(e);
		}
	};
	BX.Crm.EntityEditorToolPanel.prototype.addError = function(error)
	{
		this._errorContainer.appendChild(
			BX.create(
				"DIV",
				{
					attrs: { className: "crm-entity-section-control-error-text" },
					html: error
				}
			)
		);
		this._errorContainer.style.maxHeight = "";
	};
	BX.Crm.EntityEditorToolPanel.prototype.clearErrors = function()
	{
		this._errorContainer.innerHTML = "";
		this._errorContainer.style.maxHeight = "0px";
	};
	BX.Crm.EntityEditorToolPanel.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorToolPanel.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.Crm.EntityEditorToolPanel.messages) === "undefined")
	{
		BX.Crm.EntityEditorToolPanel.messages = {};
	}
	BX.Crm.EntityEditorToolPanel.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorToolPanel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region FIELD SELECTOR
if(typeof(BX.Crm.EntityEditorFieldSelector) === "undefined")
{
	BX.Crm.EntityEditorFieldSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._scheme = null;
		this._excludedNames = null;
		this._closingNotifier = null;
		this._contentWrapper = null;
		this._popup = null;
	};

	BX.Crm.EntityEditorFieldSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._scheme = BX.prop.get(this._settings, "scheme", null);
			if(!this._scheme)
			{
				throw "BX.Crm.EntityEditorFieldSelector. Parameter 'scheme' is not found.";
			}
			this._excludedNames = BX.prop.getArray(this._settings, "excludedNames", []);
			this._closingNotifier = BX.CrmNotifier.create(this);
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.EntityEditorFieldSelector.messages, name, name);
		},
		isSchemeElementEnabled: function(schemeElement)
		{
			var name = schemeElement.getName();
			for(var i = 0, length = this._excludedNames.length; i < length; i++)
			{
				if(this._excludedNames[i] === name)
				{
					return false;
				}
			}
			return true;
		},
		addClosingListener: function(listener)
		{
			this._closingNotifier.addListener(listener);
		},
		removeClosingListener: function(listener)
		{
			this._closingNotifier.removeListener(listener);
		},
		isOpened: function()
		{
			return this._popup && this._popup.isShown();
		},
		open: function()
		{
			if(this.isOpened())
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: {},
					zIndex: 1,
					titleBar: BX.prop.getString(this._settings, "title", ""),
					content: this.prepareContent(),
					lightShadow : true,
					contentNoPaddings: true,
					buttons: [
						new BX.PopupWindowButton(
							{
								text : this.getMessage("select"),
								className : "ui-btn ui-btn-success",
								events:
								{
									click: BX.delegate(this.onAcceptButtonClick, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : this.getMessage("cancel"),
								className : "ui-btn ui-btn-link",
								events:
								{
									click: BX.delegate(this.onCancelButtonClick, this)
								}
							}
						)
					]
				}
			);

			this._popup.show();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		prepareContent: function()
		{
			this._contentWrapper = BX.create("div", { props: { className: "crm-entity-field-selector-window" } });
			var container = BX.create("div", { props: { className: "crm-entity-field-selector-window-list" } });
			this._contentWrapper.appendChild(container);

			var elements = this._scheme.getElements();
			for(var i = 0; i < elements.length; i++)
			{
				var element = elements[i];
				if(!this.isSchemeElementEnabled(element))
				{
					continue;
				}

				var effectiveElements = [];
				var elementChildren = element.getElements();
				var childElement;
				for(var j = 0; j < elementChildren.length; j++)
				{
					childElement = elementChildren[j];
					if(childElement.isTransferable() && childElement.getName() !== "")
					{
						effectiveElements.push(childElement);
					}
				}

				if(effectiveElements.length === 0)
				{
					continue;
				}

				var parentName = element.getName();
				var parentTitle = element.getTitle();

				container.appendChild(
					BX.create(
						"div",
						{
							attrs: { className: "crm-entity-field-selector-window-list-caption" },
							text: parentTitle
						}
					)
				);

				for(var k = 0; k < effectiveElements.length; k++)
				{
					childElement = effectiveElements[k];

					var childElementName = childElement.getName();
					var childElementTitle = childElement.getTitle();

					var itemId = parentName + "\\" + childElementName;
					var itemWrapper = BX.create(
						"div",
						{
							attrs: { className: "crm-entity-field-selector-window-list-item" }
						}
					);
					container.appendChild(itemWrapper);

					itemWrapper.appendChild(
						BX.create(
							"input",
							{
								attrs:
								{
									id: itemId,
									type: "checkbox",
									className: "crm-entity-field-selector-window-list-checkbox"
								}
							}
						)
					);

					itemWrapper.appendChild(
						BX.create(
							"label",
							{
								attrs:
								{
									for: itemId,
									className: "crm-entity-field-selector-window-list-label"
								},
								text: childElementTitle
							}
						)
					);
				}
			}
			return this._contentWrapper;
		},
		getSelectedItems: function()
		{
			if(!this._contentWrapper)
			{
				return [];
			}

			var results = [];
			var checkBoxes = this._contentWrapper.querySelectorAll("input.crm-entity-field-selector-window-list-checkbox");
			for(var i = 0, length = checkBoxes.length; i < length; i++)
			{
				var checkBox = checkBoxes[i];
				if(checkBox.checked)
				{
					var parts = checkBox.id.split("\\");
					if(parts.length >= 2)
					{
						results.push({ sectionName: parts[0], fieldName: parts[1] });
					}
				}
			}

			return results;
		},
		onAcceptButtonClick: function()
		{
			this._closingNotifier.notify([ { isCanceled: false, items: this.getSelectedItems() } ]);
			this.close();
		},
		onCancelButtonClick: function()
		{
			this._closingNotifier.notify([{ isCanceled: true }]);
			this.close();
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._contentWrapper = null;
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(!this._popup)
			{
				return;
			}

			this._contentWrapper = null;
			this._popup = null;
		}
	};

	if(typeof(BX.Crm.EntityEditorFieldSelector.messages) === "undefined")
	{
		BX.Crm.EntityEditorFieldSelector.messages = {};
	}

	BX.Crm.EntityEditorFieldSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorFieldSelector(id, settings);
		self.initialize(id, settings);
		return self;
	}
}
//endregion

//region USER SELECTOR
if(typeof(BX.Crm.EntityEditorUserSelector) === "undefined")
{
	BX.Crm.EntityEditorUserSelector = function()
	{
		this._id = "";
		this._settings = {};
	};

	BX.Crm.EntityEditorUserSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._isInitialized = false;
		},
		getId: function()
		{
			return this._id;
		},
		open: function(anchor)
		{
			if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.containerWindow)
			{
				return;
			}

			if(!this._isInitialized)
			{
				BX.SocNetLogDestination.init(
					{
						name: this._id,
						extranetUser:  false,
						userSearchArea: "I",
						bindMainPopup: { node: anchor, offsetTop: "5px", offsetLeft: "15px" },
						callback: { select : BX.delegate(this.onSelect, this) },
						showSearchInput: true,
						departmentSelectDisable: true,
						items:
						{
							users: BX.Crm.EntityEditorUserSelector.users,
							groups: {},
							sonetgroups: {},
							department: BX.Crm.EntityEditorUserSelector.department,
							departmentRelation : BX.SocNetLogDestination.buildDepartmentRelation(BX.Crm.EntityEditorUserSelector.department)
						},
						itemsLast: BX.Crm.EntityEditorUserSelector.last,
						itemsSelected: {},
						isCrmFeed: false,
						useClientDatabase: false,
						destSort: {},
						allowAddUser: false,
						allowSearchCrmEmailUsers: false,
						allowUserSearch: true
					}
				);
				this._isInitialized = true;
			}

			BX.SocNetLogDestination.openDialog(this._id, { bindNode: anchor });
			this._mainWindow = BX.SocNetLogDestination.containerWindow;
		},
		close: function()
		{
			if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.containerWindow)
			{
				BX.SocNetLogDestination.closeDialog();
				this._mainWindow = null;
				this._isInitialized = false;
			}

		},
		onSelect: function(item, type, search, bUndeleted)
		{
			if(type !== "users")
			{
				return;
			}

			var callback = BX.prop.getFunction(this._settings, "callback", null);
			if(callback)
			{
				callback(this, item);
			}
		}
	};

	BX.Crm.EntityEditorUserSelector.items = {};
	BX.Crm.EntityEditorUserSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUserSelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region CRM SELECTOR
if(typeof(BX.Crm.EntityEditorCrmSelector) === "undefined")
{
	BX.Crm.EntityEditorCrmSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeIds = [];
		this._supportedItemTypes = {};
	};

	BX.Crm.EntityEditorCrmSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._isInitialized = false;

			this._entityTypeIds = BX.prop.getArray(this._settings, "entityTypeIds", []);
			this._supportedItemTypes = [];
			for(var i = 0, l = this._entityTypeIds.length; i < l; i++)
			{
				var entityTypeId = this._entityTypeIds[i];
				if(entityTypeId === BX.CrmEntityType.enumeration.contact)
				{
					this._supportedItemTypes.push({ name: "contacts", altName: "CRMCONTACT" });
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.company)
				{
					this._supportedItemTypes.push({ name: "companies", altName: "CRMCOMPANY" });
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.lead)
				{
					this._supportedItemTypes.push({ name: "leads", altName: "CRMLEAD" });
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.deal)
				{
					this._supportedItemTypes.push({ name: "deals", altName: "CRMDEAL" });
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		isOpened: function()
		{
			return BX.SocNetLogDestination.isOpenDialog();
		},
		open: function(anchor)
		{
			if(this.isOpened())
			{
				return;
			}

			if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.containerWindow)
			{
				return;
			}

			if(!this._isInitialized)
			{
				var items = {};
				var itemsLast = {};
				var allowedCrmTypes = [];

				for(var i = 0, l = this._supportedItemTypes.length; i < l; i++)
				{
					var typeInfo = this._supportedItemTypes[i];
					items[typeInfo.name] = BX.Crm.EntityEditorCrmSelector[typeInfo.name];
					itemsLast[typeInfo.name] = BX.Crm.EntityEditorCrmSelector[typeInfo.name + "Last"];
					allowedCrmTypes.push(typeInfo.altName);
				}

				itemsLast["crm"] = {};

				var initParams =
				{
					name: this._id,
					extranetUser:  false,
					bindMainPopup: { node: anchor, offsetTop: "20px", offsetLeft: "20px" },
					callback: { select : BX.delegate(this.onSelect, this) },
					showSearchInput: true,
					departmentSelectDisable: true,
					items: items,
					itemsLast: itemsLast,
					itemsSelected: {},
					useClientDatabase: false,
					destSort: {},
					allowAddUser: false,
					allowSearchCrmEmailUsers: false,
					allowUserSearch: false,
					isCrmFeed: true,
					CrmTypes: allowedCrmTypes
				};

				if(BX.prop.getBoolean(this._settings, "enableMyCompanyOnly", false))
				{
					initParams["enableMyCrmCompanyOnly"] = true;
				}

				BX.SocNetLogDestination.init(initParams);
				this._isInitialized = true;
			}

			BX.SocNetLogDestination.openDialog(this._id, { bindNode: anchor });
			this._mainWindow = BX.SocNetLogDestination.containerWindow;
		},
		close: function()
		{
			if(!this.isOpened())
			{
				return;
			}

			if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.containerWindow)
			{
				BX.SocNetLogDestination.closeDialog();
				this._mainWindow = null;
			}
		},
		onSelect: function(item, type, search, bUndeleted, name, state)
		{
			if(state !== "select")
			{
				return;
			}

			var isSupported = false;
			for(var i = 0, l = this._supportedItemTypes.length; i < l; i++)
			{
				var typeInfo = this._supportedItemTypes[i];
				if(typeInfo.name === type)
				{
					isSupported = true;
					break;
				}
			}

			if(!isSupported)
			{
				return;
			}

			var callback = BX.prop.getFunction(this._settings, "callback", null);
			if(callback)
			{
				callback(this, item);
			}
		}
	};

	if(typeof(BX.Crm.EntityEditorCrmSelector.contacts) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.contacts = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.contactsLast) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.contactsLast = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.companies) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.companies = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.companiesLast) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.companiesLast = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.leads) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.leads = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.leadsLast) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.leadsLast = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.deals) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.deals = {};
	}

	if(typeof(BX.Crm.EntityEditorCrmSelector.dealsLast) === "undefined")
	{
		BX.Crm.EntityEditorCrmSelector.dealsLast = {};
	}

	BX.Crm.EntityEditorCrmSelector.items = {};
	BX.Crm.EntityEditorCrmSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorCrmSelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
//endregion

//region BIZPROC
if(typeof BX.Crm.EntityBizprocManager === "undefined")
{
	BX.Crm.EntityBizprocManager = function()
	{
		this._id = "";
		this._settings = {};
		this._moduleId = "";
		this._entity = "";
		this._documentType = "";
		this._autoExecuteType = 0;

		this._containerId = null;
		this._fieldName = null;

		this._validParameters = null;
		this._formInput = null;

		this._editor = null;
		this._starter = null;
	};
	BX.Crm.EntityBizprocManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._hasParameters = BX.prop.getBoolean(this._settings, "hasParameters", false);
			this._moduleId = BX.prop.getString(this._settings, "moduleId", "");
			this._entity = BX.prop.getString(this._settings, "entity", "");
			this._documentType = BX.prop.getString(this._settings, "documentType", "");
			this._autoExecuteType = BX.prop.getInteger(this._settings, "autoExecuteType", 0);
			this._containerId = BX.prop.getString(this._settings, "containerId", '');
			this._fieldName = BX.prop.getString(this._settings, "fieldName", '');
			this._contentNode = this._containerId ? BX(this._containerId) : null;

			if (this._hasParameters)
			{
				this._starter = new BX.Bizproc.Starter({
					moduleId: this._moduleId,
					entity: this._entity,
					documentType: this._documentType
				});
			}
		},
		/**
		 *
		 * @param {BX.Crm.EntityValidationResult} result
		 * @returns {BX.Promise}
		 */
		onBeforeSave: function(result)
		{
			var promise = new BX.Promise();

			var deferredWaiter = function()
			{
				window.setTimeout(
					BX.delegate(
						function()
						{
							promise.fulfill();
						},
						this
					),
					0
				);
			};

			if(result.getStatus() && this._hasParameters && this._validParameters === null)
			{
				try
				{
					this._starter.showAutoStartParametersPopup(
						this._autoExecuteType,
						{
							contentNode: this._contentNode,
							callback: this.onFillParameters.bind(this, promise)
						}
					);
					this._contentNode = null;
				}
				catch (e)
				{
					if ('console' in window)
					{
						window.console.log('Error occurred when bizproc popup is going to show', e);
					}
					deferredWaiter();
				}
			}
			else
			{
				deferredWaiter();
			}

			return promise;
		},

		onAfterSave: function()
		{
			this._validParameters = null;
		},

		onFillParameters: function(promise, data)
		{
			this._validParameters = data.parameters;

			if (!this._formInput && this._editor)
			{
				var form = this._editor.getFormElement();
				this._formInput = BX.create("input", { props: { type: "hidden", name: this._fieldName } });
				form.appendChild(this._formInput);
			}

			if (this._formInput)
			{
				this._formInput.value = this._validParameters;
			}

			promise.fulfill();
		}
	};
	if(typeof(BX.Crm.EntityBizprocManager.messages) === "undefined")
	{
		BX.Crm.EntityBizprocManager.messages = {};
	}
	BX.Crm.EntityBizprocManager.items = {};
	BX.Crm.EntityBizprocManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityBizprocManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}

if(typeof BX.Crm.EntityRestPlacementManager === "undefined")
{
	BX.Crm.EntityRestPlacementManager = function()
	{
		this._id = "";
		this._entity = "";

		this._editor = null;
	};

	BX.Crm.EntityRestPlacementManager.items = {};
	BX.Crm.EntityRestPlacementManager.prototype = {
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._entity = this.getSetting("entity");

			var bottomButton = BX(this.getSetting("bottom_button_id"));
			if(bottomButton)
			{
				BX.bind(bottomButton, 'click', BX.proxy(this.openMarketplace, this));
			}

			BX.defer(this.initializeInterface, this)();
		},

		openMarketplace: function()
		{
			BX.rest.Marketplace.open({
				PLACEMENT: this.getSetting("placement")
			});
		},

		getSetting: function(name)
		{
			return BX.prop.getString(this._settings, name, '')
		},

		initializeInterface: function()
		{
			if(!!BX.rest && !!BX.rest.AppLayout)
			{
				var PlacementInterface = BX.rest.AppLayout.initializePlacement('CRM_' + this._entity + '_DETAIL_TAB');

				var entityTypeId = this._editor._entityTypeId, entityId = this._editor._entityId;

				PlacementInterface.prototype.resizeWindow = function(params, cb)
				{
					var f = BX(this.params.layoutName);
					params.height = parseInt(params.height);

					if(!!params.height)
					{
						f.style.height = params.height + 'px';
					}

					var p = BX.pos(f);
					cb({width: p.width, height: p.height});
				};

				PlacementInterface.prototype.reloadData = function(params, cb)
				{
					BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
					cb();
				};
			}
		}
	};

	BX.Crm.EntityRestPlacementManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityRestPlacementManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}

//endregion
