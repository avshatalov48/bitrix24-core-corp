/**
 * @module im/messenger/provider/services/chat/comments
 */
jn.define('im/messenger/provider/services/chat/comments', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerCounterSender } = require('im/messenger/lib/counters/counter-manager/messenger/sender');
	const { Feature } = require('im/messenger/lib/feature');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	const logger = getLoggerWithContext('dialog--chat-service', 'CommentsService');

	/**
	 * @class CommentsService
	 */
	class CommentsService
	{
		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
		}

		subscribe(dialogId)
		{
			this.store.dispatch('dialoguesModel/unmute', { dialogId });

			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			this.store.dispatch('commentModel/subscribe', { messageId: dialog.parentMessageId });

			return runAction(RestMethod.imV2ChatCommentSubscribe, {
				data: { dialogId },
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('subscribe error', error);
			});
		}

		subscribeByPostId(postId)
		{
			this.store.dispatch('commentModel/subscribe', { messageId: postId });

			return runAction(RestMethod.imV2ChatCommentSubscribe, {
				data: {
					postId,
					createIfNotExists: 'Y',
					autoJoin: 'Y',
				},
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('subscribeByPostId error', error);
			});
		}

		unsubscribe(dialogId)
		{
			this.store.dispatch('dialoguesModel/mute', { dialogId });

			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			this.store.dispatch('commentModel/unsubscribe', { messageId: dialog.parentMessageId });

			return runAction(RestMethod.imV2ChatCommentUnsubscribe, {
				data: { dialogId },
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('unsubscribe error', error);
			});
		}

		unsubscribeByPostId(postId)
		{
			this.store.dispatch('commentModel/unsubscribe', { messageId: postId });

			return runAction(RestMethod.imV2ChatCommentUnsubscribe, {
				data: {
					postId,
					createIfNotExists: 'Y',
					autoJoin: 'Y',
				},
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('unsubscribeByPostId error', error);
			});
		}

		readChannelComments(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			this.#readChannelCommentsLocal(dialog.chatId);

			return runAction(RestMethod.imV2ChatCommentReadAll, {
				data: { dialogId },
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('readAllChannelComments server error', error);
			});
		}

		// eslint-disable-next-line consistent-return
		async #readChannelCommentsLocal(chatId)
		{
			if (Feature.isMessengerV2Enabled)
			{
				try
				{
					await this.store.dispatch('counterModel/readChildChatsCounters', {
						parentChatId: chatId,
					});
				}
				catch (error)
				{
					logger.error('readChannelCommentsLocal error', error);
				}
			}

			const currentChannelCounter = this.store.getters['commentModel/getChannelCounters'](chatId);
			if (currentChannelCounter === 0)
			{
				return Promise.resolve();
			}

			try
			{
				await this.store.dispatch('commentModel/deleteChannelCounters', { channelId: chatId });
				MessengerCounterSender.getInstance().sendReadChannelComments(chatId);
				serviceLocator.get('tab-counters').updateDelayed();
			}
			catch (error)
			{
				logger.error('readChannelCommentsLocal old Messenger error', error);
			}
		}
	}

	module.exports = { CommentsService };
});
