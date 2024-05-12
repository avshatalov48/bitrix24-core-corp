/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/copilot/message
 */
jn.define('im/messenger/provider/pull/copilot/message', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { MessageBasePullHandler } = require('im/messenger/provider/pull/lib');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Counters } = require('im/messenger/lib/counters');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Notifier } = require('im/messenger/lib/notifier');
	const {
		DialogType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--copilot-message');

	/**
	 * @class CopilotMessagePullHandler
	 */
	class CopilotMessagePullHandler extends MessageBasePullHandler
	{
		constructor()
		{
			super({ logger });

			this.setWritingTimer(300_000);
		}

		setWritingTimer(value)
		{
			this.writingTimer = value;
		}

		handleMessage(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleMessage and nothing happened`, params, extra);
			// TODO handle message action is not available now for copilot chat
		}

		handleMessageChat(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command, { ignoreServerTimeAgoCheck: true }))
			{
				return;
			}

			if (params.chat && params.chat[params.chatId].type !== DialogType.copilot)
			{
				return;
			}

			logger.info(`${this.getClassName()}.handleMessageChat:`, params, extra);

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();

			const recentParams = clone(params);
			recentParams.message.text = ChatMessengerCommon.purifyText(
				recentParams.message.text,
				recentParams.message.params,
			);
			recentParams.message.status = recentParams.message.senderId === userId ? 'received' : '';
			const userData = recentParams.message.senderId > 0
				? recentParams.users[recentParams.message.senderId]
				: { id: 0 };
			const recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: recentParams.chat[recentParams.chatId],
				user: userData,
				lines: recentParams.lines,
				message: recentParams.message,
				counter: recentParams.counter,
				liked: false,
			});

			this.updateDialog(params)
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => {
					const dialog = this.getDialog(dialogId);
					if (
						extra && extra.server_time_ago <= 5
						&& params.message.senderId !== userId
						&& dialog && !dialog.muteList.includes(userId)
					)
					{
						const dialogTitle = ChatTitle.createFromDialogId(dialogId).getTitle();
						const userName = ChatTitle.createFromDialogId(userData.id).getTitle();
						const avatar = ChatAvatar.createFromDialogId(dialogId).getAvatarUrl();

						Notifier.notify({
							dialogId: dialog.dialogId,
							title: dialogTitle,
							text: (userName ? `${userName}: ` : '') + recentItem.message.text,
							avatar,
						});
					}

					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			const dialog = this.getDialog(dialogId);
			if (!dialog || dialog.hasNextPage)
			{
				return;
			}

			const hasUnloadMessages = dialog.hasNextPage;
			if (hasUnloadMessages)
			{
				return;
			}

			this.setUsers(params).then(() => {
				this.setFiles(params).then(() => {
					this.setMessage(params);
					this.checkWritingTimer(dialogId, userData);
				});
			});
		}

		handleMessageDelete(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleMessageDelete and nothing happened`, params, extra);
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteCompleteParams} params
		 * @param extra
		 * @param command
		 */
		handleMessageDeleteComplete(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleMessageDeleteComplete and nothing happened`, params, extra);
		}

		/**
		 * @param {AddReactionParams} params
		 * @param extra
		 * @param command
		 */
		handleAddReaction(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleAddReaction and nothing happened`, params);
			// TODO add reaction action is not available now for copilot chat
		}

		/**
		 * @param {DeleteReactionParams} params
		 * @param extra
		 * @param command
		 */
		handleDeleteReaction(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleDeleteReaction and nothing happened`, params);
			// TODO delete reaction is not available now for copilot chat
		}

		handleReadMessage(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleReadMessage and nothing happened`, params);
			// TODO read private message action is not available now for copilot chat
		}

		handleUnreadMessage(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleUnreadMessage and nothing happened`, params);
			// TODO unread private message action is not available now for copilot chat
		}

		handleReadMessageOpponent(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleReadMessageOpponent and nothing happened`, params);
			// TODO read private message opponent action is not available now for copilot chat
		}

		handleUnreadMessageOpponent(params, extra, command)
		{
			logger.info(`${this.getClassName()}.handleUnreadMessageOpponent and nothing happened`, params);
			// TODO read private message opponent action is not available now for copilot chat
		}

		/**
		 * @override
		 */
		saveShareDialogCache()
		{
			return true;
		}
	}

	module.exports = {
		CopilotMessagePullHandler,
	};
});
