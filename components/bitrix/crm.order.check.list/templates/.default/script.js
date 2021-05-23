BX.namespace("BX.Crm");

if(typeof(BX.Crm.OrderPaymentCheckList) === "undefined")
{
	BX.Crm.OrderPaymentCheckList = function()
	{
		this._id = "";
		this._settings = "";
	};

	BX.Crm.OrderPaymentCheckList.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this.unbindEvents();
				this.bindEvents();
			},

			bindEvents: function()
			{
				BX.bind(document.querySelector('.order-payment-check-add'), 'click', BX.delegate(function (e) {
					this.onShowCheck(e, this._settings.ADD_CHECK_URL);
				}, this));

			},
			unbindEvents: function()
			{
				BX.unbind(document.querySelector('.order-payment-check-add'), 'click', BX.delegate(function (e) {
					this.onShowCheck(e, this._settings.ADD_CHECK_URL);
				}, this));

			},

			onShowCheck: function(e, url)
			{
				BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPaymentCheckUpdate, this));

				BX.Crm.Page.openSlider(url, { width: 500 });
			},

			onPaymentCheckUpdate: function(event)
			{
				if (event.getEventId() === 'CrmOrderPaymentCheck::Update')
				{
					this.unbindEvents();
					BX.Main.gridManager.reload(this._id);
				}
			}
		};


	BX.Crm.OrderPaymentCheckList.create = function(id, settings)
	{
		var self = new BX.Crm.OrderPaymentCheckList();
		self.initialize(id, settings);
		return self;
	};

	BX.Crm.OrderPaymentCheckList.refreshCheck = function(id, settings)
	{
		var _settings = settings ? settings : {};
		var _ajaxUrl = BX.type.isNotEmptyString(_settings.AJAX_URL) ? _settings.AJAX_URL : '';
		var _gridId = BX.type.isNotEmptyString(_settings.GRID_ID) ? _settings.GRID_ID : '';

		if ( !BX.type.isNotEmptyString(_ajaxUrl) )
		{
			return;
		}

		var data = {
			ACTION: 'REFRESH_CHECK',
			ID: id
		};

		BX.ajax(
			{
				url: _ajaxUrl,
				method: 'POST',
				dataType: 'json',
				data: data,
				onsuccess: BX.delegate(function(data) {
					if (BX.type.isNotEmptyString(data.ERROR))
					{
						alert(data.ERROR);
					}
					else
					{
						BX.Main.gridManager.reload(_gridId);
					}

				} , this),
				onfailure: function (data)
				{
				}
			});
	}
}