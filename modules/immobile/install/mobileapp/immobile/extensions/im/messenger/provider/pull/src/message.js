/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

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

			const recipientId =
				params.message.senderId === userId
					? params.message.recipientId
					: params.message.senderId
			;

			const recentParams = clone(params);
			const recentItem = RecentConverter.fromPushToModel({
				id: recipientId,
				user: recentParams.users[recipientId],
				message: recentParams.message,
				counter: recentParams.counter,
				writing: false,
			});

			this.store.dispatch('usersModel/set', Object.values(params.users));
			this.store.dispatch('recentModel/set', [ recentItem ])
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

			const message = DialogConverter.fromPushToMessage(params);
			this.store.dispatch('filesModel/set', Object.values(params.files)).then(() => {
				this.store.dispatch('messagesModel/add', message);
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
			recentParams.message.text = ChatMessengerCommon.purifyText(recentParams.message.text, recentParams.message.params);
			recentParams.message.status = recentParams.message.senderId === userId ? 'received' : '';

			const recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: recentParams.chat[recentParams.chatId],
				user: recentParams.message.senderId > 0 ? recentParams.users[recentParams.message.senderId]: { id: 0 },
				lines: recentParams.lines,
				message: recentParams.message,
				counter: recentParams.counter,
				liked: false,
				writing: false,
			});

			this.store.dispatch('recentModel/set', [ recentItem ])
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
					text: (recentItem.user.name ? recentItem.user.name + ': ' : '') + recentItem.message.text,
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

			const message = DialogConverter.fromPushToMessage(params);
			this.store.dispatch('filesModel/set', Object.values(params.files)).then(() => {
				this.store.dispatch('messagesModel/add', message);
			});
		}

		handleMessageUpdate(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageUpdate: ', params);

			this.updateMessage(params);
		}

		handleMessageDelete(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageDelete: ', params);

			params.text = Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED');

			this.updateMessage(params);
		}

		handleMessageDeleteComplete(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageDeleteComplete: ', params);

			this.updateMessage(params);
		}

		handleStartWriting(params, extra, command)
		{
			const dialogId = params.dialogId;

			const isSuccess = this.setRecentItemWriting(dialogId, true);
			if (isSuccess)
			{
				ChatTimer.start('writing', dialogId, 29500, () => {
					this.setRecentItemWriting(dialogId, false);
				});
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
				messageIds: params.viewedMessages
			});
		}

		unreadMessage(params)
		{

		}

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
				}
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
				message.file = recentParams.params && recentParams.params.FILE_ID ? recentParams.params.FILE_ID.length > 0: false;
				message.attach = recentParams.params && recentParams.params.ATTACH ? recentParams.params.ATTACH.length > 0: false;

				recentItem.message = {
					...recentItem.message,
					...message,
				};
			}

			recentItem.writing = false;

			this.store.dispatch('recentModel/set', [ recentItem ]);
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
				userList: params.users.map(userId => Number(userId)),
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

			//TODO: also change data in user model
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

			this.store.dispatch('recentModel/set', [ recentItem ])
				.then(() => Counters.update())
			;
		}

		setRecentItemWriting(dialogId, isWriting)
		{
			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return false;
			}

			Logger.info('MessagePullHandler.handleStartWriting: ', dialogId);

			recentItem.writing = isWriting;

			this.store.dispatch('recentModel/set', [ recentItem ]);

			return true;
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
	}

	module.exports = {
		MessagePullHandler,
	};
});
