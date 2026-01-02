/**
 * @module im/messenger-v2/controller/recent/service/select/lib/opener
 */
jn.define('im/messenger-v2/controller/recent/service/select/lib/opener', (require, exports, module) => {
	const { RecentRest } = require('im/messenger/provider/rest');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('recent--dialog-opener');

	/**
	 * @param {object} recentFields
	 * @param {string} recentFields.id
	 * @param {boolean} recentFields.unread
	 */
	function setRecentModelWithCounters(recentFields)
	{
		const store = serviceLocator.get('core').getStore();

		store.dispatch('recentModel/update', [recentFields])
			.catch((error) => logger.error(
				'setRecentModelWithCounters: recentModel/update error',
				error,
			));
	}

	function removeUnreadState(dialogId)
	{
		const store = serviceLocator.get('core').getStore();
		const recentItem = store.getters['recentModel/getById'](dialogId);
		if (!recentItem)
		{
			return;
		}

		const unreadBeforeChange = recentItem.unread;

		setRecentModelWithCounters({
			id: dialogId,
			unread: false,
		});

		RecentRest.read({ dialogId }).catch((result) => {
			logger.error(
				'removeUnreadState: recentRest.read error',
				result.error(),
			);

			this.setRecentModelWithCounters({
				id: dialogId,
				unread: unreadBeforeChange,
			});
		});
	}

	async function openDialog(dialogId)
	{
		const dialogManager = serviceLocator.get('dialog-manager');
		if (!dialogManager)
		{
			logger.error('openDialog error: dialogManager not found!', dialogId);

			return;
		}

		await dialogManager.openDialog({ dialogId });
		removeUnreadState(dialogId);
	}

	module.exports = { openDialog };
});
