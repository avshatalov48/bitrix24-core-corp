/**
 * @module im/messenger/db/table/counter
 */
jn.define('im/messenger/db/table/counter', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	/**
	 * @CounterTable
	 */
	class CounterTable extends Table
	{
		getName()
		{
			return 'b_im_counter';
		}

		getFields()
		{
			return [
				{ name: 'chatId', type: FieldType.integer, unique: true, index: true },
				{ name: 'parentChatId', type: FieldType.integer, index: true },
				{ name: 'type', type: FieldType.text },
				{ name: 'counter', type: FieldType.integer },
			];
		}

		getPrimaryKey()
		{
			return 'chatId';
		}
	}

	module.exports = {
		CounterTable,
	};
});
