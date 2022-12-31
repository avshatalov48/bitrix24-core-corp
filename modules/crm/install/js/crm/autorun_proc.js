BX.namespace("BX.Crm");

if(typeof(BX.AutoRunProcessState) === "undefined")
{
	BX.AutoRunProcessState =
	{
		intermediate: 0,
		running: 1,
		completed: 2,
		stopped: 3,
		error: 4
	};
}

if(typeof(BX.AutorunProcessManager) === "undefined")
{
	BX.AutorunProcessManager = function()
	{
		this._id = "";
		this._settings = {};

		this._serviceUrl = "";
		this._actionName = "";

		this._controllerActionName = "";

		this._params = null;

		this._container = null;
		this._panel = null;
		this._runHandle = 0;

		this._hasLayout = false;

		this._state = BX.AutoRunProcessState.intermediate;
		this._processedItemCount = 0;
		this._totalItemCount = 0;
		this._errors = null;
	};
	BX.AutorunProcessManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_lrp_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			this._actionName = BX.prop.getString(this._settings, "actionName", "");
			this._controllerActionName = BX.prop.getString(this._settings, "controllerActionName", "");

			if(this._serviceUrl === "" && this._controllerActionName === "")
			{
				throw "AutorunProcessManager: Either the serviceUrl or controllerActionName parameter must be specified.";
			}

			this._container = BX(this.getSetting("container"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "AutorunProcessManager: Could not find container.";
			}

			this._params = BX.prop.getObject(this._settings, "params", null);
			if(BX.prop.getBoolean(this._settings, "enableLayout", false))
			{
				this.layout();
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getTimeout: function()
		{
			return BX.prop.getInteger(this._settings, "timeout", 2000);
		},
		getMessage: function(name)
		{
			var m = BX.AutorunProcessManager.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getParams: function()
		{
			return this._params;
		},
		setParams: function(params)
		{
			this._params = params;
		},
		isHidden: function()
		{
			return !this._hasLayout || this._panel.isHidden();
		},
		show: function()
		{
			if(this._hasLayout)
			{
				this._panel.show();
			}
		},
		hide: function()
		{
			if(this._hasLayout)
			{
				this._panel.hide();
			}
		},
		scrollInToView: function()
		{
			if(this._panel)
			{
				this._panel.scrollInToView();
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			if(!this._panel)
			{
				var title = BX.prop.getString(this._settings, "title", "");
				if(title === "")
				{
					title = this.getMessage("title");
				}

				var stateTemplate = BX.prop.getString(this._settings, "stateTemplate", "");
				if(stateTemplate === "")
				{
					stateTemplate = this.getMessage("stateTemplate");
				}

				this._panel = BX.AutorunProcessPanel.create(
					this._id,
					{
						manager: this,
						container: this._container,
						enableCancellation: BX.prop.getBoolean(this._settings, "enableCancellation", false),
						title: title,
						stateTemplate: stateTemplate
					}
				);
			}
			this._panel.layout();
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._panel.clearLayout();
			this._hasLayout = false;
		},
		getPanel: function()
		{
			return this._panel;
		},
		setPanel: function(panel)
		{
			this._panel = panel;

			if(this._panel)
			{
				this._panel.setManager(this);
				this._hasLayout =  this._panel.hasLayout();
			}
			else
			{
				this._hasLayout = false;
			}
		},
		refresh: function()
		{
			if(!this._hasLayout)
			{
				this.layout();
			}

			if(this._panel.isHidden())
			{
				this._panel.show();
			}
			this._panel.onManagerStateChange();
		},
		getState: function()
		{
			return this._state;
		},
		getProcessedItemCount: function()
		{
			return this._processedItemCount;
		},
		getTotalItemCount: function()
		{
			return this._totalItemCount;
		},
		getErrorCount: function()
		{
			return this._errors ? this._errors.length : 0;
		},
		getErrors: function()
		{
			return this._errors ? this._errors : [];
		},
		run: function()
		{
			if(this._state === BX.AutoRunProcessState.stopped)
			{
				this._state = BX.AutoRunProcessState.intermediate;
			}
			this.startRequest();
		},
		runAfter: function(timeout)
		{
			this._runHandle = window.setTimeout(BX.delegate(this.run, this), timeout);
		},
		stop: function()
		{
			this._state = BX.AutoRunProcessState.stopped;
			BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
		},
		reset: function()
		{
			if(this._runHandle > 0)
			{
				window.clearTimeout(this._runHandle);
				this._runHandle = 0;
			}

			if(this._panel && this._panel.isHidden())
			{
				this._panel.show();
			}

			this._processedItemCount = this._totalItemCount = 0;
			this._error = "";
			this._errorExtras = null;
		},
		startRequest: function()
		{
			if(this._state === BX.AutoRunProcessState.stopped)
			{
				return;
			}

			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;

			this._state = BX.AutoRunProcessState.running;

			var data = {};
			if(this._serviceUrl !== "")
			{
				if(this._actionName !== "")
				{
					data["ACTION"] = this._actionName;
				}

				if(this._params)
				{
					data["PARAMS"] = this._params;
				}
				data.sessid = BX.bitrix_sessid();

				BX.ajax(
					{
						url: this._serviceUrl,
						method: "POST",
						dataType: "json",
						data: data,
						onsuccess: BX.delegate(this.onRequestSuccess, this),
						onfailure: BX.delegate(this.onRequestFailure, this)
					}
				);
			}
			else
			{
				if(this._params)
				{
					data["params"] = this._params;
				}

				BX.ajax.runAction(
					this._controllerActionName,
					{ data: data }
				).then(
					function(result){ this.onRequestSuccess(BX.prop.getObject(result, "data", {})); }.bind(this),
					function(result){ this.onRequestFailure(BX.prop.getObject(result, "data", {})); }.bind(this)
				);
			}
	   },
		onRequestSuccess: function(result)
		{
			this._requestIsRunning = false;
			if(this._state === BX.AutoRunProcessState.stopped)
			{
				return;
			}

			if(this._serviceUrl !== "")
			{
				var status = BX.prop.getString(result, "STATUS", "");

				if(status === "ERROR")
				{
					this._state = BX.AutoRunProcessState.error;
				}
				else if(status === "COMPLETED")
				{
					this._state = BX.AutoRunProcessState.completed;
				}

				if(this._state === BX.AutoRunProcessState.error)
				{
					this._errors = BX.prop.getArray(result, "ERRORS", []);
					if(this._errors.length === 0)
					{
						this._errors.push({ "message": this.getMessage("requestError") });
					}
				}
				else
				{
					this._processedItemCount = BX.prop.getInteger(result, "PROCESSED_ITEMS", 0);
					this._totalItemCount = BX.prop.getInteger(result, "TOTAL_ITEMS", 0);
					this._errors = BX.prop.getArray(result, "ERRORS", []);
				}
			}
			else
			{
				status = BX.prop.getString(result, "status", "");

				if(status === "ERROR")
				{
					this._state = BX.AutoRunProcessState.error;
				}
				else if(status === "COMPLETED")
				{
					this._state = BX.AutoRunProcessState.completed;
				}

				if(this._state === BX.AutoRunProcessState.error)
				{
					this._errors = BX.prop.getArray(result, "errors", []);
					if(this._errors.length === 0)
					{
						this._errors.push({ "message": this.getMessage("requestError") });
					}
				}
				else
				{
					this._processedItemCount = BX.prop.getInteger(result, "processedItems", 0);
					this._totalItemCount = BX.prop.getInteger(result, "totalItems", 0);
					this._errors = BX.prop.getArray(result, "errors", []);
				}
			}

			this.refresh();
			if(this._state === BX.AutoRunProcessState.running)
			{
				window.setTimeout(BX.delegate(this.startRequest, this), this.getTimeout());
			}
			else if(this._state === BX.AutoRunProcessState.completed
				&& BX.prop.getBoolean(this._settings, "hideAfterComplete", true)
			)
			{
				this.hide();
			}

			BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
		},
		onRequestFailure: function(result)
		{
			this._requestIsRunning = false;

			this._state = BX.AutoRunProcessState.error;
			this._error = this.getMessage("requestError");

			this.refresh();
			BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
		}
	};
	if(typeof(BX.AutorunProcessManager.messages) === "undefined")
	{
		BX.AutorunProcessManager.messages = {};
	}
	BX.AutorunProcessManager.items = {};
	BX.AutorunProcessManager.create = function(id, settings)
	{
		var self = new BX.AutorunProcessManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
	BX.AutorunProcessManager.createIfNotExists = function(id, settings)
	{
		if(this.items.hasOwnProperty(id))
		{
			return this.items[id];
		}

		var self = new BX.AutorunProcessManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.AutorunProcessPanel) === "undefined")
{
	BX.AutorunProcessPanel = function()
	{
		this._id = "";
		this._settings = {};

		this._manager = null;
		this._container = null;
		this._wrapper = null;
		this._stateNode = null;
		this._progressNode = null;
		this._hasLayout = false;
		this._isHidden = false;
	};
	BX.AutorunProcessPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("container"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "AutorunProcessPanel: Could not find container.";
			}

			this._manager = this.getSetting("manager");
			this._isHidden = this.getSetting("isHidden", false);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		scrollInToView: function()
		{
			if(!this._container)
			{
				return;
			}

			var rect = BX.pos(this._container);
			if(window.scrollY > rect.top)
			{
				window.scrollTo(window.scrollX, rect.top);
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-view-progress" } });
			BX.addClass(this._wrapper, this._isHidden ? "crm-view-progress-hide" : "crm-view-progress-show crm-view-progress-bar-active");

			this._container.appendChild(this._wrapper);

			this._wrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-view-progress-info" },
						text: this.getSetting("title", "Please wait...")
					}
				)
			);

			this._progressNode = BX.create("DIV", { attrs: { className: "crm-view-progress-bar-line" } });
			this._stateNode = BX.create("DIV", { attrs: { className: "crm-view-progress-steps" } });
			this._wrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-view-progress-inner" },
						children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-view-progress-bar" },
									children: [ this._progressNode ]
								}
							),
							this._stateNode
						]
					}
				)
			);

			if(BX.prop.getBoolean(this._settings, "enableCancellation", false))
			{
				this._wrapper.appendChild(
					BX.create("a",
						{
							attrs: { className: "crm-view-progress-link", href: "#" },
							text: BX.message("JS_CORE_WINDOW_CANCEL"),
							events: { click: BX.delegate(this.onCancelButtonClick, this) }
						}
					)
				);
			}

			this._hasLayout = true;
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		isHidden: function()
		{
			return this._isHidden;
		},
		show: function()
		{
			if(!this._isHidden)
			{
				return;
			}

			if(!this._hasLayout)
			{
				return;
			}

			BX.removeClass(this._wrapper, "crm-view-progress-hide");
			BX.addClass(this._wrapper, "crm-view-progress-show");

			this._isHidden = false;
		},
		hide: function()
		{
			if(this._isHidden)
			{
				return;
			}

			if(!this._hasLayout)
			{
				return;
			}

			BX.removeClass(this._wrapper, "crm-view-progress-show");
			BX.addClass(this._wrapper, "crm-view-progress-hide");

			this._isHidden = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.remove(this._wrapper);
			this._wrapper = this._stateNode = null;

			this._hasLayout = false;
		},
		getManager: function()
		{
			return this._manager;
		},
		setManager: function(manager)
		{
			this._manager = manager;
		},
		onManagerStateChange: function()
		{
			if(!(this._hasLayout && this._manager))
			{
				return;
			}

			var state = this._manager.getState();
			if(state !== BX.AutoRunProcessState.error)
			{
				var processed = this._manager.getProcessedItemCount();
				var total = this._manager.getTotalItemCount();

				var progress = 0;
				if(total !== 0)
				{
					progress = Math.floor((processed / total) * 100);
					var offset = progress % 5;
					if(offset !== 0)
					{
						progress -= offset;
					}
				}

				this._stateNode.innerHTML = (processed > 0 && total > 0)
					? this.getSetting("stateTemplate", "#processed# from #total#").replace('#processed#', processed).replace('#total#', total)
					: "";

				this._progressNode.className = "crm-view-progress-bar-line";
				if(progress > 0)
				{
					this._progressNode.className += " crm-view-progress-line-" + progress.toString();
				}
			}
		},
		onCancelButtonClick: function(e)
		{
			this._manager.stop();
			return BX.eventReturnFalse(e);
		}
	};
	BX.AutorunProcessPanel.items = {};
	BX.AutorunProcessPanel.isExists = function(id)
	{
		return this.items.hasOwnProperty(id);
	};

	BX.AutorunProcessPanel.create = function(id, settings)
	{
		var self = new BX.AutorunProcessPanel();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.Crm.ProcessSummaryPanel) === "undefined")
{
	BX.Crm.ProcessSummaryPanel = function()
	{
		this._id = "";
		this._settings = {};

		this._data = null;
		this._container = null;
		this._wrapper = null;
	};

	BX.Crm.ProcessSummaryPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._container = BX(BX.prop.get(this._settings, "container"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BatchConversionPanel: Could not find container.";
			}
			this._data = BX.prop.getObject(this._settings, "data", {});
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var messages = BX.prop.getObject(this._settings, "messages", BX.Crm.ProcessSummaryPanel.messages);
			return BX.prop.getString(messages, name, name);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-view-progress" } });
			BX.addClass(this._wrapper, this._isHidden ? "crm-view-progress-hide" : "crm-view-progress-show");
			BX.addClass(this._wrapper, "crm-view-progress-row-hidden");

			this._container.appendChild(this._wrapper);

			var summaryElements = [ BX.create("span", { text: this.getMessage("summaryCaption") }) ];

			var substitution = new RegExp(BX.prop.getString(this._settings, "numberSubstitution", "#number#"), "ig");

			var succeeded = BX.prop.getInteger(this._data, "succeededCount", 0);
			if(succeeded > 0)
			{
				summaryElements.push(
					BX.create("span",
						{
							attrs: { className: "crm-view-progress-text" },
							text: this.getMessage("summarySucceeded").replace(substitution, succeeded)
						}
					)
				);
			}

			var failed = BX.prop.getInteger(this._data, "failedCount", 0);
			if(failed > 0)
			{
				summaryElements.push(
					BX.create("span",
						{
							attrs: { className: "crm-view-progress-link crm-view-progress-text-button" },
							text: this.getMessage("summaryFailed").replace(substitution, failed),
							events: { click: BX.delegate(this.onToggleErrorButtonClick, this)  }
						}
					)
				);
			}

			var elements = [];
			elements.push(
				BX.create("DIV",
					{
						attrs: { className: "crm-view-progress-info" },
						children: summaryElements
					}
				)
			);

			elements.push(
				BX.create("a",
					{
						attrs: { className: "crm-view-progress-link", href: "#" },
						text: BX.message("JS_CORE_WINDOW_CLOSE"),
						events: { click: BX.delegate(this.onCloseButtonClick, this) }
					}
				)
			);

			this._wrapper.appendChild(
				BX.create("DIV", {
					attrs: { className: "crm-view-progress-row" },
					children: elements
				})
			);

			var errors = BX.prop.getArray(this._data, "errors", []);
			if(errors.length > 0)
			{
				for(var i = 0, length = errors.length; i < length; i++)
				{
					var error = errors[i];
					var errorElements = [];

					var info = BX.prop.getObject(
						BX.prop.getObject(error, "customData", {}),
						"info",
						null
					);

					if(info)
					{
						var title = BX.prop.getString(info, "title", "");
						var showUrl = BX.prop.getString(info, "showUrl", "");

						if(title !== "" && showUrl !== "")
						{
							errorElements.push(
								BX.create(
									"a",
									{
										props: { className: "crm-view-progress-link", href: showUrl, target: "_blank" },
										text: title + ":"
									}
								)
							);
						}
					}

					errorElements.push(
						BX.create("span",
							{
								attrs: { className: "crm-view-progress-text" },
								text: error["message"]
							}
						)
					);

					this._wrapper.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "crm-view-progress-row" },
								children:
									[
										BX.create("DIV",
											{
												attrs: { className: "crm-view-progress-info" },
												children: errorElements
											}
										)
									]
							}
						)
					);
				}
			}
			else
			{
				var timeout = this.getDisplayTimeout();
				if(timeout > 0)
				{
					window.setTimeout(function(){ this.clearLayout(); }.bind(this), timeout);
				}
			}
			this._hasLayout = true;

			BX.onCustomEvent(window, "BX.Crm.ProcessSummaryPanel:onLayout", [ this ]);
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		isHidden: function()
		{
			return this._isHidden;
		},
		show: function()
		{
			if(!this._isHidden)
			{
				return;
			}

			if(!this._hasLayout)
			{
				return;
			}

			BX.removeClass(this._wrapper, "crm-view-progress-hide");
			BX.addClass(this._wrapper, "crm-view-progress-show");

			this._isHidden = false;
		},
		hide: function()
		{
			if(this._isHidden)
			{
				return;
			}

			if(!this._hasLayout)
			{
				return;
			}

			BX.removeClass(this._wrapper, "crm-view-progress-show");
			BX.addClass(this._wrapper, "crm-view-progress-hide");

			this._isHidden = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.remove(this._wrapper);
			this._wrapper = null;

			this._hasLayout = false;

			BX.onCustomEvent(window, "BX.Crm.ProcessSummaryPanel:onClearLayout", [ this ]);
		},
		getDisplayTimeout: function()
		{
			return BX.prop.getInteger(this._settings, "displayTimeout", 0);
		},
		onCloseButtonClick: function(e)
		{
			this.clearLayout();
			return BX.eventReturnFalse(e);
		},
		onToggleErrorButtonClick: function ()
		{
			BX.toggleClass(this._wrapper, "crm-view-progress-row-hidden");
		}
	};

	if(typeof(BX.Crm.ProcessSummaryPanel.messages) === "undefined")
	{
		BX.Crm.ProcessSummaryPanel.messages = {};
	}

	BX.Crm.ProcessSummaryPanel.create = function(id, settings)
	{
		var self = new BX.Crm.ProcessSummaryPanel();
		self.initialize(id, settings);
		return self;
	}
}
