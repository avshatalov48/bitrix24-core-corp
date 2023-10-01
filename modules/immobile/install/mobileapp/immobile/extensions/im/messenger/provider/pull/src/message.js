/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/message
 */
jn.define('im/messenger/provider/pull/message', (require, exports, module) => {
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Logger } = require('im/messenger/lib/logger');
	const { Counters } = require('im/messenger/lib/counters');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Notifier } = require('im/messenger/lib/notifier');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { ReactionType } = require('im/messenger/const');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const/rest');

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
			});

			this.store.dispatch('usersModel/set', Object.values(params.users));
			this.store.dispatch('recentModel/set', [recentItem])
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

			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
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

		handleMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageChat ', params);

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
			});

			this.store.dispatch('recentModel/set', [recentItem])
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

			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
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

		handleMessageDeleteComplete(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageDeleteComplete: ', params, extra);

			this.fullDeleteMessage(params);
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

			const message = clone(this.store.getters['messagesModel/getMessageById'](messageId));
			if (!message)
			{
				return;
			}

			this.store.dispatch('messagesModel/update', {
				id: params.id,
				fields: {
					text: params.text,
					params: params.params,
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

			recentItem.writing = false;
			this.store.dispatch('recentModel/set', [recentItem]);
		}

		fullDeleteMessage(params)
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

			const newLastMessage = params.newLastMessage;
			if (newLastMessage && newLastMessage.message)
			{
				recentItem.message = {
					text: newLastMessage.message.text,
					date: newLastMessage.message.date,
					author_id: newLastMessage.message.author_id,
					id: newLastMessage.message.id,
					file: newLastMessage.files.length > 0,
				};
				this.store.dispatch('recentModel/set', [recentItem])
					.catch((err) => Logger.error(err));
			}
		}

		handleReadMessageOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageOpponent: ', params);

			this.updateMessageViewedByOthers(params);
			this.updateMessageStatus(params);
		}

		handleReadMessageChatOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageChatOpponent: ', params);

			this.updateMessageViewedByOthers(params);
			this.updateMessageStatus(params);
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

		handleMessageLike(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageLike: ', params);

			const dialogId = params.dialogId;

			const payload = {
				dialogId,
				messageId: params.id,
				reactionId: ReactionType.like,
				userList: params.users.map((userId) => Number(userId)),
			};

			this.store.dispatch('messagesModel/setReaction', payload);

			const currentDialogId = this.store.getters['applicationModel/getDialogId'];
			if (dialogId === currentDialogId)
			{
				return;
			}

			const currentUserId = MessengerParams.getUserId();
			if (currentUserId === params.senderId)
			{
				return;
			}

			this.store.dispatch('recentModel/like', {
				id: dialogId,
				messageId: params.id,
				liked: params.set,
			});
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

			this.store.dispatch('recentModel/set', [recentItem])
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
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			const user = this.store.getters['usersModel/getUserById'](userId);
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
				userModel = this.store.getters['usersModel/getUserById'](userData.id);
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
	}

	module.exports = {
		MessagePullHandler,
	};
});
