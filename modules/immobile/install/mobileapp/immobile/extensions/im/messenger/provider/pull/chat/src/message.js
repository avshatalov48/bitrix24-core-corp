/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/message
 */
jn.define('im/messenger/provider/pull/chat/message', (require, exports, module) => {
	const { Type } = require('type');
	const { BaseMessagePullHandler } = require('im/messenger/provider/pull/base');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Counters } = require('im/messenger/lib/counters');
	const { Notifier } = require('im/messenger/lib/notifier');
	const {
		DialogType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { ChatRecentMessageManager } = require('im/messenger/provider/pull/lib/recent/chat');

	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-message');

	/**
	 * @class ChatMessagePullHandler
	 */
	class ChatMessagePullHandler extends BaseMessagePullHandler
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

			const recentMessageManager = this.getRecentMessageManager(params, extra);
			if (recentMessageManager.isCopilotChat())
			{
				return;
			}

			if (recentMessageManager.isChannelListEvent())
			{
				return;
			}

			logger.info(`${this.getClassName()}.handleMessageChat `, params, extra, command);

			if (recentMessageManager.isCommentChat())
			{
				this.setCommentInfo(params)
					.catch((error) => {
						logger.error(`${this.getClassName()}.handleMessageChat setCommentInfo error:`, error);
					})
				;
			}

			if (recentMessageManager.isLinesChat())
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					Counters.openlinesCounter.detail[params.dialogId] = params.counter;
					Counters.update();
				}

				return;
			}

			this.updateDialog(params)
				.then(() => recentMessageManager.updateRecent())
				.then(() => {
					this.messageNotify(params, extra, recentMessageManager.getMessageText());
					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			const dialog = this.getDialog(recentMessageManager.getDialogId());
			if (!dialog)
			{
				return;
			}

			this.setUsers(params)
				.then(() => this.setFiles(params))
				.then(() => {
					const hasUnloadMessages = dialog.hasNextPage;
					if (hasUnloadMessages)
					{
						this.storeMessage(params);
					}
					else
					{
						this.setMessage(params);
					}

					this.checkWritingTimer(
						recentMessageManager.getDialogId(),
						recentMessageManager.getSender(),
					);
				})
			;
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

		handleReadAllChannelComments(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}
			this.logger.log(`${this.constructor.name}.handleReadAllChannelComments`, params);

			const { chatId } = params;

			this.store.dispatch('commentModel/deleteChannelCounters', { channelId: chatId });
		}

		isNeedUpdateRecent(params)
		{
			const chatType = params?.chat?.[params?.chatId]?.type;

			return ![DialogType.comment, DialogType.openChannel].includes(chatType);
		}

		isNeedNotify(params)
		{
			const chatType = params?.chat?.[params?.chatId]?.type;

			return ![DialogType.comment].includes(chatType);
		}

		async updateRecent(params, recentItem)
		{
			if (!this.isNeedUpdateRecent(params))
			{
				return;
			}

			// eslint-disable-next-line consistent-return
			return this.store.dispatch('recentModel/set', [recentItem]);
		}

		messageNotify(params, extra, messageText)
		{
			if (!this.isNeedNotify(params))
			{
				return;
			}

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();
			const userData = params.message.senderId > 0
				? params.users[params.message.senderId]
				: { id: 0 };

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
					text: this.createMessageChatNotifyText(messageText, userName),
					avatar,
				});
			}
		}

		async setCommentInfo(params)
		{
			const chat = params.chat?.[params.chatId];

			if (Type.isNumber(params.counter))
			{
				await this.store.dispatch('commentModel/setCommentWithCounter', {
					messageId: chat.parent_message_id,
					chatId: params.chatId,
					messageCount: chat.message_count,
					dialogId: params.dialogId,
					newUserId: params.message.senderId,
					chatCounterMap: {
						[chat.parent_chat_id]: {
							[chat.id]: params.counter,
						},
					},
				});

				return;
			}

			await this.store.dispatch('commentModel/setComment', {
				messageId: chat.parent_message_id,
				chatId: params.chatId,
				messageCount: chat.message_count,
				dialogId: params.dialogId,
				newUserId: params.message.senderId,
			});
		}

		getRecentMessageManager(params, extra = {})
		{
			return new ChatRecentMessageManager(params, extra);
		}
	}

	module.exports = {
		ChatMessagePullHandler,
	};
});
