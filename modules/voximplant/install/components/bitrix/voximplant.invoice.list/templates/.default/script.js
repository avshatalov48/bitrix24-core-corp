(function()
{
	var downloadUrlTemplate = "";
	BX.namespace("BX.Voximplant");

	BX.Voximplant.Invoices = {
		init: function(params)
		{
			downloadUrlTemplate = BX.prop.getString(params, 'downloadUrlTemplate', downloadUrlTemplate);
		},

		getDownloadUrl: function(invoiceNumber)
		{
			return downloadUrlTemplate.replace("INVOICE_NUMBER", invoiceNumber);
		},

		downloadInvoice: function(invoiceNumber)
		{
			document.location.href = this.getDownloadUrl(invoiceNumber);
		},

		showClosingDocumentsRequest: function()
		{
			var scripts;

			BX.SidePanel.Instance.open("voximplant:invoice.request", {
				width: 700,
				cacheable: false,
				contentCallback: function()
				{
					var result = new BX.Promise();

					top.BX.ajax.runComponentAction(
						"bitrix:voximplant.invoice.request",
						"renderComponent",
						{}
					).then(function(response)
					{
						var data = response.data;
						var processed = top.BX.processHTML(data.html);
						scripts = processed.SCRIPT;

						result.resolve(processed.HTML);
					}).catch(function(response)
					{
						console.error(response.errors[0]);
					});

					return result;
				},
				events: {
					onLoad: function (event)
					{
						top.BX.ajax.processScripts(scripts);

						scripts = null;
					}
				}
			})
		},

	}
})();