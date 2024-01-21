/**
 * @module im/messenger/db/update/updater
 */
jn.define('im/messenger/db/update/updater', (require, exports, module) => {
	const { Type } = require('type');
	const { OptionTable } = require('im/messenger/db/table');

	/**
	 * @class Updater
	 */
	class Updater
	{
		constructor()
		{
			this.connection = new OptionTable();
		}

		/**
		 * @param options
		 * @param {String} options.query
		 * @param {Array} options.values
		 * @return {Promise<*>}
		 */
		async executeSql(options)
		{
			return this.connection.executeSql(options);
		}

		async isTableExists(tableName)
		{
			const result = await this.executeSql({
				query: "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
				values: [tableName],
			});

			if (
				result
				&& Type.isArrayFilled(result.rows)
				&& Type.isArrayFilled(result.rows[0])
				&& result.rows[0][0] === tableName
			)
			{
				return true;
			}

			return false;
		}

		async isColumnExists(tableName, columnName)
		{
			const result = await this.executeSql({
				query: `pragma table_info(${tableName})`,
			});

			if (
				result
				&& Type.isArrayFilled(result.rows)
			)
			{
				const columnIndex = result.rows.findIndex((row) => row[1] === columnName);

				return columnIndex !== -1;
			}

			return false;
		}
	}

	module.exports = {
		Updater,
	};
});
