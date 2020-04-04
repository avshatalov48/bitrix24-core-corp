BX.namespace("BX.Mobile.Grid.Sort");

BX.Mobile.Grid.Sort = {
	gridId: "",
	eventName: "",

	init: function(params)
	{
		if (typeof params === "object")
		{
			this.gridId = params.gridId || "";
			this.eventName = params.eventName || "";
		}

		var fields = BX("bx-mobile-interface-sort-block").querySelectorAll("[data-role='mobile-sort-item']");
		if (fields)
		{
			for(var i=0; i<fields.length; i++)
			{
				if(BX.hasClass(fields[i], "mobile-grid-field-selected"))
					this.selectedField = fields[i].getAttribute("data-sort-id");
				new window.FastButton(fields[i], BX.proxy(function()
				{
					BX.Mobile.Grid.Sort.selectField(this.item);
				}, { item:fields[i] }));
			}
		}
	},
	selectedField:"",
	apply : function()
	{
		window.BXMobileApp.UI.Page.LoadingScreen.show();
		var sortFieldNode = BX.findChild(BX("bx-mobile-interface-sort-block"), {className:"mobile-grid-field-selected"}, true, false);
		var sortBy = sortFieldNode ? sortFieldNode.getAttribute("data-sort-id") : "";

		var sortOrderNode = BX.findChild(BX("bx-mobile-interface-sort-block"), {className:"mobile-grid-button-sort-selected"}, true, false);
		var sortOrder = sortOrderNode ? sortOrderNode.getAttribute("data-role") : "asc";

		BX.ajax.post(
			'/mobile/?mobile_action=mobile_grid_sort',
			{
				sessid: BX.bitrix_sessid(),
				sortBy: sortBy,
				sortOrder: sortOrder,
				action: "sort",
				gridId: this.gridId
			},
			BX.proxy(function()
			{
				window.BXMobileApp.UI.Page.LoadingScreen.hide();
				try
				{
					if(this.selectedField != sortBy)
					{
						fabric.Answers.sendCustomEvent("TASK/SORT_FIELD_SET/"+sortBy, {});
					}
				}catch (e)
				{

				}

				window.BXMobileApp.onCustomEvent(this.eventName, { sortBy: sortBy, sortOrder: sortOrder, action: "sort", gridId: this.gridId }, true);
				app.closeModalDialog();
			}, this)
		);
	},
	selectField: function(item)
	{
		var fields = BX.findChildren(BX("bx-mobile-interface-sort-block"), {className: "mobile-grid-field-selected"}, true);
		if (fields)
		{
			for(var i=0; i<fields.length; i++)
			{
				BX.removeClass(fields[i], "mobile-grid-field-selected");
			}
		}
		BX.addClass(item, "mobile-grid-field-selected");
	},
	selectOrder: function(sort)
	{
		var ascNode = BX("bx-mobile-interface-sort-block").querySelector("[data-role='asc']");
		var descNode = BX("bx-mobile-interface-sort-block").querySelector("[data-role='desc']");

		if (sort == "asc")
		{
			BX.removeClass(descNode, "mobile-grid-button-sort-selected");
			BX.addClass(ascNode, "mobile-grid-button-sort-selected");
		}
		else if (sort == "desc")
		{
			BX.removeClass(ascNode, "mobile-grid-button-sort-selected");
			BX.addClass(descNode, "mobile-grid-button-sort-selected");
		}
	}
};