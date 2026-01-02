/**
 * @module im/messenger/db/table/transcript
 */
jn.define('im/messenger/db/table/transcript', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class TranscriptTable extends Table
	{
		getName()
		{
			return 'b_im_transcript';
		}

		getPrimaryKey()
		{
			return 'fileId';
		}

		getFields()
		{
			return [
				{ name: 'fileId', type: FieldType.integer, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer },
				{ name: 'messageId', type: FieldType.integer, unique: true, index: true },
				{ name: 'text', type: FieldType.text },
				{ name: 'status', type: FieldType.text },
			];
		}
	}

	module.exports = {
		TranscriptTable,
	};
});
