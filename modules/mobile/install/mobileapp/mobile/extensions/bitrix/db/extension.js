/**
 * Use deps 'db'
 * @module db
 */

/**
 * Class for Web SQL Database Bitrix Core DB
 * @param params
 * @constructor
 */

(function (window)
{
	var BX = window.BX;

	/**
	 * Parameters description:
	 * version - version of the database
	 * name - name of the database
	 * displayName - display name of the database
	 * capacity - size of the database in bytes.
	 * @param params
	 */
	BX.dataBase = function (params)
	{
        this.tableList = [];
        this.jsonFields = {};
        this.dbObject = window.sqlitePlugin.openDatabase(params);
        this.dbBandle = 'SQLitePlugin';
    };

	BX.dataBase.create = function (params)
	{
        return new BX.dataBase(params);
    };

	BX.dataBase.prototype.setJsonFields = function (tableName, fields)
	{
		if (typeof fields == 'string')
		{
			if (fields == '')
			{
				fields = [];
			}
			else
			{
				fields = [fields];
			}
		}

		if (tableName && BX.type.isArray(fields))
		{
			tableName = tableName.toString().toUpperCase();

			this.jsonFields[tableName] = [];
			if (fields.length > 0)
			{
				for (var i = 0; i < fields.length; i++)
				{
					this.jsonFields[tableName].push(
						fields[i].toString().toUpperCase()
					);
				}
			}
			else
			{
				delete this.jsonFields[tableName];
			}
		}

		return true;
	}

	BX.dataBase.prototype.isTableExists = function (tableName, callback)
	{
		tableName = tableName.toUpperCase();
		var promise = new BX.Promise();
		if (typeof callback != 'function')
		{
			callback = function(){};
		}

		var tableListCallback = function (tableList)
		{
			if (tableList.indexOf(tableName) > -1)
			{
				callback(true, tableName);
				promise.fulfill(tableName);
			}
			else
			{
				callback(false, tableName);
				promise.reject(tableName);
			}
		};

		if (this.tableList.length <= 0)
		{
			this.getTableList().then(tableListCallback);
		}
		else
		{
			tableListCallback(this.tableList);
		}

		return promise;
	};

	/**
	 * Takes the list of existing tables from the database
	 * @param callback The callback handler will be invoked with boolean parameter as a first argument
	 * @example
	 */
	BX.dataBase.prototype.getTableList = function (callback)
	{
		var promise = new BX.Promise();
		if (typeof callback != 'function')
		{
			callback = function(){};
		}

		var callbackFunc = callback;
		this.query({
			query: "SELECT tbl_name from sqlite_master WHERE type = 'table'",
			values: []
		}).then(function (success) {
			this.tableList = [];
			if (success.result.count > 0)
			{
				for (var i = 0; i < success.result.items.length; i++)
				{
					this.tableList[this.tableList.length] = success.result.items[i].tbl_name.toString().toUpperCase();
				}
			}
			callbackFunc(this.tableList);
			promise.fulfill(this.tableList);
		}.bind(this)).catch(function (error){
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Creates the table in the database
	 * @param params
	 */
	BX.dataBase.prototype.createTable = function (params)
	{
		var promise = new BX.Promise();

		params = params || {};
		if (typeof params.success != 'function')
		{
			params.success = function(result, transaction){};
		}
		if (typeof params.fail != 'function')
		{
			params.fail = function(result, transaction, query){};
		}

		params.action = 'create';

		this.query(
			this.getQuery(params)
		).then(function (success) {
			this.getTableList();
			success.result.tableName = params.tableName;
			params.success(success.result, success.transaction);
			promise.fulfill(success);
		}.bind(this)).catch(function (error){
			params.fail(error.result, error.transaction, error.query, params);
			error.queryParams = params;
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Drops the table from the database
	 * @param params
	 */
	BX.dataBase.prototype.dropTable = function (params)
	{
		var promise = new BX.Promise();

		params = params || {};
		if (typeof params.success != 'function')
		{
			params.success = function(result, transaction){};
		}
		if (typeof params.fail != 'function')
		{
			params.fail = function(result, transaction, query){};
		}

		params.action = "drop";

		this.query(
			this.getQuery(params)
		).then(function (success) {
			this.getTableList();
			success.result.tableName = params.tableName;
			params.success(success.result, success.transaction);
			promise.fulfill(success);
		}.bind(this)).catch(function (error){
			params.fail(error.result, error.transaction, error.query, params);
			error.queryParams = params;
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Add row to the database
	 * @param params
	 */
	BX.dataBase.prototype.addRow = function (params)
	{
		var promise = new BX.Promise();

		params = params || {};
		if (typeof params.success != 'function')
		{
			params.success = function(result, transaction){};
		}
		if (typeof params.fail != 'function')
		{
			params.fail = function(result, transaction, query){};
		}

		params.action = "insert";

		this.query(
			this.getQuery(params)
		).then(function (success) {
			params.success(success.result, success.transaction);
			success.result.tableName = params.tableName;
			promise.fulfill(success);
		}).catch(function (error){
			params.fail(error.result, error.transaction, error.query, params);
			error.queryParams = params;
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Add row to the database
	 * @param params
	 */
	BX.dataBase.prototype.replaceRow = function (params)
	{
		var promise = new BX.Promise();

		params = params || {};
		if (typeof params.success != 'function')
		{
			params.success = function(result, transaction){};
		}
		if (typeof params.fail != 'function')
		{
			params.fail = function(result, transaction, query){};
		}

		params.action = "replace";

		this.query(
			this.getQuery(params)
		).then(function (success) {
			params.success(success.result, success.transaction);
			success.result.tableName = params.tableName;
			promise.fulfill(success);
		}).catch(function (error){
			params.fail(error.result, error.transaction, error.query, params);
			error.queryParams = params;
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Gets the data from the table
	 * @param params
	 */
	BX.dataBase.prototype.getRows = function (params)
	{
		var promise = new BX.Promise();

		params = params || {};
		if (typeof params.success != 'function')
		{
			params.success = function(result, transaction){};
		}
		if (typeof params.fail != 'function')
		{
			params.fail = function(result, transaction, query){};
		}

		params.action = "select";

		this.query(
			this.getQuery(params)
		).then(function (success) {
			var tableName = params.tableName.toString().toUpperCase();
			if (
				this.jsonFields[tableName]
				&& this.jsonFields[tableName].length
				&& success.result.items.length
			)
			{
				for (var i = 0; i < success.result.items.length; i++)
				{
					for (var j = 0; j < this.jsonFields[tableName].length; j++)
					{
						if (success.result.items[i][this.jsonFields[tableName][j]])
						{
							success.result.items[i][this.jsonFields[tableName][j]] = JSON.parse(success.result.items[i][this.jsonFields[tableName][j]]);
						}
					}
				}
			}
			params.success(success.result, success.transaction);
			success.result.tableName = params.tableName;
			promise.fulfill(success);
		}.bind(this)).catch(function (error){
			params.fail(error.result, error.transaction, error.query, params);
			error.queryParams = params;
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Updates the table
	 * @param params
	 */
	BX.dataBase.prototype.updateRows = function (params)
	{
		var promise = new BX.Promise();

		params = params || {};
		if (typeof params.success != 'function')
		{
			params.success = function(result, transaction){};
		}
		if (typeof params.fail != 'function')
		{
			params.fail = function(result, transaction, query){};
		}

		params.action = "update";

		this.query(
			this.getQuery(params)
		).then(function (success) {
			params.success(success.result, success.transaction);
			success.result.tableName = params.tableName;
			promise.fulfill(success);
		}).catch(function (error){
			params.fail(error.result, error.transaction, error.query, params);
			error.queryParams = params;
			promise.reject(error);
		});

		return promise;
	};

	/**
	 * Deletes rows from the table
	 * @param params
	 */
	BX.dataBase.prototype.deleteRows = function (params)
	{
		params.action = "delete";
        var promise = new BX.Promise();
        var str = this.getQuery(params);

        this.query(str)
            .then(function (success)
            {
                success.result.tableName = params.tableName;
                promise.fulfill(success);
            })
            .catch(function (error)
            {
            	if (typeof error == 'object' && error)
				{
                	error.queryParams = params;
				}
                promise.reject(error);
            }
        );

        return promise;
	};

	/**
	 * Builds the query string and the set of values.
	 * @param params
	 * @returns {{query: string, values: Array}}
	 */
	BX.dataBase.prototype.getQuery = function (params)
	{
		var values = [];
		var where = params.filter;
		var order = params.order || null;
		var limit = params.limit || null;
		var select = params.fields;
		var insert = params.insertFields;
		var set = params.updateFields;
		var tableName = params.tableName;
		var strQuery = "";

		switch (params.action)
		{
			case "create":
			{
				var fieldsString = "";
				if (typeof(select) == "object")
				{
					var field = "";
					var type = "";
					for (var j = 0; j < select.length; j++)
					{
						field = "";
						type = "";
						if (typeof(select[j]) == "object")
						{
							if (select[j].name)
							{
								field = select[j].name;
							}
							if (field && select[j].type)
							{
								if (
									select[j].type.toLowerCase() == 'integer'
									|| select[j].type.toLowerCase() == 'real'
									|| select[j].type.toLowerCase() == 'text'
								)
								{
									field += " "+select[j].type;
								}
							}
							if (field && select[j].unique && select[j].unique == true)
							{
								field += " unique";
							}
						}
						else if (typeof(select[j]) == "string" && select[j].length > 0)
						{
							field = select[j];
						}

						if (field.length > 0)
						{
							if (fieldsString.length > 0)
								fieldsString += "," + field.toUpperCase();
							else
								fieldsString = field.toUpperCase();
						}
					}
				}

				strQuery = "CREATE TABLE IF NOT EXISTS " + tableName.toUpperCase() + " (" + fieldsString + ") ";

				break;
			}

			case "drop":
			{
				strQuery = "DROP TABLE IF EXISTS " + tableName.toUpperCase();
				break;
			}
			case "select":
			{
				var orderParam = [];
				if (order && typeof order == 'object')
				{
					for (var key in order)
					{
						if (order.hasOwnProperty(key))
						{
							orderParam.push(key+" "+(order[key] == "DESC"? "DESC": "ASC"));
						}
					}
				}

				var limitParam = null;
				if (limit)
				{
					if (typeof limit == 'object')
					{
						limitParam = parseInt(limit.limit)+(limit.offset? ', '+parseInt(limit.offset): '');
					}
					else if (typeof limit == 'number')
					{
						limitParam = limit;
					}
				}

				strQuery = "SELECT " + this.getValueArrayString(select, "*") +
							" FROM " + tableName.toUpperCase() +
							" " + this.getFilter(where) +
							(orderParam.length > 0? " ORDER BY " + orderParam.join(', ') + " ": "") +
							(limitParam? " LIMIT " + limitParam + " ": "");

				values = this.getValues([where]);
				break;
			}

			case "replace":
			{
				var groups = 0;
				var groupSize = 0;
				var keyString = "";
				if (BX.type.isArray(insert))
				{
					values = this.getValues(insert, 'insert');
					for (var i in insert[0])
					{
						groupSize++
					}
					groups = insert.length;
					keyString = this.getKeyString(insert[0])
				}
				else
				{
					values = this.getValues([insert], 'insert');
					groups = 1;
					groupSize = values.length;
					keyString = this.getKeyString(insert)
				}

				strQuery = "REPLACE INTO " + tableName.toUpperCase() + " (" + keyString + ") VALUES %values%";

				var placeholder = [];
				var placeholderGroup = [];
				for (var i = 0; i < groups; i++)
				{
					placeholder = [];
					for (var j = 0; j < groupSize; j++)
					{
						placeholder.push('?');
					}
					placeholderGroup.push(placeholder.join(','));
				}

				strQuery = strQuery.replace("%values%", "("+placeholderGroup.join("), (")+")");

				break;
			}

			case "insert":
			{
				var groups = 0;
				var groupSize = 0;
				var keyString = "";
				if (BX.type.isArray(insert))
				{
					values = this.getValues(insert, 'insert');
					for (var i in insert[0])
					{
						groupSize++
					}
					groups = insert.length;
					keyString = this.getKeyString(insert[0])
				}
				else
				{
					values = this.getValues([insert], 'insert');
					groups = 1;
					groupSize = values.length;
					keyString = this.getKeyString(insert)
				}

				strQuery = "INSERT INTO " + tableName.toUpperCase() + " (" + keyString + ") VALUES %values%";

				var placeholder = [];
				var placeholderGroup = [];
				for (var i = 0; i < groups; i++)
				{
					placeholder = [];
					for (var j = 0; j < groupSize; j++)
					{
						placeholder.push('?');
					}
					placeholderGroup.push(placeholder.join(','));
				}

				strQuery = strQuery.replace("%values%", "("+placeholderGroup.join("), (")+")");

				break;
			}

			case "delete":
			{
				strQuery = "DELETE FROM " + tableName.toUpperCase() + " " + this.getFilter(where);
				values = this.getValues([where]);
				break;
			}

			case "update":
			{
				strQuery = "UPDATE " + tableName.toUpperCase() + " " + this.getFieldPair(set, "SET ") + " " + this.getFilter(where);
				values = this.getValues([set], 'update').concat(
					this.getValues([where])
				);
				break;
			}
		}
		return {
			query: strQuery,
			values: values
		}
	};


	/**
	 * Gets pairs for query string
	 * @param {object} fields The object with set of key-value pairs
	 * @param {string} operator The keyword that will be join on the beginning of the string
	 * @returns {string}
	 */
	BX.dataBase.prototype.getFieldPair = function (fields, operator)
	{
		var pairsRow = "";
		var keyWord = operator || "";

		if (typeof(fields) == "object")
		{
			var i = 0;
			for (var key in fields)
			{
				var pair = ((i > 0) ? ", " : "") + (key.toUpperCase() + "=" + "?");
				if (pairsRow.length == 0 && keyWord.length > 0)
					pairsRow = keyWord;
				pairsRow += pair;
				i++;
			}
		}

		return pairsRow;
	};

	BX.dataBase.prototype.getFilter = function (fields)
	{
		var pairsRow = "";
		var keyWord = "WHERE ";

		if (typeof(fields) == "object")
		{
			var i = 0;
			for (var key in fields)
			{
				var pair = "";
				var count = 1;
				if (typeof(fields[key]) == "object")
				{
					count = fields[key].length;
				}

				for (var j = 0; j < count; j++)
				{
					pair = ((j > 0) ? pair + " OR " : "(") + (key.toUpperCase() + "=" + "?");
					if ((j + 1) == count)
						pair += ")"
				};

				pairsRow += pair;
				i++;
			}
		}
		else if (typeof fields == "string")
		{
			pairsRow = fields;
		}
		return pairsRow == "" ? "" : "WHERE " + pairsRow;
	};

	/**
	 * Gets the string with keys of fields that have splitted by commas
	 * @param fields
	 * @param defaultResult
	 * @returns {string}
	 */
	BX.dataBase.prototype.getKeyString = function (fields, defaultResult)
	{
		var result = "";
		if (!defaultResult)
			defaultResult = "";

		if (BX.type.isArray(fields))
		{
			for (var i = 0; i < fields.length; i++)
			{
				for (var key in fields[i])
				{
					if (result.length > 0)
						result += "," + key.toUpperCase();
					else
						result = key.toUpperCase();
				}
			}
		}
		else if (typeof(fields) == "object")
		{
			for (var key in fields)
			{
				if (result.length > 0)
					result += "," + key.toUpperCase();
				else
					result = key.toUpperCase();
			}
		}

		if (result.length == 0)
			result = defaultResult;

		return result;
	};

	/**
	 * Gets the string with values of the array that have splitted by commas
	 * @param fields
	 * @param defaultResult
	 * @returns {string}
	 */
	BX.dataBase.prototype.getValueArrayString = function (fields, defaultResult)
	{
		var result = "";
		if (!defaultResult)
			defaultResult = "";
		if (typeof(fields) == "object")
		{
			for (var i = 0; i < fields.length; i++)
			{

				if (result.length > 0)
					result += "," + fields[i].toUpperCase();
				else
					result = fields[i].toUpperCase();
			}
		}


		if (result.length == 0)
			result = defaultResult;

		return result;
	};

	/**
	 * Gets the array of values
	 * @param values
	 * @returns {Array}
	 */
	BX.dataBase.prototype.getValues = function (values, type)
	{
		type = type || 'undefined';

		var resultValues = [];
		for (var j = 0; j < values.length; j++)
		{
			var valuesItem = values[j];

			if (BX.type.isArray(valuesItem))
			{
				for (var i = 0; i < valuesItem.length; i++)
				{
					if ((type == 'insert' || type == 'update') && typeof(valuesItem[i]) == "object")
					{
						resultValues.push(JSON.stringify(valuesItem[i]));
					}
					else if (typeof(valuesItem[i]) == "object")
					{
						for (var keyField in valuesItem[i])
						{
							if (typeof(valuesItem[i][keyField]) == "object")
							{
								resultValues.push(JSON.stringify(valuesItem[i][keyField]));
							}
							else
							{
								resultValues.push(valuesItem[i][keyField]);
							}
						}
					}
					else
					{
						resultValues.push(valuesItem[i]);
					}
				}
			}
			else if (typeof(valuesItem) == "object")
			{
				for (var i in valuesItem)
				{
					if ((type == 'insert' || type == 'update') && typeof(valuesItem[i]) == "object")
					{
						resultValues.push(JSON.stringify(valuesItem[i]));
					}
					else if (typeof(valuesItem[i]) == "object")
					{
						for (var keyField in valuesItem[i])
						{
							if (typeof(valuesItem[i][keyField]) == "object")
							{
								resultValues.push(JSON.stringify(valuesItem[i][keyField]));
							}
							else
							{
								resultValues.push(valuesItem[i][keyField]);
							}
						}
					}
					else
					{
						resultValues.push(valuesItem[i]);
					}
				}
			}
		}

		return resultValues;
	};

	/**
	 * Executes the query
	 * @param success The success callback
	 * @param fail The failture callback
	 * @returns {string}
	 * @param query
	 */
	BX.dataBase.prototype.query = function (query, success, fail)
	{
		var promise = new BX.Promise();
		if (typeof success != 'function')
		{
			success = function(result, transaction){};
		}
		if (typeof fail != 'function')
		{
			fail = function(result, transaction, query){};
		}

		if (!this.dbObject)
		{
			fail(null, null, null);
			promise.reject(null, null, null);
			return promise;
		}

		this.dbObject.executeSql(
			query.query,
			query.values,
			function (results)
			{
				var result = {
					originalResult: results
				};

				var len = results.rows.length;
				if (len >= 0)
				{
					result.count = len;
					result.items = [];

					for (var i = 0; i < len; i++)
					{
						var item = {};
						var dbItem = results.rows.item(i);
						for (var key in dbItem)
						{
							if (dbItem.hasOwnProperty(key))
							{
								item[key] = dbItem[key];
							}
						}
						result.items.push(item);
					}
				}
				success(result, null);
				promise.fulfill({result: result, transaction: null});
			},
			function (res)
			{
				fail(res, null, query);
				promise.reject({result: res, transaction: null, query: query});
			}
		);
		return promise;
	};

	/**
	 * Gets the beautifying result from the query response
	 * @param results
	 * @returns {*}
	 */

	BX.dataBase.prototype.getResponseObject = function (results)
	{

		var len = results.rows.length;

		var result = [];
		for (var i = 0; i < len; i++)
		{
			result[result.length] = results.rows.item(i);
		}

		return result;
	};

})(window);




/** REACT DATABASE **/

var TableEntry = function(name, db, fields)
{
	this.name = name;
	this.db = db;
	this.fields = fields;
};



TableEntry.prototype = {
	__proto__ : TableEntry.prototype,
	name : null,
	db : null,
	delete : function(filter)
	{
		return this.db.deleteRows({
			tableName : this.name,
			filter : filter
		})
	},
	get : function(filter, order = null, limit = null)
	{
		let getPromise = new BX.Promise();

		this.db.getRows({
			tableName : this.name,
			filter : filter,
			order : order,
			limit : limit
		}).then(data => {
			let modifiedData = data.result.items.map(item => this.convertForReading(item, this.fields));
			getPromise.fulfill(modifiedData, data)
		}).catch(e => getPromise.reject(e));

		return getPromise;
	},
	getLike : function(filter)
	{
		let getPromise = new BX.Promise();
		let where = "";
		let fields = Object.keys(filter);
		fields.forEach((key) =>
		{
			let expression = key + " LIKE ?";
			where += (where === ""? "": " AND ") + expression;
		});

		this.db.query({
			query : ("SELECT * FROM " + this.name + " WHERE " + where).toUpperCase(),
			values : Object.values(filter)
		}).then(data => getPromise.fulfill(data.result.items, data)
		).catch(e => getPromise.reject(e));

		return getPromise;
	},
	add : function(insertFields)
	{
		return this.db.addRow({
			tableName : this.name,
			insertFields : insertFields
		})
	},
	replace : function(replaceFields)
	{
		return this.db.replaceRow({
			tableName : this.name,
			insertFields : replaceFields
		})
	},
	update : function(primaryValue, updateFields)
	{
		if(this.getPrimaryKey() == null)
		{
			console.error("No primary key in table description");
			return;
		}

		let filter = {};
		filter[this.getPrimaryKey()] = primaryValue;
		return this.db.updateRows({
			tableName : this.name,
			updateFields : updateFields,
			filter: filter
		})
	},

	convertForReading:function(item, fieldsDesc)
	{
		let convertedEntity = {};
		for(let fieldName in item)
		{
			let fieldDesc = this.findFieldDesc(fieldName);
			let value = item[fieldName];
			if(typeof fieldDesc === "object" && fieldDesc["class"])
			{
				value = this.convertFieldToEntity(value, fieldDesc["class"]);
			}

			convertedEntity[fieldName] = value;
		}

		return convertedEntity;
	},
	findFieldDesc:function(name){
		return this.fields.find(field =>{
			if(typeof field === "object")
			{
				if(field.name.toUpperCase() === name.toUpperCase())
					return true;
			}
			else if(field.toUpperCase() === name.toUpperCase())
			{
				return true;
			}

			return false;
		});
	},
	getPrimaryKey:function(){
		let primaryKeyDesc = this.fields.find(field =>{
			if(typeof field === "object")
			{
				if(field.primary === true)
					return true;
			}

			return false;
		});

		if(primaryKeyDesc)
			return primaryKeyDesc["name"];

		return null;
	},
	convertFieldToEntity:function(fieldValue, entityClass)
	{
		let value = fieldValue;
		try
		{
			value = JSON.parse(fieldValue);
			if(entityClass.toUpperCase() != "JSON")
			{
				let classConstructor = eval(entityClass);
				if(typeof classConstructor === "function")
				{
					let classInstance = new classConstructor();
					let keys = Object.keys(value);
					Object.values(value).forEach((v,i) =>classInstance[keys[i]] = v);
					value = classInstance;
				}
			}
		}
		catch(e)
		{

		}

		return value;
	},
};




var ReactDatabase = function(dbName = 'default', dbUser = 'default', dbLanguage = 'en', tablesDescs = {})
{
	this.tables = tablesDescs;
	dbLanguage = '_'+dbLanguage.toString().toLowerCase();
	dbUser = '_'+dbUser.toString().toLowerCase();

	let id = (typeof currentDomain != 'undefined'? currentDomain: location.origin).replace(/(http.?:\/\/)|(:|\.)/mg, "");
	let databaseName = dbName + '_' + id + dbUser + dbLanguage+'.db';
	this.db = BX.dataBase.create({name : databaseName, location : 'default'});

	this.debug = false;

	console.info("ReactDatabase: init "+ databaseName, this.db);
};


ReactDatabase.prototype = {
	__proto__ : ReactDatabase.prototype,
	tables:{},
	table : function(desc)
	{
		let tablePromise = new BX.Promise();
		let tableDesc = null;
		if(typeof desc == "object")
		{
			tableDesc = desc;
		}
		else if(typeof desc === "string" && this.tables[desc])
		{
			tableDesc = this.tables[desc];
			desc = tableDesc;
		}

		if(tableDesc != null)
		{
			this.db.isTableExists(desc.name)
				.then(() =>
				{
					if (this.debug) console.info("ReactDatabase.table: table '" + desc.name + "' is exists");
					tablePromise.fulfill(new TableEntry(desc.name, this.db, desc.fields))
				})
				.catch((e) =>
					{
						if (this.debug) console.info("ReactDatabase.table: creating table " + desc.name, e);
						this.db.createTable(
							{
								tableName : desc.name,
								fields : desc.fields
							})
							.then(() => tablePromise.fulfill(new TableEntry(desc.name, this.db, desc.fields)))
							.catch((e) => tablePromise.reject(e))
					}
				);

		}
		else
		{
			tablePromise.reject({
				code: 0,
				message: "Can't find table description"
			});
		}

		return tablePromise;
	},
	tableGet : function(desc, filter)
	{
		let tableGetPromise = BX.Promise();
		this.table(desc)
			.then(table => table.get(filter)
				.then(data => tableGetPromise.fulfill(data))
				.catch(e => tableGetPromise.reject(e))
			)
			.catch(e => tableGetPromise.reject(e));

		return tableGetPromise;
	},
	tableClear : function(desc)
	{
		this.table(desc).then(table => table.delete());
	}
};

