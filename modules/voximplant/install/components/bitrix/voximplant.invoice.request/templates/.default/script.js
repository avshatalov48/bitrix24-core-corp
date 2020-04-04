;(function()
{
	BX.namespace("BX.Voximplant");

	BX.Voximplant.DocsRequest = function(config)
	{
		this.elements = {
			submitButton: config.submitButton,
			cancelButton: config.cancelButton
		};

		this.init();
	};

	BX.Voximplant.DocsRequest.prototype = {
		init: function()
		{
			BX.bind(this.elements.submitButton, "click", this.onSubmitClick.bind(this));
			BX.bind(this.elements.cancelButton, "click", this.onCancelClick.bind(this));
		},

		onSubmitClick: function()
		{
			var form = document.forms["request-docs"];

			var request = {
				period: form["PERIOD"].value,
				index: form["ADDRESS_INDEX"].value,
				address: form["ADDRESS"].value,
				email: form["EMAIL"].value
			};

			BX.ajax.runComponentAction(
				"bitrix:voximplant.invoice.request",
				"sendRequest",
				{data: request}
			).then(function(response)
			{
				BX.UI.Notification.Center.notify({
					content: BX.message("VOX_CLOSING_DOCS_REQUEST_SENT")
				});
				BX.SidePanel.Instance.close();

			}).catch(function(response)
			{
				var errors = response.errors;

				var message = errors.map(function(er)
				{
					return er.message
				}).join("; ");
				BX.Voximplant.alert(" ", message);
			});
		},

		onCancelClick: function()
		{
			BX.SidePanel.Instance.close();
		}
	}
})();