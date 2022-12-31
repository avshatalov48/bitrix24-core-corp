BX.namespace("BX.Crm");

//region MODEL
if(typeof BX.Crm.EntityModel === "undefined")
{
	/**
	 * @extends BX.UI.EntityModel
	 * @constructor
	 */
	BX.Crm.EntityModel = function()
	{
		BX.Crm.EntityModel.superclass.constructor.apply(this);
		this.eventsNamespace = 'Crm.EntityModel';
	};

	BX.extend(BX.Crm.EntityModel, BX.UI.EntityModel);

	BX.Crm.EntityModel.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntityModel.superclass.initialize.apply(this, [id, settings]);
		this._changeNotifier = BX.CrmNotifier.create(this);
		this._lockNotifier = BX.CrmNotifier.create(this);
	};
	BX.Crm.EntityModel.prototype.getEventArguments = function()
	{
		var eventArgs = BX.Crm.EntityModel.superclass.getEventArguments.apply(this);
		eventArgs.entityTypeId = this.getEntityTypeId();

		return eventArgs;
	};
	BX.Crm.EntityModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.undefined;
	};
	BX.Crm.EntityModel.prototype.getOwnerInfo = function()
	{
		return(
			{
				ownerID: this.getEntityId(),
				ownerType: BX.CrmEntityType.resolveName(this.getEntityTypeId())
			}
		);
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

if(typeof BX.Crm.FactoryBasedModel === "undefined")
{
	/**
	 * @extends BX.Crm.EntityModel
	 * @memberOf BX.Crm
	 * @constructor
	 */
	BX.Crm.FactoryBasedModel = function()
	{
		BX.Crm.FactoryBasedModel.superclass.constructor.apply(this);

		/**
		 * @type {number}
		 * @protected
		 */
		this._entityTypeId = null;
	};

	BX.extend(BX.Crm.FactoryBasedModel, BX.Crm.EntityModel);

	/**
	 * @param {string} id
	 * @param {Object} settings
	 */
	BX.Crm.FactoryBasedModel.prototype.initialize = function(id, settings)
	{
		BX.Crm.FactoryBasedModel.superclass.initialize.apply(this, [id, settings]);

		this._entityTypeId = BX.prop.getInteger(settings, 'entityTypeId', BX.CrmEntityType.enumeration.undefined);
	};

	BX.Crm.FactoryBasedModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent('BX.Crm.ItemDetailsComponent:onStageChange', this.onStageChange.bind(this));
	};

	/**
	 * @param {BX.Event.BaseEvent} event
	 */
	BX.Crm.FactoryBasedModel.prototype.onStageChange = function(event)
	{
		var entityTypeId = BX.prop.getInteger(event.getData(), "entityTypeId", BX.CrmEntityType.enumeration.undefined);
		var entityId = BX.prop.getInteger(event.getData(), "id", 0);

		if( (entityTypeId !== this.getEntityTypeId()) || (entityId !== this.getEntityId()) )
		{
			return;
		}

		var stageId = BX.prop.getString(event.getData(), "stageId", '');
		if( (stageId !== this.getField("STAGE_ID", "")) && (stageId !== '') )
		{
			this.setField("STAGE_ID", stageId);

			var previousStageId = BX.prop.getString(event.getData(), "previousStageId", null);
			if(previousStageId)
			{
				this.setField("PREVIOUS_STAGE_ID", previousStageId);
			}
		}
	};

	/**
	 * @return {number}
	 */
	BX.Crm.FactoryBasedModel.prototype.getEntityTypeId = function()
	{
		return this._entityTypeId;
	};

	/**
	 * @return {boolean}
	 */
	BX.Crm.FactoryBasedModel.prototype.isCaptionEditable = function()
	{
		return true;
	};

	/**
	 * @return {string}
	 */
	BX.Crm.FactoryBasedModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};

	/**
	 * @param {string} caption
	 */
	BX.Crm.FactoryBasedModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};

	/**
	 * @param {Object} data
	 */
	BX.Crm.FactoryBasedModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};

	/**
	 * @param {string} id
	 * @param {Object} settings
	 * @return {BX.Crm.FactoryBasedModel}
	 */
	BX.Crm.FactoryBasedModel.create = function(id, settings)
	{
		var self = new BX.Crm.FactoryBasedModel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.QuoteModel === "undefined")
{
	/**
	 * @extends BX.Crm.FactoryBasedModel
	 * @memberOf BX.Crm
	 * @constructor
	 */
	BX.Crm.QuoteModel = function()
	{
		BX.Crm.QuoteModel.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.QuoteModel, BX.Crm.FactoryBasedModel);

	/**
	 * @return {boolean}
	 */
	BX.Crm.QuoteModel.prototype.isCaptionEditable = function()
	{
		return true;
	};

	/**
	 * Quote caption and quote TITLE field are separate entities and should not be confused
	 *
	 * @return {string}
	 */
	BX.Crm.QuoteModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		if (BX.Type.isString(title) && title.length > 0)
		{
			return title;
		}
		var caption = null;
		if (this.getField('IS_USE_NUMBER_IN_TITLE_PLACEHOLDER'))
		{
			caption = BX.Loc.getMessage(
				'CRM_QUOTE_TITLE',
				{
					'#QUOTE_NUMBER#': this.getField('QUOTE_NUMBER'),
					'#BEGINDATE#': this.getField('BEGINDATE')
				}
			);
		}
		else
		{
			var id = Number(this.getField('ID'));
			if (id <= 0)
			{
				id = '';
			}
			caption = BX.Loc.getMessage(
				'CRM_QUOTE_TITLE_PLACEHOLDER',
				{
					'#ID#': id,
				}
			);
		}

		return BX.Type.isString(caption) ? caption : '';
	};

	/**
	 * You can't change quote caption
	 *
	 * @param {string} caption
	 */
	BX.Crm.QuoteModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};

	/**
	 * You can't change quote caption
	 *
	 * @param {Object} data
	 */
	BX.Crm.QuoteModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};

	/**
	 * @param {string} id
	 * @param {Object} settings
	 * @return {BX.Crm.QuoteModel}
	 */
	BX.Crm.QuoteModel.create = function(id, settings)
	{
		var self = new BX.Crm.QuoteModel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.SmartInvoiceModel === "undefined")
{
	/**
	 * @extends BX.Crm.FactoryBasedModel
	 * @memberOf BX.Crm
	 * @constructor
	 */
	BX.Crm.SmartInvoiceModel = function()
	{
		BX.Crm.SmartInvoiceModel.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.SmartInvoiceModel, BX.Crm.FactoryBasedModel);

	/**
	 * @return {boolean}
	 */
	BX.Crm.SmartInvoiceModel.prototype.isCaptionEditable = function()
	{
		return true;
	};

	/**
	 * SmartInvoice caption and SmartInvoice TITLE field are separate entities and should not be confused
	 *
	 * @return {string}
	 */
	BX.Crm.SmartInvoiceModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		if (BX.Type.isString(title) && title.length > 0)
		{
			return title;
		}
		var caption = null;
		if (this.getField('IS_USE_NUMBER_IN_TITLE_PLACEHOLDER'))
		{
			caption = BX.Loc.getMessage(
				'CRM_SMART_INVOICE_TITLE',
				{
					'#NUMBER#': this.getField('ACCOUNT_NUMBER'),
					'#BEGINDATE#': this.getField('BEGINDATE')
				}
			);
		}
		else
		{
			var id = Number(this.getField('ID'));
			if (id <= 0)
			{
				id = '';
			}
			caption = BX.Loc.getMessage(
				'CRM_SMART_INVOICE_TITLE_PLACEHOLDER',
				{
					'#ID#': id,
				}
			);
		}

		return BX.Type.isString(caption) ? caption : '';
	};

	/**
	 * You can't change smart invoice caption
	 *
	 * @param {string} caption
	 */
	BX.Crm.SmartInvoiceModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};

	/**
	 * You can't change smart invoice caption
	 *
	 * @param {Object} data
	 */
	BX.Crm.SmartInvoiceModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};

	/**
	 * @param {string} id
	 * @param {Object} settings
	 * @return {BX.Crm.SmartInvoiceModel}
	 */
	BX.Crm.SmartInvoiceModel.create = function(id, settings)
	{
		var self = new BX.Crm.SmartInvoiceModel();
		self.initialize(id, settings);
		return self;
	};
}

