/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/table/table
 */
jn.define('im/messenger/db/table/table', (require, exports, module) => {
	include('sqlite');

	const { Type } = require('type');

	const { Settings } = require('im/messenger/lib/settings');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('database-table--table');

	const FieldType = Object.freeze({
		integer: 'integer',
		text: 'text',
		date: 'date',
		boolean: 'boolean',
		json: 'json',
		map: 'map',
	});

	/**
	 * @abstract
	 * @implements ITable
	 */
	class Table
	{
		constructor()
		{
			this.fieldsCollection = this.getFieldsCollection();

			this.saveHandlerCollection = {
				[FieldType.date]: (value) => {
					return DateHelper.cast(value).toISOString();
				},
				[FieldType.boolean]: (value) => {
					if (value === true)
					{
						return '1';
					}

					if (value === false)
					{
						return '0';
					}

					return '';
				},
				[FieldType.json]: (value) => {
					return JSON.stringify(value);
				},
				[FieldType.map]: (value) => {
					if (value instanceof Map)
					{
						return JSON.stringify(Object.fromEntries(value));
					}

					return JSON.stringify(value);
				},
			};

			this.restoreHandlerCollection = {
				[FieldType.date]: (value) => {
					return DateHelper.cast(value, null);
				},
				[FieldType.boolean]: (value) => {
					return value === '1';
				},
				[FieldType.json]: (value) => {
					try
					{
						return JSON.parse(value);
					}
					catch (error)
					{
						logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, error);

						return null;
					}
				},
				[FieldType.map]: (value) => {
					try
					{
						const obj = JSON.parse(value);

						return new Map(Object.entries(obj));
					}
					catch (error)
					{
						logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, error);

						return null;
					}
				},
			};

			if (this.isSupported)
			{
				this.table = new DatabaseTable(this.getName(), this.getFields());
			}
		}

		get isSupported()
		{
			return Settings.isLocalStorageSupported;
		}

		/**
		 * @abstract
		 */
		getName()
		{
			throw new Error('Table: getName() must be override in subclass.');
		}

		/**
		 * @abstract
		 */
		getFields()
		{
			throw new Error('Table: getFields() must be override in subclass.');
		}

		getFieldsCollection()
		{
			if (!this.fieldsCollection)
			{
				const fieldsCollection = {};
				const fields = this.getFields();
				fields.forEach((field) => {
					fieldsCollection[field.name] = field;
				});

				this.fieldsCollection = fieldsCollection;
			}

			return this.fieldsCollection;
		}

		getRestoreHandlerByFieldType(fieldType)
		{
			return this.restoreHandlerCollection[fieldType];
		}

		getSaveHandlerByFieldType(fieldType)
		{
			return this.saveHandlerCollection[fieldType];
		}

		getMap()
		{
			return this.table.getMap();
		}

		add(items, replace = true)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return Promise.resolve();
			}

			if (!Type.isArrayFilled(items))
			{
				logger.log(`Table.add: ${this.getName()} nothing to add.`);

				return Promise.resolve({
					changes: 0,
					columns: [],
					rows: [],
					lastInsertId: -1,
					errors: [
						new Error('NOTHING_TO_ADD'),
					],
				});
			}

			return this.table.add(items, replace)
				.then(() => {
					logger.log(`Table.add complete: ${this.getName()}`, items, replace);
				})
				.catch((error) => {
					logger.error(`Table.add error: ${this.getName()}`, error, items, replace);
				})
			;
		}

		/**
		 *
		 * @param options
		 * @return {Promise<{items: Array}>}
		 */
		async getList(options)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return Promise.resolve({
					items: [],
				});
			}

			const result = await this.table.getList(options);

			result.items = result.items.map((row) => this.restoreDatabaseRow(row));

			return result;
		}

		async getById(id)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return null;
			}

			const result = await this.getList({
				filter: {
					id,
				},
				limit: 1,
			});

			if (Type.isArrayFilled(result.items))
			{
				return result.items[0];
			}

			return null;
		}

		async getListByIds(idList, shouldRestoreRows = true)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled || !Type.isArrayFilled(idList))
			{
				return {
					items: [],
				};
			}
			const idsFormatted = Type.isNumber(idList[0]) ? idList.toString() : idList.map((id) => `"${id}"`);
			const result = await this.executeSql({
				query: `
					SELECT * 
					FROM ${this.getName()} 
					WHERE id IN (${idsFormatted})
				`,
			});

			const {
				columns,
				rows,
			} = result;

			const list = {
				items: [],
			};

			rows.forEach((row) => {
				const listRow = {};
				row.forEach((value, index) => {
					const key = columns[index];
					listRow[key] = value;
				});

				if (shouldRestoreRows === true)
				{
					list.items.push(this.restoreDatabaseRow(listRow));
				}
				else
				{
					list.items.push(listRow);
				}
			});

			return list;
		}

		async deleteByIdList(idList)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled || !Type.isArrayFilled(idList))
			{
				return Promise.resolve({});
			}

			const idsFormatted = Type.isNumber(idList[0]) ? idList.toString() : idList.map((id) => `"${id}"`);
			const result = await this.executeSql({
				query: `
					DELETE
					FROM ${this.getName()}
					WHERE id IN (${idsFormatted})
				`,
			});

			logger.log(`Table.deleteByIdList complete: ${this.getName()}`, idList);

			return result;
		}

		update(options)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.update(options);
		}

		delete(filter)
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.delete(filter)
				.then(() => {
					logger.log(`Table.delete complete: ${this.getName()}`, filter);
				})
				.catch((error) => {
					logger.error(`Table.delete error: ${this.getName()}`, error, filter);
				})
			;
		}

		create()
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.create();
		}

		drop()
		{
			if (this.isSupported)
			{
				return this.table.drop();
			}

			return Promise.resolve();
		}

		prepareInsert(insert)
		{
			if (this.isSupported)
			{
				return this.table.drop();
			}

			return Promise.resolve();
		}

		executeSql({ query, values })
		{
			if (!this.isSupported || !Settings.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.executeSql({ query, values });
		}

		validate(item)
		{
			const fieldsCollection = this.getFieldsCollection();

			const row = {};
			Object.keys(item).forEach((fieldName) => {
				const doesTableHaveField = fieldsCollection[fieldName];
				if (!doesTableHaveField)
				{
					return;
				}

				let fieldValue = item[fieldName];

				const fieldType = fieldsCollection[fieldName].type;
				const saveHandler = this.getSaveHandlerByFieldType(fieldType);
				if (Type.isFunction(saveHandler))
				{
					fieldValue = saveHandler(fieldValue);
				}

				row[fieldName] = fieldValue;
			});

			return row;
		}

		restoreDatabaseRow(row)
		{
			const fieldsCollection = this.getFieldsCollection();

			const restoredRow = {};
			Object.keys(row).forEach((fieldName) => {
				let fieldValue = row[fieldName];

				const fieldType = fieldsCollection[fieldName]?.type;
				if (!fieldType)
				{
					logger.error(`Table.restoreDatabaseRow error in ${this.getName()}: "${fieldName}" is in the database but not in the table model`);
				}

				const restoreHandler = this.getRestoreHandlerByFieldType(fieldType);
				if (Type.isFunction(restoreHandler))
				{
					fieldValue = restoreHandler(fieldValue);
				}

				restoredRow[fieldName] = fieldValue;
			});

			return restoredRow;
		}
	}

	module.exports = {
		Table,
		FieldType,
	};
});
