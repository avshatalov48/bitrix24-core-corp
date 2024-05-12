/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/reaction
 */
jn.define('im/messenger/db/model-writer/vuex/reaction', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('repository--reaction');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class ReactionWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/reactionsModel/set', this.addRouter)
				.on('messagesModel/reactionsModel/add', this.addRouter)
				.on('messagesModel/reactionsModel/updateWithId', this.addRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/reactionsModel/set', this.addRouter)
				.off('messagesModel/reactionsModel/add', this.addRouter)
				.off('messagesModel/reactionsModel/updateWithId', this.addRouter)
			;
		}

		/**
		 * @param {MutationPayload<ReactionsSetData, ReactionsSetActions>} mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'setFromPullEvent',
				'set',
				'setReaction',
				'removeReaction',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			if (!Type.isArrayFilled(data.reactionList))
			{
				return;
			}

			const reactionList = [];
			data.reactionList.forEach((reaction) => {
				const modelReaction = this.store.getters['messagesModel/reactionsModel/getByMessageId'](reaction.messageId);
				if (modelReaction)
				{
					reactionList.push(modelReaction);
				}
			});

			if (!Type.isArrayFilled(reactionList))
			{
				return;
			}

			this.repository.reaction.saveFromModel(reactionList)
				.catch((error) => logger.error('ReactionWriter.addRouter.saveFromModel.catch:', error));
		}
	}

	module.exports = {
		ReactionWriter,
	};
});