if (typeof BX.Crm.StoreDocumentModel === "undefined")
{
	BX.Crm.StoreDocumentModel = function()
	{
		BX.Crm.StoreDocumentModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.StoreDocumentModel, BX.Crm.EntityModel);
	BX.Crm.StoreDocumentModel.prototype.isCaptionEditable = function()
	{
		return true;
	};
	BX.Crm.StoreDocumentModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.storeDocument;
	};
	BX.Crm.StoreDocumentModel.prototype.getCaption = function()
	{
		return this.getField("TITLE", "");
	};
	BX.Crm.StoreDocumentModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.StoreDocumentModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.StoreDocumentModel.create = function(id, settings)
	{
		var self = new BX.Crm.StoreDocumentModel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.SmartDocumentModel === "undefined")
{
	/**
	 * @extends BX.Crm.FactoryBasedModel
	 * @memberOf BX.Crm
	 * @constructor
	 */
	BX.Crm.SmartDocumentModel = function()
	{
		BX.Crm.SmartDocumentModel.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.SmartDocumentModel, BX.Crm.FactoryBasedModel);
	/**
	 * @param {string} id
	 * @param {Object} settings
	 * @return {BX.Crm.QuoteModel}
	 */
	BX.Crm.SmartDocumentModel.create = function(id, settings)
	{
		var self = new BX.Crm.SmartDocumentModel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
