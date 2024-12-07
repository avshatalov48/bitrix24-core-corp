/**
 * @module im/messenger/db/table/queue
 */
jn.define('im/messenger/db/table/queue', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class QueueTable extends Table
	{
		getName()
		{
			return 'b_im_queue';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.text, unique: true, index: true },
				{ name: 'requestName', type: FieldType.text },
				{ name: 'requestData', type: FieldType.json },
				{ name: 'priority', type: FieldType.integer, index: true },
				{ name: 'messageId', type: FieldType.integer },
			];
		}
	}

	module.exports = {
		QueueTable,
	};
});
