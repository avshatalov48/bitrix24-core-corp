BX.namespace("BX.Crm");

if(typeof(BX.Crm.BatchDeletionManager) === "undefined")
{
	BX.Crm.BatchDeletionManager = function()
	{
		this._id = "";
		this._settings = {};

		this._gridId = "";
		this._entityTypeId = BX.CrmEntityType.enumeration.undefined;
		this._entityIds = null;
		this._filter = null;
		this._operationHash = "";

		this._containerId = "";
		this._errors = null;

		this._progress = null;
		this._hasLayout = false;

		this._isRunning = false;

		this._progressChangeHandler = BX.delegate(this.onProgress, this);
		this._documentUnloadHandler = BX.delegate(this.onDocumentUnload, this);

		this._summaryLayoutHandler = BX.delegate(this.onSummaryLayout, this);
		this._summaryClearLayoutHandler = BX.delegate(this.onSummaryClearLayout, this);
	};
	BX.Crm.BatchDeletionManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_batch_deletion_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._gridId = BX.prop.getString(this._settings, "gridId", this._id);
			this._entityTypeId = BX.prop.getInteger(
				this._settings,
				"entityTypeId",
				BX.CrmEntityType.enumeration.undefined
			);

			this._containerId = BX.prop.getString(this._settings, "container", "");
			if(this._containerId === "")
			{
				throw "BX.Crm.BatchDeletionManager: Could not find container.";
			}

			//region progress
			this._progress = BX.AutorunProcessManager.create(
				this._id,
				{
					controllerActionName: "crm.api.entity.processDeletion",
					container: this._containerId,
					enableCancellation: true,
					title: this.getMessage("title"),
					timeout: 1000,
					stateTemplate: BX.prop.getString(this._settings, "stateTemplate", "#processed# / #total#"),
					enableLayout: false
				}
			);
			//region
			this._errors = [];
		},
		getId: function()
		{
			return this._id;
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
		getFilter: function()
		{
			return this._filter;
		},
		setFilter: function(filter)
		{
			this._filter = BX.type.isPlainObject(filter) ? filter : null;
		},
		getMessage: function(name)
		{
			var messages = BX.prop.getObject(this._settings, "messages", BX.Crm.BatchDeletionManager.messages);
			return BX.prop.getString(messages, name, name);
		},
		scrollInToView: function()
		{
			if(this._progress)
			{
				this._progress.scrollInToView();
				this.refreshGridHeader();
			}
		},
		refreshGridHeader: function()
		{
			window.requestAnimationFrame(
				function()
				{
					var grid = BX.Main.gridManager.getById(this._gridId);
					if(grid && grid.instance && grid.instance.pinHeader)
					{
						grid.instance.pinHeader.refreshRect();
						grid.instance.pinHeader._onScroll();
					}
				}.bind(this)
			);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._progress.layout();
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._progress.clearLayout();
			this._hasLayout = false;
		},
		getState: function()
		{
			return this._progress.getState();
		},
		getProcessedItemCount: function()
		{
			return this._progress.getProcessedItemCount();
		},
		getTotalItemCount: function()
		{
			return this._progress.getTotalItemCount();
		},
		execute: function()
		{
			var id = this._id.toLowerCase() + "_batch_delete";
			var dialog = BX.Crm.ConfirmationDialog.get(id);
			if(!dialog)
			{
				dialog = BX.Crm.ConfirmationDialog.create(
					id,
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
							this.layout();
							this.run();

							window.setTimeout(this.scrollInToView.bind(this), 100);
						}
					}.bind(this)
				);
			}
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
			this._isRunning = true;

			BX.bind(window, "beforeunload", this._documentUnloadHandler);
			this.enableGridFilter(false);

			var params =
				{
					gridId: this._gridId,
					entityTypeId: this._entityTypeId,
					extras: BX.prop.getObject(this._settings, "extras", {})
				};

			if(BX.type.isArray(this._entityIds) && this._entityIds.length > 0)
			{
				params["entityIds"] = this._entityIds;
			}

			BX.ajax.runAction(
				"crm.api.entity.prepareDeletion",
				{ data: { params:  params } }
			).then(
				function(response)
				{
					var hash = BX.prop.getString(
						BX.prop.getObject(response, "data", {}),
						"hash",
						""
					);

					if(hash === "")
					{
						this.reset();
						return;
					}

					this._operationHash = hash;
					this._progress.setParams({ hash: this._operationHash });
					this._progress.run();

					BX.addCustomEvent(this._progress, "ON_AUTORUN_PROCESS_STATE_CHANGE", this._progressChangeHandler);

				}.bind(this)
			);
		},
		stop: function()
		{
			if(!this._isRunning)
			{
				return;
			}
			this._isRunning = false;

			BX.ajax.runAction(
				"crm.api.entity.cancelDeletion",
				{ data: { params: { hash: this._operationHash } } }
			);

			this.reset();
		},
		reset: function()
		{
			BX.unbind(window, "beforeunload", this._documentUnloadHandler);
			BX.removeCustomEvent(this._progress, "ON_AUTORUN_PROCESS_STATE_CHANGE", this._progressChangeHandler);

			this._isRunning = false;
			this._operationHash = "";
			this._errors = [];

			var enableGridReload = this._progress.getProcessedItemCount() > 0;
			this._progress.reset();

			if(this._hasLayout)
			{
				window.setTimeout(BX.delegate(this.clearLayout, this), 100);
			}

			this.enableGridFilter(true);
			if(enableGridReload)
			{
				BX.Main.gridManager.reload(this._gridId);
			}
		},
		enableGridFilter: function(enable)
		{
			var container = this._gridId !== "" ? BX(this._gridId + "_search_container") : null;
			if(!container)
			{
				return;
			}

			if(enable)
			{
				BX.removeClass(container, "main-ui-disable");
			}
			else
			{
				BX.addClass(container, "main-ui-disable");
			}
		},
		getErrorCount: function()
		{
			return this._errors ? this._errors.length : 0;
		},
		getErrors: function()
		{
			return this._errors ? this._errors : [];
		},
		onDocumentUnload: function(e)
		{
			return(e.returnValue = this.getMessage("windowCloseConfirm"));
		},
		onProgress: function(sender)
		{
			var state = this._progress.getState();
			if(state === BX.AutoRunProcessState.stopped)
			{
				this.stop();
				return;
			}

			var errors = this._progress.getErrors();
			if(errors.length > 0)
			{
				if(!this._errors)
				{
					this._errors = errors;
				}
				else
				{
					this._errors = this._errors.concat(errors);
				}
			}

			if(state === BX.AutoRunProcessState.completed)
			{
				var failed = this.getErrorCount();
				var succeeded = this.getProcessedItemCount() - failed;

				BX.addCustomEvent(window, "BX.Crm.ProcessSummaryPanel:onLayout", this._summaryLayoutHandler);
				BX.Crm.ProcessSummaryPanel.create(
					this._id,
					{
						container: this._containerId,
						data: { succeededCount: succeeded, failedCount: failed, errors: this.getErrors() },
						messages: BX.prop.getObject(this._settings, "messages", null),
						numberSubstitution: "#number#",
						displayTimeout: 1500
					}
				).layout();
				this.reset();

				window.setTimeout(
					function ()
					{
						BX.onCustomEvent(
							window,
							"BX.Crm.BatchDeletionManager:onProcessComplete",
							[ this ]
						);
					}.bind(this),
					300
				);
			}
		},
		onSummaryLayout: function()
		{
			this.refreshGridHeader();
			BX.removeCustomEvent(window, "BX.Crm.ProcessSummaryPanel:onLayout", this._summaryLayoutHandler);
			BX.addCustomEvent(window, "BX.Crm.ProcessSummaryPanel:onClearLayout", this._summaryClearLayoutHandler);
		},
		onSummaryClearLayout: function()
		{
			this.refreshGridHeader();
			BX.removeCustomEvent(window, "BX.Crm.ProcessSummaryPanel:onClearLayout", this._summaryClearLayoutHandler);
		}
	};
	if(typeof(BX.Crm.BatchDeletionManager.messages) === "undefined")
	{
		BX.Crm.BatchDeletionManager.messages = {};
	}

	BX.Crm.BatchDeletionManager.items = {};
	BX.Crm.BatchDeletionManager.getItem = function(id)
	{
		return BX.prop.get(this.items, id, null);
	};
	BX.Crm.BatchDeletionManager.create = function(id, settings)
	{
		var self = new BX.Crm.BatchDeletionManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}