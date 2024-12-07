/**
 * @module im/messenger/controller/participant-manager
 */
jn.define('im/messenger/controller/participant-manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { UIMenu } = require('layout/ui/menu');

	/**
	 * @desc This class provider calling context menu with custom action items
	 */
	class ParticipantManager
	{
		/**
		 * @desc Open widget without instance
		 * @static
		 * @param {object} options
		 * @param {Array<ActionItem>} options.actionsItems
		 * @param {LayoutComponent} options.ref
		 */
		static open({ actionsItems = [], ref })
		{
			const instanceManger = new ParticipantManager({ actionsItems, ref });
			instanceManger.open();
		}

		/**
		 * @constructor
		 * @param {object} options
		 * @param {Array<ActionItem>} options.actionsItems
		 * @param {LayoutComponent} options.ref
		 */
		constructor({ actionsItems = [], ref })
		{
			this.actionsItems = actionsItems;
			this.actionsData = [];
			this.ref = ref;
			this.menu = {};

			this.createMenu();
		}

		createMenu()
		{
			this.prepareActionsData();
			this.menu = new UIMenu(this.actionsData);
		}

		open()
		{
			Logger.log(`${this.constructor.name}.open, actionsItems:`, this.actionsItems);
			this.prepareActionsData();

			this.menu.show({ target: this.ref });
		}

		/**
		 * @desc Prepare actions objects data by name
		 * @return void
		 */
		prepareActionsData() {
			this.actionsItems.forEach((action) => {
				this.actionsData.push({
					id: action.actionName,
					title: Loc.getMessage(`IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_${action.actionName.toUpperCase()}`),
					onItemSelected: action.callback,
					showIcon: Boolean(action.icon),
					iconName: action.icon,
					testId: `SIDEBAR_USER_CONTEXT_MENU_${action.actionName.toUpperCase()}`,
				});
			});
		}
	}

	module.exports = { ParticipantManager };
});
