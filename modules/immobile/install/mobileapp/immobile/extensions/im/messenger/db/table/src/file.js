/**
 * @module im/messenger/db/table/file
 */
jn.define('im/messenger/db/table/file', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class FileTable extends Table
	{
		getName()
		{
			return 'b_im_file';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'dialogId', type: FieldType.text },
				{ name: 'name', type: FieldType.text },
				{ name: 'date', type: FieldType.date },
				{ name: 'type', type: FieldType.text },
				{ name: 'extension', type: FieldType.text },
				{ name: 'size', type: FieldType.integer },
				{ name: 'image', type: FieldType.json },
				{ name: 'status', type: FieldType.text },
				{ name: 'progress', type: FieldType.text },
				{ name: 'authorId', type: FieldType.integer },
				{ name: 'authorName', type: FieldType.text },
				{ name: 'urlPreview', type: FieldType.text },
				{ name: 'urlShow', type: FieldType.text },
				{ name: 'urlDownload', type: FieldType.text },
			];
		}
	}

	module.exports = {
		FileTable,
	};
});
