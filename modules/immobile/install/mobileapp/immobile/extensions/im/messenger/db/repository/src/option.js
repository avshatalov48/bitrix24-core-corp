/**
 * @module im/messenger/db/repository/option
 */
jn.define('im/messenger/db/repository/option', (require, exports, module) => {
	const { Type } = require('type');

	const {
		OptionTable,
	} = require('im/messenger/db/table');

	/**
	 * @class OptionRepository
	 */
	class OptionRepository
	{
		constructor()
		{
			this.optionTable = new OptionTable();
		}

		async set(name, value)
		{
			return this.optionTable.add([{ name, value }]);
		}

		async get(name, defaultValue = null)
		{
			const result = await this.optionTable.getList({
				filter: {
					name,
				},
				limit: 1,
			});

			if (Type.isArrayFilled(result.items))
			{
				return result.items[0].value;
			}

			return defaultValue;
		}

		async delete(name)
		{
			return this.optionTable.delete({ name });
		}
	}

	module.exports = {
		OptionRepository,
	};
});
