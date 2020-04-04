BX.namespace("BX.Mobile.Grid.Filter");

BX.Mobile.Grid.Filter = {
	gridId: "",
	eventName: "",
	ajaxPath: "",
	formId: "",
	formFields: {},

	init: function(params)
	{
		if (typeof params === "object")
		{
			this.gridId = params.gridId || "";
			this.eventName = params.eventName || "";
			this.ajaxPath = params.ajaxPath || "";
			this.formId = params.formId || "";
			this.formFields = params.formFields || {};
		}
	},

	apply: function()
	{
		window.BXMobileApp.UI.Page.LoadingScreen.show();

		var form = BX(this.formId);
		if(form)
		{
			var dataAjax = {gridId: this.gridId, action: "saveFilter", sessid:BX.bitrix_sessid()};
			var dataFormValues = {};
			var dataFormRows = [];
			for (var i = 0; i < form.elements.length; i++)
			{
				if (form[i].name in this.formFields)
				{
					if (form[i].tagName == "SELECT")
					{
						var selNode = form[i].options[form[i].selectedIndex];
						if (selNode && selNode.value)
							dataFormValues[form[i].name] = selNode.value;
						else
							dataFormValues[form[i].name] = "";
					}
					else
						dataFormValues[form[i].name] = form[i].value;

					if (form[i].name)
					{
						var itemId = form[i].name;
						//itemId = itemId.replace("bx_", "");
						dataFormRows.push(itemId);
					}
				}
			}
			dataAjax.fields = dataFormValues;
			dataAjax.filter_rows = dataFormRows;

			BX.ajax.post(
				this.ajaxPath,
				dataAjax,
				BX.proxy(function()
				{
					window.BXMobileApp.UI.Page.LoadingScreen.hide();
					window.BXMobileApp.onCustomEvent(this.eventName, {}, true);
					app.closeModalDialog();
				}, this)
			);
		}
	}
};