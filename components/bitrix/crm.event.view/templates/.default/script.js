function crm_event_desc(iid)
{
	BX('event_desc_short_'+iid).style.display = 'none';
	BX('event_desc_full_'+iid).style.display = 'block';
}

if(typeof(BX.CrmEventListManager) === "undefined")
{
	BX.CrmEventListManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._entityId = "";
		this._gridId = "";
		this._tabId = "";
		this._formId = "";
		this._form = null;
		this._grid = null;
		this._deletionConfirmationDlg = null;
		this._currentItemId = "";

		this._formTabShowHandler = BX.delegate(this._onFormTabShow, this);
		this._pageReloadHandler = BX.delegate(this._onBeforeEventPageReload, this);
	};

	BX.CrmEventListManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName");
			this._entityId = this.getSetting("entityId");
			this._gridId = this.getSetting("gridId");
			this._tabId = this.getSetting("tabId");
			this._formId = this.getSetting("formId");

			var formObjName = "bxForm_" + this._formId;
			if(typeof(window[formObjName]) !== "undefined")
			{
				this._form = window[formObjName];
			}

			if(this._form)
			{
				this.adjustGridFilter(this._form.GetActiveTabId());
				BX.addCustomEvent(this._form, "OnTabShow", this._formTabShowHandler);
				BX.addCustomEvent(window, "CrmBeforeEventPageReload", this._pageReloadHandler);
			}

			var gridObjName = "bxGrid_" + this._gridId;
			if(typeof(window[gridObjName]) !== "undefined")
			{
				this._grid = window[gridObjName];
			}
		},
		release: function()
		{
			if(this._form)
			{
				BX.removeCustomEvent(this._form, "OnTabShow", this._formTabShowHandler);
				BX.removeCustomEvent(window, "CrmBeforeEventPageReload", this._pageReloadHandler);
				this._form = null;
			}

			if(this._grid)
			{
				this._grid = null;
			}
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultvalue;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmEventListManager.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		adjustGridFilter: function(tabId)
		{
			if(tabId !== this._tabId && BX.InterfaceGridFilterPopup && BX.InterfaceGridFilterPopup.items[this._gridId])
			{
				BX.InterfaceGridFilterPopup.items[this._gridId].close();
			}
		},
		addItem: function()
		{
			var url = this.getSetting("addItemUrl", "");
			if(url === "")
			{
				return;
			}

			url = BX.util.add_url_param(url,
				{
					"FORM_ID": this._formId.toUpperCase(),
					"ENTITY_TYPE": this._entityTypeName,
					"ENTITY_ID": this._entityId
				}
			);

			var dialog = new BX.CDialog({ content_url: url, width: '498', height: '275', resizable: false });
			dialog.Show();
		},
		_onFormTabShow: function(tabId)
		{
			this.adjustGridFilter(tabId);
		},
		_onBeforeEventPageReload: function(eventData)
		{
			var tabId = this._form.GetActiveTabId();
			if(tabId !== this._tabId)
			{
				this._form.SelectTab(this._tabId);
			}

			BX.Main.gridManager.reload(this._gridId);
			eventData.cancel = true;
		}
	};

	if(typeof(BX.CrmEventListManager.messages) === "undefined")
	{
		BX.CrmEventListManager.messages = {};
	}

	BX.CrmEventListManager.items = {};
	BX.CrmEventListManager.remove = function(id)
	{
		if(!this.items.hasOwnProperty(id))
		{
			return;
		}

		this.items[id].release();
		delete this.items[id];
	};
	BX.CrmEventListManager.create = function(id, settings)
	{
		if(this.items.hasOwnProperty(id))
		{
			this.items[id].release();
			delete this.items[id];
		}

		var self = new BX.CrmEventListManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	}
}