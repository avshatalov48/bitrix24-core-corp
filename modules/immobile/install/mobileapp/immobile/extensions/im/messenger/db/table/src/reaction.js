/**
 * @module im/messenger/db/table/reaction
 */
jn.define('im/messenger/db/table/reaction', (require, exports, module) => {
	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class ReactionTable extends Table
	{
		getName()
		{
			return 'b_im_reaction';
		}

		getPrimaryKey()
		{
			return 'messageId';
		}

		getFields()
		{
			return [
				{ name: 'messageId', type: FieldType.integer, unique: true, index: true },
				{ name: 'ownReactions', type: FieldType.set },
				{ name: 'reactionCounters', type: FieldType.json },
				{ name: 'reactionUsers', type: FieldType.map },
			];
		}

		async getListByMessageIds(idList)
		{
			if (!Feature.isLocalStorageEnabled || !Type.isArrayFilled(idList))
			{
				return {
					items: [],
				};
			}
			const idsFormatted = Type.isNumber(idList[0]) ? idList.toString() : idList.map((id) => `"${id}"`);
			const result = await this.executeSql({
				query: `
					SELECT * 
					FROM ${this.getName()} 
					WHERE messageId IN (${idsFormatted})
				`,
			});

			const {
				columns,
				rows,
			} = result;

			const list = {
				items: [],
			};

			rows.forEach((row) => {
				const listRow = {};
				row.forEach((value, index) => {
					const key = columns[index];
					listRow[key] = value;
				});

				list.items.push(this.restoreDatabaseRow(listRow));
			});

			return list;
		}

		/**
		 * @param {number} chatId
		 * @return {Promise<Awaited<{}>>}
		 */
		async deleteByChatId(chatId)
		{
			if (!Feature.isLocalStorageEnabled || this.readOnly || !Type.isNumber(chatId))
			{
				return Promise.resolve({});
			}

			const query = `
				DELETE FROM b_im_reaction
				WHERE messageId IN (
					SELECT id FROM b_im_message
					WHERE chatId = ${chatId}
				);
			`;

			return this.executeSql({ query });
		}
	}

	module.exports = {
		ReactionTable,
	};
});
