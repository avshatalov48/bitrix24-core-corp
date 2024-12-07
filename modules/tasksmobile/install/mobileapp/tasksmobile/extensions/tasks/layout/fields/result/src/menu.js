/**
 * @module tasks/layout/fields/result/menu
 */
jn.define('tasks/layout/fields/result/menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');

	const actions = {
		REMOVE: 'remove',
		UPDATE: 'update',
	};

	class Menu
	{
		constructor()
		{
			/** @type {UI.Menu|null} */
			this.menu = null;
			/** @type {function|null} */
			this.onRemove = null;
			/** @type {function|null} */
			this.onUpdate = null;
		}

		/**
		 * @public
		 * @param target
		 * @param {Function} onUpdate
		 * @param {Function} onRemove
		 */
		show({ target, onUpdate, onRemove })
		{
			this.onRemove = onRemove;
			this.onUpdate = onUpdate;

			if (!this.menu)
			{
				this.menu = new UIMenu([
					{
						id: actions.UPDATE,
						testId: actions.UPDATE,
						title: Loc.getMessage('TASKS_FIELDS_RESULT_MENU_UPDATE'),
						iconName: Icon.EDIT,
						sectionCode: 'default',
						onItemSelected: () => this.onUpdate(),
					},
					{
						id: actions.REMOVE,
						testId: actions.REMOVE,
						title: Loc.getMessage('TASKS_FIELDS_RESULT_MENU_REMOVE'),
						iconName: Icon.CROSS,
						sectionCode: 'default',
						onItemSelected: () => this.onRemove(),
					},
				]);
			}
			this.menu.show({ target });
		}
	}

	module.exports = { Menu };
});
