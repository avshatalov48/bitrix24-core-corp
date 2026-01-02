/**
 * @module im/messenger/db/model-writer/vuex/vote
 */
jn.define('im/messenger/db/model-writer/vuex/vote', (require, exports, module) => {
	const { Type } = require('type');
	const { getLogger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	const logger = getLogger('repository--vote');

	class VoteWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();

			this.setRouter = this.setRouter.bind(this);
			this.updateWithIdRouter = this.updateWithIdRouter.bind(this);
		}

		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/voteModel/set', this.setRouter)
				.on('messagesModel/voteModel/updateWithId', this.updateWithIdRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/voteModel/set', this.setRouter)
				.off('messagesModel/voteModel/updateWithId', this.updateWithIdRouter)
			;
		}

		/**
		 * @param {MutationPayload<VoteSetData, VoteSetActions>} mutation.payload
		 */
		setRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const data = mutation?.payload?.data || {};
			const actionName = mutation?.payload?.actionName;
			const saveActions = ['setFromResponse', 'setFromPullEvent'];

			if (
				!saveActions.includes(actionName)
				|| !Type.isArrayFilled(data.voteList)
			)
			{
				return;
			}

			const voteList = [];

			data.voteList.forEach((vote) => {
				const dialogHelper = DialogHelper.createByChatId(vote.chatId);
				if (!dialogHelper?.isLocalStorageSupported)
				{
					return;
				}

				const voteModel = this.store.getters['messagesModel/voteModel/getByMessageId'](vote.messageId);
				if (voteModel)
				{
					voteList.push(voteModel);
				}
			});

			if (Type.isArrayFilled(voteList))
			{
				this.repository.vote.saveFromModel(voteList)
					.catch((error) => logger.error('VoteWriter.setRouter.saveFromModel.catch:', error))
				;
			}
		}

		/**
		 * @param {MutationPayload<VoteUpdateWithIdData, VoteUpdateWithIdActions>} mutation.payload
		 */
		updateWithIdRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};

			if (
				actionName !== 'updateWithId'
				|| !Type.isPlainObject(data.vote)
			)
			{
				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](data.id);
			const dialogHelper = DialogHelper.createByChatId(messageModel.chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			const voteModel = this.store.getters['messagesModel/voteModel/getByMessageId'](data.vote.messageId);
			if (voteModel)
			{
				this.repository.vote
					.saveFromModel([voteModel])
					.catch((error) => logger.error('VoteWriter.updateWithIdRouter.saveFromModel.catch:', error))
				;
			}
		}
	}

	module.exports = { VoteWriter };
});
