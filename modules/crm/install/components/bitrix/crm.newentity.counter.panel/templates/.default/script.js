if(typeof(BX.CrmNewEntityCounterPanel) === "undefined")
{
	BX.CrmNewEntityCounterPanel = function ()
	{
		this._id = "";
		this._settings = {};
		this._userId = 0;

		this._pullTagName = "";
		this._pullCommands = null;

		this._entityTypeId = 0;
		this._categoryId = null;
		this._lastEntityId = 0;
		this._gridId = "";

		this._counter = 0;
		
		this._wrapper = null;
		this._container = null;
		this._counterContainer = null;
		this._counterWrapper = null;

		this._previousNode = null;
		this._currentNode = null;
		this._nextNode = null;

		this._refreshHandle = 0;

		this._refreshCallback = BX.delegate(this.doRefresh, this);
		this._refreshSuccessCallback = BX.delegate(this.onRefreshSuccess, this);
		this._resetCallback = BX.delegate(this.reset, this);
		this._resetSuccessCallback = BX.delegate(this.onResetSuccess, this);
		this._clickCallback = BX.delegate(this.onClick, this);

		this._gridReloadCallback = BX.delegate(this.onGridReload, this);
		this._pullCallback = BX.delegate(this.onPullEvent, this);

		this._isShown = false;
		this._hasLayout = false;
	};

	BX.CrmNewEntityCounterPanel.prototype =
	{
		initialize: function (id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._userId = BX.prop.getInteger(this._settings, "userId", 0);
			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._categoryId = BX.prop.getInteger(this._settings, "categoryId", null);
			this._lastEntityId = BX.prop.getInteger(this._settings, "lastEntityId", 0);
			this._gridId = BX.prop.getString(this._settings, "gridId", "");

			this._wrapper = BX(BX.prop.getString(this._settings, "wrapperId", ""));
			if(!BX.type.isElementNode(this._wrapper))
			{
				throw "BX.CrmNewEntityCounterPanel: Could not find wrapper node.";
			}

			this._isShown = this._wrapper.style.display !== "none";

			this._container = BX(BX.prop.getString(this._settings, "containerId", ""));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmNewEntityCounterPanel: Could not find container node.";
			}

			this._counterContainer = BX(BX.prop.getString(this._settings, "counterContainerId"));
			if(!BX.type.isElementNode(this._counterContainer))
			{
				throw "BX.CrmNewEntityCounterPanel: Could not find valueContainer.";
			}
			BX.Dom.clean(this._counterContainer);

			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			if(this._serviceUrl === "")
			{
				throw "BX.CrmNewEntityCounterPanel: Could not find serviceUrl.";
			}

			this._pullTagName = BX.prop.getString(this._settings, "pullTagName", "");
			this._pullCommands = BX.prop.getObject(this._settings, "pullCommands", []);
			if(this._pullTagName !== "")
			{
				BX.addCustomEvent("onPullEvent-crm", this._pullCallback);
				this.extendWatch();
			}

			if(this._gridId !== "")
			{
				BX.addCustomEvent(window, "Grid::beforeRequest", this._gridReloadCallback);
			}

			this.layout();
		},
		release: function()
		{
			BX.removeCustomEvent("onPullEvent-crm", this._pullCallback);
			BX.removeCustomEvent(window, "Grid::beforeRequest", this._gridReloadCallback);
		},
		getId: function()
		{
			return this._id;
		},
		resolveEntityCommand: function(pullCommand)
		{
			for(var key in this._pullCommands)
			{
				if(!this._pullCommands.hasOwnProperty(key))
				{
					continue;
				}

				if(pullCommand === this._pullCommands[key])
				{
					return key;
				}
			}

			return "";
		},
		onGridReload: function(sender, args)
		{
			if(this._gridId === BX.prop.getString(args, "gridId", ""))
			{
				this.cancelRefresh();
				window.setTimeout(this._resetCallback, 0);
			}
		},
		onPullEvent: function(command, params)
		{
			var entityCommand = this.resolveEntityCommand(command);
			if(entityCommand === "")
			{
				return;
			}

			if(entityCommand === "add" || entityCommand === "remove")
			{
				this.cancelRefresh();
				this.refresh();
			}
		},
		extendWatch: function()
		{
			if(BX.type.isFunction(BX.PULL) && this._pullTagName !== "")
			{
				BX.PULL.extendWatch(this._pullTagName);
				window.setTimeout(BX.delegate(this.extendWatch, this), 1000);
			}
		},
		refresh: function()
		{
			this._refreshHandle = window.setTimeout(this._refreshCallback, 60000);
		},
		cancelRefresh: function()
		{
			if(this._refreshHandle)
			{
				window.clearTimeout(this._refreshHandle);
				this._refreshHandle = 0;
			}
		},
		doRefresh: function()
		{
			this._refreshHandle = 0;

			BX.ajax(
				{
					url: BX.util.add_url_param(this._serviceUrl, { "action": "GET_NEW_ENTITY_IDS" }),
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION": "GET_NEW_ENTITY_IDS",
							"LAST_ENTITY_ID": this._lastEntityId,
							"ENTITY_TYPE_ID": this._entityTypeId,
							"CATEGORY_ID": BX.Type.isNumber(this._categoryId) ? this._categoryId : '',
						},
					onsuccess: this._refreshSuccessCallback
				}
			);
		},
		onRefreshSuccess: function(data)
		{
			this.setupCounter(
				BX.prop.getInteger(
					BX.prop.getObject(data, "DATA", null),
					"NEW_ENTITY_COUNT",
					0
				)
			);
		},
		reset: function()
		{
			this.setupCounter(0);

			BX.ajax(
				{
					url: BX.util.add_url_param(this._serviceUrl, { "action": "GET_LAST_ENTITY_ID" }),
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION": "GET_LAST_ENTITY_ID",
							"ENTITY_TYPE_ID": this._entityTypeId
						},
					onsuccess: this._resetSuccessCallback
				}
			);
		},
		onResetSuccess: function(data)
		{
			this._lastEntityId = BX.prop.getInteger(
				BX.prop.getObject(data, "DATA", null),
				"LAST_ENTITY_ID",
				this._lastEntityId
			);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._counterWrapper = BX.create("div", { attrs: { className: "crm-alert-entity-counter-animate-wrap" } });

			this._nextNode = BX.create("span",
				{
					attrs: { className: "crm-alert-entity-counter-plus" }
				}
			);
			this._counterWrapper.appendChild(this._nextNode);

			this._currentNode = BX.create("span",
				{
					attrs: { className: "crm-alert-entity-counter-origin" }
				}
			);
			this._counterWrapper.appendChild(this._currentNode);

			this._previousNode = BX.create("span",
				{
					attrs: { className: "crm-alert-entity-counter-minus" }
				}
			);
			this._counterWrapper.appendChild(this._previousNode);
			this._counterContainer.appendChild(this._counterWrapper);

			if(this._counter <= 99)
			{
				this._nextNode.innerHTML = (this._counter + 1).toString();
				this._currentNode.innerHTML = this._counter.toString();
				this._previousNode.innerHTML = (this._counter - 1).toString();
			}
			else
			{
				this._nextNode.innerHTML = "";
				this._currentNode.innerHTML = "99+";
				this._previousNode.innerHTML = "";
			}

			BX.bind(this._container, "click", this._clickCallback);

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._container, "click", this._clickCallback);

			this._counterWrapper = BX.remove(this._counterWrapper);
			this._previousNode = this._currentNode = this._nextNode = null;
			this._hasLayout = false;
		},
		setupCounter: function(value)
		{
			var diff = value - this._counter;
			if(diff === 0)
			{
				return;
			}

			var className = diff > 0
				? "crm-alert-entity-counter-animate-plus"
				: "crm-alert-entity-counter-animate-minus";

			BX.addClass(this._counterWrapper, className);
			setTimeout(
				BX.delegate(
					function()
					{

						BX.removeClass(this._counterWrapper, className );
						this._counterWrapper.style.marginTop = '';

						this._counter += diff;
						if(this._counter < 0)
						{
							this._counter = 0;
						}

						if(this._counter <= 99)
						{
							this._currentNode.innerHTML = this._counter.toString();
							this._previousNode.innerHTML = (this._counter - 1).toString();
							this._nextNode.innerHTML = (this._counter + 1).toString();
						}
						else
						{
							this._currentNode.innerHTML = this._previousNode.innerHTML = this._nextNode.innerHTML = "99+";
						}

						if(this._counter > 0 && !this._isShown)
						{
							this.show();
						}
						else if(this._counter === 0 && this._isShown)
						{
							this.hide();
						}

					},
					this
				),
				0
			);
		},
		show: function()
		{
			this._wrapper.style.display = "block";
			window.setTimeout(
				BX.delegate(
					function()
					{
						BX.removeClass(this._wrapper, "crm-alert-entity-counter-animate-hide");
						BX.addClass(this._wrapper, "crm-alert-entity-counter-animate-show");
					},
					this
				),
				0
			);
			this._isShown = true;
		},
		hide: function()
		{
			BX.removeClass(this._wrapper, "crm-alert-entity-counter-animate-show");
			BX.addClass(this._wrapper, "crm-alert-entity-counter-animate-hide");

			window.setTimeout(
				BX.delegate(function(){ this._wrapper.style.display = "none"; }, this),
				500
			);
			this._isShown = false;
		},
		reloadGrid: function()
		{
			if(this._gridId !== "")
			{
				BX.Main.gridManager.reload(this._gridId);
			}
		},
		onClick: function(e)
		{
			this.reloadGrid();
		}
	};
	BX.CrmNewEntityCounterPanel.items = {};
	BX.CrmNewEntityCounterPanel.getItem = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	BX.CrmNewEntityCounterPanel.removeItemById = function(id)
	{
		if(!this.items.hasOwnProperty(id))
		{
			return;
		}

		this.items[id].release();
		delete this.items[id];
	};
	BX.CrmNewEntityCounterPanel.create = function(id, settings)
	{
		var self = new BX.CrmNewEntityCounterPanel();
		self.initialize(id, settings);

		this.items[self.getId()] = self;
		return self;
	};
}