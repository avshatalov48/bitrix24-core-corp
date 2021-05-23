BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityEditorMultipleUser === "undefined")
{
	BX.Crm.EntityEditorMultipleUser = function()
	{
		BX.Crm.EntityEditorMultipleUser.superclass.constructor.apply(this);

		this._input = null;
		this._userSelector = null;

		this._map = null;
		this._infos = null;
		this._items = null;
		this._innerWrapper = null;

		this._topButton = null;
		this._bottomButton = null;

		this._topButtonClickHandler = BX.delegate(this.onTopButtonClick, this);
		this._bottomButtonClickHandler = BX.delegate(this.onBottomButtonClick, this);
	};
	BX.extend(BX.Crm.EntityEditorMultipleUser, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorMultipleUser.prototype.isSingleEditEnabled = function()
	{
		return true;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.doInitialize = function()
	{
		this._map = this._schemeElement.getDataObjectParam("map", {});
		this.initializeItems();
	};
	BX.Crm.EntityEditorMultipleUser.prototype.initializeItems = function()
	{
		this._infos = this._model.getSchemeField(this._schemeElement, "infos", []);
		this._items = [];
		for(var i = 0, length = this._infos.length; i < length; i++)
		{
			this.addItem(this._infos[i]);
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.getItemCount = function()
	{
		return this._items !== null ? this._items.length : 0;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.findItemIndexById = function(id)
	{
		if(!BX.type.isNumber(id))
		{
			id = parseInt(id);
			if(isNaN(id))
			{
				id = 0;
			}
		}

		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i].getValue() === id)
			{
				return i;
			}
		}

		return -1;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.findItemIndex = function(item)
	{
		if(!this._items)
		{
			return -1;
		}

		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}

		return -1;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.addItem = function(data)
	{
		var item = BX.Crm.EntityEditorMultipleUserItem.create("", { parent: this, data: data });

		if(this._items === null)
		{
			this._items = [];
		}

		this._items.push(item);

		if(this._hasLayout)
		{
			item.setMode(this._mode);
			item.setContainer(this._innerWrapper);
			item.layout();
		}

		return item;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.deleteItem = function(item)
	{
		if(!this._items)
		{
			return;
		}

		var index = this.findItemIndex(item);
		if(index >= 0)
		{
			item.clearLayout();
			item.setContainer(null);

			this._items.splice(index, 1);
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.adjust = function()
	{
		if(this.isInViewMode())
		{
			return;
		}

		if(this.getItemCount() === 0 && this._input === null)
		{
			this._input = BX.create("input", { attrs:{ name: this.getDataKey(), type: "hidden" } });
			this._wrapper.appendChild(this._input);
		}
		else if(this.getItemCount() > 0 && this._input !== null)
		{
			this._input = BX.remove(this._input);
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.isSingleEditEnabled = function()
	{
		return true;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.hasContentToDisplay = function()
	{
		if(this._mode === BX.Crm.EntityEditorMode.edit)
		{
			return true;
		}

		return (this._model.getMappedField(this._map, "data", []).length > 0);
	};
	BX.Crm.EntityEditorMultipleUser.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		var title = this._schemeElement.getTitle();
		this._wrapper.appendChild(this.createTitleNode(title));

		if(this.hasContentToDisplay())
		{
			this._innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner" } });
			this._wrapper.appendChild(this._innerWrapper);

			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];

				item.setMode(this._mode);
				item.setContainer(this._innerWrapper);
				item.layout();
			}

			if(this.isInEditMode())
			{
				this._bottomButton = BX.create("span",
					{
						props: { className: "crm-entity-widget-content-add-employees" },
						text : BX.prop.getString(
							this._schemeElement.getDataObjectParam("messages", {}),
							"addObserver",
							BX.message("CRM_EDITOR_ADD")
						),
						events: { click: this._bottomButtonClickHandler }
					}
				);

				this._wrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-content-block-add-field" },
							children: [ this._bottomButton ]
						}
					)
				);
			}
		}
		else
		{
			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					text: this.getMessage("isEmpty")
				}
			);
			this._wrapper.appendChild(this._innerWrapper);
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.adjust();

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.doRegisterLayout = function()
	{
		if(this.isInEditMode()
			&& this.checkModeOption(BX.Crm.EntityEditorModeOptions.individual)
		)
		{
			window.setTimeout(
				function(){ this.getSelector().open(this._bottomButton); }.bind(this),
				500
			);
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.doClearLayout = function(options)
	{
		this._input = null;

		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			item.clearLayout();
			item.setContainer(null);
		}
		this._innerWrapper = null;

		if(this._topButton)
		{
			BX.unbind(this._topButton, "click", this._topButtonClickHandler);
			this._topButton = null;
		}

		if(this._bottomButton)
		{
			BX.unbind(this._bottomButton, "click", this._bottomButtonClickHandler);
			this._bottomButton = null;
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.createTitleActionControls = function()
	{
		var controls = [];
		if(this.isInViewMode() && this.isEditInViewEnabled() && !this.isReadOnly())
		{
			this._topButton = BX.create("span",
				{
					props: { className: "crm-entity-widget-content-block-title-action-btn" },
					text: BX.message("CRM_EDITOR_ADD"),
					events: { click: this._topButtonClickHandler }
				}
			);
			controls.push(this._topButton);
		}
		return controls;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.getDataKey = function()
	{
		return BX.prop.getString(this._map, "data", this.getName());
	};
	BX.Crm.EntityEditorMultipleUser.prototype.save = function()
	{
		var values = [];
		var infos = [];
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];

			values.push(item.getValue());
			infos.push(item.getData());
		}

		this._infos = infos;
		this._model.setMappedField(this._map, "data", values);
		this._model.setSchemeField(this._schemeElement, "infos", infos);
	};
	BX.Crm.EntityEditorMultipleUser.prototype.onTopButtonClick = function(e)
	{
		//If any other control has changed try to switch to edit mode.
		if(this._mode === BX.Crm.EntityEditorMode.view && this.isEditInViewEnabled() && this.getEditor().isChanged())
		{
			this.switchToSingleEditMode();
		}
		else
		{
			this.getSelector().open(this._topButton);
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.onBottomButtonClick = function(e)
	{
		this.getSelector().open(this._bottomButton);
	};
	BX.Crm.EntityEditorMultipleUser.prototype.getSelector = function()
	{
		if(!this._userSelector)
		{
			this._userSelector = BX.Crm.EntityEditorUserSelector.create(
				this._id,
				{ callback: BX.delegate(this.processItemSelect, this) }
			);
		}

		return this._userSelector;
	};
	BX.Crm.EntityEditorMultipleUser.prototype.processItemSelect = function(selector, item)
	{
		if(!(this.isInEditMode() || (this.isEditInViewEnabled() && !this.isReadOnly())))
		{
			return;
		}

		var userId = BX.prop.getInteger(item, "entityId", 0);
		if(this.findItemIndexById(userId) >= 0)
		{
			this._userSelector.close();
			return;
		}

		var userInfo =
			{
				ID: userId,
				PHOTO_URL: BX.prop.getString(item, "avatar", ""),
				FORMATTED_NAME: BX.util.htmlspecialcharsback(BX.prop.getString(item, "name", "")),
				WORK_POSITION: BX.util.htmlspecialcharsback(BX.prop.getString(item, "desc", ""))
			};

		userInfo["SHOW_URL"] = this._schemeElement.getDataStringParam("pathToProfile", "")
			.replace(/#user_id#/gi, userInfo["ID"]);

		this.addItem(userInfo);
		this._userSelector.close();

		this.adjust();

		if(this.isInEditMode())
		{
			this.markAsChanged();
		}
		else
		{
			this._editor.saveControl(this);
		}
	};
	BX.Crm.EntityEditorMultipleUser.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorMultipleUser.prototype.processItemDeletion = function(item)
	{
		this.deleteItem(item);

		this.adjust();
		this.markAsChanged();
	};
	BX.Crm.EntityEditorMultipleUser.prototype.getRuntimeValue = function()
	{
		if (this._mode === BX.Crm.EntityEditorMode.edit && this._selectedData["id"] > 0)
		{
			return this._selectedData["id"];
		}
		return "";
	};
	BX.Crm.EntityEditorMultipleUser.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorMultipleUser.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorMultipleUser.superclass.getMessage.apply(this, arguments)
		);
	};
	if(typeof(BX.Crm.EntityEditorMultipleUser.messages) === "undefined")
	{
		BX.Crm.EntityEditorMultipleUser.messages = {};
	}
	BX.Crm.EntityEditorMultipleUser.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultipleUser();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorMultipleUserItem === "undefined")
{
	BX.Crm.EntityEditorMultipleUserItem = function()
	{
		this._id = "";
		this._settings = null;

		this._parent = null;
		this._editor = null;

		this._mode = BX.Crm.EntityEditorMode.view;
		this._data = null;

		this._container = null;
		this._wrapper = null;

		this._photoElement = null;
		this._nameElement = null;
		this._positionElement = null;

		this._input = null;
		this._deleteButton = null;
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);

		this._hasLayout = false;
	};

	BX.Crm.EntityEditorMultipleUserItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._parent = BX.prop.get(this._settings, "parent", null);
			this._editor = this._parent.getEditor();
			this._data = BX.prop.getObject(this._settings, "data", {});
			if (BX.prop.getString(this._data, "WORK_POSITION", "") == '&nbsp;')
			{
				this._data.WORK_POSITION = "";
			}
		},
		getValue: function()
		{
			return BX.prop.getInteger(this._data, "ID", 0);
		},
		getMode: function()
		{
			return this._mode;
		},
		setMode: function(mode)
		{
			this._mode = mode;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		getIndex: function()
		{
			return this._parent.findItemIndex(this);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var value = BX.prop.getInteger(this._data, "ID", 0);
			var formattedName = BX.prop.getString(this._data, "FORMATTED_NAME", "");
			var position = BX.prop.getString(this._data, "WORK_POSITION", "");
			var showUrl = BX.prop.getString(this._data, "SHOW_URL", "");
			var photoUrl = BX.prop.getString(this._data, "PHOTO_URL", "");

			this._photoElement = BX.create("a",
				{
					props: { className: "crm-widget-employee-avatar-container", target: "_blank" },
					style:
						{
							backgroundImage: photoUrl !== "" ? "url('" + photoUrl + "')" : "",
							backgroundSize: photoUrl !== "" ? "30px" : ""
						}
				}
			);

			this._nameElement = BX.create("a",
				{
					props: { className: "crm-widget-employee-name", target: "_blank" },
					text: formattedName
				}
			);

			if (showUrl !== "")
			{
				this._photoElement.href = showUrl;
				this._nameElement.href = showUrl;
			}

			this._positionElement = BX.create("SPAN",
				{
					props: { className: "crm-widget-employee-position" },
					text: position
				}
			);

			this._wrapper = BX.create("div", { props: { className: "crm-widget-employee-container" } });
			this._deleteButton = null;

			if(this._mode === BX.Crm.EntityEditorMode.edit)
			{
				this._deleteButton = BX.create(
					"div",
					{
						props: { className: "crm-widget-employee-remove" },
						text: BX.message("CRM_EDITOR_DELETE")
					}
				);
				BX.bind(this._deleteButton, "click", this._deleteButtonHandler);
				this._wrapper.appendChild(this._deleteButton);
			}

			this._wrapper.appendChild(this._photoElement);
			this._wrapper.appendChild(
				BX.create("span",
					{
						props: { className: "crm-widget-employee-info" },
						children: [ this._nameElement, this._positionElement ]
					}
				)
			);

			if(this._parent.isInEditMode())
			{
				this._input = BX.create(
					"input",
					{
						attrs:
							{
								name: this._parent.getDataKey() + "[" + this.getIndex() + "]",
								type: "hidden",
								value: value
							}
					}
				);
				this._wrapper.appendChild(this._input);
			}

			this._container.appendChild(this._wrapper);
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._deleteButton)
			{
				BX.unbind(this._deleteButton, "click", this._deleteButtonHandler);
				this._deleteButton = null;
			}

			this._input = null;
			this._photoElement = this._nameElement = this._positionElement = null;
			this._wrapper = BX.remove(this._wrapper);
			this._hasLayout = false;
		},
		onDeleteButtonClick: function(e)
		{
			this._parent.processItemDeletion(this);
		}
	};

	BX.Crm.EntityEditorMultipleUserItem.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultipleUserItem();
		self.initialize(id, settings);
		return self;
	};
}