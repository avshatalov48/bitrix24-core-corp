if(typeof(BX.CrmEventListView) === "undefined")
{
	BX.CrmEventListView = function()
	{
	};
	BX.extend(BX.CrmEventListView, BX.CrmEntityListView);
	BX.CrmEventListView.prototype.doInitialize = function()
	{
	};
	BX.CrmEventListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_dealings_list" }, true, false);
	};
	BX.CrmEventListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_history_list_item" }, true, true);
	};
	BX.CrmEventListView.prototype.getWaiterClassName = function()
	{
		return "crm_history_list_item_wait";
	};
	BX.CrmEventListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "EVENT", register) : null;
	};
	BX.CrmEventListView.prototype.createItemView = function(settings)
	{
		return BX.CrmEventListItemView.create(settings);
	};
	BX.CrmEventListView.prototype.createSearchParams = function(val)
	{
		return { SUBJECT: val };
	};
	BX.CrmEventListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmEventListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmEventListView.prototype._processClearSearchClick = function()
	{
		if(this.isFiltered())
		{
			this.applyFilterPreset(this.findFilterPreset("clear_filter"));
		}
		return true;
	};

	BX.CrmEventListView.create = function(id, settings)
	{
		var self = new BX.CrmEventListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmEventListView.messages) === "undefined")
	{
		BX.CrmEventListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmEventListItemView) === "undefined")
{
	BX.CrmEventListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmEventListItemView, BX.CrmEntityView);
	BX.CrmEventListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);

		if(!this._model && this._container)
		{
			var id = this._container.getAttribute("data-entity-id");
			if(BX.type.isNotEmptyString(id))
			{
				this._model = this._dispatcher.getModelById(id);
			}
		}

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmEventListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_history_list_item" }
					//events: { "click": BX.delegate(this._onContainerClick, this) }
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
					attrs: { className: "crm_history_title" },
					text: m.getStringParam("EVENT_NAME")
				}
			)
		);

		var text1 = m.getStringParam("EVENT_TEXT_1");
		var text2 = m.getStringParam("EVENT_TEXT_2");

		var descr = BX.create("DIV", { attrs: { className: "crm_history_descr" } });
		this._container.appendChild(descr);

		descr.innerHTML = text1;
		if(text2 !== "")
		{
			descr.innerHTML += " &rarr; ";
			descr.innerHTML += text2;
		}

		var legend = BX.create("DIV",
			{
				attrs: { className: "crm_history_cnt" }
			}
		);
		this._container.appendChild(legend);
		legend.innerHTML = m.getStringParam("DATE_CREATE") + ", " + m.getStringParam("CREATED_BY_FORMATTED_NAME");

		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "clb" }
				}
			)
		);
	};
	BX.CrmEventListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmEventListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmEventListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmEventListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmEventListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmEventListItemView.prototype.redirectToView = function()
	{
		var m = this._model;
		if(!m)
		{
			return;
		}

		var showUrl = m.getDataParam("SHOW_URL", "");
		if(showUrl !== "")
		{
			BX.CrmMobileContext.redirect({ url: showUrl });
		}
	};
	BX.CrmEventListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmEventListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmEventListItemView.create = function(settings)
	{
		var self = new BX.CrmEventListItemView();
		self.initialize(settings);
		return self;
	};
}
