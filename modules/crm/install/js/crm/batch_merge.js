BX.namespace("BX.Crm");

if(typeof(BX.Crm.BatchMergeManager) === "undefined")
{
	BX.Crm.BatchMergeManager = function()
	{
		this._id = "";
		this._settings = {};

		this._gridId = "";
		this._entityTypeId = BX.CrmEntityType.enumeration.undefined;
		this._entityIds = null;

		this._wrapper = null;
		this._errors = null;
		this._isRunning = false;

		this._documentUnloadHandler = BX.delegate(this.onDocumentUnload, this);
		this._requestCompleteHandler = BX.delegate(this.onRequestComplete, this);
		this._externalEventHandler = null;
	};
	BX.Crm.BatchMergeManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_batch_merge_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._gridId = BX.prop.getString(this._settings, "gridId", this._id);
			this._entityTypeId = BX.prop.getInteger(
				this._settings,
				"entityTypeId",
				BX.CrmEntityType.enumeration.undefined
			);

			var container = BX(BX.prop.getString(this._settings, "container", ""));
			if(!BX.type.isElementNode(container))
			{
				throw "BX.Crm.BatchMergeManager: Could not find container.";
			}

			this._wrapper = BX.create("div", {});
			container.appendChild(this._wrapper);

			this._errors = [];
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(
				BX.prop.getObject(this._settings, "messages", BX.Crm.BatchMergeManager.messages),
				name,
				name
			);
		},
		getEntityIds: function()
		{
			return this._entityIds;
		},
		setEntityIds: function(entityIds)
		{
			this._entityIds = BX.type.isArray(entityIds) ? entityIds : [];
		},
		resetEntityIds: function()
		{
			this._entityIds = [];
		},
		getErrors: function()
		{
			return this._errors ? this._errors : [];
		},
		execute: function()
		{
			var dialogId = this._id.toLowerCase();
			var dialog = BX.Crm.ConfirmationDialog.get(dialogId);
			if(!dialog)
			{
				dialog = BX.Crm.ConfirmationDialog.create(
					dialogId,
					{
						title: this.getMessage("title"),
						content: this.getMessage("confirmation")
					}
				);
			}

			if(!dialog.isOpened())
			{
				dialog.open().then(
					function(result)
					{
						if(!BX.prop.getBoolean(result, "cancel", true))
						{
							this.startRequest();
						}
					}.bind(this)
				);
			}
		},
		isRunning: function()
		{
			return this._isRunning;
		},
		startRequest: function()
		{
			if(this._isRunning)
			{
				return;
			}
			this._isRunning = true;

			BX.Main.gridManager.getInstanceById(this._gridId).tableFade();
			BX.bind(window, "beforeunload", this._documentUnloadHandler);

			var params =
				{
					entityTypeId: this._entityTypeId,
					extras: BX.prop.getObject(this._settings, "extras", {})
				};

			if(BX.type.isArray(this._entityIds) && this._entityIds.length > 0)
			{
				params["entityIds"] = this._entityIds;
			}

			BX.ajax.runAction(
				"crm.api.entity.mergeBatch",
				{ data: { params:  params } }
			).then(
				this._requestCompleteHandler
			).catch(
				this._requestCompleteHandler
			);
		},
		onRequestComplete: function(response)
		{
			BX.Main.gridManager.getInstanceById(this._gridId).tableUnfade();
			BX.unbind(window, "beforeunload", this._documentUnloadHandler);
			this._isRunning = false;
			this._errors = [];

			var status = BX.prop.getString(response, "status", "");
			var data = BX.prop.getObject(response, "data", {});

			if(status === "error")
			{
				if(BX.prop.getString(data, "STATUS", "") === "CONFLICT")
				{
					this.openMerger();
					return;
				}

				var errorInfos = BX.prop.getArray(response, "errors", []);
				for(var i = 0, length = errorInfos.length; i < length; i++)
				{
					this._errors.push(BX.prop.getString(errorInfos[i], "message"));
				}
			}

			this.displaySummary();
			if(this._errors.length === 0)
			{
				window.setTimeout(
					this.complete.bind(this),
					0
				);
			}
		},
		displaySummary: function()
		{
			var messages = [this.getMessage("summaryCaption")];
			if(this._errors.length > 0)
			{
				messages.push(
					this.getMessage("summaryFailed").replace(/#number#/gi, this._entityIds.length)
				);
				messages = messages.concat(this._errors);
			}
			else
			{
				messages.push(
					this.getMessage("summarySucceeded").replace(/#number#/gi, this._entityIds.length)
				);
			}

			BX.UI.Notification.Center.notify(
				{
					content: messages.join("<br/>"),
					position: "top-center",
					autoHideDelay: 5000
				}
			);
		},
		openMerger: function()
		{
			this._contextId = this._id + "_" + BX.util.getRandomString(6).toUpperCase();

			BX.Crm.Page.open(
				BX.util.add_url_param(
					BX.prop.getString(this._settings, "mergerUrl", ""),
					{
						externalContextId: this._contextId,
						id: this._entityIds
					}
				)
			);

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
		},
		complete: function ()
		{
			BX.onCustomEvent(
				window,
				"BX.Crm.BatchMergeManager:onComplete",
				[ this ]
			);

			BX.Main.gridManager.reload(this._gridId);
		},
		onDocumentUnload: function(e)
		{
			return(e.returnValue = this.getMessage("windowCloseConfirm"));
		},
		onExternalEvent: function(params)
		{
			var eventName = BX.prop.getString(params, "key", "");

			if(eventName !== "onCrmEntityMergeComplete")
			{
				return;
			}

			var value = BX.prop.getObject(params, "value", {});

			if(this._contextId !== BX.prop.getString(value, "context", ""))
			{
				return;
			}

			BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			this._externalEventHandler = null;

			this.displaySummary();
			window.setTimeout(
				this.complete.bind(this),
				0
			);
		}
	};
	if(typeof(BX.Crm.BatchMergeManager.messages) === "undefined")
	{
		BX.Crm.BatchMergeManager.messages = {};
	}

	BX.Crm.BatchMergeManager.items = {};
	BX.Crm.BatchMergeManager.getItem = function(id)
	{
		return BX.prop.get(this.items, id, null);
	};
	BX.Crm.BatchMergeManager.create = function(id, settings)
	{
		var self = new BX.Crm.BatchMergeManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}