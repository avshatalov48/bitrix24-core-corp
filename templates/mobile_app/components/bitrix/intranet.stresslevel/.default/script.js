;(function(window){
	app.enableScroll(false);
	var namespace = BX.namespace('BX.Intranet.StressLevelMobile');
	if (namespace.Manager)
	{
		return;
	}

	namespace.Manager = function(params)
	{
		this.init(params);
	};

	namespace.Manager.prototype = {
		init: function (params)
		{
			this.buttons = {
				disclaimer: BX('disclaimer-accept'),
				disclaimer_reject: BX('disclaimer-reject')
			};
			if (this.buttons.disclaimer)
			{
				BX.bind(this.buttons.disclaimer, 'click', this.acceptDisclaimer.bind(this));
			}
			if (this.buttons.disclaimer_reject)
			{
				BX.bind(this.buttons.disclaimer_reject, 'click', this.rejectDisclaimer.bind(this));
			}
		},

		acceptDisclaimer: function()
		{
			if (this.buttons.disclaimer)
			{
				BX.addClass(this.buttons.disclaimer, 'ui-btn-clock');
			}

			BX.ajax.runAction('socialnetwork.api.user.stresslevel.setdisclaimer', {
				data: {
				}
			}).then(function(response) {
				if (this.buttons.disclaimer)
				{

					BXMobileApp.Events.postToComponent("onDisclaimerAccepted", []);
					BXMobileApp.UI.Page.closeModalDialog();
					BX.removeClass(this.buttons.disclaimer, 'ui-btn-clock');
				}
			}.bind(this),
			function(response) {
				if (this.buttons.disclaimer)
				{
					BX.removeClass(this.buttons.disclaimer, 'ui-btn-clock');
				}
			}.bind(this));
		},
		rejectDisclaimer: function()
		{
			BXMPage.closeModalDialog();
		}
	};

	BX.ready(function () {
		new BX.Intranet.StressLevelMobile.Manager({
		});
	});

})(window);