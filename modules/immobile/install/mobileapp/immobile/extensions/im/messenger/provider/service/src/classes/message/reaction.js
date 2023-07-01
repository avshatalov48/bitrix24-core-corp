/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/classes/message/reaction
 */
jn.define('im/messenger/provider/service/classes/message/reaction', (require, exports, module) => {

	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		ReactionType,
		RestMethod,
	} = require('im/messenger/const');

	/**
	 * @class ReactionService
	 */
	class ReactionService
	{
		constructor({ store, chatId })
		{
			this.store = store;
			this.chatId = chatId;
		}

		add(reactionId, messageId)
		{
			reactionId = ReactionType.like; //TODO: Support for other reactions

			Logger.warn('ReactionService: add', reactionId, messageId);
			this.store.dispatch('messagesModel/addReaction', {
				messageId,
				reactionId,
				userList: [MessengerParams.getUserId()],
			});

			const queryParams = {
				'MESSAGE_ID': messageId,
				'ACTION': 'plus',
			};
			BX.rest.callMethod(RestMethod.imMessageLike, queryParams).catch(error => {
				Logger.error('ReactionService: error reaction add', error);

				this.store.dispatch('messagesModel/removeReaction', {
					messageId,
					reactionId,
					userList: [MessengerParams.getUserId()],
				});
			});
		}

		remove(reactionId, messageId)
		{
			reactionId = ReactionType.like; //TODO: Support for other reactions

			Logger.warn('ReactionService: remove', reactionId, messageId);
			this.store.dispatch('messagesModel/removeReaction', {
				messageId,
				reactionId,
				userList: [MessengerParams.getUserId()],
			});

			const queryParams = {
				'MESSAGE_ID': messageId,
				'ACTION': 'minus',
			};
			BX.rest.callMethod(RestMethod.imMessageLike, queryParams).catch(error => {
				Logger.error('ReactionService: error reaction remove', error);

				this.store.dispatch('messagesModel/addReaction', {
					messageId,
					reactionId,
					userList: [MessengerParams.getUserId()],
				});
			});
		}
	}

	module.exports = {
		ReactionService,
	};
});
