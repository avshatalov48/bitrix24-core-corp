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

		this._ufAccessRights = {};
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
			var formTagName = BX.prop.getString(this._settings, "formTagName", "form");
			this._formElement = BX.create(formTagName, {props: { name: this._id + "_form"}});
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

			this._ufAccessRights = BX.prop.getObject(this._settings, "ufAccessRights", {});

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
				this._controls[i].clearLayout({release: true});
			}
			for(i = 0, length = this._controllers.length; i < length; i++)
			{
				this._controllers[i].release();
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
			if(this._mode !== BX.Crm.EntityEditorMode.view)
			{
				this._mode = BX.Crm.EntityEditorMode.view;
				this._modeChangeNotifier.notify([ this ]);
			}
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

			data = BX.mergeEx(data, this.prepareControllersData(data));

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

			data = BX.mergeEx(data, this.prepareControllersData(data));

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
		prepareControllersData: function(data)
		{
			if (!BX.type.isPlainObject(data))
			{
				data = {};
			}
			for(var i = 0, length = this._controllers.length; i < length; i++)
			{
				data = this._controllers[i].onBeforesSaveControl(data);
			}
			return data;
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
			var validator = BX.Crm.EntityAsyncValidator.create();
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
