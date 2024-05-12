/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/classes/message/reaction
 */
jn.define('im/messenger/provider/service/classes/message/reaction', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { RestMethod } = require('im/messenger/const');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');
	const { runAction } = require('im/messenger/lib/rest');

	/**
	 * @class ReactionService
	 */
	class ReactionService
	{
		constructor({ chatId })
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			this.chatId = chatId;
		}

		set(reaction, messageId)
		{
			const reactions = this.store.getters['messagesModel/reactionsModel/getByMessageId'](messageId);

			if (reactions && reactions.ownReactions.has(reaction))
			{
				this.remove(reaction, messageId);
			}
			else
			{
				this.add(reaction, messageId);
			}
		}

		/**
		 * @param {ReactionType} reaction
		 * @param messageId
		 */
		add(reaction, messageId)
		{
			Logger.warn('ReactionService: add', reaction, messageId);

			/** @type {ReactionsModelSetReactionPayload} */
			const storeParams = {
				messageId,
				reaction,
				userId: MessengerParams.getUserId(),
			};

			this.store.dispatch('messagesModel/reactionsModel/setReaction', storeParams);

			const queryParams = {
				messageId,
				reaction,
				actionUuid: UuidManager.getInstance().getActionUuid(),
			};

			runAction(RestMethod.imV2ChatMessageReactionAdd, { data: queryParams }).catch((errors) => {
				Logger.error('ReactionService.add error:', errors);

				this.store.dispatch('messagesModel/reactionsModel/removeReaction', storeParams);
			});
		}

		remove(reaction, messageId)
		{
			Logger.warn('ReactionService: remove', reaction, messageId);

			/** @type {ReactionsModelSetReactionPayload} */
			const storeParams = {
				messageId,
				reaction,
				userId: MessengerParams.getUserId(),
			};

			this.store.dispatch('messagesModel/reactionsModel/removeReaction', storeParams);

			const queryParams = {
				reaction,
				messageId,
				actionUuid: UuidManager.getInstance().getActionUuid(),
			};

			runAction(RestMethod.imV2ChatMessageReactionDelete, { data: queryParams }).catch((errors) => {
				Logger.error('ReactionService.remove error:', errors);

				this.store.dispatch('messagesModel/reactionsModel/setReaction', storeParams);
			});
		}
	}

	module.exports = {
		ReactionService,
	};
});
