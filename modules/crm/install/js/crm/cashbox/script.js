BX.namespace("BX.Crm");

if(typeof(BX.Crm.CheckCorrection) === "undefined")
{
	BX.Crm.CheckCorrection = function()
	{
		this._id = "";
		this._settings = "";
	};

	BX.Crm.CheckCorrection.prototype =
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
				BX.bind(document.querySelector('.check-correction-add'), 'click', BX.delegate(function (e) {
					this.onShowCheck(e, this._settings.ADD_CHECK_CORRECTION_URL);
				}, this));

			},
			unbindEvents: function()
			{
				BX.unbind(document.querySelector('.check-correction-add'), 'click', BX.delegate(function (e) {
					this.onShowCheck(e, this._settings.ADD_CHECK_CORRECTION_URL);
				}, this));

			},

			onShowCheck: function(e, url)
			{
				BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPaymentCheckUpdate, this));

				BX.Crm.Page.openSlider(url, { width: 500, cacheable : false});
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


	BX.Crm.CheckCorrection.create = function(id, settings)
	{
		var self = new BX.Crm.CheckCorrection();
		self.initialize(id, settings);
		return self;
	};
}