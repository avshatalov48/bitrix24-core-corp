/**
 * @module im/messenger/db/table/vote
 */
jn.define('im/messenger/db/table/vote', (require, exports, module) => {
	const { Type } = require('type');
	const { Table, FieldType } = require('im/messenger/db/table/table');

	class VoteTable extends Table
	{
		getName()
		{
			return 'b_im_vote';
		}

		getPrimaryKey()
		{
			return 'messageId';
		}

		getFields()
		{
			return [
				{ name: 'messageId', type: FieldType.integer, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'voteId', type: FieldType.integer },
				{ name: 'isFinished', type: FieldType.boolean },
				{ name: 'isVoted', type: FieldType.boolean },
				{ name: 'votedAnswerIds', type: FieldType.set },
				{ name: 'votedCounter', type: FieldType.integer },
				{ name: 'questionVotedCounter', type: FieldType.map },
				{ name: 'answerVotedCounter', type: FieldType.map },
			];
		}

		/**
		 * @param {number} chatId
		 * @return {Promise<Awaited<{}>>}
		 */
		async deleteByChatId(chatId)
		{
			if (this.readOnly || !Type.isNumber(chatId))
			{
				return Promise.resolve({});
			}

			return this.executeSql({
				query: `
					DELETE FROM ${this.getName()}
					WHERE chatId = ${chatId};
				`,
			});
		}
	}

	module.exports = { VoteTable };
});
