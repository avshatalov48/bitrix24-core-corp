/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/message
 */
jn.define('im/messenger/provider/pull/chat/message', (require, exports, module) => {
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
	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-message');

	/**
	 * @class ChatMessagePullHandler
	 */
	class ChatMessagePullHandler extends MessageBasePullHandler
	{
		constructor()
		{
			super({ logger });
		}

		handleMessageChat(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			if (params.chat && params.chat[params.chatId].type === DialogType.copilot)
			{
				return;
			}

			logger.info(`${this.getClassName()}.handleMessageChat `, params, extra);

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();

			if (params.lines)
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					Counters.openlinesCounter.detail[params.dialogId] = params.counter;
					Counters.update();
				}

				return;
			}

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

		/**
		 *
		 * @param {MessagePullHandlerPinAddParams} params
		 * @param extra
		 * @param command
		 */
		handlePinAdd(params, extra, command)
		{
			this.setUsers(params)
				.catch((error) => {
					logger.error('ChatMessagePullHandler.handlePinAdd set users error', error);
				})
			;
			this.setFiles(params)
				.catch((error) => {
					logger.error('ChatMessagePullHandler.handlePinAdd set files error', error);
				})
			;

			this.store.dispatch('messagesModel/pinModel/set', {
				pin: params.pin,
				messages: params.additionalMessages,
			})
				.catch((error) => {
					logger.error('ChatMessagePullHandler.handlePinAdd set pin error', error);
				})
			;
		}

		/**
		 *
		 * @param {MessagePullHandlerPinDeleteParams} params
		 * @param extra
		 * @param command
		 */
		handlePinDelete(params, extra, command)
		{
			this.store.dispatch('messagesModel/pinModel/delete', {
				chatId: params.chatId,
				messageId: params.messageId,
			})
				.catch((error) => {
					logger.error('ChatMessagePullHandler.handlePinDelete delete pin error', error);
				})
			;
		}
	}

	module.exports = {
		ChatMessagePullHandler,
	};
});
