/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/message
 */
jn.define('im/messenger/provider/pull/message', (require, exports, module) => {
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Logger } = require('im/messenger/lib/logger');
	const { Counters } = require('im/messenger/lib/counters');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Notifier } = require('im/messenger/lib/notifier');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { UuidManager } = require('im/messenger/lib/uuid');
	const { DialogType } = require('im/messenger/const');

	/**
	 * @class MessagePullHandler
	 */
	class MessagePullHandler extends PullHandler
	{
		handleMessage(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessage ', params);

			const dialogId = params.message.recipientId;
			const userId = MessengerParams.getUserId();

			const recipientId = params.message.senderId === userId
				? params.message.recipientId
				: params.message.senderId
			;

			const recentParams = clone(params);
			const userData = recentParams.users[recipientId];
			const recentItem = RecentConverter.fromPushToModel({
				id: recipientId,
				user: userData,
				message: recentParams.message,
				counter: recentParams.counter,
				writing: false,
				date_update: new Date(),
			});

			this.store.dispatch('usersModel/set', Object.values(params.users));

			if (!recentItem)
			{
				return;
			}
			this.updateDialog(params)
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => {
					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			if (extra && extra.server_time_ago <= 5 && params.message.senderId !== userId)
			{
				Notifier.notify({
					dialogId: recipientId,
					title: recentItem.user.name,
					text: recentItem.message.text,
					avatar: recentItem.user.avatar,
				});
			}

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

			this.setFiles(params).then(() => {
				this.setMessage(params);
				this.checkWritingTimer(params.dialogId, userData);
			});
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
			}

			if (params.message.senderId === MessengerParams.getUserId() && params.message.id > dialog.lastReadId)
			{
				dialogFieldsToUpdate.lastId = params.message.id;
			}

			dialogFieldsToUpdate.counter = params.counter;

			if (Object.keys(dialogFieldsToUpdate).length > 0)
			{
				return this.store.dispatch('dialoguesModel/update', {
					dialogId: params.dialogId,
					fields: dialogFieldsToUpdate,
				}).then(() => this.store.dispatch('dialoguesModel/clearLastMessageViews', {
					dialogId: params.dialogId,
				}));
			}
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
					counter: params.counter,
					type: DialogType.user,
					name: opponent.name,
					avatar: opponent.avatar,
					color: opponent.color,
					chatId: params.chatId,
				});
			}

			if (!params.chat)
			{
				return false;
			}

			return this.store.dispatch('dialoguesModel/set', {
				...params.chat[params.chatId],
				dialogId: params.dialogId,
				counter: params.counter,
				chatId: params.chatId,
			});
		}

		handleMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageChat ', params, extra);

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
				writing: false,
				date_update: new Date(),
			});

			this.updateDialog(params)
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => {
					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			if (
				extra && extra.server_time_ago <= 5
				&& params.message.senderId !== userId
				&& !recentItem.chat.mute_list[userId]
			)
			{
				Notifier.notify({
					dialogId: recentItem.id,
					title: recentItem.chat.name,
					text: (recentItem.user.name ? `${recentItem.user.name}: ` : '') + recentItem.message.text,
					avatar: recentItem.chat.avatar,
				});
			}

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

			this.setFiles(params).then(() => {
				this.setMessage(params);
				this.checkWritingTimer(dialogId, userData);
			});
		}

		handleMessageUpdate(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageUpdate: ', params);

			this.updateMessage(params);
		}

		handleMessageDelete(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageDelete: ', params, extra);

			// eslint-disable-next-line no-param-reassign
			params.text = Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED');

			this.updateMessage(params);
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteCompleteParams} params
		 * @param extra
		 * @param command
		 */
		handleMessageDeleteComplete(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageDeleteComplete: ', params, extra);

			this.fullDeleteMessage(params);
		}

		/**
		 * @param {AddReactionParams} params
		 * @param extra
		 * @param command
		 */
		handleAddReaction(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleAddReaction: ', params);
			if (UuidManager.hasActionUuid(extra.action_uuid))
			{
				Logger.info('MessagePullHandler.handleAddReaction: we already locally processed this action');

				return;
			}
			const {
				actualReactions: { reaction: actualReactionsState, usersShort },
				userId,
				reaction,
			} = params;
			const message = this.store.getters['messagesModel/getById'](actualReactionsState.messageId);
			if (!message)
			{
				return;
			}

			if (MessengerParams.getUserId().toString() === userId.toString())
			{
				actualReactionsState.ownReactions = [reaction];
			}

			this.store.dispatch('messagesModel/reactionsModel/setFromPullEvent', {
				usersShort,
				reactions: [actualReactionsState],
			});
		}

		/**
		 * @param {DeleteReactionParams} params
		 * @param extra
		 * @param command
		 */
		handleDeleteReaction(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleDeleteReaction: ', params);
			if (UuidManager.hasActionUuid(extra.action_uuid))
			{
				Logger.info('MessagePullHandler.handleDeleteReaction: we already locally processed this action');

				return;
			}
			const { actualReactions: { reaction: actualReactionsState, usersShort } } = params;

			const message = this.store.getters['messagesModel/getById'](actualReactionsState.messageId);
			if (!message)
			{
				return;
			}

			this.store.dispatch('messagesModel/reactionsModel/setFromPullEvent', {
				usersShort,
				reactions: [actualReactionsState],
			});
		}

		async handleStartWriting(params, extra, command)
		{
			const isHasRecent = this.setRecentItemWriting(params, true);
			const isHasDialog = await this.setDialogItemWriting(params, true);
			if (isHasRecent || isHasDialog)
			{
				ChatTimer.start('writing', `${params.dialogId} ${params.userName}`, 25000, () => {
					if (isHasRecent)
					{
						this.setRecentItemWriting(params, false);
					}

					if (isHasDialog)
					{
						this.setDialogItemWriting(params, false);
					}
				}, params);
			}
		}

		handleReadMessage(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessage: ', params);

			this.readMessage(params);
			this.updateCounters(params);
		}

		handleReadMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageChat: ', params);

			this.readMessage(params);
			this.updateCounters(params);
		}

		handleUnreadMessage(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleUnreadMessage: ', params);

			this.unreadMessage(params);
			this.updateCounters(params);
		}

		handleUnreadMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleUnreadMessageChat: ', params);

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

		updateMessage(params)
		{
			const dialogId = params.dialogId;
			const messageId = params.id;
			const messageParams = params.params;

			const message = clone(this.store.getters['messagesModel/getById'](messageId));
			if (!message)
			{
				return;
			}

			if (message.params && message.params.replyId)
			{
				// this copyrighting params need for update quote - not deleting
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

				recentItem.date_update = new Date();
			}

			recentItem.writing = false;
			this.store.dispatch('recentModel/set', [recentItem]);
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteCompleteParams} params
		 */
		async fullDeleteMessage(params)
		{
			const dialogId = params.dialogId;
			const messageId = params.id;

			this.store.dispatch('messagesModel/delete', { id: messageId })
				.catch((err) => Logger.error(err));

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			let isNeedUpdateRecentItem = false;
			const dialogItem = this.store.getters['dialoguesModel/getById'](dialogId);

			if (params.counter !== dialogItem.counter)
			{
				recentItem.counter = params.counter;

				await this.updateDialog({
					dialogId,
					message: {
						senderId: params.senderId,
						id: messageId,
					},
					counter: params.counter,
				});

				isNeedUpdateRecentItem = true;
			}

			const newLastMessage = params.newLastMessage;
			if (newLastMessage)
			{
				recentItem.message = {
					text: newLastMessage.text,
					date: newLastMessage.date,
					author_id: newLastMessage.author_id,
					id: newLastMessage.id,
					file: newLastMessage.files ? (newLastMessage.files.length > 0) : false,
				};

				recentItem.date_update = new Date();

				isNeedUpdateRecentItem = true;
			}

			if (isNeedUpdateRecentItem)
			{
				this.store.dispatch('recentModel/set', [recentItem])
					.then(() => {
						Counters.update();

						this.saveShareDialogCache();
					})
					.catch((err) => Logger.error(err))
				;
			}
		}

		handleReadMessageOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageOpponent: ', params);

			this.updateMessageViewedByOthers(params);
			this.updateMessageStatus(params);
			this.updateChatLastMessageViews(params);
		}

		handleReadMessageChatOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageChatOpponent: ', params);

			this.updateMessageViewedByOthers(params);
			this.updateMessageStatus(params);
			this.updateChatLastMessageViews(params);
		}

		handleUnreadMessageOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleUnreadMessageOpponent: ', params);

			this.updateMessageStatus(params);
		}

		handleUnreadMessageChatOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleUnreadMessageChatOpponent: ', params);

			this.updateMessageStatus(params);
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

				this.store.dispatch('recentModel/set', [recentItem]);
			}

			const recentUserItem = clone(this.store.getters['recentModel/getById'](userId));
			if (!recentUserItem)
			{
				return;
			}

			recentUserItem.user.idle = false;
			recentUserItem.user.last_activity_date = new Date(params.date);

			this.store.dispatch('recentModel/set', [recentUserItem]);

			// TODO: also change data in user model
		}

		/**
		 * @desc Update views message in dialog model store
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

			const hasFirstViewer = Boolean(dialogModelState.lastMessageViews.firstViewer);
			if (hasFirstViewer)
			{
				// FIXME this case occurs when the user is using 2 or more devices at the same time ( work only first user )
				//  need wait update while this bug in backend will be fix
				const isDoubleFirstViewer = params.userId === dialogModelState.lastMessageViews.firstViewer.userId;
				if (DialogHelper.isDialogId(params.dialogId) && !isDoubleFirstViewer)
				{
					this.store.dispatch('dialoguesModel/incrementLastMessageViews', {
						dialogId: params.dialogId,
					});
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
				});
			}
		}

		updateMessageViewedByOthers(params)
		{
			this.store.dispatch('messagesModel/setViewedByOthers', { messageIds: params.viewedMessages });
		}

		updateCounters(params)
		{
			const dialogId = params.dialogId;

			if (params.lines)
			{
				Counters.openlinesCounter.detail[params.dialogId] = params.counter;
				Counters.update();

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
					counter: params.counter,
					lastReadId: params.lastId,
				},
			})
				.then(() => this.store.dispatch('recentModel/set', [recentItem]))
				.then(() => Counters.update())
			;
		}

		setRecentItemWriting(params, isWriting)
		{
			const { dialogId } = params;
			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return false;
			}

			Logger.info('MessagePullHandler.handleStartWriting: ', dialogId);

			recentItem.writing = isWriting;

			this.store.dispatch('recentModel/set', [recentItem]);

			return true;
		}

		/**
		 * @desc set writing list data to dialog model
		 * @param {object} params
		 * @param {string} params.dialogId
		 * @param {number} params.userId
		 * @param {string} params.userName
		 * @param {boolean} isWriting
		 * @return (Promise|boolean}
		 */
		setDialogItemWriting(params, isWriting)
		{
			const { dialogId, userId } = params;
			const dialog = this.getDialog(dialogId);
			const user = this.store.getters['usersModel/getById'](userId);
			if (!dialog || !user)
			{
				return false;
			}

			Logger.info('MessagePullHandler.handleStartWriting.setDialogItemWriting ', params);

			return this.store.dispatch('dialoguesModel/updateWritingList', {
				dialogId,
				fields: {
					writingList: [{ ...params, isWriting }],
				},
			}).then(() => true);
		}

		saveShareDialogCache()
		{
			const firstPage = this.store.getters['recentModel/getRecentPage'](1, 50);
			ShareDialogCache.saveRecentItemList(firstPage)
				.then((cache) => {
					Logger.log('MessagePullHandler: Saving recent items for the share dialog is successful.', cache);
				})
				.catch((cache) => {
					Logger.log('MessagePullHandler: Saving recent items for share dialog failed.', firstPage, cache);
				})
			;
		}

		setFiles(params)
		{
			if (!params.files)
			{
				return Promise.resolve();
			}

			const promises = [];
			const files = Object.values(params.files);
			files.forEach((file) => {
				const templateFileIdExists = this.store.getters['filesModel/isInCollection']({
					fileId: params.message?.templateFileId,
				});

				if (templateFileIdExists)
				{
					const updateFileWithIdPromise = this.store.dispatch('filesModel/updateWithId', {
						id: params.message?.templateFileId,
						fields: file,
					});

					promises.push(updateFileWithIdPromise);
				}
				else
				{
					const setFilePromise = this.store.dispatch('filesModel/set', file);
					promises.push(setFilePromise);
				}
			});

			return Promise.all(promises);
		}

		setMessage(params)
		{
			const message = DialogConverter.fromPushToMessage(params);

			const messageWithTemplateId = this.store.getters['messagesModel/isInChatCollection']({
				messageId: params.message.templateId,
			});

			const messageWithRealId = this.store.getters['messagesModel/isInChatCollection']({
				messageId: params.message.id,
			});

			if (messageWithRealId)
			{
				Logger.warn('New message pull handler: we already have this message', params.message);
				this.store.dispatch('messagesModel/update', {
					id: params.message.id,
					fields: params.message,
				});
			}
			else if (!messageWithRealId && messageWithTemplateId)
			{
				Logger.warn('New message pull handler: we already have the TEMPORARY message', params.message);
				this.store.dispatch('messagesModel/updateWithId', {
					id: params.message.templateId,
					fields: params.message,
				});
			}
			// it's an opponent message or our own message from somewhere else
			else if (!messageWithRealId && !messageWithTemplateId)
			{
				Logger.warn('New message pull handler: we dont have this message', params.message);

				this.store.dispatch('messagesModel/add', message);
			}
		}

		/**
		 * @desc Check is has writing timer by user and stop it
		 * @param {string} dialogId
		 * @param {object} userData
		 * @void
		 */
		checkWritingTimer(dialogId, userData) {
			let userModel = userData;

			if (!userModel.name)
			{
				userModel = this.store.getters['usersModel/getById'](userData.id);
			}
			const timerId = `${dialogId} ${userModel.name}`;
			if (this.isHasTimerWriting(timerId))
			{
				this.stopTimerWriting(timerId);
			}
		}

		/**
		 * @desc Returns check is has timer with 'writing' type by id
		 * @param {string|number} timerId
		 * @return (boolean}
		 */
		isHasTimerWriting(timerId)
		{
			return ChatTimer.isHasTimer('writing', timerId);
		}

		/**
		 * @desc Stop timer with 'writing' type by id
		 * @param {string|number} timerId
		 * @return (boolean}
		 */
		stopTimerWriting(timerId)
		{
			return ChatTimer.stop('writing', timerId);
		}

		getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId);
		}
	}

	module.exports = {
		MessagePullHandler,
	};
});
