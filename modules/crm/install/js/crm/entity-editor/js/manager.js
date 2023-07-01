BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityEditorDupManager === "undefined")
{
	BX.Crm.EntityEditorDupManager = function()
	{
		this._id = "";
		this._settings = null;
		this._groupInfos = null;

		this._isEnabled = false;
		this._serviceUrl = "";
		this._entityTypeName = "";
		this._form = null;
		this._controller = null;
	};
	BX.Crm.EntityEditorDupManager.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._isEnabled = BX.prop.getBoolean(this._settings, "enabled", "");
				if(!this._isEnabled)
				{
					return;
				}

				this._groupInfos = BX.prop.getObject(this._settings, "groups", {});

				this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
				this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
				this._form = BX.prop.get(this._settings, "form", null);
				this._ignoredItems = BX.prop.getArray(this._settings, 'ignoredItems', []);

				this._controller = BX.CrmDupController.create(
					this._id,
					{
						serviceUrl: this._serviceUrl,
						entityTypeName: this._entityTypeName,
						form: this._form,
						clientSearchBox: BX.prop.get(this._settings, 'clientSearchBox', null),
						enableEntitySelect: BX.prop.getBoolean(this._settings, 'enableEntitySelect', false),
						searcSummaryPosition: "right",
						ignoredItems: this._ignoredItems,
					}
				);
			},
			isEnabled: function()
			{
				return this._isEnabled;
			},
			search: function()
			{
				this._controller.initialSearch();
			},
			getGroupInfo: function(groupId)
			{
				return this._groupInfos.hasOwnProperty(groupId) ? this._groupInfos[groupId] : null;
			},
			getGroup: function(groupId)
			{
				return this._isEnabled ? this._controller.getGroup(groupId) : null;
			},
			ensureGroupRegistered: function(groupId)
			{
				if(!this._isEnabled)
				{
					return null;
				}

				var group = this.getGroup(groupId);
				if(!group)
				{
					group = this._controller.registerGroup(groupId, this.getGroupInfo(groupId));
				}
				return group;
			},
			registerField: function(config)
			{
				if(!this._isEnabled)
				{
					return null;
				}

				var groupId = BX.prop.getString(config, "groupId", "");
				var field = BX.prop.getObject(config, "field", null);
				if(groupId === "" || !field)
				{
					return null;
				}

				var group = this.ensureGroupRegistered(groupId);
				if(!group)
				{
					return null;
				}

				return group.registerField(field);
			},
			unregisterField: function(config)
			{
				if(!this._isEnabled)
				{
					return;
				}

				var groupId = BX.prop.getString(config, "groupId", "");
				var field = BX.prop.getObject(config, "field", null);
				if(groupId === "" || !field)
				{
					return;
				}

				var group = this.getGroup(groupId);
				if(!group)
				{
					return;
				}

				group.unregisterField(field);
			}
		};
	BX.Crm.EntityEditorDupManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorDupManager();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityBizprocManager === "undefined")
{
	BX.Crm.EntityBizprocManager = function()
	{
		this._id = "";
		this._settings = {};
		this._moduleId = "";
		this._entity = "";
		this._documentType = "";
		this._autoExecuteType = 0;

		this._containerId = null;
		this._fieldName = null;

		this._validParameters = null;
		this._formInput = null;

		this._editor = null;
		this._starter = null;
	};
	BX.Crm.EntityBizprocManager.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._hasParameters = BX.prop.getBoolean(this._settings, "hasParameters", false);
				this._moduleId = BX.prop.getString(this._settings, "moduleId", "");
				this._entity = BX.prop.getString(this._settings, "entity", "");
				this._documentType = BX.prop.getString(this._settings, "documentType", "");
				this._autoExecuteType = BX.prop.getInteger(this._settings, "autoExecuteType", 0);
				this._containerId = BX.prop.getString(this._settings, "containerId", '');
				this._fieldName = BX.prop.getString(this._settings, "fieldName", '');
				this._contentNode = this._containerId ? BX(this._containerId) : null;

				if (this._hasParameters)
				{
					this._starter = new BX.Bizproc.Starter({
						moduleId: this._moduleId,
						entity: this._entity,
						documentType: this._documentType
					});
				}
			},
			/**
			 *
			 * @param {BX.Crm.EntityValidationResult} result
			 * @returns {BX.Promise}
			 */
			onBeforeSave: function(result)
			{
				var promise = new BX.Promise();

				var deferredWaiter = function()
				{
					window.setTimeout(
						BX.delegate(
							function()
							{
								promise.fulfill();
							},
							this
						),
						0
					);
				};

				if(result.getStatus() && this._hasParameters && this._validParameters === null)
				{
					try
					{
						this._starter.showAutoStartParametersPopup(
							this._autoExecuteType,
							{
								contentNode: this._contentNode,
								callback: this.onFillParameters.bind(this, promise)
							}
						);
						this._contentNode = null;
					}
					catch (e)
					{
						if ('console' in window)
						{
							window.console.log('Error occurred when bizproc popup is going to show', e);
						}
						deferredWaiter();
					}
				}
				else
				{
					deferredWaiter();
				}

				return promise;
			},

			onAfterSave: function()
			{
				this._validParameters = null;
			},

			onFillParameters: function(promise, data)
			{
				this._validParameters = data.parameters;

				if (!this._formInput && this._editor)
				{
					var form = this._editor.getFormElement();
					this._formInput = BX.create("input", { props: { type: "hidden", name: this._fieldName } });
					form.appendChild(this._formInput);
				}

				if (this._formInput)
				{
					this._formInput.value = this._validParameters;
				}

				promise.fulfill();
			}
		};
	if(typeof(BX.Crm.EntityBizprocManager.messages) === "undefined")
	{
		BX.Crm.EntityBizprocManager.messages = {};
	}
	BX.Crm.EntityBizprocManager.items = {};
	BX.Crm.EntityBizprocManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityBizprocManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}

if(typeof BX.Crm.EntityRestPlacementManager === "undefined")
{
	BX.Crm.EntityRestPlacementManager = function()
	{
		this._id = "";
		this._entity = "";

		this._editor = null;
	};

	BX.Crm.EntityRestPlacementManager.items = {};
	BX.Crm.EntityRestPlacementManager.prototype = {
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._entity = this.getSetting("entity");

			var bottomButton = BX(this.getSetting("bottom_button_id"));
			if(bottomButton)
			{
				BX.bind(bottomButton, 'click', BX.proxy(this.openMarketplace, this));
			}

			BX.defer(this.initializeInterface, this)();
		},

		openMarketplace: function()
		{
			BX.rest.Marketplace.open({
				PLACEMENT: this.getSetting("placement")
			});
		},

		getSetting: function(name)
		{
			return BX.prop.getString(this._settings, name, '')
		},

		initializeInterface: function()
		{
			if(!!BX.rest && !!BX.rest.AppLayout)
			{
				var PlacementInterface = BX.rest.AppLayout.initializePlacement('CRM_' + this._entity + '_DETAIL_TAB');

				var entityTypeId = this._editor._entityTypeId, entityId = this._editor._entityId;

				PlacementInterface.prototype.resizeWindow = function(params, cb)
				{
					var f = BX(this.params.layoutName);
					params.height = parseInt(params.height);

					if(!!params.height)
					{
						f.style.height = params.height + 'px';
					}

					var p = BX.pos(f);
					cb({width: p.width, height: p.height});
				};

				PlacementInterface.prototype.reloadData = function(params, cb)
				{
					BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
					cb();
				};
			}
		}
	};

	BX.Crm.EntityRestPlacementManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityRestPlacementManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
