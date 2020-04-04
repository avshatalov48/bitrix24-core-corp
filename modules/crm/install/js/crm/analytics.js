BX.namespace("BX.Crm");

if(typeof(BX.Crm.AnalyticTracker) === "undefined")
{
	BX.Crm.AnalyticTracker = function()
	{
		this._id = "";
		this._settings = {};
	};

	BX.Crm.AnalyticTracker.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_analytic_tracker";
			this._settings = settings ? settings : {};
		},
		getId: function()
		{
			return this._id;
		},
		prepareEntityActionParams: function(action, entityTypeId, contextParams)
		{
			var params =
				{
					page: this._id,
					type: BX.CrmEntityType.resolveName(entityTypeId).toLowerCase(),
					action: action
				};

			if(BX.type.isPlainObject(contextParams))
			{
				params = BX.mergeEx(params, contextParams);
			}

			var additionalParams = BX.prop.getObject(this._settings, "params", null);
			if(BX.type.isPlainObject(additionalParams))
			{
				params = BX.mergeEx(params, additionalParams);
			}

			return params;

		}
	};
	BX.Crm.AnalyticTracker.config = null;
	BX.Crm.AnalyticTracker.current = null;

	BX.Crm.AnalyticTracker.getCurrent = function()
	{
		if(!this.current && this.config)
		{
			this.current = new BX.Crm.AnalyticTracker();
			this.current.initialize(BX.prop.getString(this.config, "id"), BX.prop.getObject(this.config, "settings"));
		}
		return this.current;
	};
}