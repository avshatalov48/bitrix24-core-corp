/**
 * @module im/messenger/db/table/reaction
 */
jn.define('im/messenger/db/table/reaction', (require, exports, module) => {
	const { Type } = require('type');

	const { Settings } = require('im/messenger/lib/settings');
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

		getFields()
		{
			return [
				{ name: 'messageId', type: FieldType.integer, unique: true, index: true },
				{ name: 'ownReactions', type: FieldType.json },
				{ name: 'reactionCounters', type: FieldType.json },
				{ name: 'reactionUsers', type: FieldType.map },
			];
		}

		async getListByMessageIds(idList)
		{
			if (!Settings.isLocalStorageEnabled || !Type.isArrayFilled(idList))
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
	}

	module.exports = {
		ReactionTable,
	};
});
