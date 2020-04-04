//BX.CrmQuickPanelView
if(typeof(BX.CrmQuickPanelView) === "undefined")
{
	BX.CrmQuickPanelView = function()
	{
		this._id = "";
		this._settings = null;

		this._entityTypeName = "";
		this._entityId = 0;
		this._serviceUrl = "";

		this._formId = "";
		this._prefix = "";

		this._formSettingsManager = null;
		this._placeholder = null;
		this._wrapper = null;
		this._innerWrapper = null;
		this._leftContainer = null;
		this._centerContainer = null;
		this._rightContainer = null;
		this._bottomContainer = null;

		this._instantEditor = null;

		this._lastChangedSection = null;
		this._isRequestRunning = false;
		this._requestCompleteCallback = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._resizeHandler = BX.delegate(this._onWindowResize, this);

		this._enableUserConfig = false;
		this._isExpanded = true;
		this._isFixed = false;
		this._isFixedLayout = false;
		this._isMenuShown = false;

		this._config = {};
		this._headerConfig = {};
		this._entityData = null;
		this._sections = {};
		this._headerItems = {};
		this._models = {};

		this._menuButton = null;
		this._pinButton = null;
		this._toggleButton = null;

		this._wait = null;
		this._waitAnchor = null;

		this._menu = null;
		this._menuId = "";

		this._enableInstantEdit = false;
		this._enableDragOverHandling = true;
		this._isIE = false;

		this._unloadHandlers = [];
		this._editorCreatedHandler = BX.delegate(this._onEditorCreated, this);
		this._itemDropHandler = BX.delegate(this._onItemDrop, this);

		BX.addCustomEvent(
			window,
			"CrmControlPanelLayoutChange",
			BX.delegate(this._onControlPanelLayoutChange, this)
		);
	};
	BX.CrmQuickPanelView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._entityId = parseInt(this.getSetting("entityId", 0));

			this._prefix = this.getSetting("prefix", "");

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "CrmQuickPanelView: Could no find service url .";
			}

			this._placeholder = BX(this.resolveElementId("placeholder"));
			if(!this._placeholder)
			{
				throw "CrmQuickPanelView: Could no find placeholder.";
			}

			this._wrapper = BX(this.resolveElementId("wrap"));
			if(!this._wrapper)
			{
				throw "CrmQuickPanelView: Could no find wrapper.";
			}

			this._innerWrapper = BX(this.resolveElementId("inner_wrap"));
			if(!this._innerWrapper)
			{
				throw "CrmQuickPanelView: Could no find inner wrapper.";
			}

			this._entityData = this.getSetting("entityData");
			if(!this._entityData)
			{
				throw "CrmQuickPanelView: The entity data are not found.";
			}

			this._config = this.getSetting("config", {});
			if(!BX.type.isNotEmptyString(this._config["enabled"]))
			{
				this._config["enabled"] = "N";
			}

			this._enableInstantEdit = !!this.getSetting("enableInstantEdit", false);

			this._enableUserConfig = this._config["enabled"] === "Y";
			this._isExpanded = this._config["expanded"] === "Y";
			this._isFixed = this._config["fixed"] === "Y";
			if(this._isFixed)
			{
				this.adjust();
				BX.bind(window, "scroll", this._scrollHandler);
				BX.bind(window, "resize", this._resizeHandler);
			}

			this._menuButton = BX(this.resolveElementId("menu_btn"));
			if(this._menuButton)
			{
				BX.bind(this._menuButton, "click", BX.delegate(this._onMenuButtonClick, this));
			}
			this._menuId = this._id.toLowerCase() + "_main_menu";

			this._pinButton = BX(this.resolveElementId("pin_btn"));
			if(this._pinButton)
			{
				BX.bind(this._pinButton, "click", BX.delegate(this._onPinButtonClick, this));
			}

			this._toggleButton = BX(this.resolveElementId("toggle_btn"));
			if(this._toggleButton)
			{
				BX.bind(this._toggleButton, "click", BX.delegate(this._onToggleButtonClick, this));
			}

			var leftContainerId = this.getSetting("leftContainerId", "");
			if(!BX.type.isNotEmptyString(leftContainerId))
			{
				leftContainerId = this._prefix + "_left_container";
			}

			this._leftContainer = BX(leftContainerId);
			if(!this._leftContainer)
			{
				throw "CrmQuickPanelView: The left container is not found.";
			}

			this.prepareSection("left", this._leftContainer);

			var centerContainerId = this.getSetting("centerContainerId", "");
			if(!BX.type.isNotEmptyString(centerContainerId))
			{
				centerContainerId = this._prefix + "_center_container";
			}

			this._centerContainer = BX(centerContainerId);
			if(!this._centerContainer)
			{
				throw "CrmQuickPanelView: The center container is not found.";
			}

			this.prepareSection("center", this._centerContainer);

			var rightContainerId = this.getSetting("rightContainerId", "");
			if(!BX.type.isNotEmptyString(rightContainerId))
			{
				rightContainerId = this._prefix + "_right_container";
			}

			this._rightContainer = BX(rightContainerId);
			if(!this._rightContainer)
			{
				throw "CrmQuickPanelView: The right container is not found.";
			}

			this.prepareSection("right", this._rightContainer);

			var bottomContainerId = this.getSetting("bottomContainerId", "");
			if(!BX.type.isNotEmptyString(bottomContainerId))
			{
				bottomContainerId = this._prefix + "_bottom_container";
			}

			this._bottomContainer = BX(bottomContainerId);
			if(!this._bottomContainer)
			{
				throw "CrmQuickPanelView: The bottom container is not found.";
			}

			this.prepareSection("bottom", this._bottomContainer);

			this._formId = this.getSetting("formId", "");
			if(BX.type.isNotEmptyString(this._formId))
			{
				this._formSettingsManager = typeof(BX.CrmFormSettingManager) !== "undefined"
					? BX.CrmFormSettingManager.items[this._formId] : null;
				if(!this._formSettingsManager)
				{
					BX.addCustomEvent(
						window,
						"CrmFormSettingManagerCreate",
						BX.delegate(this._onFormSettingManagerCreate, this)
					);
				}
			}

			var progressLegendId = this.getSetting("progressLegendId", "");
			if(!BX.type.isNotEmptyString(progressLegendId))
			{
				progressLegendId = this._prefix + "_progress_legend";
			}

			this._headerConfig = this.getSetting("headerConfig", {});
			this.prepareHeader();

			this._isIE = BX.browser.IsIE();

			BX.CrmQuickPanelMultiField.setWrapper(this._innerWrapper);
			BX.CrmQuickPanelAddress.setWrapper(this._innerWrapper);

			var instantEditor = BX.CrmInstantEditor.getDefault();
			if(instantEditor)
			{
				this.setInstantEditor(instantEditor);
			}
			else
			{
				BX.addCustomEvent(window, "CrmInstantEditorCreated", this._editorCreatedHandler);
			}

			var bin = BX.CrmDragDropBin.getInstance();
			BX.addCustomEvent(bin, "CrmDragDropBinItemDrop", BX.delegate(this._onDragDropBinItemDrop, this));

			window.onbeforeunload = BX.delegate(this._onUnload, this);
		},
		isAllowedDragContext: function(contextId)
		{
			return (this._formSettingsManager
				&& this._formSettingsManager.getDraggableFieldContextId() === contextId);
		},
		prepareHeader: function()
		{
			for(var k in this._headerConfig)
			{
				if(!this._headerConfig.hasOwnProperty(k))
				{
					continue;
				}
				var config = this._headerConfig[k];
				var type = BX.type.isNotEmptyString(config["type"]) ? config["type"] : "";
				var fieldId = BX.type.isNotEmptyString(config["fieldId"]) ? config["fieldId"] : "";
				var container = BX(this._prefix + "_" + k.toLowerCase());

				this._headerItems[k] = BX.CrmQuickPanelHeaderItem.create(k, { model: this.getFieldModel(fieldId), container: container });
			}
		},
		prepareSection: function(id, container)
		{
			var section = BX.CrmQuickPanelSection.create(
				id,
				{
					view: this,
					prefix: this._id,
					container: container,
					config: BX.type.isNotEmptyString(this._config[id]) ? this._config[id].split(",") : []
				}
			);
			this._sections[id] = section;
			section.setDragDropContainerId(this._id + "_" + id);
			if(section.getItemCount() === 0)
			{
				section.createPlaceHolder(-1);
			}
			return section;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getEnityId: function()
		{
			return this._entityId;
		},
		getPrefix: function()
		{
			return this._prefix;
		},
		getInstantEditor: function()
		{
			return this._instantEditor;
		},
		setInstantEditor: function(instantEditor)
		{
			this._instantEditor = instantEditor;

			for(var k in this._sections)
			{
				if(!this._sections.hasOwnProperty(k))
				{
					continue;
				}

				var items =  this._sections[k].getItems();
				for(var i = 0; i < items.length; i++)
				{
					items[i].setInstantEditor(instantEditor);
				}
			}

			for(var n in this._models)
			{
				if(this._models.hasOwnProperty(n))
				{
					this._models[n].setInstantEditor(instantEditor);
				}
			}

			BX.addCustomEvent("CrmInstantEditorSetFieldReadOnly", BX.delegate(this._onSetReadOnlyField, this));
			var fieldNames = instantEditor.getReadOnlyFieldNames();
			for(var j = 0; j < fieldNames.length; j++)
			{
				this.setItemLocked(fieldNames[j], true);
			}

			BX.addCustomEvent(this._instantEditor, "CrmInstantEditorFieldValueSaved", BX.delegate(this._onEditorFieldValueSave, this));
		},
		getMessage: function(name)
		{
			var m = BX.CrmQuickPanelView.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		resolveElementId: function(id)
		{
			return this._prefix !== "" ? (this._prefix + "_" + id) : id;
		},
		isExpanded: function()
		{
			return this._isExpanded;
		},
		setExpanded: function(expanded)
		{
			expanded = !!expanded;
			if(this._isExpanded === expanded)
			{
				return;
			}

			this._isExpanded = expanded;

			BX.onCustomEvent(
				window,
				"CrmQuickPanelViewExpanded",
				[this, this._isExpanded]
			);

			if(this._isExpanded)
			{
				BX.removeClass(this._toggleButton, "crm-lead-header-contact-btn-close");
				BX.addClass(this._toggleButton, "crm-lead-header-contact-btn-open");
			}
			else
			{
				BX.removeClass(this._toggleButton, "crm-lead-header-contact-btn-open");
				BX.addClass(this._toggleButton, "crm-lead-header-contact-btn-close");
			}
			this.saveConfig(false);
		},
		isFixed: function()
		{
			return this._isFixed;
		},
		setFixed: function(fixed)
		{
			fixed = !!fixed;
			if(this._isFixed === fixed)
			{
				return;
			}

			if(fixed)
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				BX.bind(window, "scroll", this._scrollHandler);

				BX.unbind(window, "resize", this._resizeHandler);
				BX.bind(window, "resize", this._resizeHandler);

				BX.removeClass(this._pinButton, "crm-lead-header-contact-btn-unpin");
				BX.addClass(this._pinButton, "crm-lead-header-contact-btn-pin");
			}
			else
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				BX.unbind(window, "resize", this._resizeHandler);

				BX.removeClass(this._wrapper, "crm-lead-header-table-wrap-fixed");
				BX.removeClass(this._pinButton, "crm-lead-header-contact-btn-pin");
				BX.addClass(this._pinButton, "crm-lead-header-contact-btn-unpin");

				this._placeholder.style.height = this._placeholder.style.width = "";
				this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";
			}

			this._isFixed = fixed;
			this._isFixedLayout = false;

			this.saveConfig(false);
		},
		isInstantEditEnabled: function()
		{
			return this._enableInstantEdit;
		},
		getConfig: function()
		{
			var config =
			{
				enabled: this._enableUserConfig ? "Y" : "N",
				expanded: this._isExpanded ? "Y" : "N",
				fixed: this._isFixed ? "Y" : "N"
			};
			for(var k in this._sections)
			{
				if(!this._sections.hasOwnProperty(k))
				{
					continue;
				}

				var items = this._sections[k].getItems();
				var ids = [];
				for(var i = 0; i < items.length; i++)
				{
					ids.push(items[i].getId());
				}
				
				config[k] = ids.length > 0 ? ids.join(",") : "";
			}
			return config;
		},
		saveConfig: function(forAllUsers, callback)
		{
			forAllUsers = !!forAllUsers && this.getSetting("canSaveSettingsForAll", false);

			var data = { guid: this._id, action: "saveconfig", config: this.getConfig() };
			if(forAllUsers)
			{
				data["forAllUsers"] = "Y";
				data["delete"] = "Y";
			}

			if(BX.type.isFunction(callback))
			{
				this._requestCompleteCallback = callback;
			}

			this._waitAnchor = this._lastChangedSection;
			this._waiter = BX.showWait(this._lastChangedSection);

			BX.ajax.post(this._serviceUrl, data, BX.delegate(this._onConfigRequestComplete, this));
		},
		resetConfig: function(forAllUsers, callback)
		{
			forAllUsers = !!forAllUsers && this.getSetting("canSaveSettingsForAll", false);

			this._waitAnchor = this._lastChangedSection;
			this._waiter = BX.showWait(this._lastChangedSection);

			if(BX.type.isFunction(callback))
			{
				this._requestCompleteCallback = callback;
			}

			if(!this._isExpanded)
			{
				BX.onCustomEvent(
					window,
					"CrmQuickPanelViewExpanded",
					[this, true]
				);
				this._isExpanded = true;
			}

			var data = { guid: this._id, action: "resetconfig" };
			if(forAllUsers)
			{
				data["forAllUsers"] = "Y";
			}
			BX.ajax.post(this._serviceUrl, data, BX.delegate(this._onConfigRequestComplete, this));
		},
		reload: function()
		{
			window.location = window.location.href;
		},
		getFieldData: function(fieldId)
		{
			return this._entityData.hasOwnProperty(fieldId) ? this._entityData[fieldId] : null;
		},
		getFieldModel: function(fieldId)
		{
			var model = BX.CrmQuickPanelModel.getItem(fieldId);
			if(model)
			{
				return model;
			}

			var entityData = this._entityData.hasOwnProperty(fieldId) ? this._entityData[fieldId] : null;
			if(!entityData)
			{
				return null;
			}

			var type = BX.type.isNotEmptyString(entityData["type"]) ? entityData["type"] : "";
			if(type === "boolean")
			{
				model = BX.CrmQuickPanelBooleanModel.create(fieldId, { config: entityData });
			}
			else if(type === "enumeration")
			{
				model = BX.CrmQuickPanelEnumerationModel.create(fieldId, { config: entityData });
			}
			else if(type === "money")
			{
				model = BX.CrmQuickPanelMoneyModel.create(fieldId, { config: entityData });
			}
			else if(type === "html")
			{
				model = BX.CrmQuickPanelHtmlModel.create(fieldId, { config: entityData });
			}
			else if(type === "text" || type === "datetime" || type === "date")
			{
				model = BX.CrmQuickPanelTextModel.create(fieldId, { config: entityData });
			}
			else if(type === "client")
			{
				model = BX.CrmQuickPanelClientModel.create(fieldId, { config: entityData });
			}
			else if(type === "multiple_client")
			{
				model = BX.CrmQuickPanelMultipleClientModel.create(fieldId, { config: entityData });
			}
			else if(type === "composite_client")
			{
				model = BX.CrmQuickPanelCompositeClientModel.create(fieldId, { config: entityData });
			}
			else
			{
				model = BX.CrmQuickPanelModel.create(fieldId, { config: entityData });
			}

			if(this._instantEditor)
			{
				model.setInstantEditor(this._instantEditor);
			}
			return (this._models[fieldId] = model);
		},
		enableDragOverHandling: function(enable)
		{
			this._enableDragOverHandling = typeof(enable) !== "undefined" ? !!enable : true;
		},
		pauseDragOverHandling: function(timeout)
		{
			this._enableDragOverHandling = false;
			setTimeout(BX.delegate(this.enableDragOverHandling, this), timeout);
		},
		isDragOverHandlingEnabled: function()
		{
			this._enableDragOverHandling = true;
		},
		processSectionItemDeletion: function(section, item)
		{
			this._lastChangedSection = section;
			this._enableUserConfig = true;

			if(section.getItemCount() === 0)
			{
				section.createPlaceHolder(-1);
			}

			this.saveConfig(false);
		},
		processDraggedItemDrop: function(dragContainer, draggedItem)
		{
			var targetSection = dragContainer.getSection();
			var context = draggedItem.getContextData();
			var contextId = BX.type.isNotEmptyString(context["contextId"]) ? context["contextId"] : "";
			if(contextId === BX.CrmQuickPanelSectionDragItem.contextId)
			{
				var item = typeof(context["item"]) !== "undefined" ?  context["item"] : null;
				if(!item)
				{
					return;
				}

				var initialSection = item.getSection();
				if(!initialSection)
				{
					return;
				}

				if(targetSection === initialSection)
				{
					var placeholder = initialSection.getPlaceHolder();
					var index = placeholder ? placeholder.getIndex() : -1;
					if(initialSection.moveItem(item, index))
					{
						this._lastChangedSection = initialSection;
						this._enableUserConfig = true;
						this.saveConfig(false);
					}
				}
				else
				{
					if(targetSection.findItemById(item.getId()))
					{
						BX.NotificationPopup.show("field_already_exists", { messages: [this.getMessage("dragDropErrorFieldAlreadyExists")] });
					}
					else
					{
						initialSection.deleteItem(item);
						if(initialSection.getItemCount() === 0)
						{
							initialSection.createPlaceHolder(-1);
						}
						targetSection.createItem(item.getId(), item.getModel());

						this._lastChangedSection = targetSection;
						this._enableUserConfig = true;
						this.saveConfig(false);
					}
				}
			}
			else if(this._formSettingsManager && contextId === this._formSettingsManager.getDraggableFieldContextId())
			{
				var fieldId = this._formSettingsManager.resolveDraggableFieldId(context);
				if(targetSection.findItemById(fieldId))
				{
					BX.NotificationPopup.show("field_already_exists", { messages: [this.getMessage("dragDropErrorFieldAlreadyExists")] });
					return;
				}

				var model = this.getFieldModel(fieldId);
				if(!model)
				{
					BX.NotificationPopup.show("field_not_supported", { messages: [this.getMessage("dragDropErrorFieldNotSupported")] });
					return;
				}

				targetSection.createItem(fieldId, model);
				this._lastChangedSection = targetSection;
				this._enableUserConfig = true;
				this.saveConfig(false);
			}
		},
		processControlPanelLayoutChange: function(panel)
		{
			if(this._isFixed && this._isFixedLayout)
			{
				var heightOffset = panel.isFixed() ? panel.getRect().height : 0;
				this._wrapper.style.top = heightOffset > 0 ? (heightOffset.toString() + "px") : "0";
			}
		},
		getItemDropCallback: function()
		{
			return this._itemDropHandler;
		},
		adjust: function(force)
		{
			if(!this._isFixed)
			{
				return;
			}

			var heightOffset = 0;
			var panel = typeof(BX.CrmControlPanel) !== "undefined" ? BX.CrmControlPanel.getDefault() : null;
			if(panel && panel.isFixed())
			{
				heightOffset = panel.getRect().height;
			}

			if (BX.CrmQuickPanelView.getNodeRect(this._placeholder).top <= heightOffset)
			{
				if(this._isFixedLayout && force !== true)
				{
					//synchronize wrapper width
					this._wrapper.style.width = BX.CrmQuickPanelView.getNodeRect(this._placeholder).width.toString() + "px";
				}
				else
				{
					var r = BX.CrmQuickPanelView.getNodeRect(this._wrapper.parentNode);
					this._wrapper.style.height = this._placeholder.style.height = r.height.toString() + "px";
					this._wrapper.style.width = r.width.toString() + "px";
					this._wrapper.style.left = r.left > 0 ? (r.left.toString() + "px") : "0";
					this._wrapper.style.top = heightOffset > 0 ? (heightOffset.toString() + "px") : "0";

					BX.addClass(this._wrapper, "crm-lead-header-table-wrap-fixed");
					this._isFixedLayout = true;
				}
			}
			else if(this._isFixedLayout)
			{
				this._isFixedLayout = false;
				BX.removeClass(this._wrapper, "crm-lead-header-table-wrap-fixed");

				this._placeholder.style.height = this._placeholder.style.width = "";
				this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";
			}
		},
		registerUnloadHandler: function(handler)
		{
			if(!BX.type.isFunction(handler))
			{
				return false;
			}

			for(var i = 0; i < this._unloadHandlers.length; i++)
			{
				if(this._unloadHandlers[i] === handler)
				{
					return false;
				}
			}
			this._unloadHandlers.push(handler);
			return true;
		},
		unregisterUnloadHandler: function(handler)
		{
			for(var i = 0; i < this._unloadHandlers.length; i++)
			{
				if(this._unloadHandlers[i] === handler)
				{
					this._unloadHandlers.splice(i, 1);
					return true;
				}
			}
			return false;
		},
		setItemLocked: function(id, locked)
		{
			for(var k in this._sections)
			{
				if(!this._sections.hasOwnProperty(k))
				{
					continue;
				}

				var item = this._sections[k].findItemById(id);
				if(item)
				{
					item.setLocked(locked);
					break;
				}
			}
		},
		_onItemDrop: function(dragContainer, draggedItem, x, y)
		{
			this.processDraggedItemDrop(dragContainer, draggedItem);
		},
		_onEditorFieldValueSave: function(name, value)
		{
			for(var k in this._models)
			{
				if(this._models.hasOwnProperty(k))
				{
					this._models[k].processEditorFieldValueSave(name, value);
				}
			}
		},
		_onFormSettingManagerCreate: function(mgr)
		{
			this._formSettingsManager = mgr;
		},
		_onConfigRequestComplete: function()
		{
			if(this._waiter)
			{
				BX.closeWait(this._waitAnchor, this._waiter);
				this._waiter = null;
				this._waitAnchor = null;
			}

			if(this._requestCompleteCallback)
			{
				var callback = this._requestCompleteCallback;
				this._requestCompleteCallback = null;
				callback();
			}
		},
		_onMenuButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this._openMenu();
			return BX.PreventDefault(e);
		},
		_onPinButtonClick: function(e)
		{
			this.setFixed(!this.isFixed());
		},
		_onToggleButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this.setExpanded(!this.isExpanded());
			return BX.PreventDefault(e);
		},
		_openMenu: function()
		{
			if(this._isMenuShown)
			{
				return;
			}

			var menuItems =
			[
				{
					id: "reset",
					text: this.getMessage("resetMenuItem"),
					onclick: BX.delegate(this._onResetMenuItemClick, this)
				}
			];

			if(this.getSetting("canSaveSettingsForAll", false))
			{
				menuItems.push(
					{
						id: "saveForAll",
						text: this.getMessage("saveForAllMenuItem"),
						onclick: BX.delegate(this._onSaveForAllMenuItemClick, this)
					}
				);

				menuItems.push(
					{
						id: "resetForAll",
						text: this.getMessage("resetForAllMenuItem"),
						onclick: BX.delegate(this._onResetForAllMenuItemClick, this)
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			this._menu = BX.PopupMenu.create(
				this._menuId,
				this._menuButton,
				menuItems,
				{
					autoHide: true,
					offsetTop: 0,
					offsetLeft: 0,
					angle:
					{
						position: "top",
						offset: 10
					},
					events:
					{
						onPopupClose : BX.delegate(this._onMenuClose, this)
					}
				}
			);

			this._menu.popupWindow.show();
			this._isMenuShown = true;
		},
		_closeMenu: function()
		{
			if(this._menu && this._menu.popupWindow)
			{
				this._menu.popupWindow.close();
			}
		},
		_onMenuClose: function()
		{
			this._menu = null;
			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}
			this._isMenuShown = false;
		},
		_onResetMenuItemClick: function()
		{
			this._closeMenu();
			if(!this._formSettingsManager)
			{
				var self = this;
				this.resetConfig(false, function(){ self.reload(); });
			}
			else
			{
				var mgr = this._formSettingsManager;
				this.resetConfig(false, function(){ mgr.reset(); });
			}
		},
		_onResetForAllMenuItemClick: function()
		{
			this._closeMenu();
			if(!this._formSettingsManager)
			{
				var self = this;
				this.resetConfig(true, function(){ self.reload(); });
			}
			else
			{
				var mgr = this._formSettingsManager;
				this.resetConfig(true, function(){ mgr.reset(); });
			}
		},
		_onSaveForAllMenuItemClick: function()
		{
			this._closeMenu();
			if(!this._formSettingsManager)
			{
				this.saveConfig(true);
			}
			else
			{
				var mgr = this._formSettingsManager;
				this.saveConfig(
					true,
					function(){ mgr.save(true); }
				);
			}
		},
		_onWindowScroll: function()
		{
			this.adjust();
		},
		_onWindowResize: function(e)
		{
			this.adjust(true);
		},
		_onUnload: function(e)
		{
			var result = "";
			for(var i = 0; i < this._unloadHandlers.length; i++)
			{
				var text = this._unloadHandlers[i]();
				if(BX.type.isNotEmptyString(text))
				{
					if(result !== "")
					{
						result += "\r\n";
					}
					result += text;
				}
			}

			if(result !== "")
			{
				return result;
			}
		},
		_onEditorCreated: function(instantEditor)
		{
			BX.removeCustomEvent(window, "CrmInstantEditorCreated", this._editorCreatedHandler);
			this.setInstantEditor(instantEditor);
		},
		_onSetReadOnlyField: function(instantEditor, name, readonly)
		{
			if(this._instantEditor === instantEditor)
			{
				this.setItemLocked(name, readonly);
			}
		},
		_onControlPanelLayoutChange: function(panel)
		{
			this.processControlPanelLayoutChange(panel);
		},
		_onDragDropBinItemDrop: function(sender, draggedItem)
		{
			if(draggedItem instanceof BX.CrmQuickPanelSectionDragItem)
			{
				var item = draggedItem.getItem();
				if(item)
				{
					item.remove(true);
				}
			}
		}
	};
	if(typeof(BX.CrmQuickPanelView.messages) === "undefined")
	{
		BX.CrmQuickPanelView.messages = {};
	}
	BX.CrmQuickPanelView.getNodeRect = function(node)
	{
		var r = node.getBoundingClientRect();
		return (
			{
				top: r.top, bottom: r.bottom, left: r.left, right: r.right,
				width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
				height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
			}
		);
	};
	BX.CrmQuickPanelView._default  = null;
	BX.CrmQuickPanelView.getDefault = function()
	{
		return this._default;
	};
	BX.CrmQuickPanelView.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelView();

		if(!this._default)
		{
			this._default = self;
		}

		self.initialize(id, settings);
		return self;
	};
}
//BX.CrmQuickPanelItem
if(typeof(BX.CrmQuickPanelItem) === "undefined")
{
	BX.CrmQuickPanelItem = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._model = null;
		this._instantEditor = null;
		this._isLocked = false;
		this._hasLayout = false;

	};
	BX.CrmQuickPanelItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};
			this._model = this.getSetting("model");

			var container = this.getSetting("container", null);
			var hasLayout = container && this.getSetting("hasLayout", true);
			this.setContainer(container, hasLayout);

			if(!this._model)
			{
				throw "CrmQuickPanelItem: The 'model' parameter is not defined in settings or empty.";
			}

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmQuickPanelItem.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getModel: function()
		{
			return this._model;
		},
		getData: function()
		{
			return this._model.getData();
		},
		getType: function()
		{
			return this._model.getType();
		},
		getCaption: function()
		{
			return this._model.getCaption();
		},
		isCaptionEnabled: function()
		{
			return this._model.isCaptionEnabled();
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container, hasLayout)
		{
			this._container = container;
			this._hasLayout = !!hasLayout;

			this.doSetContainer();
		},
		doSetContainer: function()
		{
		},
		getControl: function()
		{
			return null;
		},
		getInstantEditor: function()
		{
			return this._instantEditor;
		},
		setInstantEditor: function(instantEditor)
		{
			this._instantEditor = instantEditor;
			var control = this.getControl();
			if(control)
			{
				control.setInstantEditor(instantEditor);
			}

			this.doSetInstantEditor();
		},
		doSetInstantEditor: function()
		{
		},
		isEditable: function()
		{
			return !this._isLocked && this._model.isEditable();
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
			var control = this.getControl();
			if(control)
			{
				control.setLocked(locked);
			}

			this.doSetLocked();
		},
		doSetLocked: function()
		{
		}
	};
	if(typeof(BX.CrmQuickPanelItem.messages) === "undefined")
	{
		BX.CrmQuickPanelItem.messages = {};
	}
}
if(typeof(BX.CrmQuickPanelHeaderItem) === "undefined")
{
	BX.CrmQuickPanelHeaderItem = function()
	{
		BX.CrmQuickPanelHeaderItem.superclass.constructor.apply(this);
		this._control = null;
		this._editButton = null;
		this._editHandler = BX.delegate(this._onEditButtonClick, this);
		this._dblClickHandler = BX.delegate(this._onDoubleClick, this);
	};
	BX.extend(BX.CrmQuickPanelHeaderItem, BX.CrmQuickPanelItem);
	BX.CrmQuickPanelHeaderItem.prototype.doInitialize = function()
	{
		if(!this._container)
		{
			return;
		}

		this._control = this.createControl(this._container);
		this._editButton = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-title-edit" }, true, false);
		this.bindEvents();
	};
	BX.CrmQuickPanelHeaderItem.prototype.doSetLocked = function()
	{
		this._editButton.style.display = this._isLocked ? "none" : "";
	};
	BX.CrmQuickPanelHeaderItem.prototype.bindEvents = function()
	{
		if(this._editButton)
		{
			BX.bind(this._editButton, "click", this._editHandler);
		}

		BX.bind(this._container, "dblclick", this._dblClickHandler);
	};
	BX.CrmQuickPanelHeaderItem.prototype.createControl = function(container)
	{
		var control;
		var type = this.getType();
		if(type === "money")
		{
			control = BX.CrmQuickPanelHeaderMoney.create("", { item: this, container: container, hasLayout: true });
		}
		else
		{
			control = BX.CrmQuickPanelHeaderText.create("", { item: this, container: container, hasLayout: true });
		}

		if(this._instantEditor)
		{
			control.setInstantEditor(this._instantEditor);
		}

		return control;
	};
	BX.CrmQuickPanelHeaderItem.prototype._onEditButtonClick = function(e)
	{
		if(this._control)
		{
			this._control.toggleMode();
		}
	};
	BX.CrmQuickPanelHeaderItem.prototype._onDoubleClick = function(e)
	{
		if(!this.isEditable())
		{
			return;
		}

		if(this._control && this._control.getMode() === BX.CrmQuickPanelControl.mode.view)
		{
			this._control.switchMode(BX.CrmQuickPanelControl.mode.edit);
		}
	};
	BX.CrmQuickPanelHeaderItem.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelHeaderItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPaneSectionItem) === "undefined")
{
	BX.CrmQuickPaneSectionItem = function()
	{
		BX.CrmQuickPaneSectionItem.superclass.constructor.apply(this);
		this._section = null;
		this._prefix = "";
		//this._deleteButton = null;
		this._deleteHandler = BX.delegate(this._onDeleteButtonClick, this);
		this._editButton = null;
		this._editHandler = BX.delegate(this._onEditButtonClick, this);
		this._dragButton = null;
		this._dblClickHandler = BX.delegate(this._onDoubleClick, this);
		this._contextMenuHandler = BX.delegate(this._onContextMenu, this);
		this._control = null;
		this._dragItem = null;
		this._isInitialized = false;

		this._contextMenu = null;
		this._contextMenuId = "quick_panel_section_item";
		this._isContextMenuShown = false;
	};
	BX.extend(BX.CrmQuickPaneSectionItem, BX.CrmQuickPanelItem);
	BX.CrmQuickPaneSectionItem.prototype.doInitialize = function()
	{
		this._prefix = this.getSetting("prefix", "");
		this._section = this.getSetting("section", null);

		if(this._hasLayout)
		{
			this.initializeLayout();
		}
	};
	BX.CrmQuickPaneSectionItem.prototype.doSetContainer = function()
	{
		if(this._container && this._hasLayout)
		{
			this.initializeLayout();
		}
	};
	BX.CrmQuickPaneSectionItem.prototype.doSetLocked = function()
	{
		this._editButton.style.display = this._isLocked ? "none" : "";
	};
	BX.CrmQuickPaneSectionItem.prototype.initializeLayout = function()
	{
		if(this._isInitialized || !this._container)
		{
			return;
		}

		var enableCaption = this.isCaptionEnabled();
		this._control = this.createControl(this._container.cells[enableCaption ? 2 : 1]);
		//this._deleteButton = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-inner-del-btn" }, true, false);
		this._editButton = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-inner-edit-btn" }, true, false);
		this._dragButton = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-inner-move-btn" }, true, false);

		this.initializeDragDropAbilities();
		this.bindEvents();
		this._isInitialized = true;
	};
	BX.CrmQuickPaneSectionItem.prototype.createControl = function(container)
	{
		var control;
		var type = this.getType();
		if(type === "link")
		{
			control = BX.CrmQuickPanelLink.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "date")
		{
			control = BX.CrmQuickPanelDateTime.create("", { item: this, container: container, hasLayout: this._hasLayout, enableTime: false });
		}
		else if(type === "datetime")
		{
			control = BX.CrmQuickPanelDateTime.create("", { item: this, container: container, hasLayout: this._hasLayout, enableTime: true });
		}
		else if(type === "boolean")
		{
			control = BX.CrmQuickPanelBoolean.create("", { item: this, container: container, hasLayout: this._hasLayout, enableTime: true });
		}
		else if(type === "enumeration")
		{
			control = BX.CrmQuickPanelEnumeration.create("", { item: this, container: container, hasLayout: this._hasLayout, enableTime: true });
		}
		else if(type === "multiField")
		{
			control = BX.CrmQuickPanelMultiField.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "address")
		{
			control = BX.CrmQuickPanelAddress.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "responsible")
		{
			control = BX.CrmQuickPanelResponsible.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "client")
		{
			control = BX.CrmQuickPanelClientInfo.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "multiple_client")
		{
			control = BX.CrmQuickPanelMultipleClientInfo.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "composite_client")
		{
			control = BX.CrmQuickPanelCompositeClientInfo.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "money")
		{
			control = BX.CrmQuickPanelMoney.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "custom")
		{
			control = BX.CrmQuickPanelHtml.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else if(type === "html")
		{
			control = BX.CrmQuickPanelVisualEditor.create("", { item: this, container: container, hasLayout: this._hasLayout });
		}
		else
		{
			control = BX.CrmQuickPanelText.create("", {item: this, container: container, hasLayout: this._hasLayout});
		}

		if(this._instantEditor)
		{
			control.setInstantEditor(this._instantEditor);
		}

		return control;
	};
	BX.CrmQuickPaneSectionItem.prototype.getView = function()
	{
		return this._section ? this._section.getView() : null;
	};
	BX.CrmQuickPaneSectionItem.prototype.getPrefix = function()
	{
		return this._prefix;
	};
	BX.CrmQuickPaneSectionItem.prototype.setPrefix = function(prefix)
	{
		this._prefix = prefix;
	};
	BX.CrmQuickPaneSectionItem.prototype.getSection = function()
	{
		return this._section;
	};
	BX.CrmQuickPaneSectionItem.prototype.setSection = function(section)
	{
		this._section = section;
	};
	BX.CrmQuickPaneSectionItem.prototype.layout = function()
	{
		if(!this._container)
		{
			throw "CrmQuickPaneSectionItem: The 'container' is not assigned.";
		}

		var enableCaption = this.isCaptionEnabled();
		var row = this._container;
		var cell = row.insertCell(-1);
		cell.className = "crm-lead-header-inner-cell crm-lead-header-inner-cell-move";
		this._dragButton = BX.create("DIV", { attrs: { className: "crm-lead-header-inner-move-btn" } });
		cell.appendChild(this._dragButton);

		if(enableCaption)
		{
			cell = row.insertCell(-1);
			cell.className = "crm-lead-header-inner-cell crm-lead-header-inner-cell-title";
			cell.innerHTML = BX.util.htmlspecialchars(this.getCaption());

			cell = row.insertCell(-1);
			cell.className = "crm-lead-header-inner-cell";
		}
		else
		{
			cell = row.insertCell(-1);
			cell.className = "crm-lead-header-inner-cell crm-lead-header-inf-block";
			cell.colSpan = 2;
		}

		this._control = this.createControl(cell);
		this._control.layout();

		cell = row.insertCell(-1);
		cell.className = "crm-lead-header-inner-cell crm-lead-header-inner-cell-del";

		//this._deleteButton = BX.create("DIV", { attrs: { className: "crm-lead-header-inner-del-btn" } });
		//cell.appendChild(this._deleteButton);

		var enableEditButton = this.isEditable();
		if(enableEditButton)
		{
			enableEditButton = this._control.canChangeMode();
		}
		if(enableEditButton)
		{
			this._editButton = BX.create("DIV", { attrs: { className: "crm-lead-header-inner-edit-btn" } });
			cell.appendChild(this._editButton);
		}

		this.initializeDragDropAbilities();
		this.bindEvents();
	};
	BX.CrmQuickPaneSectionItem.prototype.createGhostNode = function()
	{
		var node = BX.create("DIV", { attrs: { className: "crm-lead-fly-item" } });
		var table = BX.create("TABLE", { attrs: { className: "crm-lead-header-inner-table" } });
		node.appendChild(table);

		var row = table.insertRow();
		var cell = row.insertCell();
		cell.className = "crm-lead-header-inner-cell crm-lead-header-inner-cell-move";
		cell.appendChild(BX.create("DIV", { attrs: { className: "crm-lead-header-inner-move-btn" } }));

		if(this.isCaptionEnabled())
		{
			cell = row.insertCell();
			cell.className = "crm-lead-header-inner-cell crm-lead-header-inner-cell crm-lead-header-inner-cell-title";
			cell.innerHTML = BX.util.htmlspecialchars(this.getCaption());

			cell = row.insertCell();
			cell.className = "crm-lead-header-inner-cell";
			cell.innerHTML = this.getContainer().cells[2].innerHTML;
		}
		else
		{
			cell = row.insertCell(-1);
			cell.className = "crm-lead-header-inner-cell crm-lead-header-inf-block";
			cell.colSpan = 2;
			cell.innerHTML = this.getContainer().cells[1].innerHTML;
		}

		cell = row.insertCell();
		cell.className = "crm-lead-header-inner-cell crm-lead-header-inner-cell-del";

		var rect = BX.pos(this._container);
		node.style.width = (rect.width - 10) + "px";
		return node;
	};
	BX.CrmQuickPaneSectionItem.prototype.clearLayout = function()
	{
		if(!this._container)
		{
			throw "CrmQuickPaneSectionItem: The 'container' is not assigned.";
		}

		if(this._control)
		{
			this._control.clearLayout();
			this._control = null;
		}

		this._closeContextMenu();
		this.releaseDragDropAbilities();
		this.unbindEvents();
		this._dragButton = null;
		this._editButton = null;
		//this._deleteButton = null;

		BX.cleanNode(this._container, false);

		this._isInitialized = false;
		this._hasLayout = false;
	};
	BX.CrmQuickPaneSectionItem.prototype.bindEvents = function()
	{
		BX.bind(this._container, "dblclick", this._dblClickHandler);

		//if(this._deleteButton)
		//{
		//	BX.bind(this._deleteButton, "click", this._deleteHandler);
		//}

		if(this._editButton)
		{
			BX.bind(this._editButton, "click", this._editHandler);
		}

		if(this._dragButton)
		{
			BX.bind(this._dragButton, "contextmenu", this._contextMenuHandler);
		}
	};
	BX.CrmQuickPaneSectionItem.prototype.unbindEvents = function()
	{
		BX.unbind(this._container, "dblclick", this._dblClickHandler);

		//if(this._deleteButton)
		//{
		//	BX.unbind(this._deleteButton, "click", this._deleteHandler);
		//}

		if(this._editButton)
		{
			BX.unbind(this._editButton, "click", this._editHandler);
		}

		if(this._dragButton)
		{
			BX.unbind(this._dragButton, "contextmenu", this._contextMenuHandler);
		}
	};
	BX.CrmQuickPaneSectionItem.prototype.remove = function(silent)
	{
		silent = !!silent;
		this._closeContextMenu();

		if(!this._section)
		{
			return;
		}

		if(silent || window.confirm(this.getMessage("deletionConfirmation")))
		{
			this._section.processItemDeletion(this);
		}
	};
	BX.CrmQuickPaneSectionItem.prototype._onContextMenu = function(e)
	{
		this._openContextMenu();
		return BX.eventReturnFalse(e);
	};
	BX.CrmQuickPaneSectionItem.prototype._openContextMenu = function()
	{
		if(this._isContextMenuShown)
		{
			return;
		}

		var currentMenu = BX.PopupMenu.getMenuById(this._contextMenuId);
		if(currentMenu)
		{
			currentMenu.popupWindow.close();
		}

		var menuItems = [];
		if(this.isEditable())
		{
			menuItems.push(
				{
					id: "edit",
					text: this.getMessage("editMenuItem"),
					onclick: BX.delegate(this._onEditMenuItemClick, this)
				}
			);
		}

		menuItems.push(
			{
				id: "delete",
				text: this.getMessage("deleteMenuItem"),
				onclick: BX.delegate(this._onDeleteMenuItemClick, this)
			}
		);

		this._contextMenu = BX.PopupMenu.create(
			this._contextMenuId,
			this._dragButton,
			menuItems,
			{
				autoHide: true,
				offsetTop: 0,
				offsetLeft: 0,
				angle: { position: "top", offset: 10 },
				events: { onPopupClose : BX.delegate(this._onContextMenuClose, this) }
			}
		);

		this._contextMenu.popupWindow.show();
		this._isContextMenuShown = true;
	};
	BX.CrmQuickPaneSectionItem.prototype._closeContextMenu = function()
	{
		if(this._contextMenu && this._contextMenu.popupWindow)
		{
			this._contextMenu.popupWindow.close();
		}
	};
	BX.CrmQuickPaneSectionItem.prototype._onContextMenuClose = function()
	{
		this._contextMenu = null;
		if(typeof(BX.PopupMenu.Data[this._contextMenuId]) !== "undefined")
		{
			BX.PopupMenu.Data[this._contextMenuId].popupWindow.destroy();
			delete BX.PopupMenu.Data[this._contextMenuId];
		}
		this._isContextMenuShown = false;
	};
	BX.CrmQuickPaneSectionItem.prototype._onDeleteButtonClick = function(e)
	{
		this.remove();
	};
	BX.CrmQuickPaneSectionItem.prototype._onDeleteMenuItemClick = function(e)
	{
		this._closeContextMenu();
		this.remove(false);
	};
	BX.CrmQuickPaneSectionItem.prototype._onEditButtonClick = function(e)
	{
		if(!this.isEditable())
		{
			return;
		}

		if(this._control)
		{
			this._control.toggleMode();
		}
	};
	BX.CrmQuickPaneSectionItem.prototype._onEditMenuItemClick = function()
	{
		this._closeContextMenu();

		if(!this.isEditable())
		{
			return;
		}

		if(this._control)
		{
			this._control.toggleMode();
		}
	};
	BX.CrmQuickPaneSectionItem.prototype._onDoubleClick = function(e)
	{
		if(!this.isEditable())
		{
			return;
		}

		if(this._control && this._control.getMode() === BX.CrmQuickPanelControl.mode.view)
		{
			this._control.switchMode(BX.CrmQuickPanelControl.mode.edit);
		}
	};
	//D&D abilities
	BX.CrmQuickPaneSectionItem.prototype.initializeDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			return;
		}

		if(!this._dragButton)
		{
			throw "CrmQuickPaneSectionItem: Could not find drag button.";
		}

		this._dragItem = BX.CrmQuickPanelSectionDragItem.create(
			this.getId(),
			{
				item: this,
				node: this._dragButton,
				showItemInDragMode: false,
				ghostOffset: { x: -8, y: -8 }
			}
		);
	};
	BX.CrmQuickPaneSectionItem.prototype.releaseDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			this._dragItem.release();
			this._dragItem = null;
		}
	};
	BX.CrmQuickPaneSectionItem.create = function(id, settings)
	{
		var self = new BX.CrmQuickPaneSectionItem();
		self.initialize(id, settings);
		return self;
	};
}
//BX.CrmQuickPanelControl
if(typeof(BX.CrmQuickPanelControl) === "undefined")
{
	BX.CrmQuickPanelControl = function()
	{
		this._id = "";
		this._settings = null;
		this._item = null;
		this._model = null;
		this._container = null;
		this._mode = 0;
		this._documentClickHandler = BX.delegate(this._onDocumentClick, this);
		this._fieldValueSaveHandler = BX.delegate(this._onFieldValueSave, this);
		this._beforeUnloadHandlerHandler = BX.delegate(this._onBeforeUnload, this);
		this._modelChangeHandler = BX.delegate(this._onModelChange, this);
		this._instantEditor = null;
		this._isEditable = false;
		this._hasLayout = false;
		this._isLocked = false;
		this._enableModelSubscription = false;
		this._enableDocumentUnloadSubscription = false;
	};
	BX.CrmQuickPanelControl.mode =
	{
		undifined: 0,
		view: 1,
		edit: 2
	};
	BX.CrmQuickPanelControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._item = this.getSetting("item", null);
			if(!this._item)
			{
				throw  "Error: Could not find item.";
			}

			this._model = this.getSetting("model", null);
			if(!this._model)
			{
				this._model = this._item.getModel();
			}

			this._container = this.getSetting("container", null);
			if(!this._container)
			{
				throw  "Error: Could not find container.";
			}

			this._hasLayout = this.getSetting("hasLayout", false);
			this._mode = BX.CrmQuickPanelControl.mode.view;

			if(this._enableDocumentUnloadSubscription)
			{
				BX.CrmQuickPanelView.getDefault().registerUnloadHandler(this._beforeUnloadHandlerHandler);
			}

			this._isLocked = this._item.isLocked();

			if(this._enableModelSubscription)
			{
				this._model.registerCallback(this._modelChangeHandler);
			}

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getInstantEditor: function()
		{
			return this._instantEditor;
		},
		setInstantEditor: function(instantEditor)
		{
			this._instantEditor = instantEditor;
			this.processInstantEditorChange();
		},
		processInstantEditorChange: function()
		{
		},
		getMode: function()
		{
			return this._mode;
		},
		isEditMode: function()
		{
			return this._mode === BX.CrmQuickPanelControl.mode.edit;
		},
		toggleMode: function()
		{
			if(this._mode === BX.CrmQuickPanelControl.mode.undifined
				|| (this._mode === BX.CrmQuickPanelControl.mode.view && !this.isEditable()))
			{
				return false;
			}

			if(this._mode === BX.CrmQuickPanelControl.mode.edit)
			{
				this.save();
			}

			var mode = this._mode === BX.CrmQuickPanelControl.mode.edit
				? BX.CrmQuickPanelControl.mode.view : BX.CrmQuickPanelControl.mode.edit;
			return this.switchMode(mode);
		},
		switchMode: function(mode)
		{
			if(this.isLocked())
			{
				return false;
			}

			if(this._mode === mode)
			{
				return false;
			}

			this.onBeforeModeChange();
			this._mode = mode;
			this.onAfterModeChange();
			this.layout();
			this.enableDocumentClick(this.isEditMode());
			return true;
		},
		onBeforeModeChange: function()
		{
		},
		onAfterModeChange: function()
		{
		},
		layout: function()
		{
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.enableDocumentClick(false);

			if(this._enableDocumentUnloadSubscription)
			{
				BX.CrmQuickPanelView.getDefault().unregisterUnloadHandler(this._beforeUnloadHandlerHandler);
			}

			if(this._enableModelSubscription)
			{
				this._model.unregisterCallback(this._modelChangeHandler);
			}

			this.doClearLayout();
			this._hasLayout = false;
		},
		doClearLayout: function()
		{
		},
		isEditable: function()
		{
			return this._isEditable;
		},
		canChangeMode: function()
		{
			return this.isEditable();
		},
		save: function()
		{
		},
		saveFieldValue: function(value)
		{
			var editor = this.getInstantEditor();
			if(editor)
			{
				BX.addCustomEvent(editor, "CrmInstantEditorFieldValueSaved", this._fieldValueSaveHandler);
				BX.showWait();
				editor.saveFieldValue(this._item.getId(), value);
			}
		},
		getMessage: function(name)
		{
			var m = BX.CrmQuickPanelControl.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isOwnElement: function(element)
		{
			return false;
		},
		enableDocumentClick: function(enable)
		{
			if(enable)
			{
				var self = this;
				window.setTimeout(function(){ BX.bind(document, "click", self._documentClickHandler) }, 0);
			}
			else
			{
				BX.unbind(document, "click", this._documentClickHandler);
			}
		},
		isLocked: function()
		{
			return this._isLocked;
		},
		setLocked: function(locked)
		{
			this._isLocked = !!locked;
		},
		isChanged: function()
		{
			return false;
		},
		_onDocumentClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(!this.isEditMode())
			{
				this.enableDocumentClick(false);
				return;
			}

			//Crutch for Chrome & IE
			var target = BX.getEventTarget(e);
			if(target === document.body)
			{
				return;
			}

			var isOwnElement = this.isOwnElement(target);
			if(isOwnElement === false)
			{
				this.toggleMode();
			}
		},
		_onFieldValueSave: function(name, value)
		{
			if(name !== this._item.getId())
			{
				return;
			}

			BX.removeCustomEvent(this.getInstantEditor(), "CrmInstantEditorFieldValueSaved", this._fieldValueSaveHandler);
			BX.closeWait();
		},
		_onBeforeUnload: function(e)
		{
			return (
				this.isEditable() && this.isEditMode() && this.isChanged()
				? this.getMessage("dataNotSaved").replace("#FIELD#", this._item.getCaption())
				: undefined
			);
		},
		_onModelChange: function(model, params)
		{
			if(params && params["source"] === this)
			{
				return;
			}

			this.layout();
		}
	};
	if(typeof(BX.CrmQuickPanelControl.messages) === "undefined")
	{
		BX.CrmQuickPanelControl.messages = {};
	}
}
if(typeof(BX.CrmQuickPanelText) === "undefined")
{
	BX.CrmQuickPanelText = function()
	{
		BX.CrmQuickPanelText.superclass.constructor.apply(this);
		this._wrapper = null;
		this._viewWrapper = null;
		this._editWrapper = null;
		this._input = null;
		this._baseTypeName = "";
		this._isMultiline = false;

		//autoresize
		this._hiddenInput = null;
		this._inputMaxHeight = 224;

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
		this._keyDownHandler = BX.delegate(this._onKeyDown, this);
		this._resizeHandler = BX.delegate(this._onResize, this);
	};
	BX.extend(BX.CrmQuickPanelText, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelText.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();
		this._baseTypeName = this._model.getDataParam("baseType");
		this._isMultiline = this._model.getDataParam("multiline", false);

		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-text-wrapper" }, true, false);
			this._viewWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-text-view-wrapper" }, true, false);
			this._editWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-text-edit-wrapper" }, true, false);
			if(this._isEditable)
			{
				if(this._isMultiline)
				{
					this._input = BX.create("TEXTAREA", { attrs: { className: "crm-lead-header-edit-field" } });
					this._hiddenInput = BX.create("TEXTAREA", { attrs: { className: "crm-lead-header-edit-field" } });
					this._hiddenInput.style.visibility = "hidden";
					this._hiddenInput.style.position = "absolute";
					this._hiddenInput.style.left = "-300px";
					document.body.appendChild(this._hiddenInput);
				}
				else
				{
					this._input = BX.create("INPUT", { attrs: { type: "text", className: "crm-lead-header-edit-inp" } });
				}
				this._editWrapper.appendChild(this._input);
			}
		}
	};
	BX.CrmQuickPanelText.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-text-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._viewWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-text-view-wrapper" } });
			this._wrapper.appendChild(this._viewWrapper);

			this._editWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-text-edit-wrapper" } });
			this._wrapper.appendChild(this._editWrapper);

			if(this._isMultiline)
			{
				this._input = BX.create("TEXTAREA", { attrs: { className: "crm-lead-header-edit-field" } });
				this._hiddenInput = BX.create("TEXTAREA", { attrs: { className: "crm-lead-header-edit-field" } });
				this._hiddenInput.style.visibility = "hidden";
				this._hiddenInput.style.position = "absolute";
				this._hiddenInput.style.left = "-300px";
				document.body.appendChild(this._hiddenInput);
			}
			else
			{
				this._input = BX.create("INPUT", { attrs: { type: "text", className: "crm-lead-header-edit-inp" } });
			}

			this._editWrapper.appendChild(this._input);

			this._hasLayout = true;
		}

		var text = this._model.getValue();
		if(!this.isEditMode())
		{
			BX.unbind(this._input, "keydown", this._keyDownHandler);

			if(this._isMultiline)
			{
				BX.unbind(this._input, "keyup", this._resizeHandler);
				BX.unbind(this._input, "change", this._resizeHandler);
			}

			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			if(this._isMultiline)
			{
				this._viewWrapper.innerHTML = BX.util.htmlspecialchars(text).replace(/(\n)/g, "<br/>");
			}
			else
			{
				this._viewWrapper.innerHTML = BX.util.htmlspecialchars(text);
			}
		}
		else
		{
			if(this._isMultiline)
			{
				var rect = BX.CrmQuickPanelView.getNodeRect(this._viewWrapper);
				var height = rect.height > 16
					? (rect.height < this._inputMaxHeight ? rect.height : this._inputMaxHeight)
					: 16;
				var width = rect.width;

				this._input.style.height = this._hiddenInput.style.height = height + "px";
				this._hiddenInput.style.width = width + "px";

				BX.bind(this._input, "keyup", this._resizeHandler);
				BX.bind(this._input, "change", this._resizeHandler);
			}

			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}
			this._input.value = text;
			this._input.focus();

			BX.bind(this._input, "keydown", this._keyDownHandler);
		}
	};
	BX.CrmQuickPanelText.prototype.doClearLayout = function()
	{
		if(this.isEditMode())
		{
			BX.unbind(this._input, "keydown", this._keyDownHandler);
			if(this._isMultiline)
			{
				BX.unbind(this._input, "keyup", this._resizeHandler);
				BX.unbind(this._input, "change", this._resizeHandler);
			}
		}
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelText.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelText.prototype.saveIfChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;
		if(this._baseTypeName === "int")
		{
			current = current.replace(/[^0-9]/g);
			if(current === "" || isNaN(parseInt(current)))
			{
				current = "0";
			}
		}

		if(previous === current)
		{
			return;
		}

		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelText.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-text-wrapper" });
	};
	BX.CrmQuickPanelText.prototype.isChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;
		return previous !== current;
	};
	BX.CrmQuickPanelText.prototype._onKeyDown = function(e)
	{
		if(!this.isEditMode())
		{
			return;
		}

		e = e || window.event;
		if(e.keyCode === 13 && !this._isMultiline)
		{
			this.saveIfChanged();
			this.switchMode(BX.CrmQuickPanelControl.mode.view);
		}
		else if(e.keyCode === 27)
		{
			this.switchMode(BX.CrmQuickPanelControl.mode.view);
		}
	};
	BX.CrmQuickPanelText.prototype._onResize = function(e)
	{
		var currentHeight = BX.CrmQuickPanelView.getNodeRect(this._input).height;
		this._hiddenInput.value = this._input.value;
		var scrollHeight = this._hiddenInput.scrollHeight;
		if (scrollHeight > this._inputMaxHeight)
		{
			scrollHeight = this._inputMaxHeight;
		}

		if(currentHeight != scrollHeight)
		{
			this._input.style.height = scrollHeight + "px";
		}
	};
	BX.CrmQuickPanelText.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelText();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelDateTime) === "undefined")
{
	BX.CrmQuickPanelDateTime = function()
	{
		BX.CrmQuickPanelDateTime.superclass.constructor.apply(this);
		this._wrapper = null;
		this._viewWrapper = null;
		this._editWrapper = null;
		this._input = null;
		this._selector = null;

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
	};
	BX.extend(BX.CrmQuickPanelDateTime, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelDateTime.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();
		this._enableTime = this.getSetting("enableTime", true);
		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-date-wrapper" }, true, false);
			this._viewWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-date-view-wrapper" }, true, false);
			this._editWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-date-edit-wrapper" }, true, false);
			if(this._isEditable)
			{
				this._input = BX.create(
					"INPUT",
					{
						attrs: { className: "crm-offer-item-inp crm-item-table-date" },
						props: { type: "text" }
					}
				);
				this._editWrapper.appendChild(this._input);
			}
		}
	};
	BX.CrmQuickPanelDateTime.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-date-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._viewWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-date-view-wrapper" } });
			this._wrapper.appendChild(this._viewWrapper);

			this._editWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-date-edit-wrapper" } });
			this._wrapper.appendChild(this._editWrapper);

			this._input = BX.create(
				"INPUT",
				{
					attrs: { className: "crm-offer-item-inp crm-item-table-date" },
					props: { type: "text" }
				}
			);
			this._editWrapper.appendChild(this._input);
			this._hasLayout = true;
		}

		if(!this.isEditMode())
		{
			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			this._viewWrapper.innerHTML = BX.util.htmlspecialchars(this._model.getDataParam("text"));
		}
		else
		{
			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}
			this._input.value = this._model.getDataParam("text");
			if(!this._selector)
			{
				this._selector = BX.CrmDateLinkField.create(
					this._input,
					null,
					{
						showTime: this._enableTime,
						setFocusOnShow: false
					}
				);
			}
		}
	};
	BX.CrmQuickPanelDateTime.prototype.doClearLayout = function()
	{
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelDateTime.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelDateTime.prototype.saveIfChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;
		if(previous === current)
		{
			return;
		}

		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelDateTime.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-date-wrapper" });
	};
	BX.CrmQuickPanelDateTime.prototype.isChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;
		return previous !== current;
	};
	BX.CrmQuickPanelDateTime.prototype.isTimeEnabled = function()
	{
		return this._enableTime;
	};
	BX.CrmQuickPanelDateTime.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelDateTime();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelBoolean) === "undefined")
{
	BX.CrmQuickPanelBoolean = function()
	{
		BX.CrmQuickPanelBoolean.superclass.constructor.apply(this);
		this._wrapper = null;
		this._viewWrapper = null;
		this._editWrapper = null;
		this._input = null;
		this._baseTypeName = "int";
		this._enableChar = false;
		this._value = false;

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
	};
	BX.extend(BX.CrmQuickPanelBoolean, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelBoolean.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();
		this._enableChar = this._model.getBaseType() === "char";
		if(this._enableChar)
		{
			this._value = this._model.getDataParam("value") === "Y";
		}
		else
		{
			this._value = this._model.getDataParam("value") === 1;
		}

		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-boolean-wrapper" }, true, false);
			this._viewWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-boolean-view-wrapper" }, true, false);
			this._editWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-boolean-edit-wrapper" }, true, false);
			if(this._isEditable)
			{
				this._input = BX.create("INPUT", { props: { type: "checkbox", checked: this._value } });
				this._editWrapper.appendChild(this._input);
			}
		}
	};
	BX.CrmQuickPanelBoolean.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-boolean-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._viewWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-boolean-view-wrapper" } });
			this._wrapper.appendChild(this._viewWrapper);

			this._editWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-boolean-edit-wrapper" } });
			this._wrapper.appendChild(this._editWrapper);

			this._input = BX.create("INPUT", { props: { type: "checkbox" } });
			this._editWrapper.appendChild(this._input);

			this._hasLayout = true;
		}

		if(!this.isEditMode())
		{
			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			this._viewWrapper.innerHTML =BX.util.htmlspecialchars(this.getMessage(this._value ? "yes" : "no"));
		}
		else
		{
			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}
			this._input.checked = this._value;

		}
	};
	BX.CrmQuickPanelBoolean.prototype.doClearLayout = function()
	{
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelBoolean.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelBoolean.prototype.saveIfChanged = function()
	{
		var previous = this._value;
		var current = this._input.checked;

		if(previous === current)
		{
			return;
		}

		this._value = current;
		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelBoolean.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-boolean-wrapper" });

	};
	BX.CrmQuickPanelBoolean.prototype.isChanged = function()
	{
		var previous = this._value;
		var current = this._input.checked;
		return previous !== current;
	};
	BX.CrmQuickPanelBoolean.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelBoolean();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelLink) === "undefined")
{
	BX.CrmQuickPanelLink = function()
	{
		BX.CrmQuickPanelLink.superclass.constructor.apply(this);
		this._wrapper = null;
		this._input = null;
		this._url = "";
		this._text = "";
	};
	BX.extend(BX.CrmQuickPanelLink, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelLink.prototype.doInitialize = function()
	{
		this._isEditable = false;

		this._url = this._model.getDataParam("url", "");
		if(this._url === "")
		{
			this._url = "#";
		}

		this._text = this._model.getDataParam("text", "");
		if(this._text === "")
		{
			this._text = this._url;
		}

		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-link-wrapper" }, true, false);
			this._input = BX.findChild(this._wrapper, { tagName: "A", className: "crm-link" }, true, false);
		}
	};
	BX.CrmQuickPanelLink.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-link-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._input = BX.create(
				"A",
				{
					attrs: { className: "crm-link" },
					props: { href: this._url, target: "_blank" },
					text: this._text
				}
			);
			this._wrapper.appendChild(this._input);
			this._hasLayout = true;
		}
	};
	BX.CrmQuickPanelLink.prototype.doClearLayout = function()
	{
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelLink.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-link-wrapper" });

	};
	BX.CrmQuickPanelLink.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelLink();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelHtml) === "undefined")
{
	BX.CrmQuickPanelHtml = function()
	{
		BX.CrmQuickPanelHtml.superclass.constructor.apply(this);
		this._wrapper = null;
		this._html = "";
	};
	BX.extend(BX.CrmQuickPanelHtml, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelHtml.prototype.doInitialize = function()
	{
		this._isEditable = false;
		this._html = this._model.getDataParam("html");

		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-custom-wrapper" }, true, false);
		}
	};
	BX.CrmQuickPanelHtml.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-custom-wrapper" } });
			this._container.appendChild(this._wrapper);
			this._wrapper.innerHTML = this._html;
			this._hasLayout = true;
		}
	};
	BX.CrmQuickPanelHtml.prototype.doClearLayout = function()
	{
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelHtml.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-custom-wrapper" });

	};
	BX.CrmQuickPanelHtml.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelHtml();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelEnumeration) === "undefined")
{
	BX.CrmQuickPanelEnumeration = function()
	{
		BX.CrmQuickPanelEnumeration.superclass.constructor.apply(this);
		this._wrapper = null;
		this._viewWrapper = null;
		this._editWrapper = null;
		this._input = null;

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
	};
	BX.extend(BX.CrmQuickPanelEnumeration, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelEnumeration.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();

		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-enumeration-wrapper" }, true, false);
			this._viewWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-enumeration-view-wrapper" }, true, false);
			this._editWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-enumeration-edit-wrapper" }, true, false);
			if(this._isEditable)
			{
				this._input = BX.create("SELECT", { attrs: { className: "crm-item-table-select" } });
				this._editWrapper.appendChild(this._input);
				this.prepareItemOptions();
			}
		}
	};
	BX.CrmQuickPanelEnumeration.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-enumeration-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._viewWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-enumeration-view-wrapper" } });
			this._wrapper.appendChild(this._viewWrapper);

			this._editWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-enumeration-edit-wrapper" } });
			this._wrapper.appendChild(this._editWrapper);

			this._input = BX.create("SELECT", { attrs: { className: "crm-item-table-select" } });
			this._editWrapper.appendChild(this._input);
			this.prepareItemOptions();

			this._hasLayout = true;
		}

		if(!this.isEditMode())
		{
			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			this._viewWrapper.innerHTML = BX.util.htmlspecialchars(this._model.getDataParam("text"));
		}
		else
		{
			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}
			this._input.selectedIndex = this.getItemIndex(this._model.getDataParam("value")) + 1;
		}
	};
	BX.CrmQuickPanelEnumeration.prototype.doClearLayout = function()
	{
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelEnumeration.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelEnumeration.prototype.saveIfChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;
		if(previous === current)
		{
			return;
		}

		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelEnumeration.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-enumeration-wrapper" });

	};
	BX.CrmQuickPanelEnumeration.prototype.isChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;
		return previous !== current;
	};
	BX.CrmQuickPanelEnumeration.prototype.getItemText = function(val)
	{
		return this._model.getItemText(val);
	};
	BX.CrmQuickPanelEnumeration.prototype.getItemIndex = function(val)
	{
		return this._model.getItemIndex(val);
	};
	BX.CrmQuickPanelEnumeration.prototype.prepareItemOptions = function()
	{
		if(!this._input)
		{
			return;
		}

		this._input.options[0] = new Option(this.getMessage("notSelected"), "");
		var items = this._model.getItems();
		for(var i = 0; i < items.length; i++)
		{
			var item = items[i];
			var id = typeof(item["ID"]) !== "undefined" ? item["ID"] : "";
			var value = typeof(item["VALUE"]) !== "undefined" ? item["VALUE"] : "";
			this._input.options[this._input.options.length] = new Option(value, id);
		}
	};
	BX.CrmQuickPanelEnumeration.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelEnumeration();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelMoney) === "undefined")
{
	BX.CrmQuickPanelMoney = function()
	{
		BX.CrmQuickPanelMoney.superclass.constructor.apply(this);
		this._wrapper = null;
		this._viewWrapper = null;
		this._editWrapper = null;
		this._input = null;
		this._wait = null;
		this._editorCreatedHandler = BX.delegate(this._onEditorCreated, this);
		this._modelChangeHandler = BX.delegate(this._onModelChange, this);

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
	};
	BX.extend(BX.CrmQuickPanelMoney, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelMoney.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();
		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-text-wrapper" }, true, false);
			this._viewWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-text-view-wrapper" }, true, false);
			this._editWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-text-edit-wrapper" }, true, false);
			if(this._isEditable)
			{
				this._input = BX.create("TEXTAREA", {attrs: {className: "crm-lead-header-edit-field"}});
				this._editWrapper.appendChild(this._input);
			}
		}
	};
	BX.CrmQuickPanelMoney.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-text-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._viewWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-text-view-wrapper" } });
			this._wrapper.appendChild(this._viewWrapper);

			this._editWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-text-edit-wrapper" } });
			this._wrapper.appendChild(this._editWrapper);

			this._input = BX.create("TEXTAREA", { attrs: { className: "crm-lead-header-edit-field" } });
			this._editWrapper.appendChild(this._input);

			this._hasLayout = true;
		}

		if(!this.isEditMode())
		{
			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			this._viewWrapper.innerHTML = BX.util.strip_tags(this._model.getFormattedValue(false));
		}
		else
		{
			var pos = BX.pos(this._viewWrapper);
			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}
			this._input.style.height = pos.height + "px";
			this._input.value = this._model.getValue();

		}
	};
	BX.CrmQuickPanelMoney.prototype.doClearLayout = function()
	{
		this._model.unregisterCallback(this._modelChangeHandler);
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelMoney.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelMoney.prototype.saveIfChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value.replace(/[^0-9.,]+/g, "");
		if(previous === current)
		{
			return;
		}

		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelMoney.prototype.isOwnElement = function(element)
	{
		return this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-text-wrapper" });

	};
	BX.CrmQuickPanelMoney.prototype.isChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value.replace(/[^0-9\.]+/g, "");
		return previous !== current;
	};
	BX.CrmQuickPanelMoney.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelMoney();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelResponsible) === "undefined")
{
	BX.CrmQuickPanelResponsible = function()
	{
		BX.CrmQuickPanelResponsible.superclass.constructor.apply(this);
		this._editButton = null;
		this._link = null;
	};
	BX.extend(BX.CrmQuickPanelResponsible, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelResponsible.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();
		this._mode = BX.CrmQuickPanelControl.mode.undifined;
	};
	BX.CrmQuickPanelResponsible.prototype.getMessage = function(name)
	{
		var msgs = BX.CrmQuickPanelResponsible.messages;
		return msgs.hasOwnProperty(name) ? msgs[name] : "";
	};
	BX.CrmQuickPanelResponsible.prototype.layout = function()
	{
		var editable = this._isEditable;

		var wrapper = BX.create("DIV", { attrs: { className: "crm-detail-info-resp-block" } });
		this._container.appendChild(wrapper);

		var header = BX.create("DIV", { attrs: { className: "crm-detail-info-resp-header" } });
		wrapper.appendChild(header);

		header.appendChild(BX.create("SPAN", { attrs: { className: "crm-detail-info-resp-text" }, text: this._item.getCaption() }));
		if(editable)
		{
			this._editButton = BX.create("SPAN", { attrs: { className: "crm-detail-info-resp-edit" }, text: this.getMessage("change") });
			header.appendChild(this._editButton);
		}

		this._link = BX.create(
			"A",
			{
				attrs: { className: "crm-detail-info-resp" },
				props:
				{
					target: "_blank",
					href: this._model.getDataParam("profileUrl")
				}
			}
		);
		wrapper.appendChild(this._link);

		var imgContainer = BX.create("DIV", { attrs: { className: "crm-detail-info-resp-img" } });
		this._link.appendChild(imgContainer);

		var photoUrl = this._model.getDataParam("photoUrl", "");
		if(photoUrl !== "")
		{
			imgContainer.appendChild(BX.create("IMG", { props: { src: photoUrl } }));
		}

		this._link.appendChild(
			BX.create(
				"SPAN",
				{
					attrs: { className: "crm-detail-info-resp-name" },
					text: this._model.getDataParam("name")
				}
			)
		);

		this._link.appendChild(
			BX.create(
				"SPAN",
				{
					attrs: { className: "crm-detail-info-resp-descr" },
					text: this._model.getDataParam("position")
				}
			)
		);

		var serviceUrl =  this._model.getDataParam("serviceUrl", "");
		var userInfoProviderId = this._model.getDataParam("userInfoProviderID", "");
		if(userInfoProviderId !== "")
		{
			BX.CrmUserInfoProvider.createIfNotExists(
				userInfoProviderId,
				{
					serviceUrl: serviceUrl,
					userProfileUrlTemplate: this._model.getDataParam("profileUrlTemplate")
				}
			);
		}

		var editorId = this._model.getDataParam("editorID", "");
		var fieldId = this._model.getDataParam("fieldID");
		if(!editable)
		{
			BX.CrmUserLinkField.create(
				{ container: this._link, userInfoProviderId: userInfoProviderId, editorId: editorId, fieldId: fieldId }
			);
		}
		else
		{
			var userSelectorName = BX.util.getRandomString(16);
			BX.CrmSidebarUserSelector.create(
				userSelectorName,
				this._editButton,
				this._link,
				userSelectorName,
				{ userInfoProviderId: userInfoProviderId, editorId: editorId, fieldId: fieldId, enableLazyLoad: true, serviceUrl: serviceUrl }
			);
		}
	};
	BX.CrmQuickPanelResponsible.prototype.canChangeMode = function()
	{
		return false;
	};
	if(typeof(BX.CrmQuickPanelResponsible.messages) === "undefined")
	{
		BX.CrmQuickPanelResponsible.messages = {};
	}
	BX.CrmQuickPanelResponsible.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelResponsible();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelMultiField) === "undefined")
{
	BX.CrmQuickPanelMultiField = function()
	{
		BX.CrmQuickPanelMultiField.superclass.constructor.apply(this);
		this._id = "";
		this._settings = null;
		this._item = null;
		this._container = null;
		this._openListButton = null;
		this._openListHandler = BX.delegate(this._onOpenListButtonClick, this);

	};
	BX.extend(BX.CrmQuickPanelMultiField, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelMultiField.prototype.layout = function()
	{
		var items = this._model.getDataParam("items", []);
		var type =  this._model.getDataParam("type", "");

		if(items.length === 0)
		{
			return;
		}

		var wrapper = BX.create("SPAN", { attrs: { className: "crm-client-contacts-block-text" } });
		this._container.appendChild(wrapper);

		var firstItem = items[0];
		wrapper.innerHTML += firstItem["value"];

		if(type === "PHONE" && BX.type.isNotEmptyString(firstItem["sipCallHtml"]))
		{
			wrapper.innerHTML += firstItem["sipCallHtml"];
			BX.addClass(wrapper, "crm-client-contacts-block-handset");
		}

		if(items.length > 1)
		{
			BX.addClass(wrapper, "crm-client-contacts-block-text-list");
			this._openListButton = BX.create(
				"SPAN",
				{
					attrs: { className: "crm-client-contacts-block-text-list-icon" }
				}
			);
			wrapper.appendChild(this._openListButton);
			BX.bind(this._openListButton, "click", this._openListHandler);
		}

		BX.CrmQuickPanelMultiField.adjustElement(wrapper);
	};
	BX.CrmQuickPanelMultiField.prototype._onOpenListButtonClick = function(e)
	{
		var items = this._model.getDataParam("items", []);
		var type =  this._model.getDataParam("type", "");

		if(items.length <= 1)
		{
			return;
		}

		var menuItems = [];
		for(var i = 1; i < items.length; i++)
		{
			var item = items[i];
			if(BX.type.isNotEmptyString(item["value"]))
			{
				menuItems.push(item);
			}
		}

		BX.CrmMultiFieldViewer.ensureCreated(
			this._id,
			{
				typeName: type,
				items: menuItems,
				anchor: this._openListButton,
				topmost: true
			}
		).show();
	};
	BX.CrmQuickPanelMultiField.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelMultiField();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmQuickPanelMultiField._wrapper = null;
	BX.CrmQuickPanelMultiField.setWrapper = function(wrapper)
	{
		this._wrapper = wrapper;
		this.adjust();
		BX.bind(window, "resize", BX.delegate(BX.CrmQuickPanelMultiField.onWindowResize, this));
	};
	BX.CrmQuickPanelMultiField.onWindowResize = function(e)
	{
		this.adjust();
	};
	BX.CrmQuickPanelMultiField.adjust = function()
	{
		if(!this._wrapper || !BX.type.isFunction(cssQuery))
		{
			return;
		}

		var maxWidth = BX.CrmQuickPanelMultiField.calculateMaxElementWidth();
		if(maxWidth <= 0)
		{
			return;
		}

		var elements = cssQuery(".crm-client-contacts-block-text", this._wrapper);
		for(var i = 0; i < elements.length; i++)
		{
			elements[i].style.maxWidth =  maxWidth + 'px';
		}
	};
	BX.CrmQuickPanelMultiField.adjustElement = function(element)
	{
		if(!this._wrapper)
		{
			return;
		}

		var maxWidth = BX.CrmQuickPanelMultiField.calculateMaxElementWidth();
		if(maxWidth > 0)
		{
			element.style.maxWidth = maxWidth + 'px';
		}
	};
	BX.CrmQuickPanelMultiField.calculateMaxElementWidth = function()
	{
		return this._wrapper ? Math.ceil((this._wrapper.offsetWidth - 2) / 3 - 200) : 0;
	};
}
if(typeof(BX.CrmQuickPanelClientInfo) === "undefined")
{
	BX.CrmQuickPanelClientInfo = function()
	{
		BX.CrmQuickPanelClientInfo.superclass.constructor.apply(this);
		this._parent = null;
		this._index = -1;
		this._isStub = false;
		this._fieldData = {};
		this._controls = {};

		this._wrapper = null;
	};
	BX.extend(BX.CrmQuickPanelClientInfo, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelClientInfo.prototype.doInitialize = function()
	{
		this._parent = this.getSetting("parent", null);
		this._isStub = this.getSetting("isStub", false);
		this._index = parseInt(this.getSetting("index", -1));

		var phone = this._model.getDataParam("PHONE", null);
		if(phone)
		{
			this._fieldData["phone"] = phone;
		}

		var email = this._model.getDataParam("EMAIL", null);
		if(email)
		{
			this._fieldData["email"] = email;
		}

		if(this._hasLayout)
		{
			var prefix = this._model.getDataParam("PREFIX", "");
			var entityId = parseInt(this._model.getDataParam("ENTITY_ID", 0));
			if(prefix !== "" && entityId > 0)
			{
				this._wrapper = BX(prefix + "_" + entityId.toString());
			}
			else
			{
				this._wrapper = BX.findChildByClassName(this._container, "crm-detail-info-resp-block", true);
			}
		}
	};
	BX.CrmQuickPanelClientInfo.prototype.getMessage = function(name)
	{
		var msgs = BX.CrmQuickPanelClientInfo.messages;
		return msgs.hasOwnProperty(name) ? msgs[name] : "";
	};
	BX.CrmQuickPanelClientInfo.prototype.getPosition = function()
	{
		return BX.pos(this._wrapper);
	};
	BX.CrmQuickPanelClientInfo.prototype.layout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		var wrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-detail-info-resp-block" } });

		var wrapperClassName = this.getSetting("wrapperClassName", "");
		if(wrapperClassName !== "")
		{
			BX.addClass(wrapper, wrapperClassName);
		}

		var wrapperWidth = this.getSetting("wrapperWidth", "");
		if(wrapperWidth !== "")
		{
			wrapper.style.width = wrapperWidth;
		}

		if(this._index < 0)
		{
			this._container.appendChild(wrapper);
		}
		else
		{
			this._container.insertBefore(wrapper, this._container.childNodes[this._index]);
		}

		if(this._isStub)
		{
			wrapper.appendChild(
				BX.create("DIV", { attrs: { className: "crm-detail-info-resp-slide-waiter" } })
			);
			this._hasLayout = true;
			return;
		}

		var entityTypeName = this._model.getDataParam("ENTITY_TYPE_NAME");
		var innerWrapperClassName = "crm-detail-info-resp";
		innerWrapperClassName += entityTypeName === "CONTACT"
			? " crm-detail-info-head-cont" : " crm-detail-info-head-firm";

		var url = this._model.getDataParam("SHOW_URL", "");
		var innerWrapper = url !== ""
			? BX.create("A", { attrs: { className: innerWrapperClassName }, props: { target: "_blank", href: url } })
			: BX.create("SPAN", { attrs: { className: innerWrapperClassName } });

		wrapper.appendChild(innerWrapper);

		var name = this._model.getDataParam("NAME", "");
		var imageUrl = this._model.getDataParam("IMAGE_URL", "");
		var isNotEmpty = name !== "" && url !== "";

		var imageContainer = BX.create("DIV", { attrs: { className: entityTypeName === "COMPANY" && isNotEmpty && imageUrl !== "" ? "crm-lead-header-company-img" : "crm-detail-info-resp-img" } });
		innerWrapper.appendChild(imageContainer);

		if(imageUrl !== "")
		{
			imageContainer.appendChild(BX.create("IMG", { props: { src: imageUrl } }));
		}

		if(isNotEmpty)
		{
			innerWrapper.appendChild(
				BX.create(
					"SPAN",
					{ attrs: { className: "crm-detail-info-resp-name" }, text: this._model.getDataParam("NAME") }
				)
			);

			innerWrapper.appendChild(
				BX.create(
					"SPAN",
					{ attrs: { className: "crm-detail-info-resp-descr" }, text: this._model.getDataParam("DESCRIPTION") }
				)
			);
		}
		else
		{
			if(name === "")
			{
				name = this.getMessage(entityTypeName === "CONTACT" ? "contactNotSelected" : "companyNotSelected");
			}

			innerWrapper.appendChild(BX.create("DIV", { attrs: { className: "crm-detail-info-empty" }, text: name }));
		}

		var control = this.createMultifieldControl("phone", wrapper);
		if(control)
		{
			this._controls["phone"] = control;
			control.layout();
		}

		control = this.createMultifieldControl("email", wrapper);
		if(control)
		{
			this._controls["email"] = control;
			control.layout();
		}

		var counter = this._parent ? this._parent.getControlCouner(this) : null;
		if(counter)
		{
			wrapper.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: "crm-detail-info-resp-slide-counter-container" },
						children:
						[
							BX.create(
								"DIV",
								{
									attrs: { className: "crm-detail-info-resp-slide-counter" },
									text: counter["number"].toString() + " / " + counter["total"].toString()
								}
							)
						]
					}
				)
			);
		}

		this._hasLayout = true;
	};
	BX.CrmQuickPanelClientInfo.prototype.doClearLayout = function()
	{
		if(this._wrapper)
		{
			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;
		}
	};
	BX.CrmQuickPanelClientInfo.prototype.createMultifieldControl = function(typeName, wrapper)
	{
		if(!this._fieldData.hasOwnProperty(typeName))
		{
			return null;
		}

		var fieldData = this._fieldData[typeName];
		var fieldWrapper = BX.create("DIV", { attrs: { className: "crm-detail-info-item" } });
		wrapper.appendChild(fieldWrapper);

		fieldWrapper.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm-detail-info-item-name" },
					text: (BX.type.isNotEmptyString(fieldData["caption"]) ? fieldData["caption"] : typeName) + ":"
				}
			)
		);

		return BX.CrmQuickPanelMultiField.create("",
			{
				item: this._item,
				model: BX.CrmQuickPanelModel.create(typeName, { config: { data: fieldData["data"] } }),
				container: fieldWrapper
			}
		);
	};
	if(typeof(BX.CrmQuickPanelClientInfo.messages) === "undefined")
	{
		BX.CrmQuickPanelClientInfo.messages = {};
	}
	BX.CrmQuickPanelClientInfo.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelClientInfo();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelMultipleClientInfo) === "undefined")
{
	BX.CrmQuickPanelMultipleClientInfo = function()
	{
		BX.CrmQuickPanelMultipleClientInfo.superclass.constructor.apply(this);

		this._index = 0;
		this._count = 0;

		this._controls = null;

		this._wrapper = null;
		this._controlWrapper = null;
		this._navPrevButton = null;
		this._navNextButton = null;

		this._navPrevHandler = BX.delegate(this.onNavPrev, this);
		this._navNextHandler = BX.delegate(this.onNavNext, this);

		this._modelDataLoadHandler = BX.delegate(this.onModelDataLoad, this);
		this._windowResizeHandler = BX.delegate(BX.CrmQuickPanelMultipleClientInfo.onWindowResize, this);
	};
	BX.extend(BX.CrmQuickPanelMultipleClientInfo, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelMultipleClientInfo.prototype.doInitialize = function()
	{
		this._index = this._model.getCurrentChildIndex();
		this._count = this._model.getChildCount();

		this._controls = [];

		if(this._hasLayout)
		{
			this._wrapper = BX.findChildByClassName(this._container, "crm-detail-info-resp-slider-container", true);
			if(!BX.type.isElementNode(this._wrapper))
			{
				throw "CrmQuickPanelMultipleClientInfo: Could not find wrapper.";
			}

			this._controlWrapper = BX.findChildByClassName(this._wrapper, "crm-detail-info-resp-slide-box", true);
			if(!BX.type.isElementNode(this._controlWrapper))
			{
				throw "CrmQuickPanelMultipleClientInfo: Could not find control wrapper.";
			}

			var controlWidth = (100 / this._count).toFixed(6);
			for(var i = 0; i < this._count; i++)
			{
				var control = null;
				var model = this._model.getChild(i);
				if(model !== null)
				{
					control = BX.CrmQuickPanelClientInfo.create("",
						{
							parent: this,
							item: this._item,
							model: model,
							container: this._controlWrapper,
							wrapperClassName: "crm-detail-info-resp-slide",
							wrapperWidth: controlWidth + "%",
							hasLayout: true,
							index: i,
							isStub: false
						}
					);
				}
				else
				{
					control = BX.CrmQuickPanelClientInfo.create("",
						{
							parent: this,
							item: this._item,
							model: BX.CrmQuickPanelClientModel.create(this._id + "_" + i.toString(), { config: {} }),
							container: this._controlWrapper,
							wrapperClassName: "crm-detail-info-resp-slide",
							wrapperWidth: controlWidth + "%",
							hasLayout: false,
							index: i,
							isStub: true
						}
					);
					control.layout();
				}
				this._controls.push(control);
			}

			this._navPrevButton = BX.findChildByClassName(this._container, "crm-detail-info-resp-slider-arrow-left", true);
			if(this._navPrevButton)
			{
				BX.bind(this._navPrevButton, "click", this._navPrevHandler);
			}

			this._navNextButton = BX.findChildByClassName(this._container, "crm-detail-info-resp-slider-arrow-right", true);
			if(this._navNextButton)
			{
				BX.bind(this._navNextButton, "click", this._navNextHandler);
			}

			this.adjust();
			BX.bind(window, "resize", this._windowResizeHandler);
		}
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.layout = function()
	{
		var wrapper = this._wrapper = BX.create("DIV",
			{ attrs: { className: "crm-detail-info-resp-slider-container" } }
		);
		this._container.appendChild(wrapper);

		var innerWrapper = BX.create("DIV",
			{ attrs: { className: "crm-detail-info-resp-slider-container-overflow" } }
		);
		wrapper.appendChild(innerWrapper);

		this._controlWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-detail-info-resp-slide-box"},
				style: { width: (100 * this._count).toString() + "%" }
			}
		);
		innerWrapper.appendChild(this._controlWrapper);

		this.initializeControls();

		this._navPrevButton = BX.create("DIV",
			{ attrs: { className: "crm-detail-info-resp-slider-arrow crm-detail-info-resp-slider-arrow-left" } }
		);
		wrapper.appendChild(this._navPrevButton);
		BX.bind(this._navPrevButton, "click", this._navPrevHandler);

		this._navNextButton = BX.create("DIV",
			{ attrs: { className: "crm-detail-info-resp-slider-arrow crm-detail-info-resp-slider-arrow-right" } }
		);
		wrapper.appendChild(this._navNextButton);
		BX.bind(this._navNextButton, "click", this._navNextHandler);

		this.adjust();
		BX.bind(window, "resize", this._windowResizeHandler);
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.doClearLayout = function()
	{
		if(this._navPrevButton)
		{
			BX.unbind(this._navPrevButton, "click", this._navPrevHandler);
			this._navPrevButton = null;
		}

		if(this._navNextButton)
		{
			BX.unbind(this._navNextButton, "click", this._navNextHandler);
			this._navNextButton = null;
		}

		if(this._wrapper)
		{
			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;
			this._controlWrapper = null;
		}

		BX.unbind(window, "resize", this._windowResizeHandler);
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.getControlCouner = function(control)
	{
		for(var i = 0; i < this._controls.length; i++)
		{
			if(this._controls[i] === control)
			{
				return { number: i + 1, total: this._count };
			}
		}

		return null;
	};

	BX.CrmQuickPanelMultipleClientInfo.prototype.initializeControls = function()
	{
		var width = (100 / this._count).toFixed(6);
		for(var i = 0; i < this._count; i++)
		{
			var settings =
				{
					parent: this,
					item: this._item,
					container: this._controlWrapper,
					wrapperClassName: "crm-detail-info-resp-slide",
					wrapperWidth: width + "%"
				};

			var model = this._model.getChild(i);
			var isStub = (model === null);
			if(isStub)
			{
				model = BX.CrmQuickPanelClientModel.create(this._id + "_" + i.toString(), { config: {} });
			}

			settings["model"] = model;
			settings["isStub"] = isStub;
			settings["hasLayout"] = !isStub && this._hasLayout;

			var control = BX.CrmQuickPanelClientInfo.create("", settings);
			this._controls.push(control);
			control.layout();
		}
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.resetControls = function()
	{
		for(var i = 0; i < this._controls.length; i++)
		{
			this._controls[i].clearLayout();
		}

		this._controls = [];
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.getPosition = function()
	{
		return (this._controls.length > this._index
			? this._controls[this._index].getPosition()
			: { top: 0, right: 0, bottom: 0, left: 0, width: 0, height: 0 });
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.adjust = function()
	{
		//Rewind index
		var maxIndex = this._count - 1;
		if(this._index < 0)
		{
			this._index = maxIndex;
		}
		else if(this._index > maxIndex)
		{
			this._index = 0;
		}

		//Set up by index
		this._controlWrapper.style.left = this._index > 0 ? ((-100 * this._index).toString() + "%") : "0";

		var pos = this.getPosition();
		this._controlWrapper.style.height = pos.height + "px";
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.getIndex = function()
	{
		return this._index;
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.setIndex = function(index)
	{
		if(this._index === index)
		{
			return;
		}

		this._model.setCurrentChildIndex(index);
		this._index = this._model.getCurrentChildIndex();

		this.adjust();

		if(!this._model.areChildrenLoaded())
		{
			this._model.loadChildren(this._modelDataLoadHandler);
		}
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.onNavPrev = function(e)
	{
		this.setIndex(this._index - 1);
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.onNavNext = function(e)
	{
		this.setIndex(this._index + 1);
	};
	BX.CrmQuickPanelMultipleClientInfo.prototype.onModelDataLoad = function(sender, params)
	{
		this.resetControls();
		this._count = this._model.getChildCount();
		for(var i = 0; i < this._count; i++)
		{
			var model = this._model.getChild(i);
			if(model === null)
			{
				throw "CrmQuickPanelMultipleClientInfo: Could not found child.";
			}

			var controlWidth = (100 / this._count).toFixed(6);
			var control = BX.CrmQuickPanelClientInfo.create("",
				{
					parent: this,
					item: this._item,
					model: model,
					container: this._controlWrapper,
					wrapperClassName: "crm-detail-info-resp-slide",
					wrapperWidth: controlWidth + "%",
					hasLayout: false,
					isStub: false
				}
			);
			this._controls.push(control);
			control.layout();
		}
		this.adjust();
	};
	BX.CrmQuickPanelMultipleClientInfo.onWindowResize = function(e)
	{
		var pos = this.getPosition();
		this._controlWrapper.style.height = pos.height + "px";
	};
	BX.CrmQuickPanelMultipleClientInfo.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelMultipleClientInfo();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelCompositeClientInfo) === "undefined")
{
	BX.CrmQuickPanelCompositeClientInfo = function()
	{
		BX.CrmQuickPanelCompositeClientInfo.superclass.constructor.apply(this);

		this._primaryControl = null;
		this._secondaryControl = null;
		this._wrapper = null;
	};
	BX.extend(BX.CrmQuickPanelCompositeClientInfo, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelCompositeClientInfo.prototype.doInitialize = function()
	{
		var primaryModel = this._model.getPrimaryModel();
		if(primaryModel instanceof BX.CrmQuickPanelClientModel)
		{
			this._primaryControl = BX.CrmQuickPanelClientInfo.create("",
				{
					item: this._item,
					container: this._container,
					model: primaryModel,
					hasLayout: this._hasLayout
				}
			);
		}
		else
		{
			throw "CrmQuickPanelCompositeClientInfo: Primary model type is not supported.";
		}

		var secondaryModel = this._model.getSecondaryModel();
		if(secondaryModel instanceof BX.CrmQuickPanelMultipleClientModel)
		{
			this._secondaryControl = BX.CrmQuickPanelMultipleClientInfo.create("",
				{
					item: this._item,
					container: this._container,
					model: secondaryModel,
					hasLayout: this._hasLayout
				}
			);
		}
		else if(secondaryModel instanceof BX.CrmQuickPanelClientModel)
		{
			this._secondaryControl = BX.CrmQuickPanelClientInfo.create("",
				{
					item: this._item,
					container: this._container,
					model: secondaryModel,
					hasLayout: this._hasLayout
				}
			);
		}
		else
		{
			throw "CrmQuickPanelCompositeClientInfo: Secondary model type is not supported.";
		}
	};
	BX.CrmQuickPanelCompositeClientInfo.prototype.getPosition = function()
	{
		return BX.pos(this._wrapper);
	};
	BX.CrmQuickPanelCompositeClientInfo.prototype.layout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		this._primaryControl.layout();
		this._secondaryControl.layout();

		this._hasLayout = true;
	};
	BX.CrmQuickPanelCompositeClientInfo.prototype.doClearLayout = function()
	{
		this._primaryControl.clearLayout();
		this._secondaryControl.clearLayout();
	};
	BX.CrmQuickPanelCompositeClientInfo.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelCompositeClientInfo();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelVisualEditor) === "undefined")
{
	BX.CrmQuickPanelVisualEditor = function()
	{
		BX.CrmQuickPanelVisualEditor.superclass.constructor.apply(this);
		this._wrapper = null;
		this._viewWrapper = null;
		this._editWrapper = null;
		this._isLoaded = false;
		this._editorName = "";
		this._editor = null;
		this._serviceUrl = "";
		this._editorHtmlLoadHandler = BX.delegate(this._onEditorHtmlLoaded, this);
		this._editorScriptLoadHandler = BX.delegate(this._onEditorScriptLoaded, this);
		this._editorContentSaveHandler = BX.delegate(this._onEditorContentSave, this);
		this._timeoutHandler = BX.delegate(this._onTimeout, this);

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
	};
	BX.extend(BX.CrmQuickPanelVisualEditor, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelVisualEditor.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();
		this._serviceUrl = this._model.getDataParam("serviceUrl", "");
		if(this._serviceUrl === "")
		{
			throw "CrmQuickPanelVisualEditor: Could no find serviceUrl.";
		}

		if(this._hasLayout)
		{
			this._wrapper = BX.findChild(this._container, { tagName: "DIV", className: "crm-lead-header-lhe-wrapper" }, true, false);
			this._viewWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-lhe-view-wrapper" }, true, false);
			this._editWrapper = BX.findChild(this._wrapper, { tagName: "DIV", className: "crm-lead-header-lhe-edit-wrapper" }, true, false);
		}
	};
	BX.CrmQuickPanelVisualEditor.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelVisualEditor.prototype.saveIfChanged = function()
	{
		if(!this._editor)
		{
			return;
		}

		this._editor.SaveContent();
		var previous = this._model.getDataParam("html");
		var current = this._editor.GetContent();
		if(previous === current)
		{
			return;
		}

		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelVisualEditor.prototype.isChanged = function()
	{
		this._editor.SaveContent();
		var previousHtml = this._model.getDataParam("html", "");
		var currentHtml = this._editor.GetContent();
		return previousHtml !== currentHtml;
	};
	BX.CrmQuickPanelVisualEditor.prototype.layout = function()
	{
		if(!this._hasLayout)
		{
			this._wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-lhe-wrapper" } });
			this._container.appendChild(this._wrapper);

			this._viewWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-lhe-view-wrapper" } });
			this._wrapper.appendChild(this._viewWrapper);

			this._editWrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-lhe-edit-wrapper" } });
			this._wrapper.appendChild(this._editWrapper);
			this._hasLayout = true;
		}

		if(!this.isEditMode())
		{
			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			this._viewWrapper.innerHTML = this._model.getDataParam("html", "");
		}
		else
		{
			this.initializeEditor();
		}
	};
	BX.CrmQuickPanelVisualEditor.prototype.doClearLayout = function()
	{
		BX.cleanNode(this._wrapper);
	};
	BX.CrmQuickPanelVisualEditor.prototype.initializeEditor = function()
	{
		if(this._isLoaded)
		{
			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}

			this._editor.ReInit(this._model.getDataParam("html", ""));
			window.setTimeout(this._timeoutHandler, 10000);
			return;
		}

		this._editorName = (this._item.getPrefix() + "_" + this._item.getId() + "_" + BX.util.getRandomString(4)).toUpperCase();

		BX.addCustomEvent("onAjaxSuccessFinish", this._editorScriptLoadHandler);
		BX.showWait(this._wrapper);
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "html",
				data:
				{
					"MODE": "GET_VISUAL_EDITOR",
					"EDITOR_ID": this._editorName,
					"EDITOR_NAME": this._editorName
				},
				onsuccess: this._editorHtmlLoadHandler
			}
		);
	};
	BX.CrmQuickPanelVisualEditor.prototype.isOwnElement = function(element)
	{
		if(!element)
		{
			return false;
		}

		if(this._wrapper !== null && this._wrapper === BX.findParent(element, { className: "crm-lead-header-lhe-wrapper" }))
		{
			return true;
		}

		//Skip popup window and overlay click
		return (BX.hasClass(element, "bx-core-window")
			|| BX.hasClass(element, "bx-core-dialog-overlay")
			|| !!(BX.findParent(element, { className: /(bx-core-window)|(bx-core-dialog-overlay)/ }))
		);
	};
	BX.CrmQuickPanelVisualEditor.prototype._onEditorHtmlLoaded = function(data)
	{
		BX.closeWait(this._wrapper);

		this._viewWrapper.style.display = "none";
		if(this._editWrapper.style.display === "none")
		{
			this._editWrapper.style.display = "";
		}
		this._editWrapper.appendChild(BX.create("DIV", { html: data  }));
		this._isLoaded = true;
	};
	BX.CrmQuickPanelVisualEditor.prototype._onEditorScriptLoaded = function(config)
	{
		if(config["url"] !== this._serviceUrl)
		{
			return;
		}

		BX.removeCustomEvent("onAjaxSuccessFinish", this._editorScriptLoadHandler);
		this.setupEditor();
	};
	BX.CrmQuickPanelVisualEditor.prototype.setupEditor = function()
	{
		if(typeof(window.JCLightHTMLEditor) ===  "undefined"
			|| typeof(window.JCLightHTMLEditor.items[this._editorName]) === "undefined")
		{
			window.setTimeout(BX.delegate(this.setupEditor, this), 500);
			return;
		}

		this._editor = window.JCLightHTMLEditor.items[this._editorName];
		this._editor.ReInit(this._model.getDataParam("html", ""));
		//BX.addCustomEvent(this._editor, "OnSaveContent", this._editorContentSaveHandler);
		window.setTimeout(this._timeoutHandler, 10000);
	};
	BX.CrmQuickPanelVisualEditor.prototype._onEditorContentSave = function()
	{
		if(this.isEditMode())
		{
			this.toggleMode();
		}
		BX.removeCustomEvent(this._editor, "OnSaveContent", this._editorContentSaveHandler);
	};
	BX.CrmQuickPanelVisualEditor.prototype._onTimeout = function()
	{
		if(!this._hasLayout || !this.isEditMode())
		{
			return;
		}

		this.saveIfChanged();
		window.setTimeout(this._timeoutHandler, 10000);
	};
	BX.CrmQuickPanelVisualEditor.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelVisualEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelHeaderText) === "undefined")
{
	BX.CrmQuickPanelHeaderText = function()
	{
		BX.CrmQuickPanelHeaderText.superclass.constructor.apply(this);
		this._viewWrapper = null;
		this._editWrapper = null;
		this._input = null;

		this._enableModelSubscription = true;
		this._enableDocumentUnloadSubscription = true;
		this._keyDownHandler = BX.delegate(this._onKeyDown, this);
	};
	BX.extend(BX.CrmQuickPanelHeaderText, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelHeaderText.prototype.doInitialize = function()
	{
		this._isEditable = this._item.isEditable();

		this._viewWrapper = BX.findChild(this._container, { tagName: "SPAN",  className: "crm-lead-header-title-text" }, true, false);
		this._editWrapper = BX.findChild(this._container, { tagName: "SPAN",  className: "crm-lead-header-title-edit-wrapper" }, true, false);
		this._input = BX.findChild(this._container, { tagName: "INPUT", className: "crm-header-lead-inp" }, true, false);

		if(this._isEditable)
		{
			this._input = BX.create("INPUT", { props: { type: "text" }, attrs: { className: "crm-header-lead-inp" } });
			this._editWrapper.appendChild(this._input);
		}
	};
	BX.CrmQuickPanelHeaderText.prototype.layout = function()
	{
		var text = this._model.getValue();
		if(!this.isEditMode())
		{
			this._editWrapper.style.display = "none";
			if(this._viewWrapper.style.display === "none")
			{
				this._viewWrapper.style.display = "";
			}
			this._viewWrapper.innerHTML = BX.util.htmlspecialchars(text);

			BX.removeClass(this._container, "crm-lead-header-title-editable");
			BX.unbind(this._input, "keydown", this._keyDownHandler);
		}
		else
		{
			this._viewWrapper.style.display = "none";
			if(this._editWrapper.style.display === "none")
			{
				this._editWrapper.style.display = "";
			}
			this._input.value = text;
			this._input.focus();
			BX.addClass(this._container, "crm-lead-header-title-editable");
			BX.bind(this._input, "keydown", this._keyDownHandler);
		}
	};
	BX.CrmQuickPanelHeaderText.prototype.save = function()
	{
		this.saveIfChanged();
	};
	BX.CrmQuickPanelHeaderText.prototype.saveIfChanged = function()
	{
		var previous = this._model.getValue();
		var current = this._input.value;

		if(previous === current)
		{
			return;
		}

		this._model.setValue(current, true, this);
	};
	BX.CrmQuickPanelHeaderText.prototype.isOwnElement = function(element)
	{
		return this._container === BX.findParent(element, { className: "crm-lead-header-title" });

	};
	BX.CrmQuickPanelHeaderText.prototype.isChanged = function()
	{
		return this._input.value !== this._model.getValue();
	};
	BX.CrmQuickPanelHeaderText.prototype._onKeyDown = function(e)
	{
		if(!this.isEditMode())
		{
			return;
		}

		e = e || window.event;
		if(e.keyCode === 13)
		{
			this.save();
			this.switchMode(BX.CrmQuickPanelControl.mode.view);
		}
		else if(e.keyCode === 27)
		{
			this.switchMode(BX.CrmQuickPanelControl.mode.view);
		}
	};
	BX.CrmQuickPanelHeaderText.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelHeaderText();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelHeaderMoney) === "undefined")
{
	BX.CrmQuickPanelHeaderMoney = function()
	{
		BX.CrmQuickPanelHeaderMoney.superclass.constructor.apply(this);
		this._viewWrapper = null;
		this._enableModelSubscription = true;
	};
	BX.extend(BX.CrmQuickPanelHeaderMoney, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelHeaderMoney.prototype.doInitialize = function()
	{
		this._isEditable = false;
		this._viewWrapper = BX.findChild(this._container, { className: "crm-lead-header-status-sum-num" }, true, false);
	};
	BX.CrmQuickPanelHeaderMoney.prototype.layout = function()
	{
		this._viewWrapper.innerHTML = BX.util.strip_tags(this._model.getFormattedValue(true));
	};
	BX.CrmQuickPanelHeaderMoney.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelHeaderMoney();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmQuickPanelAddress) === "undefined")
{
	BX.CrmQuickPanelAddress = function()
	{
		BX.CrmQuickPanelAddress.superclass.constructor.apply(this);
		this._id = "";
		this._settings = null;
		this._item = null;
		this._container = null;
		this._openPopupButton = null;
		this._openPopupHandler = BX.delegate(this._onOpenPopupButtonClick, this);
		this._isPopupShown = false;
		this._popup = null;
	};
	BX.extend(BX.CrmQuickPanelAddress, BX.CrmQuickPanelControl);
	BX.CrmQuickPanelAddress.prototype.doInitialize = function()
	{
		var lines = this._model.getDataParam("lines", []);
		if(lines.length === 0)
		{
			return;
		}

		this._openPopupButton = BX.findChild(this._container, { tagName: "SPAN", className: "crm-client-contacts-block-text-list-icon" }, true, false);
		if(this._openPopupButton)
		{
			BX.bind(this._openPopupButton, "click", this._openPopupHandler);
		}
	};
	BX.CrmQuickPanelAddress.prototype.layout = function()
	{
		var lines = this._model.getDataParam("lines", []);
		if(lines.length === 0)
		{
			return;
		}

		var wrapper = null;
		if(this._item.getSection().getId() === "bottom")
		{
			wrapper = BX.create("DIV", { attrs: { className: "crm-lead-header-lhe-wrapper" } });
			this._container.appendChild(wrapper);

			wrapper.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: "crm-lead-header-lhe-view-wrapper" },
						html: lines.join(", ")
					}
				)
			);
		}
		else
		{
			wrapper = BX.create("SPAN", { attrs: { className: "crm-client-contacts-block-text" } });
			this._container.appendChild(wrapper);
			wrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-client-contacts-block-address" },
						text: lines[0]
					}
				)
			);

			if(lines.length > 1)
			{
				BX.addClass(wrapper, "crm-client-contacts-block-text-list");
				this._openPopupButton = BX.create(
					"SPAN",
					{
						attrs: { className: "crm-client-contacts-block-text-list-icon" }
					}
				);
				wrapper.appendChild(this._openPopupButton);
				BX.bind(this._openPopupButton, "click", this._openPopupHandler);
			}
			BX.CrmQuickPanelAddress.adjustElement(wrapper);
		}
	};
	BX.CrmQuickPanelAddress.prototype._onOpenPopupButtonClick = function(e)
	{
		var lines = this._model.getDataParam("lines", []);
		if(lines.length <= 1)
		{
			return;
		}

		if(this._isPopupShown)
		{
			return;
		}

		var tab = BX.create('TABLE');
		tab.className = "crm-lead-address-popup-table";
		tab.cellSpacing = '0';
		tab.cellPadding = '0';
		tab.border = '0';
		tab.style.display = "block";

		for(var i = 0; i < lines.length; i++)
		{
			var r = tab.insertRow(-1);
			var c = r.insertCell(-1);
			c.className = "crm-lead-address-popup-text";
			c.innerHTML = lines[i];
		}

		this._popup = new BX.PopupWindow(
			this._id,
			this._openPopupButton,
			{
				autoHide: true,
				draggable: false,
				offsetLeft: 10,
				offsetTop: 0,
				angle : { offset : 0 },
				bindOptions: { forceBindPosition: true },
				closeByEsc: true,
				zIndex: -10,
				events: { onPopupClose: BX.delegate(this._onPopupClose, this) },
				content: tab
			}
		);

		this._popup.show();
		this._isPopupShown = true;
	};
	BX.CrmQuickPanelAddress.prototype._onPopupClose = function(e)
	{
		if(this._popup)
		{
			this._popup.destroy();
			this._popup = null;
			this._isPopupShown = false;
		}
	};
	BX.CrmQuickPanelAddress.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelAddress();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmQuickPanelAddress._wrapper = null;
	BX.CrmQuickPanelAddress.setWrapper = function(wrapper)
	{
		this._wrapper = wrapper;
		this.adjust();
		BX.bind(window, "resize", BX.delegate(BX.CrmQuickPanelAddress.onWindowResize, this));
	};
	BX.CrmQuickPanelAddress.onWindowResize = function(e)
	{
		this.adjust();
	};
	BX.CrmQuickPanelAddress.adjust = function()
	{
		if(!this._wrapper || !BX.type.isFunction(cssQuery))
		{
			return;
		}

		var maxWidth = BX.CrmQuickPanelAddress.calculateMaxElementWidth();
		if(maxWidth <= 0)
		{
			return;
		}

		var elements = cssQuery(".crm-client-contacts-block-text", this._wrapper);
		for(var i = 0; i < elements.length; i++)
		{
			elements[i].style.maxWidth =  maxWidth + 'px';
		}
	};
	BX.CrmQuickPanelAddress.adjustElement = function(element)
	{
		if(!this._wrapper)
		{
			return;
		}

		var maxWidth = BX.CrmQuickPanelAddress.calculateMaxElementWidth();
		if(maxWidth > 0)
		{
			element.style.maxWidth = maxWidth + 'px';
		}
	};
	BX.CrmQuickPanelAddress.calculateMaxElementWidth = function()
	{
		return this._wrapper ? Math.ceil((this._wrapper.offsetWidth - 2) / 3 - 200) : 0;
	};
}
//BX.CrmQuickPanelModel
if(typeof(BX.CrmQuickPanelModel) === "undefined")
{
	BX.CrmQuickPanelModel = function()
	{
		this._id = "";
		this._settings = {};
		this._config = null;
		this._data = null;
		this._callbacks = [];
		this._instantEditor = null;
	};
	BX.CrmQuickPanelModel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._config = this.getSetting("config");
			if(!this._config)
			{
				this._config = {};
			}

			this._data = this.getConfigParam("data");
			if(!this._data)
			{
				this._data = {};
			}
			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getType: function()
		{
			return this.getConfigParam("type", "text");
		},
		getCaption: function()
		{
			return this.getConfigParam("caption", this._id);
		},
		getMessage: function(name)
		{
			var m = BX.CrmQuickPanelModel.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isCaptionEnabled: function()
		{
			return this.getConfigParam("enableCaption", true);
		},
		isEditable: function()
		{
			return this.getConfigParam("editable", false);
		},
		registerCallback: function(callback)
		{
			if(!BX.type.isFunction(callback))
			{
				return;
			}

			for(var i = 0; i < this._callbacks.length; i++)
			{
				if(this._callbacks[i] === callback)
				{
					return;
				}
			}
			this._callbacks.push(callback);
		},
		unregisterCallback: function(callback)
		{
			if(!BX.type.isFunction(callback))
			{
				return;
			}

			for(var i = 0; i < this._callbacks.length; i++)
			{
				if(this._callbacks[i] === callback)
				{
					this._callbacks.splice(i, 1);
					return;
				}
			}
		},
		notify: function(params)
		{
			for(var i = 0; i < this._callbacks.length; i++)
			{
				this._callbacks[i](this, params);
			}
		},
		getConfigParam: function(name, defaultval)
		{
			return this._config.hasOwnProperty(name) ? this._config[name] : defaultval;
		},
		setConfigParam: function(name, val)
		{
			this._config[name] = val;
		},
		getData: function()
		{
			return this._data;
		},
		getDataParam: function(name, defaultval)
		{
			return this._data.hasOwnProperty(name) ? this._data[name] : defaultval;
		},
		setDataParam: function(name, val)
		{
			this._data[name] = val;
		},
		getInstantEditor: function()
		{
			return this._instantEditor;
		},
		setInstantEditor: function(instantEditor)
		{
			this._instantEditor = instantEditor;
		},
		getValue: function()
		{
			throw "The 'getValue' must be implemented.";
		},
		setValue: function(value, save, source)
		{
			throw "The 'setValue' must be implemented.";
		},
		saveFieldValue: function()
		{
			var editor = this.getInstantEditor();
			if(editor)
			{
				editor.saveFieldValue(this._id, this.getValue());
			}
		},
		processEditorFieldValueSave: function(name, value)
		{
		}
	};
	BX.CrmQuickPanelModel.items = {};
	BX.CrmQuickPanelModel.getItem = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	if(typeof(BX.CrmQuickPanelModel.messages) === "undefined")
	{
		BX.CrmQuickPanelModel.messages = {};
	}
	BX.CrmQuickPanelModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelTextModel) === "undefined")
{
	BX.CrmQuickPanelTextModel = function()
	{
		BX.CrmQuickPanelTextModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuickPanelTextModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelTextModel.prototype.getValue = function()
	{
		return this.getDataParam("text", "");
	};
	BX.CrmQuickPanelTextModel.prototype.setValue = function(value, save, source)
	{
		this.setDataParam("text", value);

		if(!!save)
		{
			this.saveFieldValue();
		}
		this.notify({ source: source });
	};
	BX.CrmQuickPanelTextModel.prototype.processEditorFieldValueSave = function(name, value)
	{
		if(name === this._id)
		{
			if(this.getDataParam("text", "") == value)
			{
				return;
			}

			this.setDataParam("text", value);
			this.notify({ source: null });
		}
	};
	BX.CrmQuickPanelTextModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelTextModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelBooleanModel) === "undefined")
{
	BX.CrmQuickPanelBooleanModel = function()
	{
		BX.CrmQuickPanelBooleanModel.superclass.constructor.apply(this);
		this._baseType = "";
	};
	BX.extend(BX.CrmQuickPanelBooleanModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelBooleanModel.prototype.doInitialize = function()
	{
		this._baseType = this.getDataParam("baseType");
		if(this._baseType === "char")
		{
			this.setDataParam("value", this.getDataParam("value", "N") === "Y" ? "Y" : "N");
		}
		else
		{
			this.setDataParam("value", parseInt(this.getDataParam("value", 0)) > 0 ? 1 : 0);
		}
	};
	BX.CrmQuickPanelBooleanModel.prototype.getBaseType = function()
	{
		return this._baseType;
	};
	BX.CrmQuickPanelBooleanModel.prototype.getValue = function()
	{
		return this.getDataParam("value", "");
	};
	BX.CrmQuickPanelBooleanModel.prototype.setValue = function(value, save, source)
	{
		if(this._baseType === "char")
		{
			value =  value ? "Y" : "N";
		}
		else
		{
			value =  value ? 1 : 0;
		}

		this.setDataParam("value", value);

		if(!!save)
		{
			this.saveFieldValue();
		}
		this.notify({ source: source });
	};
	BX.CrmQuickPanelBooleanModel.prototype.processEditorFieldValueSave = function(name, value)
	{
		if(name === this._id)
		{
			if(this.getDataParam("value", "") == value)
			{
				return;
			}

			this.setDataParam("value", value);
			this.notify({ source: null });
		}
	};
	BX.CrmQuickPanelBooleanModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelBooleanModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelEnumerationModel) === "undefined")
{
	BX.CrmQuickPanelEnumerationModel = function()
	{
		BX.CrmQuickPanelEnumerationModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuickPanelEnumerationModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelEnumerationModel.prototype.doInitialize = function()
	{
		this._data["items"] = BX.type.isArray(this._data["items"]) ? this._data["items"] : [];
		this._data["value"] = BX.type.isNotEmptyString(this._data["value"]) ? this._data["value"] : "";
		this._data["text"] = this.getItemText(this._data["value"]);
	};
	BX.CrmQuickPanelEnumerationModel.prototype.getValue = function()
	{
		return this.getDataParam("value", "");
	};
	BX.CrmQuickPanelEnumerationModel.prototype.setValue = function(value, save, source)
	{
		this.setDataParam("value", value);
		this.setDataParam("text", this.getItemText(value));

		if(!!save)
		{
			this.saveFieldValue();
		}
		this.notify({ source: source });
	};
	BX.CrmQuickPanelEnumerationModel.prototype.getItemIndex = function(val)
	{
		if(val === "")
		{
			return -1;
		}

		var items = this._data["items"];
		for(var i = 0; i < items.length; i++)
		{
			var item = items[i];
			var id = typeof(item["ID"]) !== "undefined" ? item["ID"] : "";
			if(id === val)
			{
				return i;
			}

		}
		return -1;
	};
	BX.CrmQuickPanelEnumerationModel.prototype.getItemText = function(val)
	{
		if(val === "")
		{
			return this.getMessage("notSelected");
		}

		var items = this._data["items"];
		for(var i = 0; i < items.length; i++)
		{
			var item = items[i];
			var id = typeof(item["ID"]) !== "undefined" ? item["ID"] : "";
			if(id === val)
			{
				return typeof(item["VALUE"]) !== "undefined" ? item["VALUE"] : "";
			}

		}

		return this.getMessage("notSelected");
	};
	BX.CrmQuickPanelEnumerationModel.prototype.getItems = function()
	{
		return this._data["items"];
	};
	BX.CrmQuickPanelEnumerationModel.prototype.processEditorFieldValueSave = function(name, value)
	{
		if(name === this._id)
		{
			if(this.getDataParam("value", "") == value)
			{
				return;
			}

			this.setDataParam("value", value);
			this.setDataParam("text", this.getItemText(value));
			this.notify({ source: null });
		}
	};
	BX.CrmQuickPanelEnumerationModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelEnumerationModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelMoneyModel) === "undefined")
{
	BX.CrmQuickPanelMoneyModel = function()
	{
		BX.CrmQuickPanelMoneyModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuickPanelMoneyModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelMoneyModel.prototype.doInitialize = function()
	{
		this._data["value"] = BX.type.isNotEmptyString(this._data["value"]) ? this._data["value"] : "0.00";
		this._data["text"] = BX.type.isNotEmptyString(this._data["text"]) ? this._data["text"] : this._data["value"];
		this._data["currencyId"] = BX.type.isNotEmptyString(this._data["currencyId"]) ? this._data["currencyId"] : "";
		this._data["currencyFieldName"] = BX.type.isNotEmptyString(this._data["currencyFieldName"]) ? this._data["currencyFieldName"] : "CURRENCY_ID";

		if(!BX.type.isNotEmptyString(this._data["serviceUrl"]))
		{
			throw "CrmQuickPanelMoneyModel: Could no find serviceUrl.";
		}
	};
	BX.CrmQuickPanelMoneyModel.prototype.getFormattedValue = function(enableCurrency)
	{
		return this.getDataParam(
			!!enableCurrency ? "formatted_sum_with_currency" : "formatted_sum",
			""
		);
	};
	BX.CrmQuickPanelMoneyModel.prototype.getValue = function()
	{
		return this.getDataParam("value", "");
	};
	BX.CrmQuickPanelMoneyModel.prototype.setValue = function(value, save, source)
	{
		this.setDataParam("value", value);
		this.setDataParam("text", value);
		this.setDataParam("formatted_sum", value);
		this.setDataParam("formatted_sum_with_currency", value);

		if(!!save)
		{
			this.saveFieldValue();
		}
		//this.notify({ source: source });
		this.startMoneyFormatRequest();
	};
	BX.CrmQuickPanelMoneyModel.prototype.startMoneyFormatRequest = function()
	{
		BX.ajax(
			{
				url: this._data["serviceUrl"],
				method: "POST",
				dataType: "json",
				data:
				{
					"MODE": "GET_FORMATTED_SUM",
					"CURRENCY_ID": this._data["currencyId"],
					"SUM": this._data["value"]
				},
				onsuccess: BX.delegate(this.onMoneyFormatRequestSuccess, this)
			}
		);
	};
	BX.CrmQuickPanelMoneyModel.prototype.onMoneyFormatRequestSuccess = function(data)
	{
		this._data["formatted_sum"] = this._data["text"] = BX.type.isNotEmptyString(data["FORMATTED_SUM"])
			? data["FORMATTED_SUM"] : "";

		this._data["formatted_sum_with_currency"] = BX.type.isNotEmptyString(data["FORMATTED_SUM_WITH_CURRENCY"])
			? data["FORMATTED_SUM_WITH_CURRENCY"] : "";

		this.notify({ source: null });
	};
	BX.CrmQuickPanelMoneyModel.prototype.processEditorFieldValueSave = function(name, value)
	{
		if(name === this._id)
		{
			if(this.getDataParam("value", "") == value)
			{
				return;
			}

			this.setDataParam("value", value);
			this.setDataParam("text", value);
			this.setDataParam("formatted_sum", value);
			this.setDataParam("formatted_sum_with_currency", value);

			//this.notify({ source: null });
			this.startMoneyFormatRequest();
		}
		else if(name === this._data["currencyFieldName"])
		{
			if(this._data["currencyId"] == value)
			{
				return;
			}

			this._data["currencyId"] = value;
			//this.notify({ source: null });
			this.startMoneyFormatRequest();
		}
	};
	BX.CrmQuickPanelMoneyModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelMoneyModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelHtmlModel) === "undefined")
{
	BX.CrmQuickPanelHtmlModel = function()
	{
		BX.CrmQuickPanelHtmlModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuickPanelHtmlModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelHtmlModel.prototype.doInitialize = function()
	{
		this._data["html"] = BX.type.isNotEmptyString(this._data["html"]) ? this._data["html"] : "";
	};
	BX.CrmQuickPanelHtmlModel.prototype.getValue = function()
	{
		return this.getDataParam("html", "");
	};
	BX.CrmQuickPanelHtmlModel.prototype.setValue = function(value, save, source)
	{
		this.setDataParam("html", value);

		if(!!save)
		{
			this.saveFieldValue();
		}
		this.notify({ source: source });
	};
	BX.CrmQuickPanelHtmlModel.prototype.processEditorFieldValueSave = function(name, value)
	{
		if(name === this._id)
		{
			if(this.getDataParam("html", "") == value)
			{
				return;
			}

			this.setDataParam("html", value);
			this.notify({ source: null });
		}
	};
	BX.CrmQuickPanelHtmlModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelHtmlModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelClientModel) === "undefined")
{
	BX.CrmQuickPanelClientModel = function()
	{
		BX.CrmQuickPanelClientModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuickPanelClientModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelClientModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelClientModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelMultipleClientModel) === "undefined")
{
	BX.CrmQuickPanelMultipleClientModel = function()
	{
		BX.CrmQuickPanelClientModel.superclass.constructor.apply(this);
		this._currentChildIndex = 0;
		this._childCount = 0;
		this._children = null;
		this._isCurrentChildChanged = false;
		this._areChildrenLoaded = false;
		this._saveCurrentChildIntervalId = 0;
		this._callback = null;
		this._notifier = null;
		this._dataLoader = null;
		this._childrenLoadHandler = BX.delegate(this.onChildrenLoad, this);
	};
	BX.extend(BX.CrmQuickPanelMultipleClientModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelMultipleClientModel.prototype.doInitialize = function()
	{
		this._childCount = parseInt(this.getDataParam("childCount", 0));
		this._currentChildIndex = parseInt(this.getDataParam("currentChildIndex", 0));
		this._notifier = BX.CrmNotifier.create(this);
		this.initailzeChildren(this.getDataParam("children", []));
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.initailzeChildren = function(configs)
	{
		this._children = [];
		for(var i = 0; i < this._childCount; i++)
		{
			var config = i < configs.length ? configs[i] : null;
			if(config === null)
			{
				//Creation of stub for absent item if required
				if(i === this._children.length)
				{
					this._children.push(null);
				}
				continue;
			}

			var data = config['data'];
			var index = data.hasOwnProperty('INDEX') ? parseInt(data['INDEX']) : i;
			if(index < i)
			{
				index = i;
			}
			else if(index > i)
			{
				//Creation of stub for absent item if required
				for(var j = i; j < index; j++)
				{
					this._children.push(null);
				}
			}
			this._children.push(this.createChild(this._id + "_" + index.toString(), config));
		}
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.getChildCount = function()
	{
		return this._childCount;
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.createChild = function(id, config)
	{
		config["enableCaption"] = this.getConfigParam("enableCaption", false);
		return BX.CrmQuickPanelClientModel.create(id, { config: config });
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.getChild = function(index)
	{
		if(index < 0 || index >= this._childCount)
		{
			throw "CrmQuickPanelMultipleClientModel: index out of bounds.";
		}

		return index < this._children.length ? this._children[index] : null;
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.getCurrentChildIndex = function()
	{
		return this._currentChildIndex;
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.setCurrentChildIndex = function(index)
	{
		//Rewind index
		var maxIndex = this._childCount - 1;
		if(index < 0)
		{
			index = maxIndex;
		}
		else if(index > maxIndex)
		{
			index = 0;
		}

		if(this._currentChildIndex === index)
		{
			return;
		}

		this._currentChildIndex = index;
		this._isCurrentChildChanged = true;
		this.saveCurrentChild();
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.saveCurrentChild = function()
	{
		if(this._saveCurrentChildIntervalId > 0)
		{
			window.clearTimeout(this._saveCurrentChildIntervalId);
			this._saveCurrentChildIntervalId = 0;
		}
		this._saveCurrentChildIntervalId = window.setTimeout(BX.delegate(this.doSaveCurrentChild, this), 1000);
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.doSaveCurrentChild = function()
	{
		if(!this._areChildrenLoaded)
		{
			return;
		}

		var service = this.getConfigParam("service", null);
		if(!BX.type.isPlainObject(service))
		{
			throw "CrmQuickPanelMultipleClientModel: Could not find service cofig.";
		}

		var owner = this.getConfigParam("owner", null);
		if(!BX.type.isPlainObject(owner))
		{
			throw "CrmQuickPanelMultipleClientModel: Could not find owner cofig.";
		}

		var currentChild = this.getCurrentChild();
		if(currentChild === null)
		{
			throw "CrmQuickPanelMultipleClientModel: Could not find current child.";
		}

		BX.ajax(
			{
				url: service["url"],
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION": "SAVE_SELECTED_BINDING",
					"PARAMS":
					{
						"OWNER_TYPE_NAME": owner["typeName"],
						"OWNER_ID": owner["id"],
						"ENTITY_TYPE_NAME": currentChild.getDataParam("ENTITY_TYPE_NAME"),
						"ENTITY_ID": currentChild.getDataParam("ENTITY_ID")
					}
				}
			}
		);

		this._isCurrentChildChanged = false;
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.getCurrentChild = function()
	{
		return this.getChild(this._currentChildIndex);
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.areChildrenLoaded = function()
	{
		return this._areChildrenLoaded;
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.loadChildren = function(callback)
	{
		if(!BX.type.isFunction(callback))
		{
			callback = null;
		}

		if(this._areChildrenLoaded)
		{
			if(callback !== null)
			{
				callback(this);
			}
			return;
		}

		var service = this.getConfigParam("service", null);
		if(!BX.type.isPlainObject(service))
		{
			throw "CrmQuickPanelMultipleClientModel: Could not find service cofig.";
		}

		var owner = this.getConfigParam("owner", null);
		if(!BX.type.isPlainObject(owner))
		{
			throw "CrmQuickPanelMultipleClientModel: Could not find owner cofig.";
		}

		var entityTypeName = this.getConfigParam("entityTypeName", "");
		if(!BX.type.isNotEmptyString(entityTypeName))
		{
			throw "CrmQuickPanelMultipleClientModel: Could not find entityTypeName.";
		}

		this._notifier.addListener(callback);
		if(this._dataLoader === null)
		{
			this._dataLoader = BX.CrmDataLoader.create(
				this._id,
				{
					serviceUrl: service["url"],
					action: "GET_BINGINGS",
					params:
					{
						"OWNER_TYPE_NAME": owner["typeName"],
						"OWNER_ID": owner["id"],
						"ENTITY_TYPE_NAME": entityTypeName,
						"FORM_ID": service["formId"]
					}
				}
			);
			this._dataLoader.load(this._childrenLoadHandler);
		}
	};
	BX.CrmQuickPanelMultipleClientModel.prototype.onChildrenLoad = function(sender, result)
	{
		if(this._areChildrenLoaded)
		{
			return;
		}

		this._areChildrenLoaded = true;
		var configs = result["DATA"];
		this._childCount = configs.length;
		this.initailzeChildren(configs);

		if(this._isCurrentChildChanged)
		{
			this.saveCurrentChild();
		}

		this._notifier.notify();
		this._notifier.resetListeners();
	};
	BX.CrmQuickPanelMultipleClientModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelMultipleClientModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
if(typeof(BX.CrmQuickPanelCompositeClientModel) === "undefined")
{
	BX.CrmQuickPanelCompositeClientModel = function()
	{
		BX.CrmQuickPanelCompositeClientModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuickPanelCompositeClientModel, BX.CrmQuickPanelModel);
	BX.CrmQuickPanelCompositeClientModel.prototype.getPrimaryModel = function()
	{
		var config = this.getConfigParam("primaryConfig");
		var type = BX.type.isNotEmptyString(config["type"]) ? config["type"] : "";
		if(type === "client")
		{
			return BX.CrmQuickPanelClientModel.create( "PRIMARY_" + this._id, { config: config });
		}

		throw "CrmQuickPanelCompositeClientModel: Model type '" + type + "' is not supported.";
	};
	BX.CrmQuickPanelCompositeClientModel.prototype.getSecondaryModel = function()
	{
		var config = this.getConfigParam("secondaryConfig");
		var type = BX.type.isNotEmptyString(config["type"]) ? config["type"] : "";
		if(type === "client")
		{
			return BX.CrmQuickPanelClientModel.create("SECONDARY_" + this._id, { config: config });
		}
		else if(type === "multiple_client")
		{
			return BX.CrmQuickPanelMultipleClientModel.create("SECONDARY_" + this._id, { config: config });
		}

		throw "CrmQuickPanelCompositeClientModel: Model type '" + type + "' is not supported.";
	};
	BX.CrmQuickPanelCompositeClientModel.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelCompositeClientModel();
		self.initialize(id, settings);
		BX.CrmQuickPanelModel.items[id] = self;
		return self;
	};
}
//BX.CrmQuickPanelItemPlaceholder
if(typeof(BX.CrmQuickPanelItemPlaceholder) === "undefined")
{
	BX.CrmQuickPanelItemPlaceholder = function()
	{
		this._settings = null;
		this._container = null;
		this._node = null;
		this._section = null;
		this._isDragOver = false;
		this._isActive = false;
		this._index = -1;
		this._timeoutId = null;
	};
	BX.CrmQuickPanelItemPlaceholder.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._container = this.getSetting("container", null);
			this._section = this.getSetting("section", null);
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
				this._node.className = active ? "crm-lead-header-drag-zone-bd" : "crm-lead-header-drag-zone-bd-inactive";
			}
		},
		getIndex: function()
		{
			return this._index;
		},
		layout: function()
		{
			if(!this._container)
			{
				throw "CrmQuickPanelItemPlaceholder: The 'container' is not assigned.";
			}

			var row = this._container;
			var cell = row.insertCell(-1);
			cell.className = "crm-lead-header-drag-zone";
			cell.colSpan = 4;
			this._node = BX.create("DIV", { attrs: { className: this._isActive ? "crm-lead-header-drag-zone-bd" : "crm-lead-header-drag-zone-bd-inactive" } });
			cell.appendChild(this._node);

			BX.bind(row, "dragover", BX.delegate(this._onDragOver, this));
			BX.bind(row, "dragleave", BX.delegate(this._onDragLeave, this));
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
	};
	BX.CrmQuickPanelItemPlaceholder.create = function(settings)
	{
		var self = new BX.CrmQuickPanelItemPlaceholder();
		self.initialize(settings);
		return self;
	};
}
//BX.CrmQuickPanelSection
if(typeof(BX.CrmQuickPanelSection) === "undefined")
{
	BX.CrmQuickPanelSection = function()
	{
		this._id = "";
		this._settings = null;
		this._view = null;
		this._items = [];
		this._container = null;
		this._placeHolder = null;
		this._dragDropContainerId = "";
		this._dragSection = null;
	};
	BX.CrmQuickPanelSection.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._view = this.getSetting("view");
			if(!this._view)
			{
				throw "CrmQuickPanelSection: The 'view' parameter is not defined in settings.";
			}

			this._container = this.getSetting("container");
			if(!this._container)
			{
				throw "CrmQuickPanelSection: The 'container' parameter is not defined in settings.";
			}

			this.initializeFromConfig(this.getSetting("config", null));

			this._dragSection = BX.CrmQuickPanelSectionDragContainer.create(
				this.getId(),
				{
					section: this,
					view: this._view,
					node: BX.findParent(this._container, { tagName: "TD", className: "crm-lead-header-cell" })
				}
			);
			this._dragSection.addDragFinishListener(this._view.getItemDropCallback());
		},
		initializeFromConfig: function(config)
		{
			if(!BX.type.isArray(config))
			{
				return;
			}

			var prefix = this.getPrefix() + "_" + this.getId();
			for(var i = 0; i < config.length; i++)
			{
				var id = config[i];
				var model = this._view.getFieldModel(id);
				var container = BX(prefix + "_" + id.toLowerCase());
				if(model && container)
				{
					var item = BX.CrmQuickPaneSectionItem.create(id, { section: this, model: model, container: container, hasLayout: true, prefix: prefix });
					this._items.push(item);
				}
			}
		},
		getPrefix: function()
		{
			return this.getSetting("prefix", "");
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getContainer: function()
		{
			return this._container;
		},
		getView: function()
		{
			return this._view;
		},
		getItems: function()
		{
			return this._items;
		},
		getItemCount: function()
		{
			return this._items.length;
		},
		getItemByIndex: function(index)
		{
			return index >= 0 && index < this._items.length ? this._items[index] : null;
		},
		createItem: function(id, model)
		{
			var item = BX.CrmQuickPaneSectionItem.create(id, { model: model });
			this.addItem(item);
			item.layout();
			return item;
		},
		addItem: function(item)
		{
			var index = -1;
			if(!item.getContainer())
			{
				var row = null;
				if(!this._placeHolder)
				{
					row = this._container.insertRow(-1);
				}
				else
				{
					row = this._placeHolder.getContainer();
					index = row.rowIndex;
					BX.cleanNode(row, false);
					this._placeHolder = null;
				}
				item.setContainer(row);
			}
			item.setSection(this);
			item.setPrefix(this.getPrefix() + "_" + this.getId());


			if(index >= 0)
			{
				this._items.splice(index, 0, item);
			}
			else
			{
				this._items.push(item);
			}
		},
		moveItem: function(item, index)
		{
			var qty = this.getItemCount();
			if(index < 0  || index > qty)
			{
				index = qty;
			}

			var currentIndex = this.findItemIndex(item);
			if(currentIndex < 0 || currentIndex === index || (currentIndex === (qty - 1) && index === qty))
			{
				return false;
			}

			var rowIndex = index;
			var currentRowIndex = item.getContainer().rowIndex;
			if(currentRowIndex < rowIndex)
			{
				rowIndex--;
			}

			item.clearLayout();
			this._container.deleteRow(currentRowIndex);
			this._items.splice(currentIndex, 1);
			if(currentIndex < index)
			{
				index--;
			}

			item.setContainer(this._container.insertRow(rowIndex));
			item.layout();
			this._items.splice(index, 0, item);

			return true;
		},
		deleteItem: function(item)
		{
			var index = this.findItemIndex(item);
			if(index < 0)
			{
				return;
			}

			this._items.splice(index, 1);
			item.clearLayout();
			this._container.deleteRow(item.getContainer().rowIndex);
		},
		createPlaceHolder: function(index)
		{
			var qty = this.getItemCount();
			if(index < 0 || index > qty)
			{
				index = qty > 0 ? qty : 0;
			}

			if(this._placeHolder)
			{
				if(this._placeHolder.getIndex() === index)
				{
					return this._placeHolder;
				}

				this._container.deleteRow(this._placeHolder.getContainer().rowIndex);
				this._placeHolder = null;
			}

			this._placeHolder = BX.CrmQuickPanelItemPlaceholder.create(
				{
					section: this,
					container: this._container.insertRow(index === qty ? -1 : index),
					index: index
				}
			);
			this._placeHolder.layout();
			return this._placeHolder;
		},
		hasPlaceHolder: function()
		{
			return !!this._placeHolder;
		},
		getPlaceHolder: function()
		{
			return this._placeHolder;
		},
		getPlaceHolderRowIndex: function()
		{
			return this._placeHolder ? this._placeHolder.getContainer().rowIndex : -1;
		},
		removePlaceHolder: function()
		{
			if(this._placeHolder)
			{
				this._container.deleteRow(this._placeHolder.getContainer().rowIndex);
				this._placeHolder = null;
			}
		},
		hidePlaceHolder: function()
		{
			if(this._items.length === 0 && this._placeHolder)
			{
				this._placeHolder.setActive(false);
			}
			else if(this._placeHolder)
			{
				this._container.deleteRow(this._placeHolder.getContainer().rowIndex);
				this._placeHolder = null;
			}
		},
		getDragEnterCallback: function()
		{
			return BX.delegate(this._onDragEnter, this);
		},
		getDragDropContainerId: function()
		{
			return this._dragDropContainerId;
		},
		setDragDropContainerId: function(containerId)
		{
			this._dragDropContainerId = containerId;
		},
		findItemIndex: function(item)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				if(item === this._items[i])
				{
					return i;
				}
			}

			return -1;
		},
		findItemById: function(id)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.getId() === id)
				{
					return item;
				}
			}

			return null;
		},
		processItemDeletion: function(item)
		{
			this.deleteItem(item);
			this._view.processSectionItemDeletion(this, item);
		}
	};
	BX.CrmQuickPanelSection.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelSection();
		self.initialize(id, settings);
		return self;
	};
}

//D&D Items
if(typeof(BX.CrmQuickPanelSectionDragItem) === "undefined")
{
	BX.CrmQuickPanelSectionDragItem = function()
	{
		BX.CrmQuickPanelSectionDragItem.superclass.constructor.apply(this);
		this._item = null;
		this._showItemInDragMode = true;
	};
	BX.extend(BX.CrmQuickPanelSectionDragItem, BX.CrmCustomDragItem);
	BX.CrmQuickPanelSectionDragItem.prototype.doInitialize = function()
	{
		this._item = this.getSetting("item");
		if(!this._item)
		{
			throw "CrmQuickPanelSectionDragItem: The 'item' parameter is not defined in settings or empty.";
		}

		this._showItemInDragMode = this.getSetting("showItemInDragMode", true);
	};
	BX.CrmQuickPanelSectionDragItem.prototype.getItem = function()
	{
		return this._item;
	};
	BX.CrmQuickPanelSectionDragItem.prototype.createGhostNode = function()
	{
		if(this._ghostNode)
		{
			return this._ghostNode;
		}

		this._ghostNode = this._item.createGhostNode();
		document.body.appendChild(this._ghostNode);
	};
	BX.CrmQuickPanelSectionDragItem.prototype.removeGhostNode = function()
	{
		if(this._ghostNode)
		{
			document.body.removeChild(this._ghostNode);
			this._ghostNode = null;
		}
	};
	BX.CrmQuickPanelSectionDragItem.prototype.getContextId = function()
	{
		return BX.CrmQuickPanelSectionDragItem.contextId;
	};
	BX.CrmQuickPanelSectionDragItem.prototype.getContextData = function()
	{
		return ({ contextId: BX.CrmQuickPanelSectionDragItem.contextId, item: this._item });
	};
	BX.CrmQuickPanelSectionDragItem.prototype.processDragStart = function()
	{
		if(!this._showItemInDragMode)
		{
			this._item.getContainer().style.display = "none";
		}
		BX.CrmQuickPanelSectionDragContainer.refresh();
	};
	BX.CrmQuickPanelSectionDragItem.prototype.processDragStop = function()
	{
		if(!this._showItemInDragMode)
		{
			this._item.getContainer().style.display = "";
		}
		BX.CrmQuickPanelSectionDragContainer.refreshAfter(300);
	};
	BX.CrmQuickPanelSectionDragItem.contextId = "quick_panel_section_item";
	BX.CrmQuickPanelSectionDragItem.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelSectionDragItem();
		self.initialize(id, settings);
		return self;
	};
}
//D&D Containers
if(typeof(BX.CrmQuickPanelSectionDragContainer) === "undefined")
{
	BX.CrmQuickPanelSectionDragContainer = function()
	{
		BX.CrmQuickPanelSectionDragContainer.superclass.constructor.apply(this);
		this._section = null;
	};
	BX.extend(BX.CrmQuickPanelSectionDragContainer, BX.CrmCustomDragContainer);
	BX.CrmQuickPanelSectionDragContainer.prototype.doInitialize = function()
	{
		this._section = this.getSetting("section");
		if(!this._section)
		{
			throw "CrmQuickPanelSectionDragContainer: The 'section' parameter is not defined in settings or empty.";
		}

		this._view = this.getSetting("view");
		if(!this._view)
		{
			throw "CrmQuickPanelSectionDragContainer: The 'view' parameter is not defined in settings or empty.";
		}
	};
	BX.CrmQuickPanelSectionDragContainer.prototype.getSection = function()
	{
		return this._section;
	};
	BX.CrmQuickPanelSectionDragContainer.prototype.createPlaceHolder = function(pos)
	{
		var rect;
		var placeholder = this._section.getPlaceHolder();
		if(placeholder)
		{
			rect = BX.pos(placeholder.getContainer());
			if(pos.y >= rect.top && pos.y <= rect.bottom)
			{
				if(!placeholder.isActive())
				{
					placeholder.setActive(true);
				}
				return;
			}
		}

		var items = this._section._items;
		for(var i = 0; i < items.length; i++)
		{
			rect = BX.pos(items[i].getContainer());
			if(pos.y >= rect.top && pos.y <= rect.bottom)
			{
				this._section.createPlaceHolder(
					(rect.top  + (rect.height / 2) - pos.y) >= 0 ? i : (i + 1)
				).setActive(true);
				return;
			}
		}

		this._section.createPlaceHolder(-1).setActive(true);
		this.refresh();
	};
	BX.CrmQuickPanelSectionDragContainer.prototype.removePlaceHolder = function()
	{
		if(!this._section.hasPlaceHolder())
		{
			return;
		}

		if(this._section.getItemCount() > 0)
		{
			this._section.removePlaceHolder();
		}
		else
		{
			this._section.getPlaceHolder().setActive(false);
		}
		this.refresh();
	};
	BX.CrmQuickPanelSectionDragContainer.prototype.isAllowedContext = function(contextId)
	{
		return (contextId === BX.CrmQuickPanelSectionDragItem.contextId
			|| this._view.isAllowedDragContext(contextId));
	};
	BX.CrmQuickPanelSectionDragContainer.refresh = function()
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].refresh();
			}
		}
	};
	BX.CrmQuickPanelSectionDragContainer.refreshAfter = function(interval)
	{
		interval = parseInt(interval);
		if(interval > 0)
		{
			window.setTimeout(function() { BX.CrmQuickPanelSectionDragContainer.refresh(); }, interval);
		}
		else
		{
			this.refresh();
		}
	};
	BX.CrmQuickPanelSectionDragContainer.items = {};
	BX.CrmQuickPanelSectionDragContainer.create = function(id, settings)
	{
		var self = new BX.CrmQuickPanelSectionDragContainer();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}