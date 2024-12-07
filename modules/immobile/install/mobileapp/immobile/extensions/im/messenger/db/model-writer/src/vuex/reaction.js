/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/reaction
 */
jn.define('im/messenger/db/model-writer/vuex/reaction', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const logger = LoggerManager.getInstance().getLogger('repository--reaction');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class ReactionWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();
			this.setRouter = this.setRouter.bind(this);
		}

		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/reactionsModel/set', this.setRouter)
				.on('messagesModel/reactionsModel/add', this.addRouter)
				.on('messagesModel/reactionsModel/updateWithId', this.updateWithIdRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/reactionsModel/set', this.setRouter)
				.off('messagesModel/reactionsModel/add', this.addRouter)
				.off('messagesModel/reactionsModel/updateWithId', this.updateWithIdRouter)
			;
		}

		/**
		 * @param {MutationPayload<ReactionsAddData, ReactionsAddActions>} mutation.payload
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
				'setReaction',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			const reaction = data.reaction;
			if (!Type.isPlainObject(reaction))
			{
				return;
			}

			const modelMessage = this.store.getters['messagesModel/getById'](reaction.messageId);
			const dialogHelper = DialogHelper.createByChatId(modelMessage.chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			const modelReaction = this.store.getters['messagesModel/reactionsModel/getByMessageId'](reaction.messageId);

			this.repository.reaction.saveFromModel([modelReaction])
				.catch((error) => logger.error('ReactionWriter.updateWithIdRouter.saveFromModel.catch:', error))
			;
		}

		/**
		 * @param {MutationPayload<ReactionsSetData, ReactionsSetActions>} mutation.payload
		 */
		setRouter(mutation)
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
				const modelMessage = this.store.getters['messagesModel/getById'](reaction.messageId);
				const dialogHelper = DialogHelper.createByChatId(modelMessage.chatId);
				if (!dialogHelper?.isLocalStorageSupported)
				{
					return;
				}

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
				.catch((error) => logger.error('ReactionWriter.addRouter.saveFromModel.catch:', error))
			;
		}

		/**
		 * @param {MutationPayload<ReactionsUpdateWithIdData, ReactionsUpdateWithIdActions>} mutation.payload
		 */
		updateWithIdRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'setReaction',
				'removeReaction',
			];

			if (!saveActions.includes(actionName))
			{
				return;
			}

			const reaction = data.reaction;
			if (!Type.isPlainObject(reaction))
			{
				return;
			}

			const modelMessage = this.store.getters['messagesModel/getById'](reaction.messageId);
			const dialogHelper = DialogHelper.createByChatId(modelMessage.chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			const modelReaction = this.store.getters['messagesModel/reactionsModel/getByMessageId'](reaction.messageId);

			this.repository.reaction.saveFromModel([modelReaction])
				.catch((error) => logger.error('ReactionWriter.updateWithIdRouter.saveFromModel.catch:', error))
			;
		}
	}

	module.exports = {
		ReactionWriter,
	};
});
