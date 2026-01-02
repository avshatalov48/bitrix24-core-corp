/**
 * @module im/messenger/db/table/recent
 */
jn.define('im/messenger/db/table/recent', (require, exports, module) => {
	const {
		Table,
		FieldType,
		FieldDefaultValue,
	} = require('im/messenger/db/table/table');
	const { DialogTable } = require('im/messenger/db/table/dialog');
	const { Feature } = require('im/messenger/lib/feature');
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogType } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { getStartWordsSearchVariants } = require('im/messenger/db/helper/start-words');

	/**
	 * @extends {Table<RecentStoredData>}
	 */
	class RecentTable extends Table
	{
		getName()
		{
			return 'b_im_recent';
		}

		getPrimaryKey()
		{
			return 'id';
		}

		getFields()
		{
			return [
				{ name: 'id', type: FieldType.text, unique: true, index: true },
				{ name: 'lastActivityDate', type: FieldType.date, index: true },
				{ name: 'message', type: FieldType.object },
				{ name: 'dateMessage', type: FieldType.date },
				{ name: 'unread', type: FieldType.boolean },
				{ name: 'pinned', type: FieldType.boolean },
				{ name: 'invitation', type: FieldType.json, defaultValue: FieldDefaultValue.emptyObject },
				{ name: 'options', type: FieldType.json },
			];
		}

		/**
		 * @param {PinnedListByDialogTypeFilter}
		 * @return {Promise<{items: Array, users: Array, messages: Array, files: Array, hasMore: boolean}>}
		 */
		async getPinnedListByDialogTypeFilter({ dialogTypes = [], exceptDialogTypes = [] })
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({
					items: [],
					users: [],
					messages: [],
					files: [],
					hasMore: false,
				});
			}

			const filterString = this.createPinnedFilter(
				{
					dialogTypes,
					exceptDialogTypes,
				},
			);

			const query = `
				SELECT b_im_recent.*, b_im_dialog.*
				FROM b_im_recent
				JOIN b_im_dialog ON b_im_recent.id = b_im_dialog.dialogId ${filterString}
			`;

			const selectResult = await this.executeSql({
				query,
			});

			const restoredRows = this.convertSelectResultToGetListResult(selectResult, true);

			const userIds = this.getUserIdsFromRecentItems(restoredRows.items);
			restoredRows.users = await this.getUsersByIdList(userIds);

			const messageIds = this.getMessageIdsFromRecentItems(restoredRows.items);
			restoredRows.messages = await this.getMessagesByIdList(messageIds);

			const filesIds = this.getFilesIdsFromRecentItems(restoredRows.items);
			restoredRows.files = await this.getFilesByIdList(filesIds);

			return restoredRows;
		}

		/**
		 * @param {ListByDialogTypeFilter}
		 * @return {Promise<RecentListResult>}
		 */
		async getListByDialogTypeFilter({ dialogTypes = [], exceptDialogTypes = [], lastActivityDate = null, limit })
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled)
			{
				return Promise.resolve({
					items: [],
					users: [],
					messages: [],
					files: [],
					hasMore: false,
				});
			}

			const filterString = this.createFilter(
				{
					dialogTypes,
					exceptDialogTypes,
					lastActivityDate,
				},
			);

			const query = `
				SELECT b_im_recent.*, b_im_dialog.*
				FROM b_im_recent
						 JOIN b_im_dialog ON b_im_recent.id = b_im_dialog.dialogId
						 LEFT JOIN b_im_draft ON b_im_recent.id = b_im_draft.dialogId ${filterString}
				ORDER BY pinned DESC, b_im_draft.lastActivityDate DESC NULLS LAST, lastActivityDate DESC
					LIMIT ?
			`;

			const selectResult = await this.executeSql({
				query,
				values: [limit],
			});

			const restoredRows = this.convertSelectResultToGetListResult(selectResult, true);

			const userIds = this.getUserIdsFromRecentItems(restoredRows.items);
			restoredRows.users = await this.getUsersByIdList(userIds);

			const messageIds = this.getMessageIdsFromRecentItems(restoredRows.items);
			restoredRows.messages = await this.getMessagesByIdList(messageIds);

			const filesIds = this.getFilesIdsFromRecentItems(restoredRows.items);
			restoredRows.files = await this.getFilesByIdList(filesIds);

			const draftIds = this.getDraftIdsFromRecentItems(restoredRows.items);
			restoredRows.draft = await this.getDraftByIdList(draftIds);

			try
			{
				const lastActivityDateObj = restoredRows.items[restoredRows.items.length - 1]?.lastActivityDate;
				restoredRows.hasMore = await this.#getHasMorePage(
					{
						dialogTypes,
						exceptDialogTypes,
						lastActivityDate: lastActivityDateObj?.toISOString() ?? null,
					},
				);
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.getListByDialogTypeFilter.getHasMorePage.catch:`, error);
			}

			return restoredRows;
		}

		/**
		 * @param {string} searchText
		 * @param {'asc'|'desc'} order='asc'
		 * @param {number} limit=25
		 *
		 * @returns {Promise<{items: *[]}>}
		 */
		async searchByText(
			searchText,
			order = 'desc',
			limit = 25,
			shouldRestoreRows = true,
		)
		{
			if (!this.isSupported || !Feature.isLocalStorageEnabled || !Type.isStringFilled(searchText))
			{
				return {
					items: [],
				};
			}

			// TODO: MessengerV2 move to config
			const filterString = this.createFilter(
				{
					exceptDialogTypes: [
						DialogType.copilot,
						DialogType.lines,
						DialogType.comment,
						DialogType.tasksTask,
					],
				},
			);

			const { sqlCondition, values } = getStartWordsSearchVariants('name', searchText);
			const result = await this.executeSql({
				query: `
					SELECT * 
					FROM b_im_dialog
					JOIN b_im_recent ON b_im_recent.id = b_im_dialog.dialogId
					${filterString} AND ${sqlCondition} 
					ORDER BY id ${order}
					LIMIT ${limit}
				`,
				values: [
					...values,
				],
			});

			return this.prepareListResult(result, shouldRestoreRows);
		}

		/**
		 * @param {Array<string>} dialogTypes
		 * @param {Array<string>} exceptDialogTypes
		 * @param {string} lastActivityDate
		 * @return {Promise<{boolean}>|boolean}
		 */
		async #getHasMorePage({ dialogTypes = [], exceptDialogTypes = [], lastActivityDate })
		{
			if (!lastActivityDate)
			{
				return Promise.resolve(false);
			}

			let hasMore = false;

			const filterString = this.createFilter(
				{
					dialogTypes,
					exceptDialogTypes,
					lastActivityDate,
				},
			);

			const query = `
				SELECT COUNT(*) AS total_count
				FROM b_im_recent
				JOIN b_im_dialog ON b_im_recent.id = b_im_dialog.dialogId ${filterString}
			`;

			const selectResult = await this.executeSql({
				query,
			});
			const restoredRows = this.convertSelectResultToGetListResult(selectResult, false);

			if (restoredRows.items.length > 0)
			{
				hasMore = Boolean(restoredRows.items[0].total_count);
			}

			return hasMore;
		}

		/**
		 * @param {Array<string>} dialogTypes
		 * @param {Array<string>} exceptDialogTypes
		 * @param {string} lastActivityDate
		 * @return {string}
		 */
		createPinnedFilter({ dialogTypes = [], exceptDialogTypes = [] })
		{
			let filterString = '';
			if (dialogTypes.length > 0)
			{
				const types = dialogTypes.map((item) => `'${item}'`);
				filterString = `WHERE type IN (${types}) AND pinned = 1`;
			}

			if (exceptDialogTypes.length > 0)
			{
				const types = exceptDialogTypes.map((item) => `'${item}'`);
				if (filterString.length > 0)
				{
					filterString += ` AND type NOT IN (${types})`;
				}
				else
				{
					filterString = `WHERE type NOT IN (${types}) AND pinned = 1`;
				}
			}

			return filterString;
		}

		/**
		 * @param {Array<string>} dialogTypes
		 * @param {Array<string>} exceptDialogTypes
		 * @param {string} lastActivityDate
		 * @return {string}
		 */
		createFilter({ dialogTypes = [], exceptDialogTypes = [], lastActivityDate = null })
		{
			let filterString = '';
			if (dialogTypes.length > 0)
			{
				const types = dialogTypes.map((item) => `'${item}'`);
				filterString = `WHERE ${DialogTable.getTableName()}.type IN (${types})`;
			}

			if (exceptDialogTypes.length > 0)
			{
				const types = exceptDialogTypes.map((item) => `'${item}'`);
				if (filterString.length > 0)
				{
					filterString += ` AND ${DialogTable.getTableName()}.type NOT IN (${types})`;
				}
				else
				{
					filterString = `WHERE ${DialogTable.getTableName()}.type NOT IN (${types})`;
				}
			}

			if (lastActivityDate)
			{
				// eslint-disable-next-line no-unused-expressions
				filterString.length > 0
					? filterString += ` AND ${this.getName()}.lastActivityDate < '${lastActivityDate}'`
					: filterString = ` WHERE ${this.getName()}.lastActivityDate < '${lastActivityDate}'`;
			}

			return filterString;
		}

		/**
		 * @param {Array<object>} recentItems
		 * @return {Array<number>}
		 */
		getUserIdsFromRecentItems(recentItems)
		{
			const userIds = [];
			recentItems.forEach((item) => {
				const senderId = item?.message?.senderId ?? 0;
				if (senderId !== 0)
				{
					userIds.push(Number(item.message.senderId));
				}

				if ([DialogType.user, DialogType.private].includes(item.chat.type))
				{
					userIds.push(Number(item.chat.dialogId));
				}
			});

			return [...new Set(userIds)];
		}

		/**
		 * @param {Array<object>} recentItems
		 * @return {Array<number>}
		 */
		getMessageIdsFromRecentItems(recentItems)
		{
			const messageIds = [];
			recentItems.forEach((item) => {
				if (Type.isNumber(item.message.id) && item.message.id > 0)
				{
					messageIds.push(item.message.id);
				}
			});

			return [...new Set(messageIds)];
		}

		/**
		 * @param {Array<object>} recentItems
		 * @return {Array<number>}
		 */
		getFilesIdsFromRecentItems(recentItems)
		{
			let filesIds = [];
			recentItems.forEach((item) => {
				if (!Type.isNil(item.message?.params?.withFile?.id))
				{
					filesIds.push(Number(item.message.params.withFile.id));
				}

				if (Type.isArrayFilled(item.message?.params?.withFile))
				{
					const ids = item.message.params?.withFile.map((id) => Number(id));
					filesIds = [...filesIds, ...ids];
				}
			});

			return [...new Set(filesIds)];
		}

		/**
		 * @param {Array<object>} recentItems
		 * @return {Array<number | string>}
		 */
		getDraftIdsFromRecentItems(recentItems)
		{
			return [...new Set(recentItems.map((item) => item.id))];
		}

		/**
		 * @param {Array<string>} ids
		 * @return {Promise<{items: Array}>}
		 */
		async getUsersByIdList(ids)
		{
			const result = await serviceLocator.get('core').getRepository().user.userTable.getListByIds(ids);

			return result.items;
		}

		/**
		 * @param {Array<string>} ids
		 * @return {Promise<{items: Array}>}
		 */
		async getMessagesByIdList(ids)
		{
			const result = await serviceLocator.get('core').getRepository().message.messageTable.getListByIds(ids);

			return result.items;
		}

		/**
		 * @param {Array<string>} ids
		 * @return {Promise<{items: Array}>}
		 */
		async getFilesByIdList(ids)
		{
			const result = await serviceLocator.get('core').getRepository().message.fileTable.getListByIds(ids);

			return result.items;
		}

		/**
		 * @param {Array<string>} ids
		 * @return {Promise<{items: Array}>}
		 */
		async getDraftByIdList(ids)
		{
			const result = await serviceLocator.get('core').getRepository().draft.draftTable.getListByIds(ids);

			return result.items;
		}

		restoreDatabaseRow(row)
		{
			const fieldsRecentCollection = this.getFieldsCollection();
			const fieldsDialogCollection = new DialogTable().getFieldsCollection();
			const restoredRow = {};
			Object.keys(row).forEach((fieldName) => {
				let fieldValue = row[fieldName];

				let fieldType = fieldsRecentCollection[fieldName]?.type;
				if (fieldType)
				{
					const restoreHandler = this.getRestoreHandlerByFieldType(fieldType);
					if (Type.isFunction(restoreHandler))
					{
						fieldValue = restoreHandler(fieldName, fieldValue);
					}

					restoredRow[fieldName] = fieldValue;
				}
				else if (fieldsDialogCollection[fieldName]?.type)
				{
					fieldType = fieldsDialogCollection[fieldName]?.type;

					const restoreHandler = this.getRestoreHandlerByFieldType(fieldType);
					if (Type.isFunction(restoreHandler))
					{
						fieldValue = restoreHandler(fieldName, fieldValue);
					}

					// eslint-disable-next-line no-unused-expressions
					restoredRow.chat ?? (restoredRow.chat = {});
					restoredRow.chat[fieldName] = fieldValue;
				}
				else
				{
					console.error(`Table.restoreDatabaseRow error in ${this.getName()}: "${fieldName}" is in the database but not in the table model`);
				}
			});

			return restoredRow;
		}
	}

	module.exports = {
		RecentTable,
	};
});
