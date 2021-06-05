BX.namespace("BX.Crm");

if(typeof BX.Crm.EntitySelector === "undefined")
{
	/**
	 * @class
	 * @constructor
	 */
	BX.Crm.EntitySelector = function()
	{
		/**
		 * @protected
		 */
		this._entitySelector = null;

		/**
		 * @type {(null|string)}
		 * @protected
		 */
		this._id = null;

		/**
		 * @type {(null|Node)}
		 * @protected
		 */
		this._target = null;

		/**
		 * @type {(null|string)}
		 * @protected
		 */
		this._entityTypeName = null;

		/**
		 * @type {(null|number)}
		 * @protected
		 */
		this._entityTypeId = null;

		/**
		 * @type {(null|number)}
		 * @protected
		 */
		this._parentEntityTypeId = null;

		/**
		 * @type {boolean}
		 * @protected
		 */
		this._enableMyCompanyOnly = false;

		/**
		 * @type {boolean}
		 * @protected
		 */
		this._withRequisites = false;

		/**
		 * @type {boolean}
		 * @protected
		 */
		this._enableSearch = true;

		/**
		 * @type {(string|null)}
		 * @protected
		 */
		this._context = null;

		/**
		 * @type {(null|onSelectCallback)}
		 * @protected
		 */
		this._onSelectCallback = null;

		/**
		 * @type {(null|function)}
		 * @protected
		 */
		this._onBeforeEntityLoadCallback = null;

		/**
		 * @type {(null|function)}
		 * @protected
		 */
		this._onAfterEntityLoadCallback = null;

	};
	BX.Crm.EntitySelector.prototype =
		{
			/**
			 * @protected
			 * @param {string} id
			 * @param {EntitySelectorSettings} settings
			 */
			initialize: function(id, settings)
			{
				this._id = String(id) ? String(id) : null;
				this._target = BX.prop.getElementNode(settings, 'target', null);
				this._entityTypeName = BX.prop.getString(settings, "entityTypeName", null);
				this._entityTypeId = BX.CrmEntityType.resolveId(this._entityTypeName);
				this._enableMyCompanyOnly = BX.prop.getBoolean(settings, "enableMyCompanyOnly", false);
				this._parentEntityTypeId = BX.prop.getInteger(settings, "parentEntityTypeId", null);
				this._withRequisites = BX.prop.getBoolean(settings, "withRequisites", false);
				this._onSelectCallback = BX.prop.getFunction(settings, "onSelectCallback", null);
				this._onBeforeEntityLoadCallback = BX.prop.getFunction(settings, "onBeforeEntityLoadCallback", null);
				this._onAfterEntityLoadCallback = BX.prop.getFunction(settings, "onAfterEntityLoadCallback", null);
				this._context = BX.prop.getString(settings, "context", null);
				this._enableSearch = BX.prop.getBoolean(settings, "enableSearch", true);
				this._entitySelector = this.createEntitySelector(id, settings);
			},

			/**
			 * @protected
			 */
			createEntitySelector: function()
			{
				throw 'Should be overwritten in the derived class';
			},

			/**
			 * @protected
			 * @param {onSelectCallback} onSelectCallback
			 * @returns {function} - adapted function bound to 'this' context
			 */
			getAdaptedOnSelectCallback: function(onSelectCallback)
			{
				throw 'Should be overwritten in the derived class';
			},

			/**
			 * @returns {boolean}
			 */
			isOpened: function()
			{
				throw 'Should be overwritten in the derived class';
			},

			/**
			 * @returns {undefined}
			 */
			open: function()
			{
				throw 'Should be overwritten in the derived class';
			},

			/**
			 * @returns {undefined}
			 */
			close: function()
			{
				throw 'Should be overwritten in the derived class';
			},
		};

	//region Static functions

	/**
	 * @typedef {Object} EntitySelectorSettings
	 * @property {Node} target
	 * @property {string} entityTypeName
	 * @property {Object} loader
	 * @property {(function|onSelectCallback)} onSelectCallback
	 * @property {function} onBeforeEntityLoadCallback
	 * @property {function} onAfterEntityLoadCallback
	 * @property {boolean} enableMyCompanyOnly
	 */

	/**
	 * @callback onSelectCallback
	 * @param {BX.CrmEntityInfo} entityInfo
	 */

	/**
	 * @param {string} id
	 * @param {EntitySelectorSettings} settings
	 *
	 * @returns {BX.Crm.EntitySelector}
	 */
	BX.Crm.EntitySelector.create = function(id, settings)
	{
		var self = BX.Crm.EntitySelector.getSelectorBySettings(settings);
		self.initialize(id, settings);
		return self;
	};

	/**
	 * @protected
	 * @param {EntitySelectorSettings} settings
	 * @returns {BX.Crm.EntitySelector}
	 */
	BX.Crm.EntitySelector.getSelectorBySettings = function(settings)
	{
		var loader = BX.prop.getObject(settings, 'loader', null);
		if (loader && BX.type.isNotEmptyObject(loader))
		{
			return new BX.Crm.EntitySelectorCrmSelector();
		}

		return new BX.Crm.EntitySelectorUISelector();
	};
	//endregion
}

