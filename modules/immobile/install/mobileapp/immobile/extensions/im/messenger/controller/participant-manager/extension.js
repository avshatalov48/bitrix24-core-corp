/**
 * @module im:messenger/controller/participant-manager
 */
jn.define('im:messenger/controller/participant-manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { menuIcons } = require('im/messenger/assets/common');

	/**
	 * @desc This class provider calling context menu with custom action items
	 */
	class ParticipantManager
	{
		/**
		 * @desc Open widget without instance
		 * @static
		 * @param {object} options
		 * @param {Array<string>} options.actions - ['remove', ...]
		 * @param {Array<Function>} options.callbacks
		 */
		static open(options = {})
		{
			const instanceManger = new ParticipantManager(options);
			instanceManger.open();
		}

		/**
		 * @constructor
		 * @param {object} options
		 * @param {Array<string>} options.actions - ['remove', ...]
		 * @param {Array<Function>} options.callbacks
		 */
		constructor(options = {})
		{
			this.actionsName = options.actions;
			this.callbacks = options.callbacks;
			this.actionsData = [];
			this.menu = {};

			this.createMenu();
		}

		createMenu()
		{
			this.prepareActionsData();
			this.menu = new ContextMenu({
				actions: this.actionsData,
			});
		}

		open()
		{
			Logger.log('ParticipantManager.open');
			this.prepareActionsData();
			this.menu.show().catch((err) => Logger.error('ParticipantManager.open', err));
		}

		/**
		 * @desc Prepare actions objects data by name
		 * @return void
		 */
		prepareActionsData() {
			this.actionsName.forEach((actionName) => {
				this.actionsData.push({
					id: actionName,
					title: Loc.getMessage(`IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_${actionName.toUpperCase()}`),
					data: {
						svgIcon: menuIcons.remove(),
					},
					onClickCallback: this.getCallbackByAction(actionName),
				});
			});
		}

		/**
		 * @desc Returns callback by string name action
		 * @param {string} actionName
		 * @return {Function}
		 */
		getCallbackByAction(actionName)
		{
			return this.callbacks[actionName];
		}
	}

	module.exports = { ParticipantManager };
});
