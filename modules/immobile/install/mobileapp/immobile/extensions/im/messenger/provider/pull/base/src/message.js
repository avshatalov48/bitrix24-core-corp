/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/base/message
 */
jn.define('im/messenger/provider/pull/base/message', (require, exports, module) => {
	/* global ChatMessengerCommon */
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { clone } = require('utils/object');

	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { Worker } = require('im/messenger/lib/helper');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { parser } = require('im/messenger/lib/parser');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');
	const { MessageDataConverter } = require('im/messenger/lib/converter/data/message');
	const { Notifier } = require('im/messenger/lib/notifier');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { ChatDataProvider } = require('im/messenger/provider/data');

	const {
		DialogType,
		EventType,
		UserRole,
		CounterType,
	} = require('im/messenger/const');
	const { NewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/base');
	const { FileUtils } = require('im/messenger/provider/pull/lib/file');

	const INPUT_ACTION_TIMER = 25000;

	/**
	 * @class BaseMessagePullHandler
	 */
	class BaseMessagePullHandler extends BasePullHandler
	{
		constructor(options)
		{
			super(options);

			this.messageViews = {};
			this.shareDialogCache = new ShareDialogCache();
			this.inputActionTimer = INPUT_ACTION_TIMER;
			this.inputActionWorkers = {};
		}

		handleMessage(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.log(`${this.getClassName()}.handleMessage `, params, extra);

			const dialogId = params.dialogId;
			const userId = MessengerParams.getUserId();

			const recipientId = params.message.senderId === userId
				? params.message.recipientId
				: params.message.senderId
			;

			const recentParams = clone(params);
			const userData = recentParams.users[recipientId];
			const recentItem = RecentDataConverter.fromPushToModel({
				id: recipientId,
				user: userData,
				message: recentParams.message,
				counter: recentParams.counter,
				lastActivityDate: recentParams.dateLastActivity,
				defaultUserRecord: false,
				liked: false,
			});

			if (!recentItem)
			{
				return;
			}
			recentItem.message.status = recentParams.message.senderId === userId ? 'received' : '';
			this.updateDialog(params)
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => {
					if (extra && extra.server_time_ago <= 5 && params.message.senderId !== userId)
					{
						const userName = ChatTitle.createFromDialogId(params.message.senderId).getTitle();
						const userAvatar = ChatAvatar.createFromDialogId(params.message.senderId).getAvatarUrl();

						Notifier.notify({
							dialogId: recipientId,
							title: userName,
							text: recentItem.message.text,
							avatar: userAvatar,
							recentConfig: params.recentConfig,
						}).catch((error) => {
							this.logger.error(`${this.getClassName()}.handleMessage notify error:`, error);
						});
					}

					serviceLocator.get('tab-counters').updateDelayed();

					this.saveShareDialogCache();
				})
				.then(() => this.setUsers(params))
				.then(() => this.setFiles(params))
				.then(() => {
					this.checkTimerInputAction(params.dialogId, params.message.senderId);

					const dialog = this.getDialog(dialogId);
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

		handleMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.log(`${this.getClassName()}.handleMessageChat`, params, extra);

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();

			if (params.lines)
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					const tabCounters = serviceLocator.get('tab-counters');
					tabCounters.updateCounterDetailByCounterState({
						chatId: params.chatId,
						counter: params.counter,
						type: CounterType.openline,
					});
					tabCounters.update();
				}

				return;
			}

			const recentParams = clone(params);
			recentParams.message.text = ChatMessengerCommon.purifyText(
				recentParams.message.text,
				recentParams.message.params,
			);
			recentParams.message.status = recentParams.message.senderId === userId ? 'received' : '';
			const userData = recentParams.message.senderId > 0 && Type.isPlainObject(recentParams.users)
				? recentParams.users[recentParams.message.senderId]
				: { id: 0 };
			const recentItem = RecentDataConverter.fromPushToModel({
				id: dialogId,
				chat: recentParams.chat[recentParams.chatId],
				user: userData,
				lines: recentParams.lines,
				message: recentParams.message,
				counter: recentParams.counter,
				liked: false,
				lastActivityDate: recentParams.dateLastActivity,
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
							text: this.createMessageChatNotifyText(recentItem.message.text, userName),
							avatar,
							recentConfig: params.recentConfig,
						}).catch((error) => {
							this.logger.error(`${this.getClassName()}.handleMessageChat notify error:`, error);
						});
					}

					serviceLocator.get('tab-counters').updateDelayed();

					this.saveShareDialogCache();
				})
				.then(() => this.setUsers(params))
				.then(() => this.setFiles(params))
				.then(() => {
					this.checkTimerInputAction(params.dialogId, params.message.senderId);

					const dialog = this.getDialog(dialogId);
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

		handleMessageUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleMessageUpdate:`, params);

			this.updateMessage(params);
		}

		handleMessageParamsUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleMessageParamsUpdate:`, params);

			this.store.dispatch('messagesModel/updateParams', {
				id: params.id,
				chatId: params.chatId,
				params: params.params,
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleMessageParamsUpdate.messagesModel/update.catch:`, err));

			if (params.params.CHAT_MESSAGE)
			{
				this.store.dispatch('commentModel/updateComment', {
					messageId: params.id,
					messageCount: params.params.CHAT_MESSAGE,
				});
			}
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params} params
		 * @param {object} extra
		 * @param {object} command
		 */
		async handleMessageDeleteV2(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleMessageDeleteV2: `, params, extra);

			const fullDeletedMessageList = params.messages.filter((message) => message.completelyDeleted);
			const updateMessageList = params.messages.filter((message) => !message.completelyDeleted);

			if (fullDeletedMessageList.length > 0)
			{
				await this.fullDeleteMessageList(fullDeletedMessageList);

				fullDeletedMessageList.forEach((message) => {
					this.fullDeletePostMessage(message.id, params.chatId);
				});

				await this.updateDialogByFullDelete(params);
				await this.updateRecentByFullDelete(params);
			}

			if (updateMessageList.length > 0)
			{
				await this.updateMessageListBySoftDelete(updateMessageList);
				await this.updateRecentBySoftDelete(params);
			}
		}

		/**
		 * @param {AddReactionParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleAddReaction(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleAddReaction: `, params);
			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}handleAddReaction: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}
			const {
				actualReactions: { reaction: actualReactionsState, usersShort },
				userId,
				reaction,
				dialogId,
			} = params;
			const message = this.store.getters['messagesModel/getById'](actualReactionsState.messageId);
			if (!('id' in message))
			{
				return;
			}

			if (MessengerParams.getUserId().toString() === userId.toString())
			{
				actualReactionsState.ownReactions = [reaction];
			}

			this.store.dispatch('usersModel/addShort', usersShort)
				.then(() => {
					this.store.dispatch('messagesModel/reactionsModel/setFromPullEvent', {
						usersShort,
						reactions: [actualReactionsState],
					});
				})
				.catch((err) => this.logger.error(`${this.getClassName()}.handleAddReaction.usersModel/addShort.catch err:`, err));

			this.#updateRecentReaction(dialogId, userId, actualReactionsState.messageId, true);
		}

		/**
		 * @param {DeleteReactionParams} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleDeleteReaction(params, extra, command)
		{
			this.logger.info(`${this.getClassName()}.handleDeleteReaction: `, params);
			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}.handleDeleteReaction: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}
			const {
				actualReactions: { reaction: actualReactionsState, usersShort },
				dialogId,
				userId,
			} = params;

			const message = this.store.getters['messagesModel/getById'](actualReactionsState.messageId);
			if (!('id' in message))
			{
				return;
			}

			this.store.dispatch('messagesModel/reactionsModel/setFromPullEvent', {
				usersShort,
				reactions: [actualReactionsState],
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleDeleteReaction.messagesModel.catch err:`, err));

			this.#updateRecentReaction(dialogId, userId, actualReactionsState.messageId, false);
		}

		/**
		 * @param {object} params
		 * @param {string} params.dialogId
		 * @param {number} params.userId
		 * @param {string} params.userName
		 * @param {string} params.userFirstName
		 * @param {string|null} params.duration
		 * @param {InputActionNotifyType} params.type
		 * @return {Promise|boolean}
		 */
		async handleInputActionNotify(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			const { dialogId, userId, type, duration } = params;
			if (!this.getDialog(dialogId))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleInputActionNotify:`, params);

			this.updateUserOnline(userId);
			this.store.dispatch('dialoguesModel/setInputAction', { ...params });

			const timerId = `${dialogId} ${userId} ${type}`;

			this.inputActionWorkers[timerId]?.stop();
			this.inputActionWorkers[timerId] = new Worker({
				frequency: duration || this.inputActionTimer,
				callback: () => {
					this.store.dispatch('dialoguesModel/removeInputAction', { ...params });
					delete this.inputActionWorkers[timerId];
				},
			});

			this.inputActionWorkers[timerId].startOnce();
		}

		handleReadMessage(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessage:`, params);

			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}.handleReadMessage: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}

			this.readMessage(params);
			this.updateCounters(params);
		}

		handleReadMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessageChat:`, params);

			if (UuidManager.getInstance().hasActionUuid(extra.action_uuid))
			{
				this.logger.info(`${this.getClassName()}.handleReadMessageChat: we already locally processed this action`);
				UuidManager.getInstance().removeActionUuid(extra.action_uuid);

				return;
			}

			if (params.lines)
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					const tabCounters = serviceLocator.get('tab-counters');
					tabCounters.updateCounterDetailByCounterState({
						chatId: params.chatId,
						counter: params.counter,
						type: CounterType.openline,
					});
					tabCounters.update();
				}

				return;
			}

			this.readMessage(params);
			this.updateCounters(params);
		}

		handleUnreadMessage(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessage:`, params);

			this.unreadMessage(params);
			this.updateCounters(params);
		}

		handleUnreadMessageChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessageChat:`, params);

			this.unreadMessage(params);
			this.updateCounters(params);
		}

		readMessage(params)
		{
			this.store.dispatch('messagesModel/readMessages', {
				chatId: params.chatId,
				messageIds: params.viewedMessages,
			});
		}

		unreadMessage(params)
		{}

		handleReadMessageOpponent(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			if (params.userId === MessengerParams.getUserId())
			{
				this.logger.warn(`${this.getClassName()}.handleReadMessageOpponent: skip read by current user`, params);

				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessageOpponent:`, params);

			this.readMessageChatOpponent(params);
		}

		// eslint-disable-next-line sonarjs/no-identical-functions
		handleReadMessageChatOpponent(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			if (params.userId === MessengerParams.getUserId())
			{
				this.logger.warn(`${this.getClassName()}.handleReadMessageChatOpponent: skip read by current user`, params);

				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadMessageChatOpponent:`, params);

			this.readMessageChatOpponent(params);
		}

		handleUnreadMessageOpponent(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessageOpponent:`, params);

			this.updateMessageStatus(params);
		}

		// eslint-disable-next-line sonarjs/no-identical-functions
		handleUnreadMessageChatOpponent(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleUnreadMessageChatOpponent:`, params);

			this.updateMessageStatus(params);
		}

		readMessageChatOpponent(params)
		{
			this.updateMessageViewedByOthers(params);
			this.updateMessageStatus(params);
			this.updateChatLastMessageViews(params);
		}

		updateMessage(params)
		{
			const dialogId = params.dialogId;
			const messageId = params.id;
			const messageParams = params.params;

			const message = clone(this.store.getters['messagesModel/getById'](messageId));
			if (!('id' in message))
			{
				return;
			}

			if (message.params && message.params.replyId)
			{
				// this copyrighting params need for update quote – not deleting
				// UPDATE: since version 24 im, the replyId comes from server
				messageParams.replyId = message.params.replyId;
			}

			this.store.dispatch('messagesModel/update', {
				id: params.id,
				fields: {
					text: params.text,
					params: messageParams,
				},
			});

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const recentParams = clone(params);
			if (recentItem.message.id === message.id)
			{
				message.text = ChatMessengerCommon.purifyText(recentParams.text, recentParams.params);
				message.params = recentParams.params;
				message.file = recentParams.params && recentParams.params.FILE_ID
					? recentParams.params.FILE_ID.length > 0
					: false
				;
				message.attach = recentParams.params && recentParams.params.ATTACH
					? recentParams.params.ATTACH.length > 0
					: false
				;

				recentItem.message = {
					...recentItem.message,
					...message,
				};
			}

			this.store.dispatch('recentModel/set', [recentItem])
				.catch((err) => this.logger.error(`${this.getClassName()}.updateMessage.recentModel/set.catch:`, err));
		}

		/**
		 * @param {Array<DeleteV2MessageObject>} messageList
		 */
		async updateMessageListBySoftDelete(messageList)
		{
			const preparedMessageList = messageList.map((message) => {
				return { ...message, text: Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED') };
			});

			await this.store.dispatch('messagesModel/updateList', {
					messageList: preparedMessageList,
				})
				.catch((error) => this.logger.error(`${this.getClassName()}.updateMessageListBySoftDelete.messagesModel/updateList.catch:`, error));
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteCompleteParams} params
		 */
		async fullDeleteMessage(params)
		{
			await this.fullDeleteMessageList([params.id]);
			this.fullDeletePostMessage(params.id, params.chatId);
			await this.updateDialogByFullDelete(params);
			await this.updateRecentByFullDelete(params);
		}

		/**
		 * @param {Array<DeleteV2MessageObject>} messageList
		 */
		async fullDeleteMessageList(messageList)
		{
			const idList = messageList.map((message) => message.id);

			await this.store.dispatch('messagesModel/deleteByIdList', { idList })
				.catch((err) => this.logger.error(`${this.getClassName()}.fullDeleteMessageList.messagesModel/deleteByIdList.catch:`, err));
		}

		/**
		 * @param {MessageId} messageId
		 * @param {number} chatId
		 * @void
		 */
		fullDeletePostMessage(messageId, chatId)
		{
			const commentInfo = this.store.getters['commentModel/getByMessageId'](messageId);
			if (commentInfo)
			{
				this.store.dispatch('commentModel/deleteCommentByMessageId', {
						messageId,
						channelChatId: chatId,
					})
					.then(() => {
						serviceLocator.get('tab-counters').update();
					})
					.catch((error) => {
						this.logger.error(`${this.constructor.name}.fullDeletePostMessage comment delete error`, error);
					})
				;

				const chatProvider = new ChatDataProvider();
				chatProvider.delete({ dialogId: commentInfo.dialogId })
					.catch((error) => {
						this.logger.error(`${this.constructor.name}.fullDeletePostMessage delete comment chat error`, error);
					})
				;

				MessengerEmitter.emit(EventType.dialog.external.delete, {
					dialogId: commentInfo.dialogId,
					shouldShowAlert: true,
					chatType: DialogType.comment,
					shouldSendDeleteAnalytics: true,
				});
			}
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params|MessagePullHandlerMessageDeleteCompleteParams} params
		 */
		async updateDialogByFullDelete(params)
		{
			const dialogItem = this.store.getters['dialoguesModel/getById'](params.dialogId);
			if (!dialogItem)
			{
				return;
			}

			const fieldsCount = {
				// counter: params.counter,
			};
			if (params.lastMessageViews?.countOfViewers
				&& (params.lastMessageViews.countOfViewers !== dialogItem.lastMessageViews.countOfViewers))
			{
				fieldsCount.lastMessageId = params.newLastMessage.id;
				fieldsCount.lastId = dialogItem.lastReadId === dialogItem.lastMessageId
					? params.newLastMessage.id : dialogItem.lastReadId;

				const fieldsViews = {
					...params.lastMessageViews.firstViewers[0],
					messageId: params.lastMessageViews.messageId,
					countOfViewers: params.lastMessageViews.countOfViewers,
				};

				await this.store.dispatch('dialoguesModel/setLastMessageViews', {
					dialogId: params.dialogId,
					fields: fieldsViews,
				});
			}

			await this.store.dispatch('dialoguesModel/update', {
				dialogId: params.dialogId,
				fields: fieldsCount,
			});
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params|MessagePullHandlerMessageDeleteCompleteParams} params
		 */
		updateRecentByFullDelete(params)
		{
			const recentItem = clone(this.store.getters['recentModel/getById'](params.dialogId));
			if (!recentItem)
			{
				return;
			}

			const newLastMessage = params.newLastMessage;
			let isNeedUpdateRecentItem = false;

			if (recentItem && newLastMessage)
			{
				recentItem.message = {
					text: ChatMessengerCommon.purifyText(newLastMessage.text, newLastMessage.params),
					date: newLastMessage.date,
					author_id: newLastMessage.author_id,
					id: newLastMessage.id,
					file: newLastMessage.files?.[0] ?? false,
				};

				isNeedUpdateRecentItem = true;
			}

			if (isNeedUpdateRecentItem)
			{
				try
				{
					this.store.dispatch('recentModel/set', [recentItem])
						.then(() => {
							serviceLocator.get('tab-counters').update();

							this.saveShareDialogCache();
						})
					;
				}
				catch (error)
				{
					this.logger.error(`${this.getClassName()}.updateRecentByFullDelete.recentModel/set.catch:`, error);
				}
			}
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params} params
		 */
		async updateRecentBySoftDelete(params)
		{
			const recentItem = clone(this.store.getters['recentModel/getById'](params.dialogId));
			if (!recentItem)
			{
				return;
			}

			const lastMessageFromServer = params.newLastMessage
				?? params.messages.find((message) => message.id === recentItem.message?.id)
			;
			if (Type.isNil(lastMessageFromServer))
			{
				return;
			}

			const message = clone(this.store.getters['messagesModel/getById'](lastMessageFromServer.id));
			if (!('id' in message))
			{
				return;
			}

			const recentParams = clone(params);
			if (lastMessageFromServer)
			{
				message.text = ChatMessengerCommon.purifyText(recentParams.text, recentParams.params);
				message.params = recentParams.params;
				message.file = recentParams.params && recentParams.params.FILE_ID
					? recentParams.params.FILE_ID.length > 0
					: false
				;
				message.attach = recentParams.params && recentParams.params.ATTACH
					? recentParams.params.ATTACH.length > 0
					: false
				;

				recentItem.message = {
					...recentItem.message,
					...message,
				};
			}

			await this.store.dispatch('recentModel/set', [recentItem])
				.catch((error) => this.logger.error(`${this.getClassName()}.updateRecentBySoftDelete.recentModel/set.catch:`, error));
		}

		updateMessageStatus(params)
		{
			const dialogId = params.dialogId;
			const userId = params.userId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			if (params.chatMessageStatus && params.chatMessageStatus !== recentItem.message.status)
			{
				recentItem.message.status = params.chatMessageStatus;

				this.store.dispatch('recentModel/set', [recentItem])
					.catch((err) => this.logger.error(`${this.getClassName()}.updateMessageStatus.recentModel/set.catch:`, err));
			}

			const user = clone(this.store.getters['usersModel/getById'](userId));
			if (!user)
			{
				return;
			}

			this.store.dispatch('usersModel/update', [{
					id: userId,
					idle: false,
					lastActivityDate: new Date(params.date),
				}])
				.catch((err) => this.logger.error(`${this.getClassName()}.updateMessageStatus.usersModel/update.catch:`, err));
		}

		/**
		 * @desc Update views a message in dialog model store
		 * @param {Object} params - pull event
		 */
		updateChatLastMessageViews(params)
		{
			const dialogModelState = this.getDialog(params.dialogId);
			if (!dialogModelState)
			{
				return;
			}

			const isLastMessage = params.viewedMessages.includes(dialogModelState.lastMessageId);
			if (!isLastMessage)
			{
				return;
			}

			const hasFirstViewer = Boolean(dialogModelState.lastMessageViews?.firstViewer);
			if (hasFirstViewer)
			{
				const isDialog = DialogHelper.isDialogId(params.dialogId);
				const hasViewerByUserId = this.#checkMessageViewsRegistry(params.userId, dialogModelState.lastMessageId);
				if (isDialog && !hasViewerByUserId)
				{
					this.store.dispatch('dialoguesModel/incrementLastMessageViews', {
							dialogId: params.dialogId,
						})
						.then(() => {
							this.#updateMessageViewsRegistry(params.userId, dialogModelState.lastMessageId);
						})
						.catch(
							(error) => this.logger.error(
								`${this.getClassName()}.updateChatLastMessageViews.dialoguesModel/incrementLastMessageViews.catch:`,
								error,
							),
						);
				}

				return;
			}

			if (params.userId)
			{
				this.store.dispatch('dialoguesModel/setLastMessageViews', {
					dialogId: params.dialogId,
					fields: {
						userId: params.userId,
						userName: params.userName,
						date: params.date,
						messageId: dialogModelState.lastMessageId,
					},
				})
					.then(() => {
						this.#updateMessageViewsRegistry(params.userId, dialogModelState.lastMessageId);
					})
					.catch(
						(err) => this.logger.error(`${this.getClassName()}.updateChatLastMessageViews.dialoguesModel/setLastMessageViews.catch:`, err),
					);
			}
		}

		updateMessageViewedByOthers(params)
		{
			this.store.dispatch('messagesModel/setViewedByOthers', { messageIds: params.viewedMessages })
				.catch(
					(err) => this.logger.error(`${this.getClassName()}.updateMessageViewedByOthers.messagesModel/setViewedByOthers.catch:`, err),
				);
		}

		async updateCounters(params)
		{
			const dialogId = params.dialogId;
			const commentCounters = this.store.getters['commentModel/getCommentCounter']({
				commentChatId: params.chatId,
				channelId: params.parentChatId,
			});
			if (params.type === DialogType.comment || commentCounters > 0)
			{
				await this.store.dispatch('commentModel/setCounters', {
					[params.parentChatId]: {
						[params.chatId]: params.counter,
					},
				});

				const channelRecentItem = this.store.getters['recentModel/getById'](`chat${params.parentChatId}`);

				this.store.dispatch('recentModel/set', [channelRecentItem])
					.then(() => serviceLocator.get('tab-counters').update())
				;

				return;
			}

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.counter = params.counter;

			const dialog = clone(this.store.getters['dialoguesModel/getById'](dialogId));

			if (!dialog)
			{
				return;
			}

			this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: {
						// counter: params.counter,
						lastId: params.lastId,
					},
				})
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => serviceLocator.get('tab-counters').update())
				.catch(
					(err) => this.logger.error(`${this.getClassName()}.updateCounters.dialoguesModel/update.catch:`, err),
				);
		}

		saveShareDialogCache()
		{
			this.shareDialogCache.saveRecentItemListThrottled();
		}

		/**
		 * @param {Array<RawUser>} params.users
		 * @return {Promise<any>|Promise<Awaited<boolean>>}
		 */
		setUsers(params)
		{
			if (!params.users)
			{
				return Promise.resolve(false);
			}

			return this.store.dispatch('usersModel/set', Object.values(params.users));
		}

		/**
		 *
		 * @param params.files
		 * @return {Promise<Awaited<unknown>[]>|Promise<void>}
		 */
		async setFiles(params)
		{
			return FileUtils.setFiles(params);
		}

		/**
		 * @param {MessageAddParams} params
		 * @return {Promise<void>}
		 */
		async setMessage(params)
		{
			const recentMessageManager = this.getNewMessageManager(params);
			if (
				recentMessageManager.isCommentChat()
				&& !this.store.getters['applicationModel/isDialogOpen'](params.dialogId))
			{
				return;
			}

			const message = MessageDataConverter.fromPushToMessage(params);

			/**
			 * @type {MessagePullHandlerAdditionalEntities || un}
			 */
			const { additionalEntities } = params.message;

			if (Type.isObject(additionalEntities))
			{
				if (Type.isArrayFilled(additionalEntities?.users))
				{
					await this.setUsers(additionalEntities);
				}

				if (Type.isArrayFilled(additionalEntities?.files))
				{
					await this.setFiles(additionalEntities);
				}
			}

			const messageWithTemplateId = this.store.getters['messagesModel/isInChatCollection']({
				messageId: params.message.templateId,
			});

			const messageWithRealId = this.store.getters['messagesModel/isInChatCollection']({
				messageId: params.message.id,
			});

			if (messageWithRealId)
			{
				this.logger.warn(`${this.getClassName()}.New message pull handler: we already have this message`, params.message);
				this.store.dispatch('messagesModel/update', {
						id: params.message.id,
						fields: params.message,
					})
					.catch((err) => this.logger.error(`${this.getClassName()}.setMessage.messagesModel/update.catch:`, err));
			}
			else if (!messageWithRealId && messageWithTemplateId)
			{
				this.logger.warn(`${this.getClassName()}.New message pull handler: we already have the TEMPORARY message`, params.message);
				this.store.dispatch('messagesModel/updateWithId', {
						id: params.message.templateId,
						fields: params.message,
					})
					.catch((err) => this.logger.error(`${this.getClassName()}.setMessage.messagesModel/updateWithId.catch:`, err));
			}
			// it's an opponent message or our own message from somewhere else
			else if (!messageWithRealId && !messageWithTemplateId)
			{
				this.logger.warn(`${this.getClassName()}.New message pull handler: we dont have this message`, params.message);

				const prevMessageId = this.store.getters['messagesModel/getLastId'](message.chatId);

				this.store.dispatch('messagesModel/add', message).then(() => {
					/** @type {ScrollToBottomEvent} */
					const scrollToBottomEventData = {
						dialogId: message.dialogId,
						messageId: message.id,
						withAnimation: true,
						prevMessageId,
					};

					BX.postComponentEvent(EventType.dialog.external.scrollToBottom, [scrollToBottomEventData]);
				});
			}
		}

		/**
		 * @param {MessageAddParams} params
		 * @return {Promise<void>}
		 */
		async storeMessage(params)
		{
			this.logger.log(`${this.getClassName()}.storeMessage`, params);

			const message = MessageDataConverter.fromPushToMessage(params);

			/**
			 * @type {MessagePullHandlerAdditionalEntities || un}
			 */
			const { additionalEntities } = params.message;

			if (Type.isObject(additionalEntities))
			{
				if (Type.isArrayFilled(additionalEntities?.users))
				{
					await this.setUsers(additionalEntities);
				}

				if (Type.isArrayFilled(additionalEntities?.files))
				{
					await this.setFiles(additionalEntities);
				}
			}

			this.store.dispatch('messagesModel/storeToLocalDatabase', [message])
				.catch((error) => this.logger.error(`${this.getClassName()}.storeMessage.messagesModel/store.catch:`, error));
		}

		updateUserOnline(userId)
		{
			return this.store.dispatch('usersModel/update', [{
				id: userId,
				fields: {
					id: userId,
					lastActivityDate: new Date(),
				},
			}]);
		}

		/**
		 * @desc Check is having writing timer by user and stop it
		 * @param {string} dialogId
		 * @param {number} userId
		 * @void
		 */
		checkTimerInputAction(dialogId, userId) {
			if (MessengerParams.getUserId() === userId)
			{
				return;
			}

			const dialogData = this.store.getters['dialoguesModel/getById'](dialogId);
			const type = dialogData?.inputActions?.find((item) => item.userId === userId)?.action?.type;
			if (!type)
			{
				return;
			}

			const timerId = `${dialogId} ${userId} ${type}`;
			if (this.hasTimerInputAction(timerId))
			{
				this.stopTimerInputAction(timerId);
			}
		}

		/**
		 * @desc Returns check is having timer with 'writing' type by id
		 * @param {string|number} timerId
		 * @return {boolean}
		 */
		hasTimerInputAction(timerId)
		{
			return Boolean(this.inputActionWorkers[timerId]?.isHasOnce());
		}

		/**
		 * @desc Stop timer with 'writing' type by id
		 * @param {string|number} timerId
		 * @return {boolean}
		 */
		stopTimerInputAction(timerId)
		{
			if (!this.inputActionWorkers[timerId])
			{
				return false;
			}

			this.inputActionWorkers[timerId].stop({ skipCallback: false });
			delete this.inputActionWorkers[timerId];

			return true;
		}

		/**
		 * @return {?DialoguesModelState}
		 */
		getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId);
		}

		/**
		 *
		 * @param {MessagePullHandlerUpdateDialogParams}params
		 * @return {Promise<any>}
		 */
		async updateDialog(params)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](params.dialogId);
			if (!dialog)
			{
				return this.addDialog(params);
			}

			const dialogFieldsToUpdate = {};
			if (params.message.id > dialog.lastMessageId)
			{
				dialogFieldsToUpdate.lastMessageId = params.message.id;
				// dialogFieldsToUpdate.counter = params.counter;
			}

			if (params.message.senderId === MessengerParams.getUserId() && params.message.id > dialog.lastReadId)
			{
				dialogFieldsToUpdate.lastId = params.message.id;
			}

			if (this.isCurrentUserOwner(dialog.owner))
			{
				dialogFieldsToUpdate.role = UserRole.owner;
			}

			if (Object.keys(dialogFieldsToUpdate).length > 0)
			{
				return this.store.dispatch('dialoguesModel/update', {
					dialogId: params.dialogId,
					fields: dialogFieldsToUpdate,
				}).then(() => this.store.dispatch('dialoguesModel/clearLastMessageViews', {
					dialogId: params.dialogId,
				})).catch((err) => this.logger.error(`${this.getClassName()}.updateDialog.catch:`, err));
			}

			return Promise.resolve(false);
		}

		/**
		 *
		 * @param {MessagePullHandlerUpdateDialogParams}params
		 * @return {Promise<any>}
		 */
		async addDialog(params)
		{
			if (DialogHelper.isChatId(params.dialogId))
			{
				if (!params.users)
				{
					return false;
				}
				/** @type {UsersModelState} */
				const opponent = params.users[params.dialogId];

				return this.store.dispatch('dialoguesModel/set', {
					dialogId: params.dialogId,
					// counter: params.counter,
					type: DialogType.user,
					name: opponent.name,
					avatar: opponent.avatar,
					color: opponent.color,
					chatId: params.chatId,
					messagesAutoDeleteDelay: params.messagesAutoDeleteConfigs?.[0]?.delay ?? 0,
				});
			}

			if (!params.chat)
			{
				return false;
			}

			const chatData = params.chat[params.chatId];
			const dialog = {
				...chatData,
				dialogId: params.dialogId,
				// counter: params.counter,
				chatId: params.chatId,
			};

			if (this.isCurrentUserOwner(chatData.owner))
			{
				dialog.role = UserRole.owner;
			}

			return this.store.dispatch('dialoguesModel/set', dialog);
		}

		/**
		 * @param {number} ownerId
		 * @return {boolean}
		 */
		isCurrentUserOwner(ownerId)
		{
			const currentUserId = serviceLocator.get('core').getUserId();

			return currentUserId === ownerId;
		}

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}

		/**
		 * @return {NewMessageManager}
		 */
		getNewMessageManager(params, extra = {})
		{
			return new NewMessageManager(params, extra);
		}

		/**
		 * @protected
		 * @param {string} messageText
		 * @param {?string} userName
		 * @return {string}
		 */
		createMessageChatNotifyText(messageText, userName)
		{
			const simplifiedMessageText = parser.simplify({
				text: messageText,
			});

			return (userName ? `${userName}: ` : '') + simplifiedMessageText;
		}

		/**
		 * @param {number} userId
		 * @param {number} messageId
		 * @return {boolean}
		 */
		#checkMessageViewsRegistry(userId, messageId)
		{
			return Boolean(this.messageViews[messageId]?.has(userId));
		}

		/**
		 * @param {number} userId
		 * @param {number} messageId
		 */
		#updateMessageViewsRegistry(userId, messageId)
		{
			if (!this.messageViews[messageId])
			{
				this.messageViews[messageId] = new Set();
			}

			this.messageViews[messageId].add(userId);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} userId
		 * @param {number} messageId
		 * @param {boolean} liked
		 * @return {boolean}
		 */
		#updateRecentReaction(dialogId, userId, messageId, liked)
		{
			if (this.store.getters['applicationModel/isDialogOpen'](dialogId))
			{
				return;
			}

			const recentItem = this.store.getters['recentModel/getById'](dialogId);
			const isOwnLike = MessengerParams.getUserId() === userId;
			const isOwnLastMessage = MessengerParams.getUserId() === recentItem.message.senderId;
			if (isOwnLike || !isOwnLastMessage)
			{
				return;
			}

			this.store.dispatch('recentModel/like', {
				messageId,
				id: dialogId,
				liked,
			}).catch((err) => this.logger.error(`${this.getClassName()}.updateRecentReaction.recentModel/like.catch:`, err));
		}
	}

	module.exports = {
		BaseMessagePullHandler,
	};
});
