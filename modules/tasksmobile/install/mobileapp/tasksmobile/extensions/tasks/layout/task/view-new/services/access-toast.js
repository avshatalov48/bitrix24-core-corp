/**
 * @module tasks/layout/task/view-new/services/access-toast
 */
jn.define('tasks/layout/task/view-new/services/access-toast', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { TaskActionAccess, TaskFieldActionAccess } = require('tasks/enum');
	const { showSafeToast } = require('toast');
	const { Icon } = require('assets/icons');

	/**
	 * @class AccessToast
	 */
	class AccessToast
	{
		constructor(layoutWidget)
		{
			this.layout = layoutWidget;

			/** @type {Toast|null} */
			this.lastNoAccessToast = null;
		}

		/**
		 * @param {string} fieldId
		 */
		showByFieldId(fieldId)
		{
			const access = TaskFieldActionAccess[fieldId] ?? TaskActionAccess.UPDATE;

			this.showByAccess(access);
		}

		/**
		 * @param {string} access
		 */
		showByAccess(access)
		{
			const actionDenied = (
				Loc.getMessage(`M_TASKS_DENIED_${access.toUpperCase()}`)
				|| Loc.getMessage('M_TASKS_DENIED_UPDATE')
			);

			Haptics.notifyWarning();

			if (this.lastNoAccessToast)
			{
				this.lastNoAccessToast.close();
			}

			this.lastNoAccessToast = showSafeToast({
				message: actionDenied,
				iconName: Icon.LOCK.getIconName(),
			}, this.layout);
		}
	}

	module.exports = { AccessToast };
});
