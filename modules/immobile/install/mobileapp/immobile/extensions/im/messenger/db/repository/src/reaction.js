/**
 * @module im/messenger/db/repository/reaction
 */
jn.define('im/messenger/db/repository/reaction', (require, exports, module) => {
	const { Type } = require('type');

	const {
		ReactionTable,
	} = require('im/messenger/db/table');

	/**
	 * @class ReactionRepository
	 */
	class ReactionRepository
	{
		constructor()
		{
			this.reactionTable = new ReactionTable();
		}

		/**
		 * @param {number} chatId
		 */
		async deleteByChatId(chatId)
		{
			return this.reactionTable.deleteByChatId(chatId);
		}

		async saveFromModel(reactionList)
		{
			const reactionListToAdd = [];

			reactionList.forEach((reaction) => {
				// eslint-disable-next-line no-param-reassign
				const reactionToAdd = this.reactionTable.validate(reaction);

				reactionListToAdd.push(reactionToAdd);
			});

			return this.reactionTable.add(reactionListToAdd, true);
		}

		async saveFromRest(reactionList)
		{
			const reactionListToAdd = [];

			reactionList.forEach((reaction) => {
				const reactionToAdd = this.validateRestReaction(reaction);

				reactionListToAdd.push(reactionToAdd);
			});

			return this.reactionTable.add(reactionListToAdd, true);
		}

		async getListByMessageIds(messageIdList)
		{
			return this.reactionTable.getListByMessageIds(messageIdList);
		}

		validateRestReaction(reaction)
		{
			let result = {};

			if (Type.isNumber(reaction.messageId))
			{
				result.messageId = reaction.messageId;
			}

			if (Type.isArrayFilled(reaction.ownReactions))
			{
				result.ownReactions = reaction.ownReactions;
			}

			if (Type.isObjectLike(reaction.reactionCounters))
			{
				result.reactionCounters = reaction.reactionCounters;
			}

			if (Type.isObjectLike(reaction.reactionUsers))
			{
				result.reactionUsers = reaction.reactionUsers;
			}

			result = this.reactionTable.validate(result);

			return result;
		}
	}

	module.exports = {
		ReactionRepository,
	};
});
