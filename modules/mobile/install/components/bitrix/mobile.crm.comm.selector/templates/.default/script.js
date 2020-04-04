if(typeof(BX.CrmCommSelectorView) === "undefined")
{
	BX.CrmCommSelectorView = function()
	{
		this._contextId = "";
		this._communicationType = "";
		this._ownerId = "";
		this._ownerType = "";
		this._stubContainer = null;
	};

	BX.extend(BX.CrmCommSelectorView, BX.CrmEntityListView);
	BX.CrmCommSelectorView.prototype.doInitialize = function()
	{
		this._contextId = this.getSetting("contextId", "");
		this._communicationType = this.getSetting("communicationType", "");
		this._ownerId = this.getSetting("ownerId", "");
		this._ownerType = this.getSetting("ownerType", "");

		BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));
	};
	BX.CrmCommSelectorView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_list_tel_list" }, true, false);
	};
	BX.CrmCommSelectorView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_list_tel" }, true, true);
	};
	BX.CrmCommSelectorView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "COMMUNICATION", register) : null;
	};
	BX.CrmCommSelectorView.prototype.createItemView = function(settings)
	{
		return BX.CrmCommSelectorItemView.create(settings);
	};
	BX.CrmCommSelectorView.prototype.createSearchParams = function(val)
	{
		return { NEEDLE: val };
	};
	BX.CrmCommSelectorView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmCommSelectorView.prototype.initializeFromExternalData = function()
	{
		var self = this;
		BX.CrmMobileContext.getCurrent().getPageParams(
			{
				callback: function(data)
				{
					if(data)
					{
						var contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
						var communicationType = BX.type.isNotEmptyString(data["communicationType"]) ? data["communicationType"] : "";
						var ownerId = BX.type.isNotEmptyString(data["ownerId"]) ? data["ownerId"] : "";
						var ownerType = BX.type.isNotEmptyString(data["ownerType"]) ? data["ownerType"] : "";

						if(!(contextId === self._contextId
							&& communicationType === self._communicationType
							&& ownerId === self._ownerId
							&& ownerType === self._ownerType))
						{
							self._contextId = contextId;
							self._communicationType = communicationType;
							self._ownerId = ownerId;
							self._ownerType = ownerType;

							self.clearSearchInput();
							self.reload(self._prepareReloadUrl(), true);
						}
					}
				}
			}
		);
	};
	BX.CrmCommSelectorView.prototype._prepareReloadUrl = function()
	{
		return this.getSetting("reloadUrlTemplate", "")
			.replace("#type#", this._communicationType)
			.replace("#owner_id#", this._ownerId)
			.replace("#owner_type#", this._ownerType);
	};
	BX.CrmCommSelectorView.prototype._onReloadRequestCompleted = function(data)
	{
		var resultData = data["DATA"] ? data["DATA"] : {};
		if(BX.type.isBoolean(resultData["SHOW_SEARCH_PANEL"]))
		{
			this.clearSearchInput();
			this.enableSearch(resultData["SHOW_SEARCH_PANEL"]);
			if(BX.type.isNotEmptyString(resultData["SEARCH_PLACEHOLDER"]))
			{
				this.setSearchInputPlaceholder(resultData["SEARCH_PLACEHOLDER"]);
			}
		}
	};
	BX.CrmCommSelectorView.prototype._onAfterPageOpen = function()
	{
		this.initializeFromExternalData();
	};
	BX.CrmCommSelectorView.prototype._createStub = function()
	{
		this._stubContainer = BX.create("DIV",
			{
				attrs: { className: "crm_block_container" },
				children:
				[
					BX.create("DIV",
						{
							attrs: { className: "crm_contact_info tac" },
							children:
							[
								BX.create("STRONG",
									{
										style: { color: "#9ca9b6", fontSize: "15px", display: "inline-block", margin: "50px 0" },
										text: this.getMessage("nothingFound")
									}
								)
							]
						}
					)
				]
			}
		);
		this._wrapper.appendChild(this._stubContainer);
	};
	BX.CrmCommSelectorView.prototype._hasStub = function()
	{
		return this._stubContainer !== null;
	};
	BX.CrmCommSelectorView.prototype._removeStub = function()
	{
		if(this._stubContainer !== null)
		{
			BX.remove(this._stubContainer);
			this._stubContainer = null;
		}
	};
	BX.CrmCommSelectorView.prototype.getMessage = function(name)
	{
		var m = BX.CrmCommSelectorView.messages;
		return BX.type.isNotEmptyString(m[name]) ? m[name] : "";
	};
	if(typeof(BX.CrmCommSelectorView.messages) === "undefined")
	{
		BX.CrmCommSelectorView.messages = {};
	}
	BX.CrmCommSelectorView.create = function(id, settings)
	{
		var self = new BX.CrmCommSelectorView();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmCommSelectorItemView) === "undefined")
{
	BX.CrmCommSelectorItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container;
		this._containerClickHandler = BX.delegate(this._onContainerClick, this);
		this._hasLayout = false;
	};
	BX.extend(BX.CrmCommSelectorItemView, BX.CrmEntityView);
	BX.CrmCommSelectorItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);

		this._container = this.getSetting("container", null);
		if(this._container)
		{
			this._hasLayout = true;
			BX.bind(this._container, "click", this._containerClickHandler);
		}

		this._model = this.getSetting("model", null);
		if(!this._model && this._container)
		{
			var key = this._container.getAttribute("data-item-key");
			if(BX.type.isNotEmptyString(key))
			{
				this._model = this._dispatcher.getModelByKey(key);
			}
		}

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmCommSelectorItemView.prototype.layout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		var rootContainer = this.getSetting("rootContainer", null);
		this._container = BX.create("LI",
			{
				attrs: { "class": "crm_list_tel" }
			}
		);
		BX.bind(this._container, "click", this._containerClickHandler);
		rootContainer.appendChild(this._container);

		var commSummary = "";
		var comms = m.getDataParam("COMMUNICATIONS", []);
		for(var i = 0; i < comms.length; i++)
		{
			if(i > 0)
			{
				commSummary += ", ";
			}
			commSummary += comms[i]["VALUE"];
		}
		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm_contactlist_tel_info crm_arrow" },
					children:
					[
						BX.create("IMG", { attrs: { src: m.getStringParam("IMAGE_URL") } }),
						BX.create("STRONG", { text: m.getStringParam("TITLE") }),
						BX.create("SPAN", { text: m.getStringParam("DESCRIPTION") }),
						BX.create("STRONG", { style: { fontSize: "12px" }, text: commSummary })
					]
				}
			)
		);

		this._container.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);

		this._hasLayout = true;
	};
	BX.CrmCommSelectorItemView.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		BX.unbind(this._container, "click", this._containerClickHandler);

		BX.cleanNode(this._container, true);
		this._container = null;
	};
	BX.CrmCommSelectorItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmCommSelectorItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};

	BX.CrmCommSelectorItemView.prototype._onContainerClick = function(e)
	{
		var m = this._model;
		if(!m)
		{
			return;
		}

		var communicationType = "";
		var communicationValue = "";
		var communications = m.getDataParam("COMMUNICATIONS", []);
		if(communications.length > 0)
		{
			var communication = communications[0];
			communicationType = communication["TYPE"];
			communicationValue = communication["VALUE"];
		}

		var context = BX.CrmMobileContext.getCurrent();
		var eventArgs =
		{
			title: m.getStringParam("TITLE"),
			type: communicationType,
			ownerId: m.getIntParam("OWNER_ID"),
			ownerType: m.getStringParam("OWNER_TYPE_NAME"),
			value: communicationValue,
			contextId: this._list.getContextId()
		};
		context.riseEvent("onCrmCommunicationSelect", eventArgs);
		context.back();
	};

	BX.CrmCommSelectorItemView.create = function(settings)
	{
		var self = new BX.CrmCommSelectorItemView();
		self.initialize(settings);
		return self;
	};
}
