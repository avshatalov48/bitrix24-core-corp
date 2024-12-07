/**
 * @module im/messenger/db/table/copilot
 */
jn.define('im/messenger/db/table/copilot', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class CopilotTable extends Table
	{
		getName()
		{
			return 'b_im_copilot';
		}

		getFields()
		{
			return [
				{ name: 'dialogId', type: FieldType.text, unique: true, index: true },
				{ name: 'aiProvider', type: FieldType.text },
				{ name: 'roles', type: FieldType.json },
				{ name: 'chats', type: FieldType.json },
				{ name: 'messages', type: FieldType.json },
			];
		}

		getPrimaryKey()
		{
			return 'dialogId';
		}
	}

	module.exports = {
		CopilotTable,
	};
});