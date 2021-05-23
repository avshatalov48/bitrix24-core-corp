if(typeof(BX.CrmEntityCounterPanel) === "undefined")
{
	BX.CrmEntityCounterPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._userId = 0;
		this._userName = "";
		this._entityTypeId = 0;
		this._extras = null;
		this._codes = null;
		this._data = null;
		this._totalInfo = null;
		this._items = null;
		this._counterManager = null;
		this._container = null;
		this._valueContainer = null;
		this._stubContainer = null;
	};

	BX.CrmEntityCounterPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._userId = BX.prop.getInteger(this._settings, "userId", 0);
			this._userName = BX.prop.getString(this._settings, "userName", this._userId);

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._extras = BX.prop.getObject(this._settings, "extras", {});
			this._codes = BX.prop.getArray(this._settings, "codes", []);

			if(BX.CrmEntityType.isDefined(this._entityTypeId))
			{
				this._counterManager = BX.Crm.EntityCounterManager.create(
					this._id,
					{
						entityTypeId: this._entityTypeId,
						codes: this._codes,
						extras: this._extras,
						serviceUrl: BX.prop.getString(this._settings, "serviceUrl", "")
					}
				);
			}

			this._data = BX.prop.getObject(this._settings, "data", {});
			this._totalInfo = BX.prop.getObject(this._settings, "totalInfo", {});

			this._container = BX(this.getSetting("containerId", ""));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmEntityCounterPanel: Could not find container.";
			}

			this._valueContainer = BX(BX.prop.getString(this._settings, "valueContainerId"));
			if(!BX.type.isElementNode(this._valueContainer))
			{
				throw "BX.CrmEntityCounterPanel: Could not find valueContainer.";
			}

			this._stubContainer = BX(BX.prop.getString(this._settings, "stubContainerId"));
			if(!BX.type.isElementNode(this._stubContainer))
			{
				throw "BX.CrmEntityCounterPanel: Could not find stubContainer.";
			}

			this._items = [];
			var itemContainers = this._valueContainer.querySelectorAll("a.crm-counter-container");
			for(var i = 0, l = itemContainers.length; i < l; i++)
			{
				var container = itemContainers[i];
				var code = container.getAttribute("data-entity-counter-code");
				if(!BX.type.isNotEmptyString(code))
				{
					continue;
				}

				var data = BX.prop.getObject(this._data, code, null);
				if(!data)
				{
					continue;
				}

				this._items.push(
					BX.CrmEntityCounterPanelItem.create(
						code,
						{ parent: this, data: data, container: container }
					)
				);
			}

			BX.addCustomEvent("onPullEvent-main", BX.delegate(this.onPullEvent, this));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		processItemSelection: function(item)
		{
			var typeId = item.getTypeId();
			if(typeId > 0)
			{
				var eventArgs =
					{
						userId: this._userId.toString(),
						userName: this._userName,
						counterTypeId: typeId.toString(),
						cancel: false
					};
				BX.onCustomEvent(window, "BX.CrmEntityCounterPanel:applyFilter", [this, eventArgs]);
				if(eventArgs.cancel)
				{
					return false;
				}
			}
			return true;
		},
		onPullEvent: function(command, params)
		{
			if (command !== "user_counter")
			{
				return;
			}

			var isChanged = false;
			var data = BX.prop.getObject(params, BX.message("SITE_ID"), {});
			for (var code in data)
			{
				if(!data.hasOwnProperty(code))
				{
					continue;
				}

				//HACK: Skip of CRM counter reset
				if(!(code.indexOf("crm") === 0 && data[code] >= 0))
				{
					continue;
				}

				if (!this._data.hasOwnProperty(code))
				{
					continue;
				}

				if(this._data[code].hasOwnProperty("VALUE")
					&& BX.convert.toNumber(this._data[code]["VALUE"]) === BX.convert.toNumber(data[code])
				)
				{
					continue;
				}

				this._data[code]["VALUE"] = data[code];
				if(!isChanged)
				{
					isChanged = true;
				}
			}

			if(isChanged)
			{
				this.prepareTotalInfo();
				this.refreshLayout();
			}
		},
		prepareTotalInfo: function()
		{
			var total = 0;
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				total += this._items[i].getValue();
			}

			var caption = BX.CrmMessageHelper.getCurrent().prepareEntityNumberDeclension(
				total,
				BX.prop.getObject(this._settings, "entityNumberDeclensions", {})
			);

			this._totalInfo = { value: total, caption: caption };
		},
		refreshLayout: function()
		{
			var totalValue = BX.prop.getInteger(this._totalInfo, "value", 0);
			if(totalValue > 0)
			{
				if(this._stubContainer.style.display !== "none")
				{
					this._stubContainer.style.display = "none";
				}

				if(this._valueContainer.style.display !== "")
				{
					this._valueContainer.style.display = "";
				}
				
				for(var i = 0, length = this._items.length; i < length; i++)
				{
					this._items[i].refreshLayout();
				}
			}
			else
			{
				if(this._valueContainer.style.display !== "none")
				{
					this._valueContainer.style.display = "none";
				}

				if(this._stubContainer.style.display !== "")
				{
					this._stubContainer.style.display = "";
				}
			}
		}
	};

	BX.CrmEntityCounterPanel.create = function(id, settings)
	{
		var self = new BX.CrmEntityCounterPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmEntityCounterPanelItem) === "undefined")
{
	BX.CrmEntityCounterPanelItem = function()
	{
		this._id = "";
		this._settings = {};
		this._parent = null;
		this._container = null;
		this._data = null;

		this._clickHandler = BX.delegate(this.onClick, this);
	};

	BX.CrmEntityCounterPanelItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._parent = BX.prop.get(this._settings, "parent");
			if(!this._parent)
			{
				throw "BX.CrmEntityCounterPanelItem: Could not find parent.";
			}

			this._data = BX.prop.getObject(this._settings, "data", {});
			this._container = BX.prop.getElementNode(this._settings, "container");
			if(!this._container)
			{
				throw "BX.CrmEntityCounterPanelItem: Could not find container.";
			}

			BX.bind(this._container, "click", this._clickHandler);
		},
		getId: function()
		{
			return this._id;
		},
		getTypeId: function()
		{
			return BX.prop.getInteger(this._data, "TYPE_ID", 0);
		},
		getValue: function()
		{
			return BX.prop.getInteger(this._data, "VALUE", 0);
		},
		refreshLayout: function()
		{
			var v = this.getValue();
			var valueWrapper = this._container.querySelector(".crm-counter-number");
			if(valueWrapper)
			{
				valueWrapper.innerHTML = v;
			}

			this._container.style.display = v > 0 ? "" : "none";
		},
		onClick: function(e)
		{
			if(!this._parent.processItemSelection(this))
			{
				return BX.PreventDefault(e);
			}
		}
	};

	BX.CrmEntityCounterPanelItem.create = function(id, settings)
	{
		var self = new BX.CrmEntityCounterPanelItem();
		self.initialize(id, settings);
		return self;
	};
}
