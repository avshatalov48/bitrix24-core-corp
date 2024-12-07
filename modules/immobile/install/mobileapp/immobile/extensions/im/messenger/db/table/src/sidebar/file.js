/**
 * @module im/messenger/db/table/sidebar/file
 */
jn.define('im/messenger/db/table/sidebar/file', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class SidebarFileTable extends Table
	{
		getName()
		{
			return 'b_im_sidebar_file';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, unique: true },
				{ name: 'messageId', type: FieldType.integer },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'authorId', type: FieldType.integer },
				{ name: 'dateCreate', type: FieldType.date },
				{ name: 'fileId', type: FieldType.integer },
				{ name: 'subType', type: FieldType.text },
			];
		}
	}

	module.exports = {
		SidebarFileTable,
	};
});
