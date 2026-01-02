/**
 * @module im/messenger/db/update/update-query-builder
 */
jn.define('im/messenger/db/update/update-query-builder', (require, exports, module) => {
	const { Type } = require('type');
	const {
		SqlFieldType,
	} = require('im/messenger/db/const');

	/**
	 * @class UpdaterQueryBuilder
	 */
	class UpdaterQueryBuilder
	{
		/**
		 * @param {{getName: () => string, getFields: () => Array<TableField>}} table
		 * @param columnName
		 * @return {string}
		 */
		addColumn(table, columnName)
		{
			const columnInfo = table.getFields().find((column) => {
				return column.name === columnName;
			});

			if (!columnInfo)
			{
				throw new Error(`${this.constructor.name}.addColumn columnInfo not found by columnName ${columnName} for ${table.getName()}`);
			}

			const sqlType = this.#getColumnType(columnInfo.type);
			if (Type.isNil(sqlType))
			{
				throw new Error(`${this.constructor.name}.addColumn unknown sqlType by fieldType ${columnInfo.type}`);
			}

			return `ALTER TABLE ${table.getName()} ADD COLUMN ${columnName} ${sqlType}`;
		}

		/**
		 * @param {string} fieldType
		 * @returns {string}
		 */
		#getColumnType(fieldType)
		{
			return SqlFieldType[fieldType];
		}
	}

	module.exports = {
		UpdaterQueryBuilder,
		updaterQueryBuilder: new UpdaterQueryBuilder(),
	};
});
