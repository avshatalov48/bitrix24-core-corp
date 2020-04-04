if(typeof(BX.CrmInvoiceEventListView) === "undefined")
{
	BX.CrmInvoiceEventListView = function()
	{
	};
	BX.extend(BX.CrmInvoiceEventListView, BX.CrmEntityListView);
	BX.CrmInvoiceEventListView.prototype.doInitialize = function()
	{
	};
	BX.CrmInvoiceEventListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_dealings_list" }, true, false);
	};
	BX.CrmInvoiceEventListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_history_list_item" }, true, true);
	};
	BX.CrmInvoiceEventListView.prototype.getWaiterClassName = function()
	{
		return "crm_history_list_item_wait";
	};
	BX.CrmInvoiceEventListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "INVOICE_EVENT", register) : null;
	};
	BX.CrmInvoiceEventListView.prototype.createItemView = function(settings)
	{
		return BX.CrmInvoiceEventListItemView.create(settings);
	};
	BX.CrmInvoiceEventListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmInvoiceEventListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};

	BX.CrmInvoiceEventListView.create = function(id, settings)
	{
		var self = new BX.CrmInvoiceEventListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmInvoiceEventListView.messages) === "undefined")
	{
		BX.CrmInvoiceEventListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmInvoiceEventListItemView) === "undefined")
{
	BX.CrmInvoiceEventListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmInvoiceEventListItemView, BX.CrmEntityView);
	BX.CrmInvoiceEventListItemView.prototype.doInitialize = function()
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
	BX.CrmInvoiceEventListItemView.prototype.layout = function()
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
					text: m.getStringParam("NAME")
				}
			)
		);

		var descr = BX.create("DIV",
			{
				attrs: { className: "crm_history_descr" },
				html: m.getStringParam("DESCRIPTION_HTML")
			}
		);
		this._container.appendChild(descr);

		var legend = BX.create("DIV",
			{
				attrs: { className: "crm_history_cnt" }
			}
		);
		this._container.appendChild(legend);
		legend.innerHTML = m.getStringParam("DATE_CREATE") + ", " + m.getStringParam("USER_FORMATTED_NAME");

		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "clb" }
				}
			)
		);
	};
	BX.CrmInvoiceEventListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmInvoiceEventListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmInvoiceEventListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmInvoiceEventListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmInvoiceEventListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmInvoiceEventListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmInvoiceEventListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmInvoiceEventListItemView.create = function(settings)
	{
		var self = new BX.CrmInvoiceEventListItemView();
		self.initialize(settings);
		return self;
	};
}
