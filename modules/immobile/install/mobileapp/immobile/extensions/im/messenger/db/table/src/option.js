/**
 * @module im/messenger/db/table/option
 */
jn.define('im/messenger/db/table/option', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');
	class OptionTable extends Table
	{
		getName()
		{
			return 'b_im_option';
		}

		getPrimaryKey()
		{
			return 'name';
		}

		getFields()
		{
			return [
				{ name: 'name', type: FieldType.text, unique: true, index: true },
				{ name: 'value', type: FieldType.text },
			];
		}
	}

	module.exports = {
		OptionTable,
	};
});
