BX.namespace("BX.Crm");

if(typeof(BX.Crm.BatchConversionManager) === "undefined")
{
	BX.Crm.BatchConversionManager = function()
	{
		this._id = "";
		this._settings = {};

		this._gridId = "";
		this._config = null;
		this._entityIds = null;
		this._enableUserFieldCheck = true;
		this._enableConfigCheck = true;

		this._filter = null;

		this._serviceUrl = "";
		this._containerId = "";
		this._errors = null;

		this._progress = null;
		this._hasLayout = false;

		this._succeededItemCount = 0;
		this._failedItemCount = 0;
		this._isRunning = false;
		this._messages = null;

		this._progressChangeHandler = BX.delegate(this.onProgress, this);
		this._documentUnloadHandler = BX.delegate(this.onDocumentUnload, this);
	};
	BX.Crm.BatchConversionManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_batch_conversion_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._gridId = BX.prop.getString(this._settings, "gridId", this._id);
			this._config = BX.prop.getObject(this._settings, "config", {});
			this._entityIds = BX.prop.getArray(this._settings, "entityIds", []);

			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			if(this._serviceUrl === "")
			{
				throw "BX.Crm.BatchConversionManager. Could not find 'serviceUrl' parameter in settings.";
			}

			this._containerId = BX.prop.getString(this._settings, "container", "");
			if(this._containerId === "")
			{
				throw "BX.Crm.BatchConversionManager: Could not find container.";
			}

			//region progress
			this._progress = BX.AutorunProcessManager.create(
				this._id,
				{
					serviceUrl: this._serviceUrl,
					actionName: "PROCESS_BATCH_CONVERSION",
					container: this._containerId,
					enableCancellation: true,
					title: this.getMessage("title"),
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
		getConfig: function()
		{
			return this._config;
		},
		setConfig: function(config)
		{
			this._config = BX.type.isPlainObject(config) ? config : {};
		},
		getEntityIds: function()
		{
			return this._entityIds;
		},
		setEntityIds: function(entityIds)
		{
			this._entityIds = BX.type.isArray(entityIds) ? entityIds : [];
		},
		getFilter: function()
		{
			return this._filter;
		},
		setFilter: function(filter)
		{
			this._filter = BX.type.isPlainObject(filter) ? filter : null;
		},
		isUserFieldCheckEnabled: function()
		{
			return this._enableUserFieldCheck;
		},
		enableUserFieldCheck: function(enableUserFieldCheck)
		{
			this._enableUserFieldCheck = enableUserFieldCheck;
		},
		isConfigCheckEnabled: function()
		{
			return this._enableConfigCheck;
		},
		enableConfigCheck: function(enableConfigCheck)
		{
			this._enableConfigCheck = enableConfigCheck;
		},
		getMessage: function(name)
		{
			if (this._messages && BX.prop.getString(this._messages, name, null))
			{
				return  BX.prop.getString(this._messages, name, name);
			}

			var messages = BX.prop.getObject(this._settings, "messages", BX.Crm.BatchConversionManager.messages);
			return BX.prop.getString(messages, name, name);
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
			var params =
				{
					GRID_ID: this._gridId,
					CONFIG: this._config,
					ENABLE_CONFIG_CHECK: this._enableConfigCheck ? "Y" : "N",
					ENABLE_USER_FIELD_CHECK: this._enableUserFieldCheck ? "Y" : "N"
				};

			if(this._filter !== null)
			{
				params["FILTER"] = this._filter;
			}
			else
			{
				params["IDS"] = this._entityIds;
			}

			var data =
				{
					ACTION: "PREPARE_BATCH_CONVERSION",
					PARAMS: params,
					sessid: BX.bitrix_sessid(),
				};

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: data,
					onsuccess: BX.delegate(this.onPrepare, this)
				}
			);
		},
		onPrepare: function(result)
		{
			var data = result["DATA"];

			var status = BX.prop.getString(data, "STATUS", '');
			this._config = BX.prop.getObject(data, "CONFIG", {});

			if (data.hasOwnProperty('messages') && BX.Type.isPlainObject(data.messages))
			{
				this._messages = data.messages;
				if (!BX.CrmLeadConverter.messages)
				{
					BX.CrmLeadConverter.messages = {};
				}
				BX.CrmLeadConverter.messages = Object.assign(BX.CrmLeadConverter.messages, data.messages);
			}

			if(status === "ERROR")
			{
				var errors = BX.prop.getArray(data, "ERRORS", []);
				var dlg = BX.Crm.NotificationDialog.create(
					"batch_conversion_error",
					{
						title: this.getMessage("title"),
						content: errors.join("<br/>")
					}
				);
				dlg.open();

				return;
			}
			if(status === "REQUIRES_SYNCHRONIZATION")
			{
				var syncEditor = BX.CrmLeadConverter.getCurrent().createSynchronizationEditor(
					this._id,
					this._config,
					BX.prop.getArray(data, "FIELD_NAMES", [])
				);
				syncEditor.addClosingListener(BX.delegate(this.onSynchronizationEditorClose, this));
				syncEditor.show();

				return;
			}

			this.layout();
			this.run();
		},
		run: function()
		{
			if(this._isRunning)
			{
				return;
			}
			this._isRunning = true;

			this._progress.setParams({ "GRID_ID": this._gridId, "CONFIG": this._config });
			this._progress.run();

			BX.addCustomEvent(this._progress, "ON_AUTORUN_PROCESS_STATE_CHANGE", this._progressChangeHandler);
			BX.bind(window, "beforeunload", this._documentUnloadHandler);
		},
		stop: function()
		{
			if(!this._isRunning)
			{
				return;
			}
			this._isRunning = false;

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: { ACTION: "STOP_BATCH_CONVERSION", PARAMS: { GRID_ID: this._gridId } },
					onsuccess: BX.delegate(this.onStop, this)
				}
			);
		},
		onStop: function(result)
		{
			this.reset();

			window.setTimeout(
				function ()
				{
					BX.onCustomEvent(
						window,
						"BX.Crm.BatchConversionManager:onStop",
						[ this ]
					);
				}.bind(this),
				300
			);
		},
		reset: function()
		{
			this._progress.reset();

			BX.removeCustomEvent(this._progress, "ON_AUTORUN_PROCESS_STATE_CHANGE", this._progressChangeHandler);
			BX.unbind(window, "beforeunload", this._documentUnloadHandler);

			if((this._succeededItemCount > 0 || this._failedItemCount > 0) && BX.getClass("BX.Main.gridManager"))
			{
				BX.Main.gridManager.reload(this._gridId);
			}

			this._succeededItemCount = this._failedItemCount = 0;
			this._isRunning = false;

			if(this._hasLayout)
			{
				window.setTimeout(BX.delegate(this.clearLayout, this), 100);
			}

			this._errors = [];
		},
		getSucceededItemCount: function()
		{
			return this._succeededItemCount;
		},
		getFailedItemCount: function()
		{
			return this._failedItemCount;
		},
		getErrors: function()
		{
			return this._errors;
		},
		onDocumentUnload: function(e)
		{
			return(e.returnValue = this.getMessage("windowCloseConfirm"));
		},
		onSynchronizationEditorClose: function(sender, args)
		{
			if(BX.prop.getBoolean(args, "isCanceled", false))
			{
				this.clearLayout();
				return;
			}

			this._config = sender.getConfig();
			this.run();

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
			if(errors.length === 0)
			{
				this._succeededItemCount++;
			}
			else
			{
				if(!this._errors)
				{
					this._errors = errors;
				}
				else
				{
					this._errors = this._errors.concat(errors);
				}

				this._failedItemCount++;
			}

			if(state === BX.AutoRunProcessState.completed)
			{
				BX.Crm.ProcessSummaryPanel.create(
					this._id,
					{
						container: this._containerId,
						data:
							{
								succeededCount: this.getSucceededItemCount(),
								failedCount: this.getFailedItemCount(),
								errors: this.getErrors()
							},
						messages: BX.prop.getObject(this._settings, "messages", null),
						numberSubstitution: "#number_leads#"
					}
				).layout();

				this.reset();

				window.setTimeout(
					function ()
					{
						BX.onCustomEvent(
							window,
							"BX.Crm.BatchConversionManager:onProcessComplete",
							[ this ]
						);
					}.bind(this),
					300
				);
			}
		}

	};
	if(typeof(BX.Crm.BatchConversionManager.messages) === "undefined")
	{
		BX.Crm.BatchConversionManager.messages = {};
	}

	BX.Crm.BatchConversionManager.items = {};
	BX.Crm.BatchConversionManager.getItem = function(id)
	{
		return BX.prop.get(this.items, id, null);
	};
	BX.Crm.BatchConversionManager.create = function(id, settings)
	{
		var self = new BX.Crm.BatchConversionManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}