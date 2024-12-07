/**
 * @module im/messenger/provider/pull/chat/recent
 */

jn.define('im/messenger/provider/pull/chat/recent', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { ChatRecentUpdateManager } = require('im/messenger/provider/pull/lib/recent/chat');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-recent');

	/* global userId */

	/**
	 * @class ChatRecentPullHandler
	 */
	class ChatRecentPullHandler extends BasePullHandler
	{
		/**
		 *
		 * @param {RecentUpdateParams} params
		 * @param extra
		 * @param command
		 */
		handleRecentUpdate(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.log('handleRecentUpdate', params, extra, command);

			const manager = new ChatRecentUpdateManager(params);
			manager.setLastMessageInfo();

			const dialogId = params.chat.dialogId;

			const message = clone(manager.getLastMessage());

			message.status = message.author_id === userId ? 'received' : '';

			message.senderId = message.author_id;

			const userData = manager.getLastMessage().author_id > 0
				? params.users[message.author_id]
				: { id: 0 };

			const recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: params.chat,
				user: userData,
				counter: params.counter,
				liked: false,
				lastActivityDate: params.lastActivityDate,
				message,
			});

			this.store.dispatch('recentModel/set', [recentItem]);
		}
	}

	module.exports = { ChatRecentPullHandler };
});
