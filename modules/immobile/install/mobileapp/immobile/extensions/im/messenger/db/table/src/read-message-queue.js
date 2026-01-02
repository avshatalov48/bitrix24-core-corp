/**
 * @module im/messenger/db/table/read-message-queue
 */
jn.define('im/messenger/db/table/read-message-queue', (require, exports, module) => {
	const { Type } = require('type');
	const { Feature } = require('im/messenger/lib/feature');

	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	/**
	 *
	 * @extends {Table<ReadMessageQueueStoredData>}
	 */
	class ReadMessageQueueTable extends Table
	{
		getName()
		{
			return 'b_im_read_message_queue';
		}

		getPrimaryKey()
		{
			return 'messageId';
		}

		/**
		 * @return {Array<TableField>}
		 */
		getFields()
		{
			return [
				{ name: 'messageId', type: FieldType.integer, index: true, unique: true },
				{ name: 'chatId', type: FieldType.integer, index: true },
			];
		}

		/**
		 * @param {Array<number>} chatIdList
		 * @return {Promise<Awaited<{}>>}
		 */
		async deleteByChatIdList(chatIdList)
		{
			if (!Feature.isLocalStorageEnabled || this.readOnly || !Type.isArrayFilled(chatIdList))
			{
				return Promise.resolve({});
			}

			const conditionColumn = 'chatId';
			const idsFormatted = this.createWhereInCondition(conditionColumn, chatIdList);

			const query = `
				DELETE FROM ${this.getName()}
				WHERE ${conditionColumn} IN (${idsFormatted});
			`;

			return this.executeSql({ query });
		}
	}

	module.exports = { ReadMessageQueueTable };
});
