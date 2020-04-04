BX.namespace("BX.Mobile.Grid.Fields");

BX.Mobile.Grid.Fields =
{
	gridId: "",
	eventName: "",

	init: function(params)
	{
		if (typeof params === "object")
		{
			this.gridId = params.gridId || "";
			this.eventName = params.eventName || "";
		}

		var fields = BX("bx-mobile-interface-fields-block").querySelectorAll("[data-role='mobile-grid-field-item']");
		if (fields)
		{
			for(var i=0; i<fields.length; i++)
			{
				if(BX.hasClass(fields[i], "mobile-grid-field-selected"))
					this.selectedFields[fields[i].getAttribute("data-id")] = true;
				new FastButton(fields[i], BX.proxy(function()
				{
					BX.Mobile.Grid.Fields.selectField(this.item);
				}, { item:fields[i] }));
			}
		}
	},
	selectField: function(item)
	{
		var name = item.getAttribute("data-id");
		if (BX.hasClass(item, "mobile-grid-field-selected"))
		{
			BX.removeClass(item, "mobile-grid-field-selected");
			delete this.changes[name]
		}
		else
		{
			if(!this.selectedFields[name])
			{
				this.changes[name] = true;
			}

			BX.addClass(item, "mobile-grid-field-selected");
		}
	},
	changes:{},
	selectedFields:{},
	apply: function()
	{

		BXMobileApp.UI.Page.LoadingScreen.show();

		var fields = [],
			code;

		var fieldNodes = BX.findChildren(BX("bx-mobile-interface-fields-block"), {className:"mobile-grid-field-selected"}, true);
		if (fieldNodes)
		{
			for (var i = 0; i < fieldNodes.length; i++)
			{
				code = fieldNodes[i].getAttribute("data-id");
				fields.push(code);
			}
		}

		BX.ajax.post(
			'/mobile/?mobile_action=mobile_grid_fields',
			{
				sessid: BX.bitrix_sessid(),
				fields: fields,
				action: "fields",
				gridId: this.gridId
			},
			BX.proxy(function()
			{
				BXMobileApp.UI.Page.LoadingScreen.hide();
				try
				{
					Object.keys(this.changes).forEach((function(field){
						fabric.Answers.sendCustomEvent("TASK/VISIBLE_FIELD/"+field, {});
					}).bind(this))
				}catch (e)
				{

				}
				BXMobileApp.onCustomEvent(this.eventName, {}, true);
				app.closeModalDialog();
			}, this)
		);
	}
};