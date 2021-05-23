if(typeof(BX.CrmClientRequisiteEditor) === "undefined")
{
	BX.CrmClientRequisiteEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._contextId = "";
		this._personTypeId = 0;
		this._sourceData = [];
		this._items = [];
		this._container = null;
		this._hasLayout = false;
	};

	BX.CrmClientRequisiteEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix");
			this._contextId = this.getSetting("contextId", "");
			this._personTypeId = parseInt(this.getSetting("personTypeId", 0));
			this._sourceData = this.getSetting("data", []);

			this._container = BX(this.getSetting("containerId", ""));
			if(!this._container)
			{
				throw "BX.CrmClientRequisiteEditor. Could not find container.";
			}

			BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getPersonTypeId: function()
		{
			return this._personTypeId;
		},
		initializeFromExternalData: function()
		{
			var self = this;
			BX.CrmMobileContext.getCurrent().getPageParams(
				{
					callback: function(data)
					{
						if(data)
						{
							var contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
							var personTypeId = BX.type.isNumber(data["personTypeId"]) ? data["personTypeId"] : 0;
							if(!(contextId === self._contextId && personTypeId === self._personTypeId))
							{
								self._contextId = contextId;
								self._personTypeId = personTypeId;
								self._sourceData = BX.type.isArray(data["data"]) ? data["data"] : [];

								self.clearLayout();
								self.layout();
							}
						}
					}
				}
			);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var data = this._sourceData;
			for(var i = 0; i < data.length; i++)
			{
				var item = BX.CrmClientRequisiteEditorItem.create(
					{
						data: data[i],
						parentContainer: this._container
					}
				);
				this._items.push(item);
				item.layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			for(var i = this._items.length - 1; i >= 0; i--)
			{
				this._items[i].clearLayout();
			}
			this._items = [];

			BX.cleanNode(this._container);
			this._hasLayout = false;
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		_onSave: function()
		{
			var data = [];
			for(var i = 0; i < this._items.length; i++)
			{
				data.push(this._items[i].toJson());
			}

			var context = BX.CrmMobileContext.getCurrent();
			var eventArgs =
			{
				contextId: this.getContextId(),
				personTypeId: this.getPersonTypeId(),
				data: data
			};

			context.riseEvent("onCrmClientRequisiteChanged", eventArgs, 2);
			window.setTimeout(context.createBackHandler(), 0);
		},
		_onAfterPageOpen: function()
		{
			this.initializeFromExternalData();
		}
	};

	BX.CrmClientRequisiteEditor.create = function(id, settings)
	{
		var self = new BX.CrmClientRequisiteEditor();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmClientRequisiteEditorItem) === "undefined")
{
	BX.CrmClientRequisiteEditorItem = function()
	{
		this._settings = {};
		this._sourceData = {};
		this._parentContainer = this._container = this._input = null;
		this._hasLayout = false;
	};

	BX.CrmClientRequisiteEditorItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._sourceData = this.getSetting("data", {});

			this._parentContainer = BX(this.getSetting("parentContainer", ""));
			if(!this._parentContainer)
			{
				throw "BX.CrmClientRequisiteEditor. Could not find parent container.";
			}

		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var data = this._sourceData;
			var code = BX.type.isNotEmptyString(data["CODE"]) ? data["CODE"] : "";
			var ttl = BX.type.isNotEmptyString(data["TITLE"]) ? data["TITLE"] : code;
			var val = BX.type.isNotEmptyString(data["VALUE"]) ? data["VALUE"] : "";

			var c = this._container = BX.create("DIV",
				{
					attrs: { className: "crm_block_container" }
				}
			);
			this._parentContainer.appendChild(c);

			c.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm_block_title fln" },
						text: ttl
					}
				)
			);
			c.appendChild(BX.create("HR"));

			this._input = BX.create("INPUT",
				{
					attrs: { type: "TEXT", className: "crm_input_text" },
					props: { value: val }
				}
			);

			c.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm_card" },
						style: { paddingBottom: "0" },
						children: [ this._input ]
					}
				)
			);

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._input = null;
			BX.cleanNode(this._container);
			this._container = BX.remove(this._container);

			this._hasLayout = false;
		},
		toJson: function()
		{
			var src = this._sourceData;
			var alias = BX.type.isNotEmptyString(src["ALIAS"]) ? src["ALIAS"] : "";
			var value = this._input ? this._input.value : "";
			return { ALIAS: alias, VALUE: value };
		}
	};

	BX.CrmClientRequisiteEditorItem.create = function(settings)
	{
		var self = new BX.CrmClientRequisiteEditorItem();
		self.initialize(settings);
		return self;
	}
}
