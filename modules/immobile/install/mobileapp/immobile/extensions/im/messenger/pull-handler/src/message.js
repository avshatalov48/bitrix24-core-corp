/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/pull-handler/message
 */
jn.define('im/messenger/pull-handler/message', (require, exports, module) => {

	const { Loc } = jn.require('loc');
	const { PullHandler } = jn.require('im/messenger/pull-handler/base');
	const { DialogConverter } = jn.require('im/messenger/lib/converter');
	const { MessengerParams } = jn.require('im/messenger/lib/params');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { Counters } = jn.require('im/messenger/lib/counters');
	const { RecentConverter } = jn.require('im/messenger/lib/converter');
	const { Notifier } = jn.require('im/messenger/lib/notifier');
	const { ShareDialogCache } = jn.require('im/messenger/cache/share-dialog');

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
			const message = DialogConverter.fromPushToMessage(params);

			const recipientId =
				params.message.senderId === userId
					? params.message.recipientId
					: params.message.senderId
			;

			const recentItem = RecentConverter.fromPushToModel({
				id: recipientId,
				user: params.users[recipientId],
				message: params.message,
				counter: params.counter,
				writing: false,
			});

			MessengerStore.dispatch('recentModel/set', [ recentItem ])
				.then(() => {
					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			MessengerStore.dispatch('messagesModel/push', {
				dialogId,
				message,
			});

			if (extra && extra.server_time_ago <= 5 && params.message.senderId !== userId)
			{
				Notifier.notify({
					dialogId: recipientId,
					title: recentItem.user.name,
					text: recentItem.message.text,
					avatar: recentItem.user.avatar,
				});
			}
		}

		handleMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleMessageChat ', params);

			const dialogId = params.message.recipientId;
			if (params.lines)
			{
				if (MessengerParams.isOpenlinesOperator())
				{
					Counters.openlinesCounter.detail[params.dialogId] = params.counter;
					Counters.update();
				}

				return;
			}

			const userId = MessengerParams.getUserId();

			params.message.text = ChatMessengerCommon.purifyText(params.message.text, params.message.params);
			params.message.status = params.message.senderId === userId ? 'received' : '';

			const recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: params.chat[params.chatId],
				user: params.message.senderId > 0 ? params.users[params.message.senderId]: { id: 0 },
				lines: params.lines,
				message: params.message,
				counter: params.counter,
				liked: false,
				writing: false,
			});

			MessengerStore.dispatch('dialoguesModel/set', [{ id: dialogId, ...params }]);

			MessengerStore.dispatch('recentModel/set', [ recentItem ])
				.then(() => {
					Counters.updateDelayed();

					this.saveShareDialogCache();
				})
			;

			const message = DialogConverter.fromPushToMessage(params);
			MessengerStore.dispatch('messagesModel/push', {
				dialogId,
				message,
			});

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

			this.updateCounters(params);
		}

		handleReadMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageChat: ', params);

			this.updateCounters(params);
		}

		handleUnreadMessage(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleUnreadMessage: ', params);

			this.updateCounters(params);
		}

		handleUnreadMessageChat(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleUnreadMessageChat: ', params);

			this.updateCounters(params);
		}

		updateMessage(params)
		{
			const dialogId = params.dialogId;
			const messageId = params.id;

			const message = ChatUtils.objectClone(MessengerStore.getters['messagesModel/get'](dialogId, messageId));
			if (!message)
			{
				return;
			}

			message.text = ChatMessengerCommon.purifyText(params.text, params.params);
			message.params = params.params;
			message.file = params.params && params.params.FILE_ID ? params.params.FILE_ID.length > 0: false;
			message.attach = params.params && params.params.ATTACH ? params.params.ATTACH.length > 0: false;

			MessengerStore.dispatch('messagesModel/add', {
				dialogId,
				messages: [ message ],
			});

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			if (recentItem.message.id === message.id)
			{
				recentItem.message = {
					...recentItem.message,
					...message,
				};
			}

			recentItem.writing = false;

			MessengerStore.dispatch('recentModel/set', [ recentItem ]);
		}

		handleReadMessageOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageOpponent: ', params);

			this.updateMessageStatus(params);
		}

		handleReadMessageChatOpponent(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadMessageChatOpponent: ', params);

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
			const currentDialogId = MessengerStore.getters['applicationModel/getDialogId'];

			if (dialogId === currentDialogId)
			{
				return;
			}

			const currentUserId = MessengerParams.getUserId();
			if (currentUserId === params.senderId)
			{
				return;
			}

			MessengerStore.dispatch('recentModel/like', {
				id: dialogId,
				messageId: params.id,
				liked: params.set,
			});
		}

		updateMessageStatus(params)
		{
			const dialogId = params.dialogId;
			const userId = params.userId;

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			if (params.chatMessageStatus && params.chatMessageStatus !== recentItem.message.status)
			{
				recentItem.message.status = params.chatMessageStatus;

				MessengerStore.dispatch('recentModel/set', [recentItem]);
			}

			const recentUserItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](userId));
			if (!recentUserItem)
			{
				return;
			}

			recentUserItem.user.idle = false;
			recentUserItem.user.last_activity_date = new Date(params.date);

			MessengerStore.dispatch('recentModel/set', [recentUserItem]);

			//TODO: also change data in user model
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

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.counter = params.counter;

			MessengerStore.dispatch('recentModel/set', [ recentItem ])
				.then(() => Counters.update())
			;
		}

		setRecentItemWriting(dialogId, isWriting)
		{
			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return false;
			}

			Logger.info('MessagePullHandler.handleStartWriting: ', dialogId);

			recentItem.writing = isWriting;

			MessengerStore.dispatch('recentModel/set', [ recentItem ]);

			return true;
		}

		saveShareDialogCache()
		{
			const firstPage = MessengerStore.getters['recentModel/getRecentPage'](1, 50);
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
