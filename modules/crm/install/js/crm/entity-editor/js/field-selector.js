BX.namespace("BX.Crm");

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
							callback: {
								select : BX.delegate(this.onSelect, this),
								unSelect: BX.delegate(this.onSelect, this)
							},
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
