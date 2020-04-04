BX.namespace("BX.Crm");

if(typeof BX.Crm.ExportManager === "undefined")
{
	BX.Crm.ExportManager = function()
	{
		this._id = "";
		this._settings = {};
		this._componentName = "";
		this._processDialog = null;
		this._siteId = "";
		this._entityType = "";
		this._sToken = "";
		this._cToken = "";
		this._token = "";
		this._initialOptions = {};
	};

	BX.Crm.ExportManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._siteId = this.getSetting("siteId", "");
			if (!BX.type.isNotEmptyString(this._siteId))
				throw "BX.Crm.ExportManager: parameter 'siteId' is not found.";

			this._componentName = this.getSetting("componentName", "");
			if (!BX.type.isNotEmptyString(this._componentName))
				throw "BX.Crm.ExportManager: parameter 'componentName' is not found.";

			this._entityType = this.getSetting("entityType", "");
			if (!BX.type.isNotEmptyString(this._entityType))
				throw "BX.Crm.ExportManager: parameter 'entityType' is not found.";

			this._sToken = this.getSetting("sToken", "");
			if (!BX.type.isNotEmptyString(this._sToken))
				throw "BX.Crm.ExportManager: parameter 'sToken' is not found.";

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
		callAction: function(action)
		{
			this._processDialog.setAction(action);
			this._processDialog.start();
		},
		startExport: function (exportType) {
			if (!BX.type.isNotEmptyString(exportType))
				throw "BX.Crm.ExportManager: parameter 'exportType' has invalid value.";

			this._cToken = "c" + Date.now();

			this._token = this._sToken + this._cToken;

			var params = {
				"SITE_ID": this._siteId,
				"PROCESS_TOKEN": this._token,
				"ENTITY_TYPE": this._entityType,
				"EXPORT_TYPE": exportType,
				"COMPONENT_NAME": this._componentName,
				"signedParameters": this.getSetting("componentParams", {})
			};

			var exportTypeMsgSuffix = exportType.charAt(0).toUpperCase() + exportType.slice(1);

			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				this._id + "_LrpDlg",
				{
					controller: 'crm.api.export',
					action: "dispatcher",
					params: params,
					initialOptions: this._initialOptions,
					title: this.getMessage("stExport" + exportTypeMsgSuffix + "DlgTitle"),
					summary: this.getMessage("stExport" + exportTypeMsgSuffix + "DlgSummary"),
					isSummaryHtml: false,
					requestHandler: function(result){
						if(BX.type.isNotEmptyString(result["STATUS"]) && result["STATUS"]=="COMPLETED")
						{
							if(BX.type.isNotEmptyString(result["DOWNLOAD_LINK"]))
							{
								result["SUMMARY_HTML"] +=
									'<br><br>' +
									'<a href="' + result["DOWNLOAD_LINK"] + '" class="ui-btn ui-btn-sm ui-btn-success ui-btn-icon-download">' +
									result['DOWNLOAD_LINK_NAME'] + '</a>' +
									'<button onclick="BX.Crm.ExportManager.currentInstance().callAction(\'clear\')" class="ui-btn ui-btn-sm ui-btn-default ui-btn-icon-remove">' +
									result['CLEAR_LINK_NAME'] + '</button>';

							}
						}
					}
				}
			);

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
			this._initialOptions = {};
		}
	};

	BX.Crm.ExportManager.prototype.getMessage = function(name)
	{
		var message = name;
		var messages = this.getSetting("messages", null);
		if (messages !== null && typeof(messages) === "object" && messages.hasOwnProperty(name))
		{
			message =  messages[name];
		}
		else
		{
			messages = BX.Crm.ExportManager.messages;
			if (messages !== null && typeof(messages) === "object" && messages.hasOwnProperty(name))
			{
				message =  messages[name];
			}
		}
		return message;
	};

	if(typeof(BX.Crm.ExportManager.messages) === "undefined")
	{
		BX.Crm.ExportManager.messages = {};
	}

	if(typeof(BX.Crm.ExportManager.items) === "undefined")
	{
		BX.Crm.ExportManager.items = {};
	}

	BX.Crm.ExportManager.create = function(id, settings)
	{
		var self = new BX.Crm.ExportManager();
		self.initialize(id, settings);
		BX.Crm.ExportManager.items[id] = self;
		BX.Crm.ExportManager.currentId = id;
		return self;
	};

	BX.Crm.ExportManager.delete = function(id)
	{
		if (BX.Crm.ExportManager.items.hasOwnProperty(id))
		{
			BX.Crm.ExportManager.items[id].destroy();
			delete BX.Crm.ExportManager.items[id];
		}
	};

	BX.Crm.ExportManager.currentId = '';
	BX.Crm.ExportManager.currentInstance = function()
	{
		return BX.Crm.ExportManager.items[BX.Crm.ExportManager.currentId];
	};
}
