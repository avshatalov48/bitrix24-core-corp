/**
 * @module im/messenger/db/table/user
 */
jn.define('im/messenger/db/table/user', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	class UserTable extends Table
	{
		getName()
		{
			return 'b_im_user';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, unique: true, index: true },
				{ name: 'name', type: FieldType.text },
				{ name: 'firstName', type: FieldType.text },
				{ name: 'lastName', type: FieldType.text },
				{ name: 'gender', type: FieldType.text },
				{ name: 'avatar', type: FieldType.text },
				{ name: 'color', type: FieldType.text },
				{ name: 'departments', type: FieldType.json },
				{ name: 'workPosition', type: FieldType.text },
				{ name: 'phones', type: FieldType.json },
				{ name: 'externalAuthId', type: FieldType.text },
				{ name: 'extranet', type: FieldType.boolean },
				{ name: 'network', type: FieldType.boolean },
				{ name: 'bot', type: FieldType.boolean },
				{ name: 'botData', type: FieldType.json },
				{ name: 'connector', type: FieldType.boolean },
				{ name: 'lastActivityDate', type: FieldType.date },
				{ name: 'mobileLastDate', type: FieldType.date },
				{ name: 'isCompleteInfo', type: FieldType.boolean },
			];
		}
	}

	module.exports = {
		UserTable,
	};
});
