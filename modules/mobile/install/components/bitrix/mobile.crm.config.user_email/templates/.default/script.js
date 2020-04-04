if(typeof(BX.CrmUserEmailConfigurator) === "undefined")
{
	BX.CrmUserEmailConfigurator = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
	};

	BX.CrmUserEmailConfigurator.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ""
					? (this._prefix + "_" + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		getFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.value : "";
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		_onSave: function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.showPopupLoader();

			//var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "SAVE_CONFIGURATION",
						"EMAIL_ADDRESSER": this.getFieldValue("EMAIL")
					},
					onsuccess: function(data)
					{
						context.hidePopupLoader();
						var eventArgs =
						{
							addresser: typeof(data["SAVED_EMAIL_ADDRESSER"]) !== "undefined" ? data["SAVED_EMAIL_ADDRESSER"] : "",
							addresserName: typeof(data["SAVED_EMAIL_ADDRESSER_NAME"]) !== "undefined" ? data["SAVED_EMAIL_ADDRESSER_NAME"] : "",
							addresserEmail: typeof(data["SAVED_EMAIL_ADDRESSER_EMAIL"]) !== "undefined" ? data["SAVED_EMAIL_ADDRESSER_EMAIL"] : ""
						};

						context.riseEvent("onCrmUserEmailConfigChange", eventArgs, 2);
						context.close();
					},
					onfailure: function(data)
					{
						context.hidePopupLoader();
					}
				}
			);
		}
	};

	BX.CrmUserEmailConfigurator.create = function(id, settings)
	{
		var self = new BX.CrmUserEmailConfigurator();
		self.initialize(id, settings);
		return self;
	}
}
