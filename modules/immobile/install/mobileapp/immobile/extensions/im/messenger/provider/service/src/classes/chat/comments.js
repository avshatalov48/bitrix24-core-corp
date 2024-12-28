/**
 * @module im/messenger/provider/service/classes/chat/comments
 */
jn.define('im/messenger/provider/service/classes/chat/comments', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Counters } = require('im/messenger/lib/counters');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('dialog--chat-service');

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
				logger.error('CommentsService: subscribe error', error);
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
				logger.error('CommentsService: unsubscribe error', error);
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
				logger.error('CommentsService: unsubscribe error', error);
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
				logger.error('CommentsService: unsubscribe error', error);
			});
		}

		readChannelComments(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			const currentChannelCounter = this.store.getters['commentModel/getChannelCounters'](dialog.chatId);
			if (currentChannelCounter === 0)
			{
				return Promise.resolve();
			}

			this.store.dispatch('commentModel/deleteChannelCounters', { channelId: dialog.chatId })
				.then(() => {
					Counters.updateDelayed();
				})
				.catch((error) => {
					logger.error('CommentsService: readAllChannelComments local error', error);
				})
			;

			return runAction(RestMethod.imV2ChatCommentReadAll, {
				data: { dialogId },
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('CommentsService: readAllChannelComments server error', error);
			});
		}
	}

	module.exports = { CommentsService };
});
