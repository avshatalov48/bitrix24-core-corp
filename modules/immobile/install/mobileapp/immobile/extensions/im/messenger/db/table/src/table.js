/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/table/table
 */
jn.define('im/messenger/db/table/table', (require, exports, module) => {
	include('sqlite');

	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
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
		set: 'set',
	});

	const FieldDefaultValue = Object.freeze({
		zeroInteger: 0,
		emptyText: '',
		noneText: 'none',
		emptyDate: '',
		falseBoolean: '0',
		trueBoolean: '1',
		null: null,
		emptyObject: {}, // these types are a literal format because it will be a JSON.stringify()
		emptyArray: [],
		emptyMap: {},
		emptySet: {},
	});

	/**
	 * @abstract
	 * @implements ITable
	 * @template TStoredItem
	 * @implements {ITable<TStoredItem>}
	 */
	class Table
	{
		constructor()
		{
			this.fieldsCollection = this.getFieldsCollection();

			this.saveHandlerCollection = {
				[FieldType.date]: this.saveDateFieldHandler.bind(this),
				[FieldType.boolean]: this.saveBooleanFieldHandler.bind(this),
				[FieldType.json]: this.saveJSONFieldHandler.bind(this),
				[FieldType.map]: this.saveMapFieldHandler.bind(this),
				[FieldType.set]: this.saveSetFieldHandler.bind(this),
				[FieldType.text]: this.saveTextFieldHandler.bind(this),
			};

			this.restoreHandlerCollection = {
				[FieldType.date]: this.restoreDateFieldHandler.bind(this),
				[FieldType.boolean]: this.restoreBooleanFieldHandler.bind(this),
				[FieldType.json]: this.restoreJSONFieldHandler.bind(this),
				[FieldType.map]: this.restoreMapFieldHandler.bind(this),
				[FieldType.set]: this.restoreSetFieldHandler.bind(this),
				[FieldType.text]: this.restoreTextFieldHandler.bind(this),
			};

			if (this.isSupported)
			{
				this.table = new DatabaseTable(this.getName(), this.getFields());
			}

			this.getPrimaryKey();
		}

		/**
		 * @abstract
		 * @desc Method must be return primary key. Composite primary key not supported!
		 * @return {string}
		 */
		getPrimaryKey()
		{
			throw new Error(`${this.constructor.name}: method getPrimaryKey must be override`);
		}

		saveDateFieldHandler(key, value)
		{
			try
			{
				return DateHelper.cast(value).toISOString();
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return null;
			}
		}

		restoreDateFieldHandler(key, value)
		{
			try
			{
				return DateHelper.cast(value, null);
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return null;
			}
		}

		saveBooleanFieldHandler(key, value)
		{
			if (value === true)
			{
				return '1';
			}

			if (value === false)
			{
				return '0';
			}

			return '';
		}

		restoreBooleanFieldHandler(key, value)
		{
			return value === '1';
		}

		saveJSONFieldHandler(key, value)
		{
			try
			{
				return JSON.stringify(value);
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return null;
			}
		}

		restoreJSONFieldHandler(key, value)
		{
			try
			{
				return JSON.parse(value);
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return null;
			}
		}

		saveMapFieldHandler(key, value)
		{
			if (value instanceof Map)
			{
				return JSON.stringify(Object.fromEntries(value));
			}

			return JSON.stringify(value);
		}

		restoreMapFieldHandler(key, value)
		{
			try
			{
				const obj = JSON.parse(value);

				return new Map(Object.entries(obj));
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return null;
			}
		}

		saveSetFieldHandler(key, value)
		{
			if (value instanceof Set)
			{
				return JSON.stringify([...value]);
			}

			return JSON.stringify(value);
		}

		restoreSetFieldHandler(key, value)
		{
			try
			{
				const obj = JSON.parse(value);

				return new Set(Object.values(obj));
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return null;
			}
		}

		saveTextFieldHandler(key, value)
		{
			return value;
		}

		restoreTextFieldHandler(key, value)
		{
			return value;
		}

		get isSupported()
		{
			return Feature.isLocalStorageSupported;
		}

		get readOnly()
		{
			return Feature.isLocalStorageReadOnlyModeEnable;
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

		getDefaultValueByFieldName(fieldName)
		{
			const field = this.fieldsCollection[fieldName];

			return field.defaultValue;
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

		/**
		 * @param {Array<TStoredItem>} items
		 * @param {boolean} replace
		 * @param {boolean} ignoreErrors
		 * @return {Promise<{lastInsertId: number, columns: *[], changes: number, rows: *[], errors: Error[]} | void>}
		 */
		add(items, replace = true, ignoreErrors = false)
		{
			if (!this.isSupported || this.readOnly || !Feature.isLocalStorageEnabled)
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
					if (ignoreErrors)
					{
						logger.warn(`Table.add error: ${this.getName()}`, error, items, replace);
					}
					else
					{
						logger.error(`Table.add error: ${this.getName()}`, error, items, replace);
					}
				})
			;
		}

		async addIfNotExist(items)
		{
			try
			{
				await this.add(items, false, true);
			}
			catch
			{ /* empty */ }
		}

		/**
		 *
		 * @param {TableGetListOptions<TStoredItem>} options
		 * @return {Promise<{items: Array<TStoredItem>}>}
		 */
		async getList(options)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({
					items: [],
				});
			}

			const result = await this.table.getList(options);

			result.items = result.items.map((row) => this.restoreDatabaseRow(row));

			return result;
		}

		/**
		 * @param {string | number} id
		 * @return {Promise<TStoredItem | null>}
		 */
		async getById(id)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return null;
			}

			const result = await this.getList({
				filter: {
					[this.getPrimaryKey()]: id,
				},
				limit: 1,
			});

			if (Type.isArrayFilled(result.items))
			{
				return result.items[0];
			}

			return null;
		}

		/**
		 * @param {Array<string | number>} idList
		 * @param shouldRestoreRows
		 * @return {Promise<{items: Array<TStoredItem>}>}
		 */
		async getListByIds(idList, shouldRestoreRows = true)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled || !Type.isArrayFilled(idList))
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
					WHERE ${this.getPrimaryKey()} IN (${idsFormatted})
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
			if (
				!this.isSupported
				|| this.readOnly
				|| !Feature.isLocalStorageEnabled
				|| !Type.isArrayFilled(idList)
			)
			{
				return Promise.resolve({});
			}

			const idsFormatted = Type.isNumber(idList[0]) ? idList.toString() : idList.map((id) => `"${id}"`);
			const result = await this.executeSql({
				query: `
					DELETE
					FROM ${this.getName()}
					WHERE ${this.getPrimaryKey()} IN (${idsFormatted})
				`,
			});

			logger.log(`${this.constructor.name}.deleteByIdList complete: ${this.getName()}`, idList);

			return result;
		}

		update(options)
		{
			if (!this.isSupported || this.readOnly || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.update(options);
		}

		delete(filter)
		{
			if (!this.isSupported || this.readOnly || !Feature.isLocalStorageEnabled)
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
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.create();
		}

		async truncate()
		{
			if (this.isSupported)
			{
				return this.executeSql({
					query: `DELETE FROM ${this.getName()}`,
				});
			}

			logger.log(`Table.truncate complete: ${this.getName()}`);

			return Promise.resolve();
		}

		drop()
		{
			if (this.isSupported)
			{
				return this.table.drop();
			}

			return Promise.resolve();
		}

		executeSql({ query, values })
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({});
			}

			return this.table.executeSql({ query, values });
		}

		validate(item)
		{
			const fieldsCollection = this.getFieldsCollection();

			const row = {};

			const itemFieldsCollection = {};
			Object.keys(item).forEach((fieldName) => {
				itemFieldsCollection[fieldName] = true;
			});

			Object.keys(fieldsCollection).forEach((fieldName) => {
				const defaultValue = this.getDefaultValueByFieldName(fieldName);
				if (!itemFieldsCollection[fieldName] && Type.isUndefined(defaultValue))
				{
					return;
				}

				let fieldValue = item[fieldName];
				if (Type.isUndefined(fieldValue))
				{
					fieldValue = defaultValue;
				}

				const fieldType = fieldsCollection[fieldName].type;
				const saveHandler = this.getSaveHandlerByFieldType(fieldType);
				if (Type.isFunction(saveHandler))
				{
					fieldValue = saveHandler(fieldName, fieldValue);
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
					fieldValue = restoreHandler(fieldName, fieldValue);
				}

				restoredRow[fieldName] = fieldValue;
			});

			return restoredRow;
		}

		convertSelectResultToGetListResult(selectResult, shouldRestoreRows)
		{
			const {
				columns,
				rows,
			} = selectResult;

			const getListResult = {
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
					getListResult.items.push(this.restoreDatabaseRow(listRow));
				}
				else
				{
					getListResult.items.push(listRow);
				}
			});

			return getListResult;
		}
	}

	module.exports = {
		Table,
		FieldType,
		FieldDefaultValue,
	};
});
