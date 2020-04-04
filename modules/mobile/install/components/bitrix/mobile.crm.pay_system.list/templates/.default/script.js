if(typeof(BX.CrmPaySystemListView) === "undefined")
{
	BX.CrmPaySystemListView = function()
	{
		this._contextId = "";
		this._personTypeId = 0;
	};
	BX.extend(BX.CrmPaySystemListView, BX.CrmEntityListView);
	BX.CrmPaySystemListView.prototype.doInitialize = function()
	{
		this._contextId = this.getSetting("contextId", "");
		this._personTypeId = parseInt(this.getSetting("personTypeId", 0));

		BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));

	};
	BX.CrmPaySystemListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_contact_list_people_list" }, true, false);
	};
	BX.CrmPaySystemListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_contact_list_people" }, true, true);
	};
	BX.CrmPaySystemListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "PAY_SYSTEM", register) : null;
	};
	BX.CrmPaySystemListView.prototype.createItemView = function(settings)
	{
		return BX.CrmPaySystemListItemView.create(settings);
	};
	BX.CrmPaySystemListView.prototype.getMode = function()
	{
		return this.getSetting("mode", "");
	};
	BX.CrmPaySystemListView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmPaySystemListView.prototype.getPersonTypeId = function()
	{
		return this._personTypeId;
	};
	BX.CrmPaySystemListView.prototype._onAfterPageOpen = function()
	{
		this.initializeFromExternalData();
	};
	BX.CrmPaySystemListView.prototype.initializeFromExternalData = function()
	{
		var self = this;
		BX.CrmMobileContext.getCurrent().getPageParams(
			{
				callback: function(data)
				{
					if(data)
					{
						var contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
						var personTypeId = BX.type.isNumber(data["personTypeId"]) ? data["personTypeId"] : "";

						if(!(contextId === self._contextId && personTypeId === self._personTypeId))
						{
							self._contextId = contextId;
							self._personTypeId = personTypeId;
							self.reload(self._prepareReloadUrl(), true);
						}
					}
				}
			}
		);
	};
	BX.CrmPaySystemListView.prototype._prepareReloadUrl = function()
	{
		return this.getSetting("reloadUrlTemplate", "")
			.replace("#person_type_id#", this._personTypeId);
	};
	BX.CrmPaySystemListView.create = function(id, settings)
	{
		var self = new BX.CrmPaySystemListView();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmPaySystemListItemView) === "undefined")
{
	BX.CrmPaySystemListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmPaySystemListItemView, BX.CrmEntityView);
	BX.CrmPaySystemListItemView.prototype.doInitialize = function()
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
	BX.CrmPaySystemListItemView.prototype.layout = function()
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
	BX.CrmPaySystemListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmPaySystemListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmPaySystemListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmPaySystemListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmPaySystemListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmPaySystemListItemView.prototype._onContainerClick = function(e)
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
				contextId: this._list.getContextId(),
				personTypeId: this._list.getPersonTypeId()
			};
			context.riseEvent("onCrmPaySystemSelect", eventArgs, 2);
			context.back();
		}
	};
	BX.CrmPaySystemListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmPaySystemListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmPaySystemListItemView.create = function(settings)
	{
		var self = new BX.CrmPaySystemListItemView();
		self.initialize(settings);
		return self;
	};
}
