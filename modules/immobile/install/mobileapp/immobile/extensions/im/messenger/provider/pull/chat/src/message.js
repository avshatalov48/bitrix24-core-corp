/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/message
 */
jn.define('im/messenger/provider/pull/chat/message', (require, exports, module) => {
	const { Type } = require('type');

	const { BaseMessagePullHandler } = require('im/messenger/provider/pull/base');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Notifier } = require('im/messenger/lib/notifier');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		DialogType,
		MessagesAutoDeleteDelay,
		ComponentCode,
	} = require('im/messenger/const');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { ChatNewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/chat');

	const logger = getLogger('pull-handler--chat-message');

	/**
	 * @class ChatMessagePullHandler
	 */
	class ChatMessagePullHandler extends BaseMessagePullHandler
	{
		constructor()
		{
			super({ logger });
		}

		/**
		 * @param {MessagesAutoDeleteDelayParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleMessagesAutoDeleteDelayChanged(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.log(`${this.constructor.name}.handleMessagesAutoDeleteDelayChanged `, params, extra);

			if (params.delay !== MessagesAutoDeleteDelay.off)
			{
				Feature.updateExistingImFeatures({ messagesAutoDeleteEnabled: true });
			}

			this.store.dispatch('dialoguesModel/update', {
				dialogId: String(params.dialogId),
				fields: {
					messagesAutoDeleteDelay: params.delay,
				},
			});
		}

		handleMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			const recentMessageManager = this.getNewMessageManager(params, extra);

			if (!recentMessageManager.needToProcessMessage())
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

			this.updateDialog(params)
				.then(() => {
					this.updateCopilotModel(params);

					return recentMessageManager.updateRecent();
				})
				.then(() => {
					this.messageNotify(params, extra, recentMessageManager.getMessageText());
					serviceLocator.get('tab-counters').updateDelayed();

					this.saveShareDialogCache();
				})
				.then(() => this.setUsers(params))
				.then(() => this.setFiles(params))
				.then(() => {
					this.checkTimerInputAction(
						recentMessageManager.getDialogId(),
						recentMessageManager.getSenderId(),
					);

					const dialog = this.getDialog(recentMessageManager.getDialogId());
					const hasUnloadMessages = dialog.hasNextPage;
					if (hasUnloadMessages)
					{
						return this.storeMessage(params);
					}

					return this.setMessage(params);
				})
				.catch((error) => {
					this.logger.error(`${this.constructor.name}.handleMessage error`, error);
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
			if (this.interceptEvent(extra))
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
			const userData = params.message.senderId > 0 && Type.isPlainObject(params.users)
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
					recentConfig: params.recentConfig,
				}).catch((error) => {
					this.logger.error(`${this.getClassName()}.messageNotify notify error:`, error);
				});
			}
		}

		async setCommentInfo(params)
		{
			const chat = params.chat?.[params.chatId];
			const parentChatId = chat.parent_chat_id;

			if (Type.isNumber(params.counter))
			{
				await this.store.dispatch('commentModel/setComment', {
					messageId: chat.parent_message_id,
					chatId: params.chatId,
					messageCount: chat.message_count,
					dialogId: params.dialogId,
					newUserId: params.message.senderId,
				});
			}
			else
			{
				await this.store.dispatch('commentModel/setComment', {
					messageId: chat.parent_message_id,
					chatId: params.chatId,
					messageCount: chat.message_count,
					dialogId: params.dialogId,
					newUserId: params.message.senderId,
				});
			}

			const dialog = this.store.getters['dialoguesModel/getByChatId'](parentChatId);
			if (!dialog)
			{
				return;
			}

			const recentItem = this.store.getters['recentModel/getById'](dialog.dialogId);
			if (!recentItem)
			{
				return;
			}

			await this.store.dispatch('recentModel/set', [recentItem]);
		}

		getNewMessageManager(params, extra = {})
		{
			return new ChatNewMessageManager(params, extra);
		}

		/**
		 * @param {MessagePullHandlerUpdateDialogParams}params
		 */
		updateCopilotModel(params)
		{
			const isCopilot = params.chat[params.chatId]?.type === DialogType.copilot;
			if (!isCopilot || !params.copilot)
			{
				return;
			}

			const copilot = params.copilot;
			const copilotModel = {
				dialogId: params.dialogId,
				chats: copilot.chats,
				aiProvider: '',
				roles: copilot.roles,
				messages: copilot.messages,
			};

			void this.store.dispatch('dialoguesModel/copilotModel/setCollection', copilotModel)
				.catch((error) => {
					logger.error(`${this.getClassName()}.updateCopilotModel: `, error);
				})
			;
		}
	}

	module.exports = {
		ChatMessagePullHandler,
	};
});
