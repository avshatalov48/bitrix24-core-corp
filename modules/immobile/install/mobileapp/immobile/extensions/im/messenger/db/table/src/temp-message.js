/**
 * @module im/messenger/db/table/temp-message
 */
jn.define('im/messenger/db/table/temp-message', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');
	const { DialogTable } = require('im/messenger/db/table/dialog');
	const { UserTable } = require('im/messenger/db/table/user');

	class TempMessageTable extends Table
	{
		getName()
		{
			return 'b_im_temp_message';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, unique: true, index: true },
				{ name: 'templateId', type: FieldType.text, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'authorId', type: FieldType.integer },
				{ name: 'date', type: FieldType.date },
				{ name: 'text', type: FieldType.text },
				{ name: 'params', type: FieldType.json },
				{ name: 'files', type: FieldType.json },
				{ name: 'unread', type: FieldType.boolean },
				{ name: 'viewed', type: FieldType.boolean },
				{ name: 'viewedByOthers', type: FieldType.boolean },
				{ name: 'sending', type: FieldType.boolean },
				{ name: 'error', type: FieldType.boolean },
				{ name: 'retry', type: FieldType.boolean },
				{ name: 'attach', type: FieldType.json },
				{ name: 'richLinkId', type: FieldType.integer },
			];
		}
	}

	module.exports = {
		TempMessageTable,
	};
});
