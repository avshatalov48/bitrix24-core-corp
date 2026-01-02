/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/table/table
 */
jn.define('im/messenger/db/table/table', (require, exports, module) => {
	/* globals DatabaseTable, include */
	include('sqlite');

	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { getLogger } = require('im/messenger/lib/logger');

	const { FieldType, FieldDefaultValue } = require('im/messenger/db/const');

	const logger = getLogger('database-table--table');

	/**
	 * @abstract
	 * @template TStoredItem
	 * @implements {ITable<TStoredItem>}
	 */
	class Table
	{
		/**
		 * @protected
		 * @type {IDatabaseTableInstance}
		 */
		static databaseTable;

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
				[FieldType.object]: this.saveObjectFieldHandler.bind(this),
				[FieldType.array]: this.saveArrayFieldHandler.bind(this),
			};

			this.restoreHandlerCollection = {
				[FieldType.date]: this.restoreDateFieldHandler.bind(this),
				[FieldType.boolean]: this.restoreBooleanFieldHandler.bind(this),
				[FieldType.json]: this.restoreJSONFieldHandler.bind(this),
				[FieldType.map]: this.restoreMapFieldHandler.bind(this),
				[FieldType.set]: this.restoreSetFieldHandler.bind(this),
				[FieldType.text]: this.restoreTextFieldHandler.bind(this),
				[FieldType.object]: this.restoreObjectFieldHandler.bind(this),
				[FieldType.array]: this.restoreArrayFieldHandler.bind(this),
			};

			this.getPrimaryKey();
		}

		/**
		* @return {IDatabaseTableInstance|null}
		*/
		get table()
		{
			if (this.constructor.databaseTable)
			{
				return this.constructor.databaseTable;
			}

			if (this.isSupported)
			{
				this.createDatabaseTableInstance();

				return this.constructor.databaseTable;
			}

			return null;
		}

		/**
		 * @void
		 */
		createDatabaseTableInstance()
		{
			this.constructor.databaseTable = new DatabaseTable(this.getName(), this.getFields());
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

		/**
		 * @deprecated FieldType.json. Use FieldType.object or FieldType.array instead
		 */
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

		/**
		 * @deprecated FieldType.json. Use FieldType.object or FieldType.array instead
		 */
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

		// eslint-disable-next-line sonarjs/no-identical-functions
		saveObjectFieldHandler(key, value)
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

		// eslint-disable-next-line sonarjs/no-identical-functions
		saveArrayFieldHandler(key, value)
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

		restoreObjectFieldHandler(key, value)
		{
			try
			{
				return JSON.parse(value);
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return FieldDefaultValue.emptyObject;
			}
		}

		restoreArrayFieldHandler(key, value)
		{
			try
			{
				return JSON.parse(value);
			}
			catch (error)
			{
				logger.error(`Table.restoreDatabaseRow error in ${this.getName()}:`, key, value, error);

				return FieldDefaultValue.emptyArray;
			}
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
		 * @return {string}
		 */
		static getTableName()
		{
			throw new Error('Table: getTableName() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getName()
		{
			throw new Error('Table: getName() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {Array<TableField>}
		 */
		getFields()
		{
			throw new Error('Table: getFields() must be override in subclass.');
		}

		/**
		 * @returns { FieldsCollection<ReturnType<this['getFields']>>}
		 */
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
				this.checkDeprecatedFields(fields);
			}

			return this.fieldsCollection;
		}

		/**
		 * @desc check and reminder about deprecated columns
		 * @param {Array<object>} fields
		 */
		checkDeprecatedFields(fields) {
			for (const field of fields)
			{
				if (field.deprecated)
				{
					logger.warn(`Deprecated field used: ${field.name}`, {
						table: this.getName(),
						replacement: field.replacement || 'none specified',
					});
				}
			}
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
				logger.log(`Table.add method in ${this.getName()} is not available`);

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

		/**
		 * @param {Array<TStoredItem>} items
		 * @return {Promise<void>}
		 */
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
		 * @param id
		 * @param {(item: TStoredItem | null) => TStoredItem} preparedItemCallback
		 * @return {Promise<void>}
		 */
		async merge(id, preparedItemCallback)
		{
			const storedItem = await this.getById(id);

			const preparedItem = preparedItemCallback(storedItem);

			// TODO remove after insert method validate into add and update
			const validatedStoredItem = this.validate(preparedItem);

			return this.add([validatedStoredItem]);
		}

		/**
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
			const idsFormatted = this.createWhereInCondition(this.getPrimaryKey(), idList);
			const result = await this.executeSql({
				query: `
					SELECT * 
					FROM ${this.getName()} 
					WHERE ${this.getPrimaryKey()} IN (${idsFormatted})
				`,
			});

			return this.prepareListResult(result, shouldRestoreRows);
		}

		/**
		 * @param {Array<TStoredItem>} result
		 * @param {Boolean} shouldRestoreRows
		 * @returns {{items: *[]}}
		 */
		prepareListResult(result, shouldRestoreRows = true)
		{
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

			const idsFormatted = this.createWhereInCondition(this.getPrimaryKey(), idList);
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

		/**
		 * @param {object} options
		 * @param {object} options.filter
		 * @param {{[colunmName: string]: any}} options.fields
		 * @return {Promise<Awaited<{}>>|*}
		 */
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

		/**
		 * @param selectResult
		 * @param shouldRestoreRows
		 * @return {{items: *[]}}
		 */
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

		/**
		 * @returns {Logger}
		 */
		get logger()
		{
			return logger;
		}

		/**
		 * @param {string} entityName
		 * @param {Array<any>} entityList
		 * @return {string}
		 */
		createWhereInCondition(entityName, entityList)
		{
			const columnInfo = this.getFieldsCollection()[entityName];
			if (Type.isNil(columnInfo))
			{
				throw new TypeError(`${this.constructor.name}.createWhereInCondition: unknown entityName: ${entityName}`);
			}

			if (!Type.isArrayFilled(entityList))
			{
				this.logger.error(`${this.constructor.name}.createWhereInCondition entityList not must be empty`, entityList);

				return '';
			}

			const saveHandler = Type.isFunction(this.saveHandlerCollection[columnInfo.type])
				? this.saveHandlerCollection[columnInfo.type]
				: (fieldName, fieldValue) => fieldValue
			;

			return entityList
				.map((entity) => `"${saveHandler(entityName, entity)}"`)
				.toString()
			;
		}
	}

	module.exports = {
		Table,
		FieldType,
		FieldDefaultValue,
	};
});
