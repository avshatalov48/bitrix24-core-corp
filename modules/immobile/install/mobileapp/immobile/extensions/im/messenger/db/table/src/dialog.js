/**
 * @module im/messenger/db/table/dialog
 */
jn.define('im/messenger/db/table/dialog', (require, exports, module) => {
	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		Table,
		FieldType,
		FieldDefaultValue,
	} = require('im/messenger/db/table/table');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('database-table--dialog');

	/**
	 * @extends {Table<DialogStoredData>}
	 */
	class DialogTable extends Table
	{
		getName()
		{
			return 'b_im_dialog';
		}

		getPrimaryKey()
		{
			return 'dialogId';
		}

		getFields()
		{
			return [
				{ name: 'dialogId', type: FieldType.text, unique: true, index: true },
				{ name: 'chatId', type: FieldType.integer },
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
				{ name: 'lastMessageViews', type: FieldType.json, defaultValue: FieldDefaultValue.emptyObject },
				{ name: 'countOfViewers', type: FieldType.integer },
				{ name: 'managerList', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
				{ name: 'readList', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
				{ name: 'muteList', type: FieldType.json, defaultValue: FieldDefaultValue.emptyArray },
				{ name: 'owner', type: FieldType.integer },
				{ name: 'entityType', type: FieldType.text },
				{ name: 'entityId', type: FieldType.integer },
				{ name: 'dateCreate', type: FieldType.date },
				{ name: 'public', type: FieldType.json, defaultValue: FieldDefaultValue.emptyObject },
				{ name: 'code', type: FieldType.text },
				{ name: 'diskFolderId', type: FieldType.integer },
				{ name: 'aiProvider', type: FieldType.text },
				{ name: 'role', type: FieldType.text, defaultValue: FieldDefaultValue.noneText },
				{ name: 'permissions', type: FieldType.json },
			];
		}

		/**
		 * @param {Array<DialogId>} dialogIdList
		 * @param shouldRestoreRows
		 * @return {Promise<{items: Array<DialogStoredData>}>}
		 */
		async getListByDialogIds(dialogIdList, shouldRestoreRows = true)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled || !Type.isArrayFilled(dialogIdList))
			{
				return {
					items: [],
				};
			}
			const idsFormatted = Type.isNumber(dialogIdList[0]) ? dialogIdList.toString() : dialogIdList.map((id) => `"${id}"`);
			const result = await this.executeSql({
				query: `
					SELECT * 
					FROM ${this.getName()} 
					WHERE dialogId IN (${idsFormatted})
				`,
			});

			return this.convertSelectResultToGetListResult(result, shouldRestoreRows);
		}

		async deleteByChatIdList(idList)
		{
			if (!Feature.isLocalStorageEnabled || this.readOnly || !Type.isArrayFilled(idList))
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
