'use strict';

BX.namespace('BX.Tasks');

BX.Tasks.InterfaceFilterButtons = function(options)
{
	this.section = options.section;
	this.checklistShowCompleted = options.checklistShowCompleted;
	this.optionsMenuButton = this.getOptionsMenuButton();

	this.bindControls();
};

BX.Tasks.InterfaceFilterButtons.prototype = {
	constructor: BX.Tasks.InterfaceFilterButtons,

	bindControls: function()
	{
		BX.bind(this.optionsMenuButton, 'click', this.createTaskMenu.bind(this));
	},

	getOptionsMenuButton: function()
	{
		var id = '';

		if (this.section === 'EDIT_TASK')
		{
			id = 'taskEditPopupMenuOptions';
		}
		else if (this.section === 'VIEW_TASK')
		{
			id = 'taskViewPopupMenuOptions';
		}

		return BX(id);
	},

	createTaskMenu: function()
	{
		var menuItemsList = [
			{
				delimiter: true,
				text: BX.message("POPUP_MENU_CHECKLIST_SECTION")
			}
		];

		menuItemsList.push({
			tabId: "showCompleted",
			text: BX.message("POPUP_MENU_SHOW_COMPLETED"),
			className: (this.checklistShowCompleted ? "menu-popup-item-accept" : "menu-popup-item"),
			onclick: function(event, item)
			{
				item.getMenuWindow().close();

				if (typeof BX.Tasks.CheckListInstance !== 'undefined')
				{
					BX.toggleClass(item.layout.item, 'menu-popup-item-accept');

					var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
					var optionManager = treeStructure.optionManager;

					optionManager.setShowCompleted(!optionManager.getShowCompleted());
					treeStructure.handleTaskOptions();
				}
			}
		});

		menuItemsList.push({
			tabId: "showOnlyMine",
			text: BX.message("POPUP_MENU_SHOW_ONLY_MINE"),
			className: "menu-popup-item",
			onclick: function(event, item)
			{
				item.getMenuWindow().close();

				if (typeof BX.Tasks.CheckListInstance !== 'undefined')
				{
					BX.toggleClass(item.layout.item, 'menu-popup-item-accept');

					var treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
					var optionManager = treeStructure.optionManager;

					optionManager.setShowOnlyMine(!optionManager.getShowOnlyMine());
					treeStructure.handleTaskOptions();
				}
			}
		});

		var menu = BX.PopupMenu.create(
			"taskPopupMenuOptions",
			this.optionsMenuButton,
			menuItemsList,
			{
				closeByEsc: true,
				offsetLeft: this.optionsMenuButton.getBoundingClientRect().width / 2,
				angle: true
			}
		);

		menu.popupWindow.show();
	},
};