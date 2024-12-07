/**
 * @module im/messenger/db/table/link-pin
 */
jn.define('im/messenger/db/table/link-pin', (require, exports, module) => {
	const { Type } = require('type');
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');
	const { Feature } = require('im/messenger/lib/feature');

	/**
	 * @class LinkPinTable
	 * @typedef {ITable<RawPin>} LinkPinTable
	 */
	class LinkPinTable extends Table
	{
		getName()
		{
			return 'b_im_link_pin';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, unique: true, index: true },
				{ name: 'messageId', type: FieldType.integer },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'authorId', type: FieldType.integer },
				{ name: 'dateCreate', type: FieldType.date },
			];
		}

		/**
		 * @param {Array<number>} messageIdList
		 */
		async deleteByMessageIdList(messageIdList)
		{
			if (!this.isSupported || this.readOnly || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve();
			}

			const idsFormatted = Type.isNumber(messageIdList[0])
				? messageIdList.toString()
				: messageIdList.map((id) => `"${id}"`)
			;

			return this.executeSql({
				query: `
					DELETE
					FROM ${this.getName()}
					WHERE messageId IN (${idsFormatted})
				`,
			});
		}
	}

	module.exports = { LinkPinTable };
});
