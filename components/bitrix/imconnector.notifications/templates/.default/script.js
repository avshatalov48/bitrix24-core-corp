;(function ()
{
	BX.namespace("BX.ImConnector");

	BX.ImConnector.Notifications =
	{
		showTermsOfService: function()
		{
			return new Promise(function(resolve)
			{
				BX.ajax.runComponentAction("bitrix:imconnector.notifications", "getTermsOfService", {
					mode: "class"
				}).then(function(response)
				{
					var termsPopup = new BX.PopupWindow({
						titleBar: response.data.title,
						content: response.data.html,
						closeIcon: true,
						width: 600,
						height: 700,
						buttons: [
							new BX.PopupWindowButton({
								text: response.data.okButton,
								className: 'popup-window-button-accept',
								events: {
									click: function ()
									{
										termsPopup.close();
										resolve();
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: BX.message('JS_CORE_WINDOW_CANCEL'),
								className: 'popup-window-button-link-cancel',
								events: {
									click: function()
									{
										termsPopup.close();
									}
								}
							})
						]
					});
					termsPopup.show();
				})
			})
		},

		onConnectButtonClick: function(button)
		{
			this.showTermsOfService().then(function()
			{
				return BX.ajax.runComponentAction("bitrix:imconnector.notifications", "saveTermsOfServiceAgreement", {
					mode: "class"
				});

			}).then(function()
			{
				button.form.submit();
			});
		},

		onSaveSettingsButtonClick: function(button)
		{
			BX.addClass(button, "ui-bnt-wait");
			BX.ajax.runComponentAction("bitrix:imconnector.notifications", "saveSettings", {
				mode: "class",
				data: new FormData(BX("settings-form"))
			}).then(function(response)
			{
				BX.removeClass(button, "ui-bnt-wait");
			}).catch(function(response)
			{
				BX.removeClass(button, "ui-bnt-wait");
				console.error(response.errors);
			})
		}
	};

})();