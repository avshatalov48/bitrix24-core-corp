BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityCounterManager === "undefined")
{
	BX.Crm.EntityCounterManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeId = 0;
		this._codes = null;
		this._extras = null;
		this._counterData = null;
		this._serviceUrl = "";
		this._isRequestRunning = false;
	};
	BX.Crm.EntityCounterManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._codes = BX.prop.getArray(this._settings, "codes", []);
			this._extras = BX.prop.getObject(this._settings, "extras", {});
			this._serviceUrl =  BX.prop.getString(this._settings, "serviceUrl", "");
			this._counterData = {};

			BX.addCustomEvent("onPullEvent-main", BX.delegate(this.onPullEvent, this));
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.resolveName(this._entityTypeId);
		},
		getCounterData: function()
		{
			return this._counterData;
		},
		setCounterData: function(data)
		{
			this._counterData = data;
		},
		onPullEvent: function(command, params)
		{
			if (command !== "user_counter")
			{
				return;
			}

			var enableRecalculation = false;
			var counterData = BX.prop.getObject(params, BX.message("SITE_ID"), {});
			for (var counterId in counterData)
			{
				if (!counterData.hasOwnProperty(counterId))
				{
					continue;
				}

				if(this._codes.indexOf(counterId) < 0)
				{
					continue;
				}

				if(BX.prop.getInteger(counterData, counterId, 0) < 0)
				{
					enableRecalculation = true;
					break;
				}
			}

			if(enableRecalculation)
			{
				this.startRecalculationRequest();
			}
		},
		startRecalculationRequest: function()
		{
			if(this._isRequestRunning)
			{
				return;
			}
			this._isRequestRunning = true;

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": "RECALCULATE",
						"ENTITY_TYPES": [ BX.CrmEntityType.resolveName(this._entityTypeId) ],
						"EXTRAS": this._extras
					},
					onsuccess: BX.delegate(this.onRecalculationSuccess, this)
				}
			);
		},
		onRecalculationSuccess: function(result)
		{
			this._isRequestRunning = false;

			var data = BX.prop.getObject(result, "DATA", null);
			if(!data)
			{
				return;
			}

			this.setCounterData(
				BX.prop.getObject(
					data,
					BX.CrmEntityType.resolveName(this._entityTypeId),
					{}
				)
			);
		}
	};
	BX.Crm.EntityCounterManager.instances = {};
	BX.Crm.EntityCounterManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityCounterManager();
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}
