/**
 * @module im/messenger/provider/pull/chat/recent
 */
jn.define('im/messenger/provider/pull/chat/recent', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');
	const { ChatRecentUpdateManager } = require('im/messenger/provider/pull/lib/recent/chat/update-manager');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('pull-handler--chat-recent');

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
			if (this.interceptEvent(extra))
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

			const recentItem = RecentDataConverter.fromPushToModel({
				id: dialogId,
				chat: params.chat,
				user: userData,
				counter: params.counter,
				lastActivityDate: params.lastActivityDate,
				message,
			});

			this.store.dispatch('recentModel/set', [recentItem]);
		}

		/**
		 * @param {UserShowInRecentParams} params
		 * @param {PullExtraParams} extra
		 * @param command
		 */
		async handleUserShowInRecent(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.log('handleUserShowInRecent', params, extra, command);
			const { items } = params;

			const users = items.map((item) => {
				return {
					...item.user,
					lastActivityDate: item.date, // draw collaber avatar without invited default avatar
				};
			});
			await this.store.dispatch('usersModel/set', users);

			const recentItems = items.map((item) => {
				return {
					id: item.user.id,
					lastActivityDate: item.date,
					invited: false,
				};
			});
			await this.store.dispatch('recentModel/set', recentItems);
		}
	}

	module.exports = { ChatRecentPullHandler };
});
