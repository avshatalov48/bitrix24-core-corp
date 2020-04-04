;(function ()
{
	var namespace = BX.namespace('BX.Dav');
	if (namespace.SynchronizeSettings)
	{
		return;
	}

	namespace.SynchronizeSettings = function(params)
	{
		this.init(params);
	};

	namespace.SynchronizeSettings.prototype = {
		init: function(params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;
			this.componentAjaxLoad = params.componentAjaxLoad == "Y";

			var button = BX("davSynchroSaveButton");
			if (BX.type.isDomNode(button))
			{
				BX.bind(button, "click", function () {

					if (this.componentAjaxLoad)
					{
						this.save(button);
					}
					else
					{
						BX('synchronize_settings_form').elements['submit'].click()
					}

				}.bind(this));
			}
		},

		save: function(button)
		{
			BX.addClass(button, "ui-btn-wait");

			BX.ajax.runComponentAction(this.componentName, "saveSettings", {
				signedParameters: this.signedParameters,
				mode: 'class',
				data: BX.ajax.prepareForm(BX('synchronize_settings_form'))
			}).then(function (response) {
				BX.removeClass(button, "ui-btn-wait");
			}, function (response) {
				BX.removeClass(button, "ui-btn-wait");
			}.bind(this));
		}
	};
})();