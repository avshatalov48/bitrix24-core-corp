BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityConfig === "undefined")
{
	BX.Crm.EntityConfig = function()
	{
		this._id = "";
		this._settings = {};
		this._scope = BX.Crm.EntityConfigScope.undefined;
		this._enableScopeToggle = true;

		this._canUpdatePersonalConfiguration = true;
		this._canUpdateCommonConfiguration = false;

		this._data = {};
		this._items = [];
		this._options = {};

		this._serviceUrl = "";
		this._isChanged = false;
	};
	BX.Crm.EntityConfig.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._scope = BX.prop.getString(this._settings, "scope", BX.Crm.EntityConfigScope.personal);
			this._enableScopeToggle = BX.prop.getBoolean(this._settings, "enableScopeToggle", true);

			this._canUpdatePersonalConfiguration = BX.prop.getBoolean(this._settings, "canUpdatePersonalConfiguration", true);
			this._canUpdateCommonConfiguration = BX.prop.getBoolean(this._settings, "canUpdateCommonConfiguration", false);

			this._data = BX.prop.getArray(this._settings, "data", []);

			this._items = [];
			for(var i = 0, length = this._data.length; i < length; i++)
			{
				var item = this._data[i];
				var type = BX.prop.getString(item, "type", "");
				if(type === "section")
				{
					this._items.push(BX.Crm.EntityConfigSection.create({ data: item }));
				}
				else
				{
					this._items.push(BX.Crm.EntityConfigField.create({ data: item }));
				}
			}

			this._options = BX.prop.getObject(this._settings, "options", {});
			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
		},
		findItemByName: function(name)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(item.getName() === name)
				{
					return item;
				}
			}
			return null;
		},
		findItemIndexByName: function(name)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(item.getName() === name)
				{
					return i;
				}
			}
			return -1;
		},
		toJSON: function()
		{
			var result = [];
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				result.push(this._items[i].toJSON());
			}
			return result;
		},
		addSchemeElementAt: function(schemeElement, index)
		{
			var data = schemeElement.createConfigItem();
			var item = schemeElement.getType() === "section"
				? BX.Crm.EntityConfigSection.create({ data: data })
				: BX.Crm.EntityConfigField.create({ data: data });

			if(index >= 0 && index < this._items.length)
			{
				this._items.splice(index, 0, item);
			}
			else
			{
				this._items.push(item);
			}

			this._isChanged = true;
		},
		moveSchemeElement: function(schemeElement, index)
		{
			var qty = this._items.length;
			var lastIndex = qty - 1;
			if(index < 0  || index > qty)
			{
				index = lastIndex;
			}

			var currentIndex = this.findItemIndexByName(schemeElement.getName());
			if(currentIndex < 0 || currentIndex === index)
			{
				return;
			}

			var item = this._items[currentIndex];
			this._items.splice(currentIndex, 1);

			qty--;

			if(index < qty)
			{
				this._items.splice(index, 0, item);
			}
			else
			{
				this._items.push(item);
			}

			this._isChanged = true;
		},
		updateSchemeElement: function(schemeElement)
		{
			var index;
			var parentElement = schemeElement.getParent();
			if(parentElement)
			{
				var parentItem = this.findItemByName(parentElement.getName());
				if(parentItem)
				{
					index = parentItem.findFieldIndexByName(schemeElement.getName());
					if(index >= 0)
					{
						parentItem.setField(
							BX.Crm.EntityConfigField.create({ data: schemeElement.createConfigItem() }),
							index
						);
						this._isChanged = true;
					}
				}
			}
			else
			{
				index = this.findItemIndexByName(schemeElement.getName());
				if(index >= 0)
				{
					if(schemeElement.getType() === "section")
					{
						this._items[index] = BX.Crm.EntityConfigSection.create({ data: schemeElement.createConfigItem() });
					}
					else
					{
						this._items[index] = BX.Crm.EntityConfigField.create({ data: schemeElement.createConfigItem() });
					}
					this._isChanged = true;
				}
			}

		},
		removeSchemeElement: function(schemeElement)
		{
			var index = this.findItemIndexByName(schemeElement.getName());
			if(index < 0)
			{
				return;
			}

			this._items.splice(index, 1);
			this._isChanged = true;
		},
		isChangeable: function()
		{
			if(this._scope === BX.Crm.EntityConfigScope.common)
			{
				return this._canUpdateCommonConfiguration;
			}
			else if(this._scope === BX.Crm.EntityConfigScope.personal)
			{
				return this._canUpdatePersonalConfiguration;
			}

			return false;
		},
		isChanged: function()
		{
			return this._isChanged;
		},
		isScopeToggleEnabled: function()
		{
			return this._enableScopeToggle;
		},
		getScope: function()
		{
			return this._scope;
		},
		setScope: function(scope)
		{
			var promise = new BX.Promise();
			if(!this._enableScopeToggle || this._scope === scope)
			{
				window.setTimeout(
					function(){ promise.fulfill(); },
					0
				);
				return promise;
			}

			this._scope = scope;

			//Scope is changed - data collections are invalid.
			this._data = [];
			this._items = [];

			BX.ajax.post(
				this._serviceUrl,
				{ guid: this._id, action: "setScope", scope: this._scope },
				function(){ promise.fulfill(); }
			);
			return promise;
		},
		registerField: function(scheme)
		{
			var parentScheme = scheme.getParent();
			if(!parentScheme)
			{
				return;
			}

			var section = this.findItemByName(parentScheme.getName());
			if(!section)
			{
				return;
			}

			section.addField(
				BX.Crm.EntityConfigField.create({ data: scheme.createConfigItem() })
			);
			this.save();
		},
		unregisterField: function(scheme)
		{
			var parentScheme = scheme.getParent();
			if(!parentScheme)
			{
				return;
			}

			var section = this.findItemByName(parentScheme.getName());
			if(!section)
			{
				return;
			}

			var field = section.findFieldByName(scheme.getName());
			if(!field)
			{
				return;
			}

			section.removeFieldByIndex(field.getIndex());
			this.save();
		},
		save: function(forAllUsers, enableOptions)
		{
			forAllUsers = !!forAllUsers;
			enableOptions = !!enableOptions;

			var promise = new BX.Promise();
			if(!this._isChanged && !forAllUsers)
			{
				window.setTimeout(
					function(){ promise.fulfill(); },
					0
				);
				return promise;
			}

			var data =
			{
				guid: this._id,
				action: "save",
				scope: this._scope,
				config: this.toJSON()
			};

			if(enableOptions)
			{
				data["options"] = this._options;
			}

			if(this._scope === BX.Crm.EntityConfigScope.personal && forAllUsers)
			{
				data["forAllUsers"] = "Y";
				data["delete"] = "Y";
			}

			BX.ajax.post(
				this._serviceUrl,
				data,
				function(){ promise.fulfill(); }
			);
			this._isChanged = false;
			return promise;
		},
		reset: function(forAllUsers)
		{
			var data =
			{
				guid: this._id,
				action: "reset",
				scope: this._scope,
				config: this.toJSON()
			};

			if(forAllUsers)
			{
				data["forAllUsers"] = "Y";
			}

			var promise = new BX.Promise();
			BX.ajax.post(
				this._serviceUrl,
				data,
				function(){ promise.fulfill(); }
			);
			return promise;
		},
		forceCommonScopeForAll: function()
		{
			var promise = new BX.Promise();
			BX.ajax.post(
				this._serviceUrl,
				{ guid: this._id, action: "forceCommonScopeForAll" },
				function(){ promise.fulfill(); }
			);
			return promise;
		},
		getOption: function(name, defaultValue)
		{
			return BX.prop.getString(this._options, name, defaultValue);
		},
		setOption: function(name, value)
		{
			if(typeof(value) === "undefined" || value === null)
			{
				return;
			}

			if(BX.prop.getString(this._options, name, null) === value)
			{
				return;
			}

			this._options[name] = value;

			if(this._scope === BX.Crm.EntityConfigScope.common)
			{
				BX.userOptions.save(
					"crm.entity.editor",
					this._id + "_common_opts",
					name,
					value,
					true
				);
			}
			else
			{
				BX.userOptions.save(
					"crm.entity.editor",
					this._id + "_opts",
					name,
					value,
					false
				);
			}
		}
	};
	BX.Crm.EntityConfig.create = function(id, settings)
	{
		var self = new BX.Crm.EntityConfig();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityConfigItem === "undefined")
{
	BX.Crm.EntityConfigItem = function()
	{
		this._settings = {};
		this._data = {};
		this._name = "";
		this._title = "";
	};

	BX.Crm.EntityConfigItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._data = BX.prop.getObject(this._settings, "data", []);
			this._name = BX.prop.getString(this._data, "name", "");
			this._title = BX.prop.getString(this._data, "title", "");

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getType: function()
		{
			return "";
		},
		getName: function()
		{
			return this._name;
		},
		getTitle: function()
		{
			return this._title;
		},
		toJSON: function()
		{
			return {};
		}
	};
}

