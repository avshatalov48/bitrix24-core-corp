BX.namespace("BX.UI");

if(typeof BX.UI.EntityScheme === "undefined")
{
	BX.UI.EntityScheme = function()
	{
		this._id = "";
		this._settings = {};
		this._elements = null;
		this._availableElements = null;
	};
	BX.UI.EntityScheme.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._elements = [];
			this._availableElements = [];

			var i, length;
			var currentData = BX.prop.getArray(this._settings, "current", []);
			for(i = 0, length = currentData.length; i < length; i++)
			{
				this._elements.push(BX.UI.EntitySchemeElement.create(currentData[i]));
			}

			var availableData = BX.prop.getArray(this._settings, "available", []);
			for(i = 0, length = availableData.length; i < length; i++)
			{
				this._availableElements.push(BX.UI.EntitySchemeElement.create(availableData[i]));
			}
		},
		getId: function()
		{
			return this._id;
		},
		getElements: function()
		{
			return ([].concat(this._elements));
		},
		findElementByName: function(name, options)
		{
			var isRecursive = BX.prop.getBoolean(options, "isRecursive", false);
			for(var i = 0, length = this._elements.length; i < length; i++)
			{
				var element = this._elements[i];
				if(element.getName() === name)
				{
					return element;
				}

				if(!isRecursive)
				{
					continue;
				}

				var childElement = element.findElementByName(name);
				if(childElement !== null)
				{
					return childElement;
				}
			}

			return null;
		},
		getAvailableElements: function()
		{
			return([].concat(this._availableElements));
		},
		setAvailableElements: function(elements)
		{
			this._availableElements = BX.type.isArray(elements) ? elements : [];
		}
	};
	BX.UI.EntityScheme.create = function(id, settings)
	{
		var self = new BX.UI.EntityScheme();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.UI.EntitySchemeElement === "undefined")
{
	BX.UI.EntitySchemeElement = function()
	{
		this._settings = {};
		this._name = "";
		this._type = "";
		this._title = "";
		this._originalTitle = "";
		this._optionFlags = 0;

		this._isEditable = true;
		this._isTransferable = true;
		this._isContextMenuEnabled = true;
		this._isRequired = false;
		this._isRequiredConditionally = false;
		this._isHeading = false;

		this._visibilityPolicy = BX.UI.EntityEditorVisibilityPolicy.always;
		this._data = null;
		this._elements = null;
		this._parent = null;
	};
	BX.UI.EntitySchemeElement.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._name = BX.prop.getString(this._settings, "name", "");
			this._type = BX.prop.getString(this._settings, "type", "");

			this._data = BX.prop.getObject(this._settings, "data", {});

			this._isEditable = BX.prop.getBoolean(this._settings, "editable", true);
			this._isTransferable = BX.prop.getBoolean(this._settings, "transferable", true);
			this._isContextMenuEnabled = BX.prop.getBoolean(this._settings, "enabledMenu", true);
			this._isTitleEnabled = BX.prop.getBoolean(this._settings, "enableTitle", true)
				&& this.getDataBooleanParam("enableTitle", true);
			this._isDragEnabled = BX.prop.getBoolean(this._settings, "isDragEnabled", true);
			this._isRequired = BX.prop.getBoolean(this._settings, "required", false);
			this._isRequiredConditionally = BX.prop.getBoolean(this._settings, "requiredConditionally", false);
			this._isHeading = BX.prop.getBoolean(this._settings, "isHeading", false);

			this._visibilityPolicy = BX.UI.EntityEditorVisibilityPolicy.parse(
				BX.prop.getString(
					this._settings,
					"visibilityPolicy",
					""
				)
			);

			//region Titles
			var title = BX.prop.getString(this._settings, "title", "");
			var originalTitle = BX.prop.getString(this._settings, "originalTitle", "");

			if(title !== "" && originalTitle === "")
			{
				originalTitle = title;
			}
			else if(originalTitle !== "" && title === "")
			{
				title = originalTitle;
			}

			this._title = title;
			this._originalTitle = originalTitle;
			//endregion

			this._optionFlags = BX.prop.getInteger(this._settings, "optionFlags", 0);

			this._elements = [];
			var elementData = BX.prop.getArray(this._settings, "elements", []);
			for(var i = 0, l = elementData.length; i < l; i++)
			{
				this._elements.push(BX.UI.EntitySchemeElement.create(elementData[i]));
			}
		},
		mergeSettings: function(settings)
		{
			this.initialize(BX.mergeEx(this._settings, settings));
		},
		getName: function()
		{
			return this._name;
		},
		getType: function()
		{
			return this._type;
		},
		getTitle: function()
		{
			return this._title;
		},
		setTitle: function(title)
		{
			this._title = this._settings["title"] = title;
		},
		getOriginalTitle: function()
		{
			return this._originalTitle;
		},
		hasCustomizedTitle: function()
		{
			return this._title !== "" && this._title !== this._originalTitle;
		},
		resetOriginalTitle: function()
		{
			this._originalTitle = this._title;
		},
		getOptionFlags: function()
		{
			return this._optionFlags;
		},
		setOptionFlags: function(flags)
		{
			this._optionFlags = this._settings["optionFlags"] = flags;
		},
		areAttributesEnabled: function()
		{
			return BX.prop.getBoolean(this._settings, "enableAttributes", true);
		},
		isEditable: function()
		{
			return this._isEditable;
		},
		isTransferable: function()
		{
			return this._isTransferable;
		},
		isRequired: function()
		{
			return this._isRequired;
		},
		isRequiredConditionally: function()
		{
			return this._isRequiredConditionally;
		},
		isContextMenuEnabled: function()
		{
			return this._isContextMenuEnabled;
		},
		isTitleEnabled: function()
		{
			return this._isTitleEnabled;
		},
		isDragEnabled: function()
		{
			return this._isDragEnabled;
		},
		isHeading: function()
		{
			return this._isHeading;
		},
		getCreationPlaceholder: function()
		{
			return BX.prop.getString(
				BX.prop.getObject(this._settings, "placeholders", null),
				"creation",
				""
			);
		},
		getVisibilityPolicy: function()
		{
			return this._visibilityPolicy;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		getDataParam: function(name, defaultval)
		{
			return BX.prop.get(this._data, name, defaultval);
		},
		getDataStringParam: function(name, defaultval)
		{
			return BX.prop.getString(this._data, name, defaultval);
		},
		getDataIntegerParam: function(name, defaultval)
		{
			return BX.prop.getInteger(this._data, name, defaultval);
		},
		getDataBooleanParam: function(name, defaultval)
		{
			return BX.prop.getBoolean(this._data, name, defaultval);
		},
		getDataObjectParam: function(name, defaultval)
		{
			return BX.prop.getObject(this._data, name, defaultval);
		},
		getDataArrayParam: function(name, defaultval)
		{
			return BX.prop.getArray(this._data, name, defaultval);
		},
		getElements: function()
		{
			return this._elements;
		},
		setElements: function(elements)
		{
			this._elements = elements;
		},
		findElementByName: function(name)
		{
			for(var i = 0, length = this._elements.length; i < length; i++)
			{
				var element = this._elements[i];
				if(element.getName() === name)
				{
					return element;
				}
			}
			return null;
		},
		getAffectedFields: function()
		{
			var results = this.getDataArrayParam("affectedFields", []);
			if(results.length === 0)
			{
				results.push(this._name);
			}
			return results;
		},
		getParent: function()
		{
			return this._parent;
		},
		setParent: function(parent)
		{
			this._parent = parent instanceof BX.UI.EntitySchemeElement ? parent : null;
		},
		createConfigItem: function()
		{
			var result = { name: this._name };

			if(this._type === "section")
			{
				result["type"] = "section";

				if(this._title !== "")
				{
					result["title"] = this._title;
				}

				result["elements"] = [];
				for(var i = 0, length = this._elements.length; i < length; i++)
				{
					//result["elements"].push({ name: this._elements[i].getName() });
					result["elements"].push(this._elements[i].createConfigItem());
				}
			}
			else
			{
				if(this._title !== "" && this._title !== this._originalTitle)
				{
					result["title"] = this._title;
				}

				if(this._optionFlags > 0)
				{
					result["optionFlags"] = this._optionFlags;
				}
			}

			return result;
		},
		clone: function()
		{
			return BX.UI.EntitySchemeElement.create(BX.clone(this._settings));
		}
	};
	BX.UI.EntitySchemeElement.create = function(settings)
	{
		var self = new BX.UI.EntitySchemeElement();
		self.initialize(settings);
		return self;
	}
}