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

BX.Tasks.KanbanComponent.SetSort = function(enabled, order)
{
	var selectorId = "tasks-popupMenuOptions";
	var menuId = "popupMenuOptions";
	var disabledClass = "webform-button-disable";
	var menu = BX.PopupMenu.getMenuById(menuId);
	var menuItems = [];

	if (menu)
	{
		menuItems = menu.menuItems;
	}

	// set icons in menu
	for (var i = 0, c = menuItems.length; i < c; i++)
	{
		if (menuItems[i].params)
		{
			if (order === menuItems[i].params.order)
			{
				BX.addClass(BX(menuItems[i].layout.item), "menu-popup-item-accept");
			}
			else
			{
				BX.removeClass(BX(menuItems[i].layout.item), "menu-popup-item-accept");
			}
		}
	}

	// enabled/disabled
	if (enabled)
	{
		BX.removeClass(BX(selectorId), disabledClass);
	}
	else
	{
		BX.addClass(BX(selectorId), disabledClass);
	}
	BX.data(BX(selectorId), "disabled", !enabled);
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

	// refresh sort-button after reload kanban
	BX.addCustomEvent("onKanbanChanged", BX.delegate(function(data) {
		// debugger
		var filterObject = BX.Main.filterManager.getById(BX.Tasks.KanbanComponent.filterId);
		var fields = filterObject.getFilterFieldsValues();
		var roleid = fields.ROLEID || 'view_all';//debugger
		BX.onCustomEvent("Tasks.Toolbar.reload", [roleid]); //FIRE

		BX.Tasks.KanbanComponent.SetSort(
			data.canSortItem, 
			data.newTaskOrder
		);
	}));

	BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url){
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.KanbanComponent.filterId);
		if(!filterManager)
		{
			alert('BX.Main.filterManager not initialised');
			return;
		}

		var fields = {
			preset_id: BX.Tasks.KanbanComponent.defaultPresetId,
			additional: { ROLEID: roleId }
		};

		var filterApi = filterManager.getApi();
		filterApi.setFilter(fields);

		window.history.pushState(null, null, url);
	});

	BX.addCustomEvent('Tasks.Toolbar:onItem', function(counterId){
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.KanbanComponent.filterId);
		if(!filterManager)
		{
			alert('BX.Main.filterManager not initialised');
			return;
		}

		var filterApi = filterManager.getApi();

		if(Number(counterId) === 8388608) //\CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL
		{
			// debugger
			var fields = { STATUS: { 0: '4' } };
			var f = filterManager.getFilterFieldsValues();
			if (f.hasOwnProperty('ROLEID') && f.ROLEID != '')
			{
				fields.ROLEID = f.ROLEID;
			}
			else
			{
				fields.ROLEID = 'view_role_originator';
			}

			//\CTasks::STATE_SUPPOSEDLY_COMPLETED
			filterApi.setFields(fields);
			filterApi.apply();
		}
		else
		{
			// debugger
			var fields = {additional:{}};
			var f = filterManager.getFilterFieldsValues();
			if(f.hasOwnProperty('ROLEID'))
			{
				fields.additional.ROLEID = f.ROLEID;
			}
			fields.preset_id= BX.Tasks.KanbanComponent.defaultPresetId;
			fields.additional.PROBLEM= counterId;

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