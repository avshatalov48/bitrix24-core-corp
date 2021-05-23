if(typeof(BX.CrmStatusListView) === "undefined")
{
	BX.CrmStatusListView = function()
	{
	};
	BX.extend(BX.CrmStatusListView, BX.CrmEntityListView);
	BX.CrmStatusListView.prototype.doInitialize = function()
	{
	}
	BX.CrmStatusListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_contact_list_people_list" }, true, false);
	};
	BX.CrmStatusListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_contact_list_people" }, true, true);
	};
	BX.CrmStatusListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "STATUS", register) : null;
	};
	BX.CrmStatusListView.prototype.createItemView = function(settings)
	{
		return BX.CrmStatusListItemView.create(settings);
	};
	BX.CrmStatusListView.prototype.getMode = function()
	{
		return this.getSetting("mode", "");
	};
	BX.CrmStatusListView.prototype.getContextId = function()
	{
		return this.getSetting("contextId", "");
	};
	BX.CrmStatusListView.create = function(id, settings)
	{
		var self = new BX.CrmStatusListView();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmStatusListItemView) === "undefined")
{
	BX.CrmStatusListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmStatusListItemView, BX.CrmEntityView);
	BX.CrmStatusListItemView.prototype.doInitialize = function()
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
	BX.CrmStatusListItemView.prototype.layout = function()
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
	BX.CrmStatusListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmStatusListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmStatusListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmStatusListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmStatusListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmStatusListItemView.prototype._onContainerClick = function(e)
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
				typeId: m.getStringParam("TYPE_ID"),
				statusId: m.getStringParam("STATUS_ID"),
				contextId: this._list.getContextId()
			};
			context.riseEvent("onCrmStatusSelect", eventArgs);
			context.back();
		}
	};
	BX.CrmStatusListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmStatusListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmStatusListItemView.create = function(settings)
	{
		var self = new BX.CrmStatusListItemView();
		self.initialize(settings);
		return self;
	};
}
