/**
 * @module im/messenger-v2/provider/pull/recent
 */
jn.define('im/messenger-v2/provider/pull/recent', (require, exports, module) => {
	/* global ChatMessengerCommon */
	const { clone } = require('utils/object');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { RecentDataProvider } = require('im/messenger/provider/data');
	const { ChatRecentUpdateManager } = require('im/messenger/provider/pull/lib/recent/chat/update-manager');

	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { NewMessageManager } = require('im/messenger-v2/provider/pull/lib/new-message-manager');

	/**
	 * @class RecentPullHandler
	 */
	class RecentPullHandler extends BasePullHandler
	{
		constructor()
		{
			super({ logger: getLoggerWithContext('pull-handler--recent-v2', RecentPullHandler) });

			this.shareDialogCache = new ShareDialogCache();
		}

		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleMessage(params, extra)
		{
			await this.#messageAdd(params, extra);
		}

		/**
		 * @param {MessageAddParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleMessageChat(params, extra)
		{
			await this.#messageAdd(params, extra);
		}

		/**
		 * @param {MessagePullHandlerUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleMessageUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleMessageUpdate:', params);

			await this.#updateRecentMessage(params);
		}

		/**
		 * @param {MessagePullHandlerMessageDeleteV2Params} params
		 * @param {PullExtraParams} extra
		 */
		async handleMessageDeleteV2(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleMessageDeleteV2:', params, extra);

			const hasNewLastMessage = Boolean(params.newLastMessage);
			if (hasNewLastMessage)
			{
				await this.#updateRecentByNewLastMessage(params.newLastMessage, params.dialogId);

				return;
			}

			await this.#updateRecentByUpdatedMessages(params.messages, params.dialogId);
		}

		/**
		 * @param {ChatUnreadPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatUnread(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleChatUnread:', params, extra);

			await this.store.dispatch('recentModel/update', [
				{
					id: params.dialogId,
					unread: params.active,
				},
			]);
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatPin(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleChatPin:', params, extra);

			await this.store.dispatch('recentModel/update', [
				{
					id: params.dialogId,
					pinned: params.active,
				},
			]);
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatHide(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleChatHide:', params, extra);

			if (params.lines)
			{
				return;
			}

			await this.store.dispatch('recentModel/delete', { id: params.dialogId });
			this.#saveShareDialogCache();
		}

		/**
		 * @param {ChatUserLeavePullHandlerParams} params
		 */
		async handleChatUserLeave(params)
		{
			this.logger.info('handleChatUserLeave:', params);

			const { dialogId, userId } = params;
			if (Number(userId) !== MessengerParams.getUserId())
			{
				return;
			}

			const chatHelper = DialogHelper.createByDialogId(dialogId);
			const recentProvider = new RecentDataProvider();
			try
			{
				if (chatHelper?.isOpenChannel)
				{
					await this.store.dispatch('recentModel/deleteOpenChannel', { id: dialogId });
					await recentProvider.deleteFromSource(RecentDataProvider.source.database, { dialogId });
				}
				else
				{
					await recentProvider.delete({ dialogId });
					this.#saveShareDialogCache();
				}
			}
			catch (error)
			{
				this.logger.error('handleChatUserLeave delete chat catch:', error);
			}
		}

		/**
		 * @param {ChatDeletePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatDelete(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleChatDelete:', params, extra);

			if (params.userId === MessengerParams.getUserId())
			{
				return;
			}

			try
			{
				const recentProvider = new RecentDataProvider();
				await recentProvider.delete({ dialogId: params.dialogId });
			}
			catch (error)
			{
				this.logger.error('handleChatDelete delete chat error', error);
			}
		}

		/**
		 * @param {UserInvitePullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleUserInvite(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleUserInvite:', params);
			const messageManager = this.getNewMessageManager(params, extra);
			const preparedRecentItem = messageManager.getPreparedRecentItemByUserInvite();

			await this.store.dispatch('recentModel/setChat', [preparedRecentItem]);
		}

		/**
		 * @param {ChatAvatarPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatAvatar(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatAvatar:', params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = this.store.getters['recentModel/getById'](dialogId);
			if (!recentItem)
			{
				return;
			}

			recentItem.avatar = params.avatar;

			await this.store.dispatch('recentModel/update', [recentItem]);
		}

		/**
		 * @desc maybe it is legacy and not used
		 * @param {ChatChangeColorPullHandlerParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleChatChangeColor(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleChatChangeColor:', params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = this.store.getters['recentModel/getById'](dialogId);
			if (!recentItem)
			{
				return;
			}

			recentItem.color = params.color;

			await this.store.dispatch('recentModel/update', [recentItem]);
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 */
		async handleBotDelete(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleBotDelete:', params);

			await this.store.dispatch('recentModel/delete', { id: params.botId });
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 */
		async handleUserUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleUserUpdate:', params);

			const recentMessageManager = this.getNewMessageManager(params, extra);
			const preparedRecentItem = recentMessageManager.getPreparedRecentItemByUserUpdate();

			await this.store.dispatch('recentModel/update', [preparedRecentItem]);
		}

		/**
		 * @desc this handler works for the scenario of adding a new bot to the portal (new registration)
		 * @param {BotUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleBotAdd(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleBotAdd:', params);

			const recentMessageManager = this.getNewMessageManager(params, extra);
			const preparedRecentItem = recentMessageManager.getPreparedRecentItemByUserUpdate();

			await this.store.dispatch('recentModel/update', [preparedRecentItem]);
		}

		/**
		 * @param {BotUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleBotUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleBotUpdate:', params);

			const recentMessageManager = this.getNewMessageManager(params, extra);
			const preparedRecentItem = recentMessageManager.getPreparedRecentItemByUserUpdate();

			await this.store.dispatch('recentModel/update', [preparedRecentItem]);
		}

		/**
		 * @param {AddReactionParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleAddReaction(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleAddReaction:', params, extra);

			await this.#updateReaction(params, true);
		}

		/**
		 * @param {DeleteReactionParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleDeleteReaction(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('handleDeleteReaction:', params, extra);

			await this.#updateReaction(params, false);
		}

		/**
		 * @param {RecentUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleRecentUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleRecentUpdate:', params, extra);

			const manager = new ChatRecentUpdateManager(params);
			manager.setLastMessageInfo();

			const recentItem = manager.getPreparedRecentItem();

			await this.store.dispatch('recentModel/setChat', [recentItem]);
		}

		/**
		 * @param {UserShowInRecentParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleUserShowInRecent(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleUserShowInRecent:', params, extra);

			const { items } = params;

			const users = items.map((item) => {
				return {
					...item.user,
					lastActivityDate: item.date, // draw collaber avatar without an invited default avatar
				};
			});
			await this.store.dispatch('usersModel/set', users);

			const recentItems = items.map((item) => {
				return {
					id: item.user.id,
					lastActivityDate: item.date,
					invited: false,
				};
			});
			await this.store.dispatch('recentModel/setChat', recentItems);
		}

		/**
		 * @param {DialogId} id
		 * @return {?RecentModelState}
		 */
		getRecent(id)
		{
			return this.store.getters['recentModel/getById'](id);
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 * @return {NewMessageManager}
		 */
		getNewMessageManager(params, extra)
		{
			return new NewMessageManager(params, extra);
		}

		/**
		 * @param {object} params
		 * @param {PullExtraParams} extra
		 */
		async #messageAdd(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}
			this.logger.info('messageAdd:', params, extra);

			const messageManager = this.getNewMessageManager(params, extra);
			if (messageManager.needToSkipMessageEvent())
			{
				return;
			}

			const recentItem = messageManager.getPreparedRecentItem();
			const tabs = messageManager.getRecentTabs();
			await this.store.dispatch('recentModel/setByRecentConfigTabs', { tabs, itemList: recentItem });
		}

		/**
		 * @param {DeleteReactionParams|AddReactionParams} params
		 * @param {boolean} liked
		 */
		async #updateReaction(params, liked)
		{
			const {
				userId,
				dialogId,
				actualReactions,
			} = params;

			if (this.store.getters['applicationModel/isDialogOpen'](dialogId))
			{
				return;
			}

			const recentItem = this.getRecent(dialogId);
			const isOwnLike = MessengerParams.getUserId() === userId;
			const isOwnLastMessage = MessengerParams.getUserId() === recentItem?.message.senderId;
			if (isOwnLike || !isOwnLastMessage)
			{
				return;
			}

			await this.store.dispatch('recentModel/like', {
				messageId: actualReactions.reaction?.messageId,
				id: dialogId,
				liked,
			});
		}

		/**
		 * @param {NewLastMessageDataMessageDeleteV2Params} newLastMessage
		 * @param {string} dialogId
		 */
		async #updateRecentByNewLastMessage(newLastMessage, dialogId)
		{
			const currentRecentItem = this.getRecent(dialogId);
			if (!currentRecentItem)
			{
				return;
			}

			currentRecentItem.message = {
				text: ChatMessengerCommon.purifyText(newLastMessage.text, newLastMessage.params),
				date: newLastMessage.date,
				author_id: newLastMessage.author_id,
				id: newLastMessage.id,
				file: (newLastMessage.file || newLastMessage.params.FILE_ID) ?? false,
				unread: newLastMessage.unread ?? false,
			};

			await this.store.dispatch('recentModel/update', [currentRecentItem]);
			this.#saveShareDialogCache();
		}

		/**
		 * @param {Array<DeleteV2MessageObject>} messages
		 * @param {string} dialogId
		 */
		async #updateRecentByUpdatedMessages(messages, dialogId)
		{
			const currentRecentItem = this.getRecent(dialogId);
			if (!currentRecentItem)
			{
				return;
			}

			const lastMessageFromServer = messages.find(
				(message) => message.id === currentRecentItem.message?.id,
			);
			if (!lastMessageFromServer)
			{
				return;
			}

			const message = this.store.getters['messagesModel/getById'](lastMessageFromServer.id);
			if (!('id' in message))
			{
				return;
			}

			const recentMessageParams = lastMessageFromServer.params;
			if (lastMessageFromServer)
			{
				message.text = ChatMessengerCommon.purifyText(lastMessageFromServer.text, recentMessageParams);
				message.params = recentMessageParams;
				message.file = recentMessageParams && recentMessageParams.FILE_ID
					? recentMessageParams.FILE_ID.length > 0
					: false
				;
				message.attach = recentMessageParams && recentMessageParams.ATTACH
					? recentMessageParams.ATTACH.length > 0
					: false
				;

				currentRecentItem.message = {
					...currentRecentItem.message,
					...message,
				};
			}

			await this.store.dispatch('recentModel/update', [currentRecentItem]);
			this.#saveShareDialogCache();
		}

		/**
		 * @param {MessagePullHandlerUpdateParams} params
		 */
		async #updateRecentMessage(params)
		{
			const recentItem = this.getRecent(params.dialogId);
			if (!recentItem)
			{
				return;
			}

			const message = clone(this.store.getters['messagesModel/getById'](params.id));
			if (!('id' in message))
			{
				return;
			}

			const recentParams = params;
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

			await this.store.dispatch('recentModel/update', [recentItem]);
		}

		/**
		 * @void
		 */
		#saveShareDialogCache()
		{
			this.shareDialogCache.saveRecentItemListThrottled();
		}
	}

	module.exports = { RecentPullHandler };
});
