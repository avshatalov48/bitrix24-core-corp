/**
 * @module im/messenger/controller/sidebar-v2/user-actions/navigation
 */
jn.define('im/messenger/controller/sidebar-v2/user-actions/navigation', (require, exports, module) => {
	const { getLogger } = require('im/messenger/lib/logger');
	const { EventType } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	const logger = getLogger('SidebarV2.UserActions.Navigation');

	async function backToRecentChats()
	{
		try
		{
			await PageManager.getNavigator().popTo('im.tabs');
		}
		catch (e)
		{
			logger.error('Cannot popTo im.tabs', e);
			BX.onCustomEvent(EventType.sidebar.destroy);
			MessengerEmitter.emit(EventType.messenger.destroyDialog);
		}
	}

	module.exports = { backToRecentChats };
});
