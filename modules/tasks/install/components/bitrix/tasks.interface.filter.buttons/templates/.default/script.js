'use strict';

BX.namespace('BX.Tasks');

BX.Tasks.InterfaceFilterButtons = function(options)
{
	this.section = options.section;
	this.entityId = options.entityId;
	this.muted = options.muted;
	this.checklistShowCompleted = options.checklistShowCompleted;

	this.initButtons();
	this.bindControls();
};

BX.Tasks.InterfaceFilterButtons.prototype = {
	constructor: BX.Tasks.InterfaceFilterButtons,

	initButtons: function()
	{
		this.muteButton = this.getMuteButton();
		this.optionsMenuButton = this.getOptionsMenuButton();
	},

	getMuteButton: function()
	{
		var id = '';

		switch (this.section)
		{
			case 'VIEW_TASK':
				id = 'taskViewMute';
				break;

			default:
				break;
		}

		return BX(id);
	},

	getOptionsMenuButton: function()
	{
		var id = '';

		switch (this.section)
		{
			case 'EDIT_TASK':
				id = 'taskEditPopupMenuOptions';
				break;

			case 'VIEW_TASK':
				id = 'taskViewPopupMenuOptions';
				break;

			default:
				break;
		}

		return BX(id);
	},

	bindControls: function()
	{
		var binds = [
			{
				control: this.muteButton,
				action: 'click',
				method: this.onMuteButtonClick.bind(this)
			},
			{
				control: this.optionsMenuButton,
				action: 'click',
				method: this.onOptionsMenuButtonClick.bind(this)
			}
		];
		binds.forEach(function(params) {
			if (params.control)
			{
				BX.bind(params.control, params.action, params.method);
			}
		});
	},

	onMuteButtonClick: function()
	{
		if (this.muteInProgress)
		{
			return;
		}
		this.muteInProgress = true;

		var action = 'tasks.task.' + (this.muted ? 'unmute' : 'mute');
		var config = {
			data: {
				taskId: this.entityId
			}
		};

		BX.ajax.runAction(action, config).then(function() {
			var oldClass = (this.muted ? 'ui-btn-icon-unfollow' : 'ui-btn-icon-follow');
			var newClass = (this.muted ? 'ui-btn-icon-follow' : 'ui-btn-icon-unfollow');
			var muteHint = (this.muted ? BX.message('MUTE_BUTTON_HINT_MUTE') : BX.message('MUTE_BUTTON_HINT_UNMUTE'));

			BX.removeClass(this.muteButton, oldClass);
			BX.addClass(this.muteButton, newClass);

			this.muteButton.setAttribute('data-hint', muteHint);
			this.muteButton.removeAttribute('data-hint-init');
			BX.UI.Hint.init();
			BX.UI.Hint.hide();
			BX.UI.Hint.show(this.muteButton, muteHint);

			this.muted = !this.muted;
			this.muteInProgress = false;
		}.bind(this));
	},

	onOptionsMenuButtonClick: function()
	{
		const deadlineMenu = new BX.Tasks.Deadline.Menu();
		const menuItemsList = [
			...deadlineMenu.menuItems,
			{
				delimiter: true,
				text: BX.Loc.getMessage('POPUP_MENU_CHECKLIST_SECTION'),
			},
			{
				tabId: 'showCompleted',
				text: BX.Loc.getMessage('POPUP_MENU_SHOW_COMPLETED'),
				className: (this.checklistShowCompleted ? 'menu-popup-item-accept' : 'menu-popup-item'),
				onclick: this.onShowCompletedItemClick.bind(this),
			},
			{
				tabId: 'showOnlyMine',
				text: BX.Loc.getMessage('POPUP_MENU_SHOW_ONLY_MINE'),
				className: 'menu-popup-item',
				onclick: this.onShowOnlyMineItemClick.bind(this),
			},
		];

		const menu = BX.PopupMenu.create(
			'taskPopupMenuOptions',
			this.optionsMenuButton,
			menuItemsList,
			{
				closeByEsc: true,
				offsetLeft: this.optionsMenuButton.getBoundingClientRect().width / 2,
				angle: true,
				events: {
					onPopupFirstShow: function(event) {
						BX.UI.Hint.init(event.getContentContainer());
					},
				},
			},
		);

		menu.popupWindow.show();
	},

	onShowCompletedItemClick: function(event, item)
	{
		item.getMenuWindow().close();

		if (!BX.Type.isUndefined(BX.Tasks.CheckListInstance))
		{
			BX.toggleClass(item.layout.item, 'menu-popup-item-accept');

			const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
			const optionManager = treeStructure.optionManager;

			optionManager.setShowCompleted(!optionManager.getShowCompleted());
			treeStructure.handleTaskOptions();
		}
	},

	onShowOnlyMineItemClick: function(event, item)
	{
		item.getMenuWindow().close();

		if (!BX.Type.isUndefined(BX.Tasks.CheckListInstance))
		{
			BX.toggleClass(item.layout.item, 'menu-popup-item-accept');

			const treeStructure = BX.Tasks.CheckListInstance.getTreeStructure();
			const optionManager = treeStructure.optionManager;

			optionManager.setShowOnlyMine(!optionManager.getShowOnlyMine());
			treeStructure.handleTaskOptions();
		}
	},
};