if(typeof BX.Crm.EntityConfigSection === "undefined")
{
	BX.Crm.EntityConfigSection = function()
	{
		BX.Crm.EntityConfigSection.superclass.constructor.apply(this);
		this._fields = [];
	};
	BX.extend(BX.Crm.EntityConfigSection, BX.Crm.EntityConfigItem);

	BX.Crm.EntityConfigSection.prototype.doInitialize = function()
	{
		this._fields = [];
		var elements = BX.prop.getArray(this._data, "elements", []);
		for(var i = 0, length = elements.length; i < length; i++)
		{
			var field = BX.Crm.EntityConfigField.create({ data: elements[i] });
			field.setIndex(i);
			this._fields.push(field);
		}
	};
	BX.Crm.EntityConfigSection.prototype.getType = function()
	{
		return "section";
	};
	BX.Crm.EntityConfigSection.prototype.getFields = function()
	{
		return this._fields;
	};
	BX.Crm.EntityConfigSection.prototype.findFieldByName = function(name)
	{
		var index = this.findFieldIndexByName(name);
		return index >= 0 ? this._fields[index] : null;
	};
	BX.Crm.EntityConfigSection.prototype.findFieldIndexByName = function(name)
	{
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			var field = this._fields[i];
			if(field.getName() === name)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityConfigSection.prototype.addField = function(field)
	{
		this._fields.push(field);
	};
	BX.Crm.EntityConfigSection.prototype.setField = function(field, index)
	{
		this._fields[index] = field;
	};
	BX.Crm.EntityConfigSection.prototype.removeFieldByIndex = function(index)
	{
		var length = this._fields.length;
		if(index < 0 || index >= length)
		{
			return false;
		}

		this._fields.splice(index, 1);
		return true;
	};
	BX.Crm.EntityConfigSection.prototype.toJSON = function()
	{
		var result = { name: this._name, title: this._title, type: "section", elements: [] };
		for(var i = 0, length = this._fields.length; i < length; i++)
		{
			result.elements.push(this._fields[i].toJSON());
		}
		return result;
	};
	BX.Crm.EntityConfigSection.create = function(settings)
	{
		var self = new BX.Crm.EntityConfigSection();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityConfigField === "undefined")
{
	BX.Crm.EntityConfigField = function()
	{
		BX.Crm.EntityConfigField.superclass.constructor.apply(this);
		this._index = -1;
		this._optionFlags = 0;

	};
	BX.extend(BX.Crm.EntityConfigField, BX.Crm.EntityConfigItem);
	BX.Crm.EntityConfigField.prototype.doInitialize = function()
	{
		this._optionFlags = BX.prop.getInteger(this._data, "optionFlags", 0);
	};
	BX.Crm.EntityConfigField.prototype.toJSON = function()
	{
		var result = { name: this._name };
		if(this._title !== "")
		{
			result["title"] = this._title;
		}
		if(this._optionFlags > 0)
		{
			result["optionFlags"] = this._optionFlags;
		}
		return result;
	};
	BX.Crm.EntityConfigField.prototype.getIndex = function()
	{
		return this._index;
	};
	BX.Crm.EntityConfigField.prototype.setIndex = function(index)
	{
		this._index = index;
	};
	BX.Crm.EntityConfigField.create = function(settings)
	{
		var self = new BX.Crm.EntityConfigField();
		self.initialize(settings);
		return self;
	};
}

//region ENTITY CONFIGURATION SCOPE
if(typeof BX.Crm.EntityConfigScope === "undefined")
{
	BX.Crm.EntityConfigScope =
		{
			undefined: '',
			personal:  'P',
			common: 'C'
		};

	if(typeof(BX.Crm.EntityConfigScope.captions) === "undefined")
	{
		BX.Crm.EntityConfigScope.captions = {};
	}

	BX.Crm.EntityConfigScope.setCaptions = function(captions)
	{
		if(BX.type.isPlainObject(captions))
		{
			this.captions = captions;
		}
	};

	BX.Crm.EntityConfigScope.getCaption = function(scope)
	{
		return BX.prop.getString(this.captions, scope, scope);
	};
}
//endregion