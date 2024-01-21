/**
 * @module im/messenger/db/table/recent
 */
jn.define('im/messenger/db/table/recent', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class RecentTable extends Table
	{
		getName()
		{
			return 'b_im_recent';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.text, unique: true },
				{ name: 'message', type: FieldType.json },
				{ name: 'dateMessage', type: FieldType.date },
				{ name: 'unread', type: FieldType.boolean },
				{ name: 'pinned', type: FieldType.boolean },
				{ name: 'invitation', type: FieldType.json },
				{ name: 'options', type: FieldType.json },
			];
		}
	}

	module.exports = {
		RecentTable,
	};
});
