if(typeof(BX.CrmProgressBarListView) === "undefined")
{
	BX.CrmProgressBarListView = function()
	{
		this._contextId = "";
		this._manager = null;
		this._entityModel = null;
	};
	BX.extend(BX.CrmProgressBarListView, BX.CrmEntityListView);
	BX.CrmProgressBarListView.prototype.doInitialize = function()
	{
		this._contextId = this.getSetting("contextId", "");
		var currentStepId = this.getSetting("currentStepId", "");
		this._entityModel = BX.CrmEntityModel.create(this.getSetting("modelData", {}));
		var selectedItem = currentStepId !== "" ? this.findItemByStepId(currentStepId) : "";
		if(selectedItem)
		{
			this.processItemSelection(selectedItem);
		}

		var entityTypeName = this.getEntityTypeName();
		if(entityTypeName === BX.CrmLeadModel.typeName)
		{
			this._manager = BX.CrmLeadStatusManager.current;
		}
		else if(entityTypeName === BX.CrmDealModel.typeName)
		{
			this._manager = BX.CrmDealStageManager.current;
		}
		else if(entityTypeName === BX.CrmInvoiceModel.typeName)
		{
			this._manager = BX.CrmInvoiceStatusManager.current;
		}

		BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));
	}
	BX.CrmProgressBarListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_block_container" }, true, false);
	};
	BX.CrmProgressBarListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_selector_status" }, true, true);
	};
	BX.CrmProgressBarListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "STATUS", register) : null;
	};
	BX.CrmProgressBarListView.prototype.createItemView = function(settings)
	{
		return BX.CrmProgressBarListItemView.create(settings);
	};
	BX.CrmProgressBarListView.prototype.getMode = function()
	{
		return this.getSetting("mode", "");
	};
	BX.CrmProgressBarListView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmProgressBarListView.prototype.getEntityTypeName = function()
	{
		return this.getSetting("entityTypeName", "");
	};
	BX.CrmProgressBarListView.prototype.getManager = function()
	{
		return this._manager;
	};
	BX.CrmProgressBarListView.prototype.findItemByStepId = function(stepId)
	{
		var items = this._items;
		for(var k in items)
		{
			if(items.hasOwnProperty(k))
			{
				var item = items[k];
				if(stepId === item.getStepId())
				{
					return item;
				}
			}
		}

		return null;
	};
	BX.CrmProgressBarListView.prototype.getEntityModel = function()
	{
		return this._entityModel;
	};
	BX.CrmProgressBarListView.prototype.initializeFromExternalData = function()
	{
		var self = this;
		BX.CrmMobileContext.getCurrent().getPageParams(
			{
				callback: function(data)
				{
					if(data)
					{
						self._contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
						self.setDisabledStepIds(BX.type.isArray(data["disabledStepIds"]) ? data["disabledStepIds"] : []);

						self._entityModel = BX.CrmEntityModel.create(typeof(data["modelData"]) !== "undefined" ? data["modelData"] : {});

						var currentStepId = BX.type.isNotEmptyString(data["currentStepId"]) ? data["currentStepId"] : "";
						var selectedItem = currentStepId !== "" ? self.findItemByStepId(currentStepId) : "";
						if(selectedItem)
						{
							self.processItemSelection(selectedItem);
						}
					}
				}
			}
		);
	};
	BX.CrmProgressBarListView.prototype._onAfterPageOpen = function()
	{
		this.initializeFromExternalData();
	};
	BX.CrmProgressBarListView.prototype.processItemSelection = function(item)
	{
		var items = this._items;
		for(var k in items)
		{
			if(!items.hasOwnProperty(k))
			{
				continue;
			}

			var curItem = items[k];
			if(curItem !== item)
			{
				curItem.setSelected(false);
			}
		}

		item.setSelected(true);
	};

	BX.CrmProgressBarListView.prototype.notify = function(model, additionalData)
	{
		if(!model || this.getMode() !== 'SELECTOR')
		{
			return;
		}

		var context = BX.CrmMobileContext.getCurrent();
		context.riseEvent(
			"onCrmProgressStepSelect",
			{
				id: model.getId(),
				name: model.getStringParam("NAME"),
				typeId: model.getStringParam("TYPE_ID"),
				statusId: model.getStringParam("STATUS_ID"),
				entityTypeName: this.getEntityTypeName(),
				contextId: this.getContextId(),
				additionalData: additionalData
			},
			2
		);
		context.back();
	};
	BX.CrmProgressBarListView.prototype.getDisabledStepIds = function()
	{
		if(!this._disabledStepIds)
		{
			this._disabledStepIds = this.getSetting("disabledStepIds", []);
		}

		return this._disabledStepIds;
	};
	BX.CrmProgressBarListView.prototype.setDisabledStepIds = function(ids)
	{
		this._disabledStepIds = ids;
		for(var i = 0; i < ids.length; i++)
		{
			var id = ids[i];
			var item = this.findItemByStepId(id);
			if(item)
			{
				item.disable(true);
			}
		}
	};
	BX.CrmProgressBarListView.create = function(id, settings)
	{
		var self = new BX.CrmProgressBarListView();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmProgressBarListItemView) === "undefined")
{
	BX.CrmProgressBarListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = this._governor = null;
		this._isSelected = false;
		this._isDisabled = false;
		this._governorReadyHandler = BX.delegate(this._onGovernorReady, this);
	};
	BX.extend(BX.CrmProgressBarListItemView, BX.CrmEntityView);
	BX.CrmProgressBarListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);
		this._isSelected = this.getSetting("isSelected", false);

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
	BX.CrmProgressBarListItemView.prototype.layout = function()
	{
		throw "Not supported.";
	};
	BX.CrmProgressBarListItemView.prototype.clearLayout = function()
	{
		throw "Not supported.";
	};
	BX.CrmProgressBarListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmProgressBarListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmProgressBarListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmProgressBarListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};

	BX.CrmProgressBarListItemView.prototype._onContainerClick = function(e)
	{
		if(this.isDisabled())
		{
			return;
		}

		this._list.processItemSelection(this);
		if(!this._governor)
		{
			this._list.notify(this._model, null);
		}
	};
	BX.CrmProgressBarListItemView.prototype._onGovernorReady = function(governor)
	{
		if(this._governor !== governor)
		{
			return;
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		if(governor.getId() === m.getStringParam("STATUS_ID"))
		{
			this._list.notify(m, governor.prepareData());
		}
	};
	BX.CrmProgressBarListItemView.prototype.isSelected = function()
	{
		return this._isSelected;
	};
	BX.CrmProgressBarListItemView.prototype.isDisabled = function()
	{
		return this._isDisabled;
	};
	BX.CrmProgressBarListItemView.prototype.disable = function(disable)
	{
		disable = !!disable;
		if(this._isDisabled === disable)
		{
			return;
		}

		this._isDisabled = disable;
		if(this._isDisabled)
		{
			BX.addClass(this._container, "crm_selector_status_disabled");
		}
		else
		{
			BX.removeClass(this._container, "crm_selector_status_disabled");
		}

	};
	BX.CrmProgressBarListItemView.prototype.setSelected = function(selected)
	{
		selected = !!selected;
		if(this._isSelected === selected)
		{
			return;
		}

		this._isSelected = selected;

		if(selected)
		{
			if(!BX.hasClass(this._container, "check"))
			{
				BX.addClass(this._container, "check");
			}

			var manager = this._list.getManager();
			this._governor = manager ? manager.prepareGovernor(this._model.getStringParam("STATUS_ID")) : null;
			if(this._governor)
			{
				this._governor.setOnReadyCallback(this._governorReadyHandler);
				this._governor.setModel(this._list.getEntityModel());
				this._governor.layout(this.getContainer());
			}
		}
		else
		{
			if(BX.hasClass(this._container, "check"))
			{
				BX.removeClass(this._container, "check");
			}

			if(this._governor)
			{
				this._governor.clearLayout();
				this._governor.setOnReadyCallback(null);
				this._governor.setModel(null);
				this._governor = null;
			}
		}
	};
	BX.CrmProgressBarListItemView.prototype.getStepId = function()
	{
		return this._model ? this._model.getStringParam("STATUS_ID") : "";
	};
	BX.CrmProgressBarListItemView.create = function(settings)
	{
		var self = new BX.CrmProgressBarListItemView();
		self.initialize(settings);
		return self;
	};
}