if(typeof BX.Crm.EntitySelectorCrmSelector === "undefined")
{
	/**
	 * @class
	 * @constructor
	 */
	BX.Crm.EntitySelectorCrmSelector = function()
	{
		BX.Crm.EntitySelectorCrmSelector.superclass.constructor.apply(this);
		/**
		 * @type {Object}
		 * @protected
		 */
		this._loaderConfig = null;
	};
	BX.extend(BX.Crm.EntitySelectorCrmSelector, BX.Crm.EntitySelector);

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorCrmSelector.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntitySelectorCrmSelector.superclass.initialize.apply(this, [id, settings]);
		this._loaderConfig = BX.prop.getObject(settings, "loader", null);
	};

	/**
	 * @protected
	 * @returns {BX.Crm.EntityEditorCrmSelector}
	 */
	BX.Crm.EntitySelectorCrmSelector.prototype.createEntitySelector = function()
	{
		return BX.Crm.EntityEditorCrmSelector.create(
			this._id,
			{
				entityTypeIds: [ this._entityTypeId ],
				enableMyCompanyOnly: this._enableMyCompanyOnly,
				callback: this.getAdaptedOnSelectCallback(this._onSelectCallback),
			}
		);
	};

	/**
	 * @callback adaptedCrmCallback
	 * @param {BX.Crm.EntityEditorCrmSelector} sender
	 * @param {Object} item
	 */

	/**
	 * @inheritDoc
	 * @returns {adaptedCrmCallback}
	 */
	BX.Crm.EntitySelectorCrmSelector.prototype.getAdaptedOnSelectCallback = function(onSelectCallback)
	{
		/**
		 * @param {BX.CrmDataLoader} sender
		 * @param {Object} result
		 */
		var onLoadCallback = function(sender, result)
		{
			var entityData = BX.prop.getObject(result, "DATA", null);
			if(entityData)
			{
				if (this._onAfterEntityLoadCallback)
				{
					this._onAfterEntityLoadCallback();
				}

				var entityInfo = BX.CrmEntityInfo.create(entityData);
				onSelectCallback(entityInfo);
			}
		}.bind(this);

		return function(sender, item)
		{
			var entityLoader = BX.prop.getObject(this._loaderConfig, this._entityTypeName, null);
			if(entityLoader)
			{
				if (this._onBeforeEntityLoadCallback)
				{
					this._onBeforeEntityLoadCallback();
				}

				var entityId = BX.prop.getInteger(item, "entityId", 0);

				BX.CrmDataLoader.create(
					this._id,
					{
						serviceUrl: entityLoader["url"],
						action: entityLoader["action"],
						params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": entityId }
					}
				).load(onLoadCallback);
			}

			this.close();
		}.bind(this);
	};

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorCrmSelector.prototype.isOpened = function()
	{
		return this._entitySelector.isOpened();
	};

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorCrmSelector.prototype.open = function()
	{
		this._entitySelector.open(this._target);
	};

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorCrmSelector.prototype.close = function()
	{
		this._entitySelector.close();
	};
}

if(typeof BX.Crm.EntitySelectorUISelector === "undefined")
{
	/**
	 * @class
	 * @constructor
	 */
	BX.Crm.EntitySelectorUISelector = function()
	{
		BX.Crm.EntitySelectorUISelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntitySelectorUISelector, BX.Crm.EntitySelector);

	/**
	 * @protected
	 * @returns {BX.UI.EntitySelector.Dialog}
	 */
	BX.Crm.EntitySelectorUISelector.prototype.createEntitySelector = function()
	{
		var entityTypeName = String(this._entityTypeName).toLowerCase();

		return new BX.UI.EntitySelector.Dialog({
			targetNode: this._target,
			id: this._id,
			context: this._context,
			enableSearch: this._enableSearch,
			multiple: false,
			hideOnSelect: true,
			entities: [
				{
					id: entityTypeName,
					dynamicLoad: true,
					dynamicSearch: true,
					options: {
						enableMyCompanyOnly: this._enableMyCompanyOnly,
						withRequisites: this._withRequisites,
						parentEntityTypeId: this._parentEntityTypeId
					},
				},
			],
			events: {
				'Item:onSelect': this.getAdaptedOnSelectCallback(this._onSelectCallback),
			}
		});
	};

	/**
	 * @callback adaptedUICallback
	 * @param {BaseEvent} event
	 */

	/**
	 * @inheritDoc
	 * @returns {adaptedUICallback}
	 */
	BX.Crm.EntitySelectorUISelector.prototype.getAdaptedOnSelectCallback = function(onSelectCallback)
	{
		return function(event)
		{
			/** @type {BX.UI.EntitySelector.Item} */
			var item = event.getData().item;
			var entityInfo = item.getCustomData().get('entityInfo');
			onSelectCallback(BX.CrmEntityInfo.create(entityInfo));
		}.bind(this);
	};

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorUISelector.prototype.isOpened = function()
	{
		return this._entitySelector.isOpen();
	};

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorUISelector.prototype.open = function()
	{
		this._entitySelector.show();
	};

	/**
	 * @inheritDoc
	 */
	BX.Crm.EntitySelectorUISelector.prototype.close = function()
	{
		this._entitySelector.hide();
	};
}