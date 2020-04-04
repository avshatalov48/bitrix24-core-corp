if(typeof(BX.CrmLocationListView) === "undefined")
{
	BX.CrmLocationListView = function()
	{
		this._contextId = "";
	};
	BX.extend(BX.CrmLocationListView, BX.CrmEntityListView);
	BX.CrmLocationListView.prototype.doInitialize = function()
	{
		this._contextId = this.getSetting("contextId", "");
		BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));
	};
	BX.CrmLocationListView.prototype.initializeFromExternalData = function()
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
	BX.CrmLocationListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_location_list" }, true, false);
	};
	BX.CrmLocationListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_itemcategory_item" }, true, true);
	};
	BX.CrmLocationListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "LOCATION", register) : null;
	};
	BX.CrmLocationListView.prototype.createItemView = function(settings)
	{
		return BX.CrmLocationListItemView.create(settings);
	};
	BX.CrmLocationListView.prototype.createSearchParams = function(val)
	{
		return { NEEDLE: val };
	};
	BX.CrmLocationListView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmLocationListView.prototype.isInSelectMode = function()
	{
		return this.getSetting("mode", "") === "SELECTOR";
	};
	BX.CrmLocationListView.prototype.processItemSelection = function(item)
	{
		if(!this.isInSelectMode())
		{
			return;
		}
		var m = item.getModel();
		if(!m)
		{
			return;
		}

		var serviceUrl = this.getSetting("serviceUrl", "");
		if(serviceUrl === "")
		{
			this.notifyItemSelected(m);
			return;
		}

		var selectedItemId = m.getIntParam("ID");

		var context = BX.CrmMobileContext.getCurrent();
		context.showPopupLoader();
		var self = this;
		BX.ajax(
			{
				url: serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION" : "SAVE_RECENT_USED_LOCATION",
					"ID": selectedItemId
				},
				onsuccess: function(data)
				{
					context.hidePopupLoader();
					self.notifyItemSelected(m);
				},
				onfailure: function(data)
				{
					context.hidePopupLoader();
				}
			}
		);

	};
	BX.CrmLocationListView.prototype.notifyItemSelected = function(itemModel)
	{
		if(!itemModel)
		{
			return;
		}

		var eventArgs =
		{
			contextId: this.getContextId(),
			id: itemModel.getIntParam("ID"),
			name: itemModel.getStringParam("NAME"),
			regionName: itemModel.getStringParam("REGION_NAME"),
			countryName: itemModel.getStringParam("COUNTRY_NAME"),
			title: itemModel.getStringParam("TITLE")
		};

		var context = BX.CrmMobileContext.getCurrent();
		context.riseEvent("onCrmLocationSelect", eventArgs, 2);
		window.setTimeout(context.createBackHandler(), 0);
	};
	BX.CrmLocationListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmLocationListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmLocationListView.prototype._onAfterPageOpen = function()
	{
		this.initializeFromExternalData();
	};
	BX.CrmLocationListView.create = function(id, settings)
	{
		var self = new BX.CrmLocationListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmLocationListView.messages) === "undefined")
	{
		BX.CrmLocationListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmLocationListItemView) === "undefined")
{
	BX.CrmLocationListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
		this._hasLayout = false;
		this._containerClickHandler = BX.delegate(this._onContainerClick, this);
	};
	BX.extend(BX.CrmLocationListItemView, BX.CrmEntityView);
	BX.CrmLocationListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);
		this._hasLayout = !!this._container;
		if(this._hasLayout)
		{
			BX.bind(this._container, "click", this._containerClickHandler);

			if(!this._model)
			{
				var id = this._container.getAttribute("data-entity-id");
				if(BX.type.isNotEmptyString(id))
				{
					this._model = this._dispatcher.getModelById(id);
				}
			}
		}
	};
	BX.CrmLocationListItemView.prototype.layout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		this._container = BX.create("LI", { attrs: { "class": "crm_itemcategory_item" } });
		this._list.addItemView(this);

		if(this._list.isInSelectMode())
		{
			BX.addClass(this._container, "crm_arrow");
			BX.bind(this._container, "click", this._containerClickHandler);
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		var title = "";
		var legend = "";

		var name = m.getStringParam("NAME");
		var regionName = m.getStringParam("REGION_NAME");
		var countryName = m.getStringParam("COUNTRY_NAME");

		if(name !== "")
		{
			title = name;
			if(regionName !== "")
			{
				legend = regionName;
			}
			if(countryName !== "")
			{
				if(legend !== '')
				{
					legend += ', ';
				}
				legend += countryName;
			}
		}
		else if(regionName !== "")
		{
			title = regionName;
			if(countryName !== "")
			{
				legend = countryName;
			}
		}
		else if(countryName !== "")
		{
			title = countryName;
		}

		var titleWrapper = BX.create("DIV", { attrs: { className: "crm_itemcategory_title" } });
		this._container.appendChild(titleWrapper);
		titleWrapper.appendChild(document.createTextNode(title));

		if(legend !== "")
		{
			this._container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: "crm_category_desc" },
						children:
						[
							BX.create("SPAN", { text: legend })
						]
					}
				)
			);
		}

		this._container.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);

		this._hasLayout = true;
	};
	BX.CrmLocationListItemView.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._list.removeItemView(this);
		BX.unbind(this._container, "click", this._containerClickHandler);
		this._container = null;

		this._hasLayout = false;
	};
	BX.CrmLocationListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmLocationListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmLocationListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmLocationListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmLocationListItemView.prototype._onContainerClick = function(e)
	{
		this._list.processItemSelection(this);
	};
	BX.CrmLocationListItemView.create = function(settings)
	{
		var self = new BX.CrmLocationListItemView();
		self.initialize(settings);
		return self;
	};
}
