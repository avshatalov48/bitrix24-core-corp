/**
 * @module im/messenger/provider/pull/channel/message
 */

jn.define('im/messenger/provider/pull/channel/message', (require, exports, module) => {
	const { ChatMessagePullHandler } = require('im/messenger/provider/pull/chat');
	const { ChannelNewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/channel');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('pull-handler--chat-message');

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
			const recentMessageManager = this.getNewMessageManager(params, extra);
			if (!recentMessageManager.needToProcessMessage())
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
					this.checkTimerInputAction(
						recentMessageManager.getDialogId(),
						recentMessageManager.getSenderId(),
					);
				})
			;
		}

		/**
		 * @param {MessagesAutoDeleteDelayParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleMessagesAutoDeleteDelayChanged(params, extra, command)
		{}

		handleMessage(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleMessage and nothing happened`, params, extra);
		}

		isNeedNotify()
		{
			return false;
		}

		getNewMessageManager(params, extra = {})
		{
			return new ChannelNewMessageManager(params, extra);
		}
	}

	module.exports = { ChannelMessagePullHandler };
});
