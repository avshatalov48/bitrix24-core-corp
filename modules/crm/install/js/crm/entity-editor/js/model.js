BX.namespace("BX.Crm");

//region MODEL
if(typeof BX.Crm.EntityModel === "undefined")
{
	BX.Crm.EntityModel = function()
	{
		this._id = "";
		this._settings = {};
		this._data = null;
		this._initData = null;
		this._lockedFields = null;
		this._changeNotifier = null;
		this._lockNotifier = null;
	};
	BX.Crm.EntityModel.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._data = BX.prop.getObject(this._settings, "data", {});
				this._initData = BX.clone(this._data);
				this._lockedFields = {};
				this._changeNotifier = BX.CrmNotifier.create(this);
				this._lockNotifier = BX.CrmNotifier.create(this);

				this.doInitialize();
			},
			doInitialize: function()
			{
			},
			getEntityTypeId: function()
			{
				return BX.CrmEntityType.enumeration.undefined;
			},
			getEntityId: function()
			{
				return BX.prop.getInteger(this._data, "ID", 0);
			},
			getOwnerInfo: function()
			{
				return(
					{
						ownerID: this.getEntityId(),
						ownerType: BX.CrmEntityType.resolveName(this.getEntityTypeId())
					}
				);
			},
			getField: function(name, defaultValue)
			{
				if(defaultValue === undefined)
				{
					defaultValue = null;
				}
				return BX.prop.get(this._data, name, defaultValue);
			},
			getStringField: function(name, defaultValue)
			{
				if(defaultValue === undefined)
				{
					defaultValue = null;
				}
				return BX.prop.getString(this._data, name, defaultValue);
			},
			getIntegerField: function(name, defaultValue)
			{
				if(defaultValue === undefined)
				{
					defaultValue = null;
				}
				return BX.prop.getInteger(this._data, name, defaultValue);
			},
			getNumberField: function(name, defaultValue)
			{
				if(defaultValue === undefined)
				{
					defaultValue = null;
				}
				return BX.prop.getNumber(this._data, name, defaultValue);
			},
			getArrayField: function(name, defaultValue)
			{
				if(defaultValue === undefined)
				{
					defaultValue = null;
				}
				return BX.prop.getArray(this._data, name, defaultValue);
			},
			registerNewField: function(name, value)
			{
				//update data
				this._data[name] = value;
				//update initialization data because of rollback.
				this._initData[name] = value;
			},
			setField: function(name, value, options)
			{
				if(this._data.hasOwnProperty(name) && this._data[name] === value)
				{
					return;
				}

				this._data[name] = value;

				if(!BX.type.isPlainObject(options))
				{
					options = {};
				}

				if(BX.prop.getBoolean(options, "enableNotification", true))
				{
					this._changeNotifier.notify(
						[
							{
								name: name,
								originator: BX.prop.get(options, "originator", null)
							}
						]
					);
					BX.onCustomEvent(
						window,
						"Crm.EntityModel.Change",
						[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), fieldName: name } ]
					);
				}
			},
			getData: function()
			{
				return this._data;
			},
			setData: function(data, options)
			{
				this._data = BX.type.isPlainObject(data) ? data : {};
				this._initData = BX.clone(this._data);

				if(BX.prop.getBoolean(options, "enableNotification", true))
				{
					this._changeNotifier.notify(
						[
							{
								forAll: true,
								originator: BX.prop.get(options, "originator", null)
							}
						]
					);
					BX.onCustomEvent(
						window,
						"Crm.EntityModel.Change",
						[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), forAll: true } ]
					);
				}
			},
			updateData: function(data, options)
			{
				if(!BX.type.isPlainObject(data))
				{
					return;
				}

				this._data = BX.mergeEx(this._data, data);
				if(BX.prop.getBoolean(options, "enableNotification", true))
				{
					this._changeNotifier.notify(
						[
							{
								forAll: true,
								originator: BX.prop.get(options, "originator", null)
							}
						]
					);
					BX.onCustomEvent(
						window,
						"Crm.EntityModel.Change",
						[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), forAll: true } ]
					);
				}
			},
			updateDataObject: function(name, data, options)
			{
				if(!this._data.hasOwnProperty(name))
				{
					this._data[name] = data;
				}
				else
				{
					this._data[name] = BX.mergeEx(this._data[name], data);
				}

				if(BX.prop.getBoolean(options, "enableNotification", true))
				{
					this._changeNotifier.notify(
						[
							{
								forAll: true,
								originator: BX.prop.get(options, "originator", null)
							}
						]
					);
					BX.onCustomEvent(
						window,
						"Crm.EntityModel.Change",
						[ this, { entityTypeId: this.getEntityTypeId(), entityId: this.getEntityId(), forAll: true } ]
					);
				}
			},
			getSchemeField: function(schemeElement, name, defaultValue)
			{
				return this.getField(schemeElement.getDataStringParam(name, ""), defaultValue);
			},
			setSchemeField: function(schemeElement, name, value)
			{
				var fieldName = schemeElement.getDataStringParam(name, "");
				if(fieldName !== "")
				{
					this.setField(fieldName, value);
				}
			},
			getMappedField: function(map, name, defaultValue)
			{
				var fieldName = BX.prop.getString(map, name, "");
				return fieldName !== "" ? this.getField(fieldName, defaultValue) : defaultValue;
			},
			setMappedField: function(map, name, value)
			{
				var fieldName = BX.prop.getString(map, name, "");
				if(fieldName !== "")
				{
					this.setField(fieldName, value);
				}
			},
			getInitFieldValue: function(name, defaultValue)
			{
				if(defaultValue === undefined)
				{
					defaultValue = null;
				}
				return BX.prop.get(this._initData, name, defaultValue);
			},
			setInitFieldValue:  function(name, value)
			{
				if(this._initData.hasOwnProperty(name) && this._initData[name] === value)
				{
					return;
				}
				this._initData[name] = value;
			},
			save: function()
			{
			},
			rollback: function()
			{
				this._data = BX.clone(this._initData);
			},
			lockField: function(fieldName)
			{
				if(this._lockedFields.hasOwnProperty(fieldName))
				{
					return;
				}

				this._lockedFields[fieldName] = true;
				this._lockNotifier.notify([ { name: name, isLocked: true } ]);
			},
			unlockField: function(fieldName)
			{
				if(!this._lockedFields.hasOwnProperty(fieldName))
				{
					return;
				}

				delete this._lockedFields[fieldName];
				this._lockNotifier.notify([ { name: name, isLocked: false } ]);
			},
			isFieldLocked: function(fieldName)
			{
				return this._lockedFields.hasOwnProperty(fieldName);
			},
			addChangeListener: function(listener)
			{
				this._changeNotifier.addListener(listener);
			},
			removeChangeListener: function(listener)
			{
				this._changeNotifier.removeListener(listener);
			},
			addLockListener: function(listener)
			{
				this._lockNotifier.addListener(listener);
			},
			removeLockListener: function(listener)
			{
				this._lockNotifier.removeListener(listener);
			},
			isCaptionEditable: function()
			{
				return false;
			},
			getCaption: function()
			{
				return "";
			},
			setCaption: function(caption)
			{
			},
			prepareCaptionData: function(data)
			{
			}
		};
	BX.Crm.EntityModel.create = function(id, settings)
	{
		var self = new BX.Crm.EntityModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.LeadModel === "undefined")
{
	BX.Crm.LeadModel = function()
	{
		BX.Crm.LeadModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.LeadModel, BX.Crm.EntityModel);
	BX.Crm.LeadModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.LeadModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.LeadModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.lead;
	};
	BX.Crm.LeadModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.LeadModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.LeadModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.LeadModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.LeadModel.create = function(id, settings)
	{
		var self = new BX.Crm.LeadModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.ContactModel === "undefined")
{
	BX.Crm.ContactModel = function()
	{
		BX.Crm.ContactModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.ContactModel, BX.Crm.EntityModel);
	BX.Crm.ContactModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.contact;
	};
	BX.Crm.ContactModel.prototype.getCaption = function()
	{
		return this.getField("FORMATTED_NAME", "");
	};
	BX.Crm.ContactModel.create = function(id, settings)
	{
		var self = new BX.Crm.ContactModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.CompanyModel === "undefined")
{
	BX.Crm.CompanyModel = function()
	{
		BX.Crm.CompanyModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.CompanyModel, BX.Crm.EntityModel);
	BX.Crm.CompanyModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.CompanyModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.company;
	};
	BX.Crm.CompanyModel.prototype.getCaption = function()
	{
		return this.getField("TITLE", "");
	};
	BX.Crm.CompanyModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.CompanyModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.CompanyModel.create = function(id, settings)
	{
		var self = new BX.Crm.CompanyModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.DealModel === "undefined")
{
	BX.Crm.DealModel = function()
	{
		BX.Crm.DealModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DealModel, BX.Crm.EntityModel);
	BX.Crm.DealModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Saved", BX.delegate(this.onEntityProgressSave, this));
	};
	BX.Crm.DealModel.prototype.onEntityProgressSave = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STAGE_ID", ""))
		{
			this.setField("STAGE_ID", stepId);
		}
	};
	BX.Crm.DealModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.deal;
	};
	BX.Crm.DealModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.DealModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.DealModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.DealModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.DealModel.create = function(id, settings)
	{
		var self = new BX.Crm.DealModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.DealRecurringModel === "undefined")
{
	BX.Crm.DealRecurringModel = function ()
	{
		BX.Crm.DealRecurringModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DealRecurringModel, BX.Crm.DealModel);

	BX.Crm.DealRecurringModel.create = function(id, settings)
	{
		var self = new BX.Crm.DealRecurringModel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.QuoteModel === "undefined")
{
	BX.Crm.QuoteModel = function()
	{
		BX.Crm.QuoteModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.QuoteModel, BX.Crm.EntityModel);
	BX.Crm.QuoteModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.QuoteModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.QuoteModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.quote;
	};
	BX.Crm.QuoteModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.QuoteModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.QuoteModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.QuoteModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.QuoteModel.create = function(id, settings)
	{
		var self = new BX.Crm.QuoteModel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
