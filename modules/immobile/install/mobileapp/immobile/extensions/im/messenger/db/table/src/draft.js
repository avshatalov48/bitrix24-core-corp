/**
 * @module im/messenger/db/table/src/draft
 */
jn.define('im/messenger/db/table/src/draft', (require, exports, module) => {
	const {
		Table,
		FieldType,
		FieldDefaultValue,
	} = require('im/messenger/db/table/table');

	class DraftTable extends Table
	{
		/**
		 * @returns {string}
		 */
		getName()
		{
			return 'b_im_draft';
		}

		/**
		 * @returns {Array<Object>}
		 */
		getFields()
		{
			return [
				{ name: 'dialogId', type: FieldType.text, unique: true, index: true },
				{ name: 'messageId', type: FieldType.text },
				{ name: 'lastActivityDate', type: FieldType.date },
				{ name: 'messageType', type: FieldType.text },
				{ name: 'type', type: FieldType.text },
				{ name: 'text', type: FieldType.text },
				{ name: 'userName', type: FieldType.text },
				{ name: 'message', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
				{ name: 'image', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
				{ name: 'video', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
			];
		}

		/**
		 * @returns {string}
		 */
		getPrimaryKey()
		{
			return 'dialogId';
		}

		/**
		 * @returns {Promise<{items: DraftStoredData[]}>}
		 */
		getDraftList()
		{
			return this.getList({
				fields: ['*'],
			}).catch((error) => {
				this.logger.error(`${this.constructor.name}.getDraftList.catch:`, error);
			});
		}
	}

	module.exports = {
		DraftTable,
	};
});
