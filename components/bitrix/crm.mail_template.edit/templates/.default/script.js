if(typeof(BX.CrmEntityFieldSelector) == "undefined")
{
	BX.CrmEntityFieldSelector = function()
	{
		this._id = "";
		this._settings = {}; //entityTypeId, map
		this._container = null;
		this._entityTypeSelector = null;
		this._selector1 = this._selector2 = this._addButton = null;
		this._entityTypeId = "";
		this._fieldTypeId = "";
		this._fieldId = "";
		this._childFieldId = "";
		this._entityTypeChangeHandler = BX.delegate(this._onEntityTypeChange, this);
	}
	BX.CrmEntityFieldSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._map = this.getSetting("map");
			if(!BX.type.isArray(this._map))
			{
				throw "BX.CrmEntityFieldSelector: Could not find map!";
			}

			this._entityTypeId = this.getSetting("entityTypeId", "");
			this._fieldTypeId = this.getSetting("fieldTypeId", "");
			this._fieldId = this.getSetting("fieldId", "");
			this._childFieldId = this.getSetting("_childFieldId", "");
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		registerEntityTypeSelector: function(selector)
		{
			if(!BX.type.isElementNode(selector))
			{
				throw "BX.CrmEntityFieldSelector: entity type selector is not DOM node!";
			}

			if(this._entityTypeSelector)
			{
				BX.unbind(selector, "change", this._entityTypeChangeHandler);
			}

			this._entityTypeSelector = selector;
			this._entityTypeId = selector.value;
			BX.bind(selector, "change", this._entityTypeChangeHandler);
			this._setupDefaultField();
		},
		getEntityTypeInfo: function(typeId)
		{
			if(!BX.type.isNotEmptyString(typeId))
			{
				return null;
			}

			for(var i = 0; i < this._map.length; i++)
			{
				var info = this._map[i];
				if(info["typeId"] == typeId)
				{
					return info;
				}
			}

			return null;
		},
		findEntityField: function(typeId, fieldId)
		{
			if(!BX.type.isNotEmptyString(typeId))
			{
				return null;
			}

			var typeInfo = this.getEntityTypeInfo(typeId);
			var fields = typeInfo && typeInfo["fields"] ? typeInfo["fields"] : [];
			for(var i = 0; i < fields.length; i++)
			{
				var field = fields[i];
				if(field["id"] == fieldId)
				{
					return field;
				}
			}
			return null;
		},
		clearLayout: function()
		{
			if(this._container)
			{
				BX.cleanNode(this._container, false);
			}

			this._selector1 = null;
			this._selector2 = null;
			this._addButton = null;
		},
		layout: function(container)
		{
			if(!container)
			{
				container = this._container;
			}

			if(!container)
			{
				throw "BX.CrmEntityFieldSelector: Container is not defined!";
			}

			if(container !== this._container)
			{
				this._container = container;
			}

			if(this._entityTypeId === "")
			{
				throw "BX.CrmEntityFieldSelector: Could not find entity type Id!";
			}

			this._selector1 = this._createSelector(
				this._entityTypeId,
				{
					"attrs":
					{
						"id": this._id + "Selector1"
					},
					"events":
					{
						"change": BX.delegate(this._onSelector1Change, this)
					}
				},
				this._fieldId
			);

			if(!this._selector1)
			{
				return;
			}

			container.appendChild(this._selector1);

			if(this._fieldTypeId !== "")
			{
				this._selector2 = this._createSelector(
					this._fieldTypeId,
					{
						"attrs":
						{
							"id": this._id + "Selector2"
						},
						"events":
						{
							"change": BX.delegate(this._onSelector2Change, this)
						}
					},
					this._childFieldId
				);

				if(this._selector2)
				{
					container.appendChild(this._selector2);
				}
			}

			this._addButton = BX.create(
					"BUTTON",
					{
						"text": BX.CrmEntityFieldSelector.getMessage("buttonAdd"),
						"events":
						{
							"click": BX.delegate(this._onAddButtonClick, this)
						}
					}
				);
			container.appendChild(this._addButton);
		},
		_createSelector: function(entityTypeId, settings, fieldId)
		{
			var entityTypeInfo = this.getEntityTypeInfo(entityTypeId);
			return entityTypeInfo
				? this._createSelect(settings, this._fieldsToOptions(entityTypeInfo["fields"]), fieldId)
				: null;
		},
		_fieldsToOptions: function(fields)
		{
			if(!BX.type.isArray(fields))
			{
				return [];
			}

			var result = [];
			for(var i = 0; i < fields.length; i++)
			{
				var f = fields[i];
				result.push(
					{
						"text": f["name"],
						"attrs":
						{
							"value": f["id"]
						}
					}
				);
			}
			return result;
		},
		_createSelect: function(selectSettings, optionSettings, value)
		{
			value = BX.type.isString(value) ? value : "";
			var select = BX.create("SELECT", selectSettings);
			for(var i = 0; i < optionSettings.length; i++)
			{
				var setting = optionSettings[i];

				if(!setting["text"])
				{
					setting["text"] = setting["value"];
				}

				if(setting["value"] == value)
				{
					setting["selected"] = "selected";
				}

				var option = BX.create("OPTION", optionSettings[i]);

				if(!BX.browser.isIE)
				{
					select.add(option,null);
				}
				else
				{
					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option,null);
					}
				}
			}
			return select;
		},
		_setupDefaultField: function()
		{
			var entityTypeInfo = this.getEntityTypeInfo(this._entityTypeId);
			if(entityTypeInfo && entityTypeInfo["fields"] && entityTypeInfo["fields"].length > 0)
			{
				var field = entityTypeInfo["fields"][0];
				this._fieldTypeId = BX.type.isNotEmptyString(field["typeId"]) ? field["typeId"] : "";
				this._fieldId = BX.type.isNotEmptyString(field["id"]) ? field["id"] : "";
				if(this._fieldTypeId == "")
				{
					this._childFieldId = "";
				}
				else
				{
					entityTypeInfo = this.getEntityTypeInfo(this._fieldTypeId);
					if(entityTypeInfo && entityTypeInfo["fields"] && entityTypeInfo["fields"].length > 0)
					{
						field = entityTypeInfo["fields"][0];
						this._childFieldId = BX.type.isNotEmptyString(field["id"]) ? field["id"] : "";
					}
				}
			}
			else
			{
				this._fieldTypeId = "";
				this._fieldId = "";
				this._childFieldId = "";
			}
		},
		_onEntityTypeChange: function(e)
		{
			this._entityTypeId = this._entityTypeSelector.value;
			this._setupDefaultField();
			this.clearLayout();
			this.layout();
		},
		_onSelector1Change: function(e)
		{
			if(!this._selector1)
			{
				return;
			}

			var field = this.findEntityField(this._entityTypeId, this._selector1.value);
			this._fieldId = field && BX.type.isNotEmptyString(field["id"]) ? field["id"] : "";
			this._fieldTypeId = field && BX.type.isNotEmptyString(field["typeId"]) ? field["typeId"] : "";

			if(this._fieldTypeId !== "")
			{
				var entityTypeInfo = this.getEntityTypeInfo(this._fieldTypeId);
				if(entityTypeInfo && entityTypeInfo["fields"] && entityTypeInfo["fields"].length > 0)
				{
					field = entityTypeInfo["fields"][0];
					this._childFieldId = BX.type.isNotEmptyString(field["id"]) ? field["id"] : "";
				}

				if(this._selector2)
				{
					BX.remove(this._selector2);
					this._selector2 = null;
				}

				this._selector2 = this._createSelector(
					this._fieldTypeId,
					{
						"attrs":
						{
							"id": this._id + "Selector2"
						},
						"events":
						{
							"change": BX.delegate(this._onSelector2Change, this)
						}
					},
					this._childFieldId
				);

				if(this._selector2)
				{
					this._container.insertBefore(this._selector2, this._addButton);
				}
			}
			else
			{
				this._childFieldId = "";
				if(this._selector2)
				{
					BX.remove(this._selector2);
					this._selector2 = null;
				}
			}
		},
		_onSelector2Change: function(e)
		{
			if(!this._selector2)
			{
				return;
			}

			var field = this.findEntityField(this._fieldTypeId, this._selector2.value);
			this._childFieldId = field && BX.type.isNotEmptyString(field["id"]) ? field["id"] : "";
		},
		_onAddButtonClick: function(e)
		{
			BX.PreventDefault(e);

			var editorName = this.getSetting("editorName", "");
			var editor = BX.type.isNotEmptyString(editorName) ? window[editorName] : null;
			if(!editor)
			{
				return;
			}

			var entityTypeInfo = this.getEntityTypeInfo(this._entityTypeId);
			if(entityTypeInfo && this._fieldId)
			{
				var path = entityTypeInfo["typeName"] + "." + this._fieldId;
				if(this._childFieldId !== "")
				{
					path += "." + this._childFieldId;
				}
				editor.InsertHTML("#" + path + "#");
			}
			return false;
		}

	};

	if(typeof(BX.CrmEntityFieldSelector.messages) == "undefined")
	{
		BX.CrmEntityFieldSelector.messages = {};
	}

	BX.CrmEntityFieldSelector.getMessage = function(id)
	{
		return typeof(BX.CrmEntityFieldSelector.messages[id]) !== "undefined"
			? BX.CrmEntityFieldSelector.messages[id] : "";
	};

	BX.CrmEntityFieldSelector.create = function(id, settings)
	{
		var self = new BX.CrmEntityFieldSelector();
		self.initialize(id, settings);
		return self;
	};
}
