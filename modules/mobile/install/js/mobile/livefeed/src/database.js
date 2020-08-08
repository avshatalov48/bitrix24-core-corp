import {Type} from "main.core";

class Database
{
	constructor(params)
	{
		this.tableName = null;
		this.keyName = null;

		this.setTableName(Type.isPlainObject(params) && Type.isStringFilled(params.tableName) ? params.tableName : 'b_default');
		this.setKeyName(Type.isPlainObject(params) && Type.isStringFilled(params.keyName) ? params.keyName : 'post_unsent');
	}

	setTableName(value)
	{
		this.tableName = value;
	}
	getTableName()
	{
		return this.tableName;
	}

	setKeyName(value)
	{
		this.keyName = value;
	}
	getKeyName()
	{
		return this.keyName;
	}

	check(callback)
	{
		if (!Type.isObject(app.db))
		{
			return false;
		}

		app.db.createTable({
			tableName: this.getTableName(),
			fields: [
				{
					name: 'KEY',
					unique: true
				},
				'VALUE'
			],
			success: (res) => { callback.success(); },
			fail: (e) => { callback.fail() }
		});
	}

	delete(groupId)
	{
		if (parseInt(groupId) <= 0)
		{
			groupId = false;
		}

		if (!Type.isObject(app.db))
		{
			return false;
		}

		this.check({
			success: () => {
				app.db.deleteRows({
					tableName: this.getTableName(),
					filter: {
						KEY: this.getKeyName() + (groupId ? '_' + groupId : '')
					},
					success: (res) => {},
					fail: (e) => {}
				});
			},
			fail: () => {}
		});
	}

	save(data, groupId)
	{
		if (parseInt(groupId) <= 0)
		{
			groupId = false;
		}

		for (let x in data)
		{
			if (!data.hasOwnProperty(x))
			{
				continue;
			}

			if (x === 'sessid')
			{
				delete data[x];
				break;
			}
		}

		if (!Type.isObject(app.db))
		{
			return false;
		}

		this.check({
			success: () => {
				app.db.getRows({
					tableName: this.getTableName(),
					filter: {
						KEY: this.getKeyName() + (groupId ? '_' + groupId : '')
					},
					success: (res) => {
						let text = JSON.stringify(data);

						if (res.items.length > 0)
						{
							app.db.updateRows({
								tableName: this.getTableName(),
								updateFields: {
									VALUE: text
								},
								filter: {
									KEY: this.getKeyName() + (groupId ? '_' + groupId : '')
								},
								success: (res) => {
								},
								fail: (e) => {
								}
							});
						}
						else
						{
							app.db.addRow({
								tableName: this.getTableName(),
								insertFields: {
									KEY: this.getKeyName() + (groupId ? '_' + groupId : ''),
									VALUE: text
								},
								success: (res) => {
								},
								fail: (e) => {
								}
							});
						}
					},
					fail: (e) => {}
				});
			},
			fail: () => {
			}
		});
	}

	load(callback, groupId)
	{
		if (parseInt(groupId) <= 0)
		{
			groupId = false;
		}

		if (!Type.isObject(app.db))
		{
			callback.onEmpty();
			return null;
		}

		this.check({
			success: () => {
				app.db.getRows({
					tableName: this.getTableName(),
					filter: {
						KEY: this.getKeyName() + (groupId ? '_' + groupId : '')
					},
					success: (res) =>
					{
						if (
							res.items.length > 0
							&& res.items[0].VALUE.length > 0
						)
						{
							var result = JSON.parse(res.items[0].VALUE);
							if (Type.isPlainObject(result))
							{
								callback.onLoad(result);
							}
							else
							{
								callback.onEmpty();
							}
						}
						else
						{
							callback.onEmpty();
						}
					},
					fail: (e) => { callback.onEmpty(); }
				});
			},
			fail: () => { callback.onEmpty(); }
		});
	}
}

export {
	Database
}