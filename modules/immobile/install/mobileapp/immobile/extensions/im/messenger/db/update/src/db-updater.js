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
		#connection;

		constructor()
		{
			this.#connection = new OptionTable();
		}

		get transactionMode()
		{
			return {
				deferred: 'DEFERRED',
				immediate: 'IMMEDIATE',
				exclusive: 'EXCLUSIVE',
			}
		}

		/**
		 * @param options
		 * @param {String} options.query
		 * @param {Array} options.values
		 * @return {Promise<*>}
		 */
		async executeSql(options)
		{
			return this.#connection.executeSql(options);
		}

		async executeSqlPretty(options)
		{
			const result = await this.#connection.executeSql(options);

			const {
				columns,
				rows,
			} = result;

			const tableData = rows.map((row) => {
				const rowObject = {};
				row.forEach((cell, index) => {
					rowObject[columns[index]] = cell;
				});

				return rowObject;
			});

			// eslint-disable-next-line no-console
			console.table(tableData);
		}

		/**
		 * @param {string} tableName
		 * @return {Promise<*>}
		 */
		async getTableInfo(tableName)
		{
			return this.executeSql({
				query: `PRAGMA table_info(${tableName});`,
			});
		}

		/**
		 * @param tableName
		 * @return {Promise<void>}
		 */
		async printTableInfo(tableName)
		{
			const tableInfo = await this.getTableInfo(tableName);
			const {
				columns,
				rows,
			} = tableInfo;

			const tableData = rows.map((row) => {
				const rowObject = {};
				row.forEach((cell, index) => {
					rowObject[columns[index]] = cell;
				});

				return rowObject;
			});

			// eslint-disable-next-line no-console
			console.table(tableData);
		}

		/**
		 * @param {string} tableName
		 * @return {Promise<boolean>}
		 */
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

		/**
		 * @param {string} tableName
		 * @param {string} columnName
		 * @return {Promise<boolean>}
		 */
		async isColumnExists(tableName, columnName)
		{
			const result = await this.getTableInfo(tableName);

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

		/**
		 * @param {string} tableName
		 * @param {string} columnName
		 * @return {Promise<boolean>}
		 */
		async isIndexExists(tableName, columnName)
		{
			const indexName = await this.getIndexName(tableName, columnName);

			return Type.isStringFilled(indexName);
		}

		/**
		 * @param {string} tableName
		 * @param {string} columnName
		 * @return {Promise<*|null>}
		 */
		async getIndexName(tableName, columnName)
		{
			const result = await this.executeSql({
				query: `SELECT name FROM sqlite_master WHERE type = 'index' AND sql LIKE '%${tableName}%' and sql LIKE '%${columnName}%';`,
			});

			if (Type.isArrayFilled(result.rows) && Type.isStringFilled(result.rows[0][0]))
			{
				return result.rows[0][0];
			}

			return null;
		}

		/**
		 * @param {string} tableName
		 * @param {string} columnName
		 * @param {boolean} unique
		 * @return {Promise<*>}
		 */
		async createIndex(tableName, columnName, unique)
		{
			const indexType = (Type.isBoolean(unique) && unique === true) ? 'UNIQUE' : '';

			return this.executeSql({
				query: `CREATE ${indexType} INDEX IF NOT EXISTS ${columnName}_${tableName}_idx ON ${tableName}(${columnName})`,
			});
		}

		/**
		 * @param {string} tableName
		 * @param {string} columnName
		 * @return {Promise<boolean>}
		 */
		async dropIndex(tableName, columnName)
		{
			const indexName = await this.getIndexName(tableName, columnName);
			if (Type.isStringFilled(indexName))
			{
				await this.executeSql({
					query: `DROP INDEX ${indexName};`,
				});

				return true;
			}

			return false;
		}

		/**
		 * @param {string} tableName
		 * @return {Promise<*>}
		 */
		async truncateTable(tableName)
		{
			return this.executeSql({
				query: `DELETE FROM ${tableName}`,
			});
		}

		/**
		 * @param {string} tableName
		 * @return {Promise<*>}
		 */
		async dropTable(tableName)
		{
			return this.executeSql({
				query: `DROP TABLE ${tableName}`,
			});
		}

		async startTransaction(transactionMode = this.transactionMode.deferred)
		{
			if (!(Object.values(this.transactionMode).includes(transactionMode)))
			{
				throw new Error(`${this.constructor.name}.startTransaction: unknown transactionMode: ${transactionMode}`);
			}

			return this.executeSql({
				query: `BEGIN ${transactionMode} TRANSACTION`,
			});
		}

		async rollbackTransaction()
		{
			return this.executeSql({
				query: 'ROLLBACK TRANSACTION',
			});
		}

		async commitTransaction()
		{
			return this.executeSql({
				query: 'COMMIT TRANSACTION',
			});
		}
	}

	module.exports = {
		Updater,
	};
});
