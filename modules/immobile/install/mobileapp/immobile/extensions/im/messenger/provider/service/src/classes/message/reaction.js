/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/classes/message/reaction
 */
jn.define('im/messenger/provider/service/classes/message/reaction', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { RestMethod } = require('im/messenger/const');
	const { UuidManager } = require('im/messenger/lib/uuid');

	/**
	 * @class ReactionService
	 */
	class ReactionService
	{
		constructor({ store, chatId })
		{
			/** @type {MessengerCoreStore} */
			this.store = store;
			this.chatId = chatId;
		}

		/**
		 * @param {ReactionType} reaction
		 * @param messageId
		 */
		add(reaction, messageId)
		{
			Logger.warn('ReactionService: add', reaction, messageId);

			const user = this.store.getters['usersModel/getById'](MessengerParams.getUserId());

			/** @type {ReactionsModelSetReactionPayload} */
			const storeParams = {
				messageId,
				reaction,
				user: {
					name: user.name,
					avatar: user.avatar || '',
					id: MessengerParams.getUserId(),
				},
			};

			this.store.dispatch('messagesModel/reactionsModel/setReaction', storeParams);

			const queryParams = {
				messageId,
				reaction,
				actionUuid: UuidManager.getActionUuid(),
			};

			BX.rest.callMethod(RestMethod.imV2ChatMessageReactionAdd, queryParams).catch((error) => {
				Logger.error('ReactionService: error reaction add', error);

				this.store.dispatch('messagesModel/reactionsModel/removeReaction', storeParams);
			});
		}

		remove(reaction, messageId)
		{
			Logger.warn('ReactionService: remove', reaction, messageId);
			const user = this.store.getters['usersModel/getById'](MessengerParams.getUserId());

			/** @type {ReactionsModelSetReactionPayload} */
			const storeParams = {
				messageId,
				reaction,
				user: {
					name: user.name,
					avatar: user.avatar || '',
					id: MessengerParams.getUserId(),
				},
			};

			this.store.dispatch('messagesModel/reactionsModel/removeReaction', storeParams);

			const queryParams = {
				reaction,
				messageId,
				actionUuid: UuidManager.getActionUuid(),
			};

			BX.rest.callMethod(RestMethod.imV2ChatMessageReactionDelete, queryParams).catch((error) => {
				Logger.error('ReactionService: error reaction remove', error);

				this.store.dispatch('messagesModel/reactionsModel/setReaction', storeParams);
			});
		}
	}

	module.exports = {
		ReactionService,
	};
});
