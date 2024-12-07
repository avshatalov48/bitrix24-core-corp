/**
 * @module im/messenger/provider/pull/channel/message
 */

jn.define('im/messenger/provider/pull/channel/message', (require, exports, module) => {
	const { ChatMessagePullHandler } = require('im/messenger/provider/pull/chat');
	const { ChannelRecentMessageManager } = require('im/messenger/provider/pull/lib/recent/channel');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-message');

	/**
	 * @class ChannelMessagePullHandler
	 */
	class ChannelMessagePullHandler extends ChatMessagePullHandler
	{
		/**
		 *
		 * @param {MessageAddParams} params
		 * @param extra
		 * @param command
		 */
		handleMessageChat(params, extra, command)
		{
			const recentMessageManager = this.getRecentMessageManager(params, extra);
			if (!recentMessageManager.isOpenChannelChat() && !recentMessageManager.isCommentChat())
			{
				return;
			}

			if (!recentMessageManager.isSharedEvent())
			{
				return;
			}

			logger.info(`${this.getClassName()}.handleMessageChat `, params, extra);

			if (recentMessageManager.isCommentChat())
			{
				this.setCommentInfo(params)
					.catch((error) => {
						logger.error(`${this.getClassName()}.handleMessageChat setCommentInfo error:`, error);
					})
				;
			}

			this.updateDialog(params)
				.then(() => recentMessageManager.updateRecent())
			;

			const dialog = this.getDialog(recentMessageManager.getDialogId());
			if (!dialog || dialog.hasNextPage)
			{
				return;
			}

			const hasUnloadMessages = dialog.hasNextPage;
			if (hasUnloadMessages)
			{
				return;
			}

			this.setUsers(params)
				.then(() => this.setFiles(params))
				.then(() => {
					this.setMessage(params);
					this.checkWritingTimer(
						recentMessageManager.getDialogId(),
						recentMessageManager.getSender(),
					);
				})
			;
		}

		handleMessage(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleMessage and nothing happened`, params, extra);
		}

		isNeedNotify(params)
		{
			const recentMessageManager = this.getRecentMessageManager(params);

			return !recentMessageManager.isCommentChat() && recentMessageManager.isUserInChat();
		}

		getRecentMessageManager(params, extra = {})
		{
			return new ChannelRecentMessageManager(params, extra);
		}
	}

	module.exports = { ChannelMessagePullHandler };
});
