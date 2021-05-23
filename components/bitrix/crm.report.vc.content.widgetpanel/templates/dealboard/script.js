;(function()
{
	BX.namespace("BX.Crm.Report");

	if (BX.Crm.Report.DealWidgetBoard)
	{
		return;
	}

	BX.Crm.Report.DealWidgetBoard =	{
		currentCategoryId: 0,
		menu: null,
		onSelectCategoryButtonClick: function(element)
		{
			if(!this.menu)
			{
				var categories = BX.CrmDealCategory.getListItems();

				var menuItems = [];

				menuItems.push({
					text: BX.message("CRM_REPORT_DEAL_ALL_DEALS"),
					dataset: {
						value: -1,
						text: BX.message("CRM_REPORT_DEAL_ALL_DEALS")
					},
					onclick: this.onMenuItemClick.bind(this)
				});

				categories.forEach(function(categoryDescription)
				{
					menuItems.push({
						text: BX.util.htmlspecialchars(categoryDescription.text),
						dataset: categoryDescription,
						onclick: this.onMenuItemClick.bind(this)
					})
				}.bind(this));

				this.menu = new BX.PopupMenuWindow('crm-select-category', element, menuItems);
			}

			this.menu.toggle();
		},
		onMenuItemClick: function(event)
		{
			var categoryId = event.currentTarget.dataset.value;
			var categoryName = event.currentTarget.dataset.text;
			this.menu.close();
			this.setCurrentCategory(categoryId).then(function()
			{
				var button = document.getElementById("crm-report-deal-category");
				button.innerText = categoryName;
				this.reload();
			}.bind(this)).catch(function(response)
			{
				this.logResponseErrors(response)
			}.bind(this));
		},
		setCurrentCategory: function(categoryId)
		{
			return BX.ajax.runComponentAction("bitrix:crm.report.vc.content.widgetpanel", "setDealCategoryId", {
				data: {
					categoryId: categoryId
				}
			});
		},
		reload: function()
		{
			var currentBoard = BX.VisualConstructor.BoardRepository.getLast();
			if(currentBoard)
			{
				currentBoard.reload();
			}
		},
		logResponseErrors: function(response)
		{
			console.error(response.errors.map(function(error){return error.message}).join("\n"));
		}
	};
})();