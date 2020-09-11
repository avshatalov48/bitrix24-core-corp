BX.namespace("Tasks.KanbanComponent");

BX.Tasks.KanbanComponent.ClickSort = function(event, item)
{
	var order = "desc";

	if (
		typeof item.params !== "undefined" &&
		typeof item.params.order !== "undefined"
	)
	{
		order = item.params.order;
	}

	// refresh icons and save selected
	if (!BX.hasClass(BX(item.layout.item), "menu-popup-item-accept"))
	{
		var menuItems = item.menuWindow.menuItems;
		for (var i = 0, c = menuItems.length; i < c; i++)
		{
			BX.removeClass(BX(menuItems[i].layout.item), "menu-popup-item-accept");
		}
		BX.addClass(BX(item.layout.item), "menu-popup-item-accept");

		BX.ajax({
			method: "POST",
			dataType: "json",
			url: ajaxHandlerPath,
			data: {
				action: "setNewTaskOrder",
				order: order,
				sessid: BX.bitrix_sessid(),
				params: ajaxParams
			},
			onsuccess: function(data)
			{
				BX.onCustomEvent(this, "onTaskSortChanged", [data]);
			}
		});
	}
};

BX.Tasks.KanbanComponent.filterId = {};
BX.Tasks.KanbanComponent.defaultPresetId = {};

BX.Tasks.KanbanComponent.onReady = function()
{
	// sort-button is disabled
	BX.bind(BX("tasks-popupMenuOptions"), "click", BX.delegate(function()
	{
		if (BX.data(BX("tasks-popupMenuOptions"), "disabled") === true)
		{
			var tooltip = new BX.PopupWindow(
				"popupMenuOptionsDisabled",
				BX("tasks-popupMenuOptions"),
				{
					closeByEsc: true,
					angle: true,
					offsetLeft: 5,
					darkMode: true,
					autoHide: true,
					zIndex: 1000,
					content: BX.message("TASKS_KANBAN_DIABLE_SORT_TOOLTIP")
				}
			);
			tooltip.show();
		}
	}));

	BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url) {
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.KanbanComponent.filterId);
		if (!filterManager)
		{
			alert('BX.Main.filterManager not initialised');
			return;
		}

		var fields = {
			preset_id: BX.Tasks.KanbanComponent.defaultPresetId,
			additional: {ROLEID: (roleId === 'view_all' ? 0 : roleId)}
		};
		var filterApi = filterManager.getApi();
		filterApi.setFilter(fields);

		window.history.pushState(null, null, url);
	});

	BX.addCustomEvent('Tasks.Toolbar:onItem', function(counterId) {
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.KanbanComponent.filterId);
		if (!filterManager)
		{
			alert('BX.Main.filterManager not initialised');
			return;
		}
		var filterApi = filterManager.getApi();
		var filterFields = filterManager.getFilterFieldsValues();

		if (Number(counterId) === 12582912 || Number(counterId) === 6291456)
		{
			var fields = {
				ROLEID: (filterFields.hasOwnProperty('ROLEID') ? filterFields.ROLEID : 0),
				PROBLEM: counterId
			};
			filterApi.setFields(fields);
			filterApi.apply();
		}
		else
		{
			fields = {
				preset_id: BX.Tasks.KanbanComponent.defaultPresetId,
				additional: {
					PROBLEM: counterId,
				}
			};
			if (filterFields.hasOwnProperty('ROLEID'))
			{
				fields.additional.ROLEID = filterFields.ROLEID;
			}
			filterApi.setFilter(fields);
		}
	});
};


BX.addCustomEvent("SidePanel.Slider:onCloseByEsc", function(event) {
	var reg = /tasks\/task\/edit/;
	var str = event.getSlider().getUrl();
	if (reg.test(str) && !confirm(BX.message('TASKS_CLOSE_PAGE_CONFIRM')))
	{
		event.denyAction();
	}
});