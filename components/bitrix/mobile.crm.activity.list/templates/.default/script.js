if(typeof(BX.CrmActivityListView) === "undefined")
{
	BX.CrmActivityListView = function()
	{
		this._isDirty = false;
		this._pageAfterOpenHandler = BX.delegate(this._onAfterPageOpen, this);
	};
	BX.extend(BX.CrmActivityListView, BX.CrmEntityListView);
	BX.CrmActivityListView.prototype.doInitialize = function()
	{
		this._stub = BX(this.getSetting("stubId"));

		BX.addCustomEvent("onOpenPageAfter", this._pageAfterOpenHandler);

		var permissions = this.getSetting("permissions", {});
		if(permissions["CREATE"])
		{
			BX.CrmMobileContext.getCurrent().createButtons(
				{
					menuButton:
					{
						type: 'context-menu',
						style: 'custom',
						callback: BX.delegate(this._onMenuButtonClick, this)
					}
				}
			);
		}
	};
	BX.CrmActivityListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_company_list" }, true, false);
	};
	BX.CrmActivityListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_company_list_item" }, true, true);
	};
	BX.CrmActivityListView.prototype.getWaiterClassName = function()
	{
		return "crm_company_list_item_wait";
	};
	BX.CrmActivityListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "ACTIVITY", register) : null;
	};
	BX.CrmActivityListView.prototype.createItemView = function(settings)
	{
		return BX.CrmActivityListItemView.create(settings);
	};
	BX.CrmActivityListView.prototype.createSearchParams = function(val)
	{
		return { SUBJECT: val };
	};
	BX.CrmActivityListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmActivityListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmActivityListView.prototype._onMenuButtonClick = function()
	{
		var permissions = this.getSetting("permissions", {});
		if(permissions["CREATE"])
		{
			var context = BX.CrmMobileContext.getCurrent();
			var menuItems =
				[
					{
						icon: 'add',
						name:  this.getMessage('menuCreateCall'),
						action: BX.delegate(this._onCallCreate, this)
					},
					{
						icon: 'add',
						name:  this.getMessage('menuCreateMeeting'),
						action: BX.delegate(this._onMeetingCreate, this)
					},
					{
						icon: 'add',
						name:  this.getMessage('menuCreateEmail'),
						action: BX.delegate(this._onEmailCreate, this)
					}
				];
			context.createMenu(menuItems);
			context.showMenu();
		}
	};
	BX.CrmActivityListView.prototype._onSearchRequestCompleted = function()
	{
		this._postLayout();
	};
	BX.CrmActivityListView.prototype._onAfterVisibilityChange = function()
	{
		this._postLayout();
	};
	BX.CrmActivityListView.prototype._postLayout = function()
	{
		if(!this.isVisible())
		{
			return;
		}

		if(this.hasItems())
		{
			this._stub.style.display = 'none';
			this._container.style.display = '';
		}
		else
		{
			this._container.style.display = 'none';
			this._stub.style.display = ''
		}
	};
	BX.CrmActivityListView.prototype._onCallCreate = function()
	{
		var url = this.getSetting('callEditUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmActivityListView.prototype._onMeetingCreate = function()
	{
		var url = this.getSetting('meetingEditUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmActivityListView.prototype._onEmailCreate = function()
	{
		var url = this.getSetting('emailEditUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmActivityListView.prototype._processClearSearchClick = function()
	{
		if(this.isFiltered())
		{
			this.applyFilterPreset(this.findFilterPreset("clear_filter"));
		}
		return true;
	};
	BX.CrmActivityListView.prototype._processExternalCreate = function(eventArgs)
	{
		this._isDirty = true;
	};
	BX.CrmActivityListView.prototype._onAfterPageOpen = function()
	{
		if(this._isDirty && this.reload(this.getSetting("reloadUrl", true)))
		{
			this._isDirty = false;
		}
	};
	BX.CrmActivityListView.create = function(id, settings)
	{
		var self = new BX.CrmActivityListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmActivityListView.messages) === "undefined")
	{
		BX.CrmActivityListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmActivityListItemView) === "undefined")
{
	BX.CrmActivityListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmActivityListItemView, BX.CrmEntityView);
	BX.CrmActivityListItemView.prototype.doInitialize = function()
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
	BX.CrmActivityListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_company_list_item" },
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

		var isExpired = m.getBoolParam("IS_EXPIRED", false);
		var isImportant = m.getBoolParam("IS_IMPORTANT", false);
		var isCompleted = m.getBoolParam("COMPLETED", false);

		if(isCompleted)
		{
			this._container.style.backgroundColor = "#f5f6f8";
		}
		else if(isExpired)
		{
			this._container.style.backgroundColor = "#fae9e7";
		}


		var imageUrl = m.getStringParam("LIST_IMAGE_URL");
		if(imageUrl !== "")
		{
			this._container.appendChild(
				BX.create("IMG",
					{
						attrs: { src: imageUrl },
						style: { width:"28px", padding:"10px 7px 0 8px", float:"left" }
					}
				)
			);

		}

		var title = BX.create("A",
			{
				attrs: { className: "crm_company_title" },
				text: m.getStringParam("SUBJECT")
			}
		);
		if(isImportant)
		{
			title.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm_important" },
						text: this._list.getMessage("important")
					}
				)
			);
		}

		if(isCompleted)
		{
			title.style.color = "#7c8182";
			title.style.textDecoration = "line-through";
		}

		this._container.appendChild(title);

		var detailContainer = BX.create(
			"DIV",
			{
				attrs: { className: "crm_company_company" }
			}
		);

		var time = m.getStringParam("END_TIME");
		if(time === "")
		{
			time = this._list.getMessage("emptyTime")
		}

		if(isExpired)
		{
			var timeData =
			{
				attrs: { className: "fwb" },
				style: { color:"#e20707" },
				text: time
			};

			detailContainer.appendChild(BX.create("SPAN", timeData));
		}
		else
		{
			detailContainer.appendChild(document.createTextNode(time));
		}

		var ownerTitle = m.getStringParam("OWNER_TITLE");
		if(ownerTitle !== "")
		{
			detailContainer.appendChild(document.createTextNode(" - "));
			detailContainer.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "fwb" },
						text: ownerTitle
					}
				)
			);
		}

		this._container.appendChild(detailContainer);

		var delimiterData = { attrs: { className: "clb" } };
		if(isImportant)
		{
			delimiterData.style = { marginBottom:"10px" };
		}

		this._container.appendChild(
			BX.create("DIV", delimiterData)
		);
	};
	BX.CrmActivityListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmActivityListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmActivityListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmActivityListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmActivityListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmActivityListItemView.prototype.redirectToView = function()
	{
		var m = this._model;
		if(!m)
		{
			return;
		}

		var showUrl = m.getDataParam("SHOW_URL", "");
		if(showUrl === "")
		{
			return;
		}

		var params = { url: showUrl };
		//Crutch for Task view
		if(m.getIntParam("TYPE_ID", 0) === 3)
		{
			params["bx24ModernStyle"] = true;
		}
		BX.CrmMobileContext.redirect(params);
	};
	BX.CrmActivityListItemView.prototype._onContainerClick = function(e)
	{
		this.redirectToView();
	};
	BX.CrmActivityListItemView.prototype._onImageClick = function(e)
	{
		this.redirectToView();
		return BX.PreventDefault(e);
	};
	BX.CrmActivityListItemView.prototype._onTitleClick = function(e)
	{
		this.redirectToView();
		return BX.PreventDefault(e);
	};
	BX.CrmActivityListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmActivityListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmActivityListItemView.create = function(settings)
	{
		var self = new BX.CrmActivityListItemView();
		self.initialize(settings);
		return self;
	};
}
