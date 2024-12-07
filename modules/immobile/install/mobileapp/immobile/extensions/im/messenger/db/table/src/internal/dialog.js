/**
 * @module im/messenger/db/table/internal/dialog
 */
jn.define('im/messenger/db/table/internal/dialog', (require, exports, module) => {
	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		Table,
		FieldType,
	} = require('im/messenger/db/table/table');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('database-table--dialog-internal');

	class DialogInternalTable extends Table
	{
		getName()
		{
			return 'b_im_dialog_internal';
		}

		getPrimaryKey()
		{
			return 'dialogId';
		}

		getFields()
		{
			return [
				{ name: 'dialogId', type: FieldType.text, unique: true, index: true },
				{ name: 'wasCompletelySync', type: FieldType.boolean, defaultValue: false }, // indicates what the last message
				// from the local database can be used as a previousId for first message that come from the SyncService
			];
		}

		/**
		 * @param {Array<string|number>} idList
		 * @param {boolean} wasCompletelySync
		 */
		async setWasCompletelySyncByIdList(idList, wasCompletelySync)
		{
			if (
				!Feature.isLocalStorageEnabled
				|| this.readOnly
				|| !Type.isArrayFilled(idList)
				|| !Type.isBoolean(wasCompletelySync))
			{
				return Promise.resolve({});
			}

			const wasCompletelySyncValue = wasCompletelySync ? '1' : '0';
			const dialogIdsToUpdate = idList.map((id) => `'${id}'`).join(',');
			const result = await this.executeSql({
				query: `
					UPDATE ${this.getName()} 
					SET wasCompletelySync = ${wasCompletelySyncValue} 
					WHERE ${this.getPrimaryKey()} IN (${dialogIdsToUpdate})
				`,
			});

			logger.log('DialogInternalTable.setWasCompletelySyncByIdList complete: ', idList, result);

			return result;
		}
	}

	module.exports = {
		DialogInternalTable,
	};
});
