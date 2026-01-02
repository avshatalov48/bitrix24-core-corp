/**
 * @module im/messenger/db/repository/vote
 */
jn.define('im/messenger/db/repository/vote', (require, exports, module) => {
	const { VoteTable } = require('im/messenger/db/table');

	/**
	 * @class VoteRepository
	 */
	class VoteRepository
	{
		constructor()
		{
			this.voteTable = new VoteTable();
		}

		/**
		 * @param {number} chatId
		 */
		async deleteByChatId(chatId)
		{
			return this.voteTable.deleteByChatId(chatId);
		}

		async saveFromModel(voteList)
		{
			return this.voteTable.add(voteList.map((vote) => this.voteTable.validate(vote)), true);
		}
	}

	module.exports = { VoteRepository };
});
