if(typeof(BX.CrmCurrencyListView) === "undefined")
{
	BX.CrmCurrencyListView = function()
	{
	};
	BX.extend(BX.CrmCurrencyListView, BX.CrmEntityListView);
	BX.CrmCurrencyListView.prototype.doInitialize = function()
	{
		this._contextId = this.getSetting("contextId", "");

		BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));
	}
	BX.CrmCurrencyListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_contact_list_people_list" }, true, false);
	};
	BX.CrmCurrencyListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_contact_list_people" }, true, true);
	};
	BX.CrmCurrencyListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "CURRENCY", register) : null;
	};
	BX.CrmCurrencyListView.prototype.createItemView = function(settings)
	{
		return BX.CrmCurrencyListItemView.create(settings);
	};
	BX.CrmCurrencyListView.prototype.getMode = function()
	{
		return this.getSetting("mode", "");
	};
	BX.CrmCurrencyListView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmCurrencyListView.prototype.initializeFromExternalData = function()
	{
		var self = this;
		BX.CrmMobileContext.getCurrent().getPageParams(
			{
				callback: function(data)
				{
					if(data)
					{
						self._contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
					}
				}
			}
		);
	};
	BX.CrmCurrencyListView.prototype._onAfterPageOpen = function()
	{
		this.initializeFromExternalData();
	};
	BX.CrmCurrencyListView.create = function(id, settings)
	{
		var self = new BX.CrmCurrencyListView();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmCurrencyListItemView) === "undefined")
{
	BX.CrmCurrencyListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmCurrencyListItemView, BX.CrmEntityView);
	BX.CrmCurrencyListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);

		if(this._container)
		{
			BX.bind(this._container, "click", BX.delegate(this._onContainerClick, this));
		}

		if(!this._model && this._container)
		{
			var info = BX.findChild(this._container, { className: "crm_entity_info" }, true, false);
			this._model = info ? this._dispatcher.getModelById(info.value) : null;
		}

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmCurrencyListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_contact_list_people" },
					events: { "click": BX.delegate(this._onContainerClick, this) }
				}
			);

			this._list.addItemView(this);
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm_contactlist_info crm_arrow" },
					children:
					[
						BX.create("STRONG", { text: m.getStringParam("NAME") })
					]
				}
			)
		);
	};
	BX.CrmCurrencyListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmCurrencyListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmCurrencyListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmCurrencyListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmCurrencyListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmCurrencyListItemView.prototype._onContainerClick = function(e)
	{
		var m = this._model;
		if(!m)
		{
			return;
		}
		if(this._list.getMode() === 'SELECTOR')
		{
			var context = BX.CrmMobileContext.getCurrent();
			var eventArgs =
			{
				id: m.getId(),
				name: m.getStringParam("NAME"),
				contextId: this._list.getContextId()
			};
			context.riseEvent("onCrmCurrencySelect", eventArgs);
			context.back();
		}
	};
	BX.CrmCurrencyListItemView.prototype.handleModelUpdate = function(model)
	{
		if(this._model !== model)
		{
			return;
		}

		this.layout();
		if(this._list)
		{
			this._list.handleItemUpdate(this);
		}
	};
	BX.CrmCurrencyListItemView.prototype.handleModelDelete = function(model)
	{
		if(this._model !== model)
		{
			return;
		}

		this.clearLayout();
		if(this._list)
		{
			this._list.handleItemDelete(this);
		}
	};
	BX.CrmCurrencyListItemView.create = function(settings)
	{
		var self = new BX.CrmCurrencyListItemView();
		self.initialize(settings);
		return self;
	};
}
