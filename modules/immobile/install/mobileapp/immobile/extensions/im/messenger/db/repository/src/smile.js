/**
 * @module im/messenger/db/repository/smile
 */
jn.define('im/messenger/db/repository/smile', (require, exports, module) => {
	const { SmileTable } = require('im/messenger/db/table');
	const { Type } = require('type');

	/**
	 * @class SmileRepository
	 */
	class SmileRepository
	{
		constructor()
		{
			this.smileTable = new SmileTable();
		}

		/**
		 *
		 * @param {Array<SmileRow>} smileList
		 */
		async save(smileList)
		{
			const preparedSmiles = smileList
				.map((smile) => this.validate(smile))
				.map((smile) => this.smileTable.validate(smile))
			;

			return this.smileTable.add(preparedSmiles, true);
		}

		/**
		 *
		 * @return {Promise<Array<SmileRow>>}
		 */
		async getSmiles()
		{
			const { items } = await this.smileTable.getList({});

			return items;
		}

		async clear()
		{
			return this.smileTable.delete({});
		}

		/**
		 *
		 * @param {Partial<SmileRow>} fields
		 */
		validate(fields)
		{
			/**
			 * @type {Partial<SmileRow>}
			 */
			const result = {};

			if (Type.isNumber(fields.id))
			{
				result.id = fields.id;
			}

			if (Type.isNumber(fields.setId))
			{
				result.setId = fields.setId;
			}

			if (Type.isNumber(fields.width))
			{
				result.width = fields.width;
			}

			if (Type.isNumber(fields.height))
			{
				result.height = fields.height;
			}

			if (Type.isString(fields.imageUrl))
			{
				result.imageUrl = fields.imageUrl;
			}

			if (Type.isString(fields.typing))
			{
				result.typing = fields.typing;
			}

			if (Type.isString(fields.name))
			{
				result.name = fields.name;
			}

			return result;
		}
	}

	module.exports = { SmileRepository };
});
