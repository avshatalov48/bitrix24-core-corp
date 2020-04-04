BX.namespace("BX.Crm");

//region Step-by-step export manager
if(typeof BX.Crm.StExportManager === "undefined")
{
	BX.Crm.StExportManager = function()
	{
		this._id = "";
		this._settings = {};
		this._processDialog = null;
		this._siteId = "";
		this._entityType = "";
		this._sToken = "";
		this._cToken = "";
		this._token = "";
		this._serviceUrl = "";
		this._initialOptions = {};
	};

	BX.Crm.StExportManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._siteId = this.getSetting("siteId", "");
			if (!BX.type.isNotEmptyString(this._siteId))
				throw "BX.Crm.StExportManager: parameter 'siteId' is not found.";
			this._entityType = this.getSetting("entityType", "");
			if (!BX.type.isNotEmptyString(this._entityType))
				throw "BX.Crm.StExportManager: parameter 'entityType' is not found.";
			this._sToken = this.getSetting("sToken", "");
			if (!BX.type.isNotEmptyString(this._sToken))
				throw "BX.Crm.StExportManager: parameter 'sToken' is not found.";
			this._serviceUrl = '/bitrix/components/bitrix/crm.' + this._entityType.toLowerCase() +
				'.list/stexport.ajax.php';
			this._initialOptions = this.getSetting("initialOptions", {});
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		startExport: function (exportType) {
			if (!BX.type.isNotEmptyString(exportType))
				throw "BX.Crm.StExportManager: parameter 'exportType' has invalid value.";

			this._cToken = "c" + Date.now();
			this._token = this._sToken + this._cToken;
			var params = {
				"SITE_ID": this._siteId,
				"PROCESS_TOKEN": this._token,
				"ENTITY_TYPE_NAME": this._entityType,
				"EXPORT_TYPE": exportType,
				"COMPONENT_PARAMS": this.getSetting("componentParams", {})
			};
			if (this._entityType === "COMPANY")
				params['MY_COMPANY_MODE'] = (this.getSetting("myCompanyMode", false) === true) ? "Y" : "N";
			var exportTypeMsgSuffix = exportType.charAt(0).toUpperCase() + exportType.slice(1);
			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				this._id + "_LrpDlg",
				{
					serviceUrl: this._serviceUrl,
					action: "STEXPORT",
					params: params,
					initialOptions: this._initialOptions,
					title: this.getMessage("stExport" + exportTypeMsgSuffix + "DlgTitle"),
					summary: this.getMessage("stExport" + exportTypeMsgSuffix + "DlgSummary"),
					isSummaryHtml: false
				}
			);
			/*BX.addCustomEvent(this._processDialog, "ON_STATE_CHANGE", BX.delegate(this.onRebuildIndexProcessStateChange, this));
			BX.addCustomEvent(this._processDialog, "ON_CLOSE", BX.delegate(this.onRebuildIndexDialogClose, this));*/
			this._processDialog.show();
		},
		destroy: function ()
		{
			this._id = "";
			this._settings = {};
			this._processDialog = null;
			this._siteId = "";
			this._entityType = "";
			this._sToken = "";
			this._cToken = "";
			this._token = "";
			this._serviceUrl = "";
			this._initialOptions = {};
		}
	};

	BX.Crm.StExportManager.prototype.getMessage = function(name)
	{
		var message = name;
		var messages = this.getSetting("messages", null);
		if (messages !== null && typeof(messages) === "object" && messages.hasOwnProperty(name))
		{
			message =  messages[name];
		}
		else
		{
			messages = BX.Crm.StExportManager.messages;
			if (messages !== null && typeof(messages) === "object" && messages.hasOwnProperty(name))
			{
				message =  messages[name];
			}
		}
		return message;
	};

	if(typeof(BX.Crm.StExportManager.messages) === "undefined")
	{
		BX.Crm.StExportManager.messages = {};
	}

	if(typeof(BX.Crm.StExportManager.items) === "undefined")
	{
		BX.Crm.StExportManager.items = {};
	}

	BX.Crm.StExportManager.create = function(id, settings)
	{
		var self = new BX.Crm.StExportManager();
		self.initialize(id, settings);
		BX.Crm.StExportManager.items[id] = self;
		return self;
	};

	BX.Crm.StExportManager.delete = function(id)
	{
		if (BX.Crm.StExportManager.items.hasOwnProperty(id))
		{
			BX.Crm.StExportManager.items[id].destroy();
			delete BX.Crm.StExportManager.items[id];
		}
	};
}
//endregion Step-by-step export manager