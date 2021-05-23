import {Type} from "main.core";

class Database
{
	constructor(params)
	{
		this.tableName = null;
		this.keyName = null;

		this.setTableName(Type.isPlainObject(params) && Type.isStringFilled(params.tableName) ? params.tableName : 'livefeed');
		this.setKeyName(Type.isPlainObject(params) && Type.isStringFilled(params.keyName) ? params.keyName : 'postUnsent');

		this.init();
	}

	init()
	{
		BXMobileApp.addCustomEvent('Livefeed.Database::clear', this.onClear.bind(this));
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

	onClear(params)
	{
		this.delete(params.groupId);
	}

	delete(groupId)
	{
		if (parseInt(groupId) <= 0)
		{
			groupId = false;
		}

		app.exec('setStorageValue', {
			storageId: this.getTableName(),
			key: this.getKeyName() + (groupId ? '_' + groupId : ''),
			value: {},
			callback: (res) => {
			}
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

		app.exec('setStorageValue', {
			storageId: this.getTableName(),
			key: this.getKeyName() + (groupId ? '_' + groupId : ''),
			value: data,
			callback: (res) => {
			}
		});
	}

	load(callback, groupId)
	{
		if (parseInt(groupId) <= 0)
		{
			groupId = false;
		}

		app.exec('getStorageValue', {
			storageId: this.getTableName(),
			key: this.getKeyName() + (groupId ? '_' + groupId : ''),
			callback: (value) =>
			{
				value = (Type.isPlainObject(value) ? value : (Type.isStringFilled(value) ? JSON.parse(value) : {}));
				if (
					Type.isPlainObject(value)
					&& Object.keys(value).length > 0
				)
				{
					callback.onLoad(value);
				}
				else
				{
					callback.onEmpty();
				}
			}
		});

	}
}

export {
	Database
}