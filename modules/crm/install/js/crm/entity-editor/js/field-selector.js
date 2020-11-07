BX.namespace("BX.Crm");

//region FIELD SELECTOR
if(typeof(BX.Crm.EntityEditorFieldSelector) === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorFieldSelector = BX.UI.EntityEditorFieldSelector;
}
//endregion

//region USER SELECTOR
if(typeof(BX.Crm.EntityEditorUserSelector) === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorUserSelector = BX.UI.EntityEditorUserSelector;
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
