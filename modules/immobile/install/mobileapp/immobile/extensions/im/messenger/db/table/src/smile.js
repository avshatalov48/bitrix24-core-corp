/**
 * @module im/messenger/db/table/smile
 */
jn.define('im/messenger/db/table/smile', (require, exports, module) => {
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');

	/**
	 * @class SmileTable
	 */
	class SmileTable extends Table
	{
		getName()
		{
			return 'b_im_smile';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.integer, unique: true, index: true },
				{ name: 'setId', type: FieldType.integer },
				{ name: 'width', type: FieldType.integer },
				{ name: 'height', type: FieldType.integer },
				{ name: 'imageUrl', type: FieldType.text },
				{ name: 'typing', type: FieldType.text },
				{ name: 'name', type: FieldType.text },
			];
		}
	}

	module.exports = { SmileTable };
});