/**
 * @module im/messenger/db/table/dialog
 */
jn.define('im/messenger/db/table/dialog', (require, exports, module) => {
	const { Type } = require('type');

	const { Settings } = require('im/messenger/lib/settings');
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('database-table--dialog');

	class DialogTable extends Table
	{
		getName()
		{
			return 'b_im_dialog';
		}

		getFields()
		{
			return [
				{ name: 'dialogId', type: FieldType.text, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer, unique: true, index: true },
				{ name: 'type', type: FieldType.text },
				{ name: 'name', type: FieldType.text },
				{ name: 'description', type: FieldType.text },
				{ name: 'avatar', type: FieldType.text },
				{ name: 'color', type: FieldType.text },
				{ name: 'extranet', type: FieldType.boolean },
				{ name: 'counter', type: FieldType.integer },
				{ name: 'userCounter', type: FieldType.integer },
				{ name: 'lastReadId', type: FieldType.integer },
				{ name: 'markedId', type: FieldType.integer },
				{ name: 'lastMessageId', type: FieldType.integer },
				{ name: 'lastMessageViews', type: FieldType.json },
				{ name: 'countOfViewers', type: FieldType.integer },
				{ name: 'managerList', type: FieldType.json },
				{ name: 'readList', type: FieldType.json },
				{ name: 'muteList', type: FieldType.json },
				{ name: 'owner', type: FieldType.integer },
				{ name: 'entityType', type: FieldType.text },
				{ name: 'entityId', type: FieldType.integer },
				{ name: 'dateCreate', type: FieldType.date },
				{ name: 'public', type: FieldType.json },
				{ name: 'code', type: FieldType.text },
				{ name: 'diskFolderId', type: FieldType.integer },
			];
		}

		async deleteByIdList(idList)
		{
			if (!Settings.isLocalStorageEnabled || !Type.isArrayFilled(idList))
			{
				return Promise.resolve({});
			}

			const dialogIdList = idList.map((id) => `'${id}'`).join(',');
			const result = await this.executeSql({
				query: `
					DELETE
					FROM ${this.getName()}
					WHERE dialogId IN (${dialogIdList})
				`,
			});

			logger.log('DialogTable.deleteByIdList complete: ', idList);

			return result;
		}

		async deleteByChatIdList(idList)
		{
			if (!Settings.isLocalStorageEnabled || !Type.isArrayFilled(idList))
			{
				return Promise.resolve({});
			}

			const chatIdList = idList.map((id) => `'${id}'`).join(',');
			const result = await this.executeSql({
				query: `
					DELETE
					FROM ${this.getName()}
					WHERE chatId IN (${chatIdList})
				`,
			});

			logger.log('DialogTable.deleteByChatIdList complete: ', idList);

			return result;
		}
	}

	module.exports = {
		DialogTable,
	};
});
