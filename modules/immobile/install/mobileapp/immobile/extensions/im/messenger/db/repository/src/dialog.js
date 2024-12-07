/**
 * @module im/messenger/db/repository/dialog
 */
jn.define('im/messenger/db/repository/dialog', (require, exports, module) => {
	const { Type } = require('type');
	const { mergeImmutable } = require('utils/object');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		DialogTable,
		MessageTable,
	} = require('im/messenger/db/table');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { DialogInternalRepository } = require('im/messenger/db/repository/internal/dialog');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const logger = LoggerManager.getInstance().getLogger('repository--dialog');

	/**
	 * @class DialogRepository
	 */
	class DialogRepository
	{
		/**
		 * @return {DialogRepository}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			/**
			 * @type {DialogTable}
			 */
			this.dialogTable = new DialogTable();

			/**
			 * @type {DialogInternalRepository}
			 */
			this.internal = new DialogInternalRepository();

			/**
			 * @type {MessageTable}
			 */
			this.messageTable = new MessageTable();
		}

		/**
		 * @param {DialogId} dialogId
		 * @return {Promise<DialogStoredData|null>}
		 */
		async getByDialogId(dialogId)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return null;
			}

			const result = await this.dialogTable.getList({
				filter: {
					dialogId,
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
		 * @param {number} chatId
		 * @return {Promise<DialogStoredData|null>}
		 */
		async getByChatId(chatId)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return null;
			}

			const result = await this.dialogTable.getList({
				filter: {
					chatId,
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
		 * @param {DialogId} dialogId
		 */
		async deleteById(dialogId)
		{
			await this.internal.deleteByIdList([dialogId]);

			return this.dialogTable.deleteByIdList([dialogId]);
		}

		async deleteByChatIdList(chatIdList)
		{
			// TODO: await this.internal.deleteByChatIdList(chatIdList);

			return this.dialogTable.deleteByChatIdList(chatIdList);
		}

		async saveFromModel(dialogList)
		{
			const dialogListToAdd = [];

			dialogList.forEach((dialog) => {
				const dialogToAdd = this.dialogTable.validate(dialog);

				dialogListToAdd.push(dialogToAdd);
			});

			await this.internal.saveByDialogList(dialogListToAdd);

			return this.dialogTable.add(dialogListToAdd, true);
		}

		async saveFromRest(dialogList)
		{
			const dialogListToAdd = [];

			dialogList.forEach((dialog) => {
				const dialogToAdd = this.dialogTable.validate(this.validateRestDialog(dialog));

				dialogListToAdd.push(dialogToAdd);
			});

			await this.internal.saveByDialogList(dialogListToAdd);

			return this.dialogTable.add(dialogListToAdd, true);
		}

		/**
		 * @return {Partial<DialogRow>}
		 */
		validateRestDialog(dialog)
		{
			const result = {};

			if (Type.isNumber(dialog.dialog_id) || Type.isStringFilled(dialog.dialog_id))
			{
				result.dialogId = dialog.dialog_id.toString();
			}

			if (Type.isNumber(dialog.dialogId) || Type.isStringFilled(dialog.dialogId))
			{
				result.dialogId = dialog.dialogId.toString();
			}

			if (Type.isNumber(dialog.chatId) || Type.isStringFilled(dialog.chatId))
			{
				result.chatId = Number.parseInt(dialog.chatId, 10);
			}
			else if (Type.isNumber(dialog.id) || Type.isStringFilled(dialog.id))
			{
				result.chatId = Number(dialog.id);
			}

			if (Type.isStringFilled(dialog.type))
			{
				result.type = dialog.type.toString();
			}

			if (Type.isNumber(dialog.name) || Type.isStringFilled(dialog.name))
			{
				result.name = dialog.name.toString();
			}

			if (Type.isString(dialog.description))
			{
				result.description = dialog.description;
			}

			if (Type.isString(dialog.avatar))
			{
				result.avatar = dialog.avatar;
			}

			if (Type.isStringFilled(dialog.color))
			{
				result.color = dialog.color;
			}

			if (Type.isBoolean(dialog.extranet))
			{
				result.extranet = dialog.extranet;
			}

			if (Type.isNumber(dialog.counter) || Type.isStringFilled(dialog.counter))
			{
				result.counter = Number.parseInt(dialog.counter, 10);
			}

			if (Type.isNumber(dialog.userCounter) || Type.isStringFilled(dialog.userCounter))
			{
				result.userCounter = Number.parseInt(dialog.userCounter, 10);
			}
			else if (Type.isNumber(dialog.user_counter) || Type.isStringFilled(dialog.user_counter))
			{
				result.userCounter = Number.parseInt(dialog.user_counter, 10);
			}

			if (Type.isNumber(dialog.lastId))
			{
				result.lastReadId = dialog.lastId;
			}

			if (Type.isNumber(dialog.last_id))
			{
				result.lastReadId = dialog.last_id;
			}

			if (Type.isNumber(dialog.markedId))
			{
				result.markedId = dialog.markedId;
			}

			if (Type.isNumber(dialog.lastMessageId) || Type.isStringFilled(dialog.lastMessageId))
			{
				result.lastMessageId = Number.parseInt(dialog.lastMessageId, 10);
			}

			if (Type.isNumber(dialog.last_message_id) || Type.isStringFilled(dialog.last_message_id))
			{
				result.lastMessageId = Number.parseInt(dialog.last_message_id, 10);
			}

			if (Type.isPlainObject(dialog.lastMessageViews))
			{
				result.lastMessageViews = dialog.lastMessageViews;
			}

			if (Type.isArray(dialog.muteList) || Type.isPlainObject(dialog.muteList))
			{
				result.muteList = dialog.muteList;
			}

			if (Type.isArray(dialog.mute_list) || Type.isPlainObject(dialog.mute_list))
			{
				result.muteList = dialog.mute_list;
			}

			if (Type.isArray(dialog.managerList) || Type.isPlainObject(dialog.managerList))
			{
				result.managerList = dialog.managerList;
			}

			if (Type.isArray(dialog.manager_list) || Type.isPlainObject(dialog.manager_list))
			{
				result.managerList = dialog.manager_list;
			}

			if (Type.isNumber(dialog.ownerId) || Type.isStringFilled(dialog.ownerId))
			{
				result.owner = Number.parseInt(dialog.ownerId, 10);
			}

			if (Type.isNumber(dialog.owner) || Type.isStringFilled(dialog.owner))
			{
				result.owner = dialog.owner;
			}

			if (Type.isStringFilled(dialog.entityType))
			{
				result.entityType = dialog.entityType;
			}

			if (Type.isStringFilled(dialog.entity_type))
			{
				result.entityType = dialog.entity_type;
			}

			if (Type.isNumber(dialog.entityId) || Type.isStringFilled(dialog.entityId))
			{
				result.entityId = dialog.entityId.toString();
			}

			if (Type.isNumber(dialog.entity_id) || Type.isStringFilled(dialog.entity_id))
			{
				result.entityId = dialog.entity_id.toString();
			}

			if (!Type.isUndefined(dialog.dateCreate))
			{
				result.dateCreate = DateHelper.cast(dialog.dateCreate);
			}

			if (!Type.isUndefined(dialog.date_create))
			{
				result.dateCreate = DateHelper.cast(dialog.date_create);
			}

			if (Type.isPlainObject(dialog.public))
			{
				result.public = {};

				if (Type.isStringFilled(dialog.public.code))
				{
					result.public.code = dialog.public.code;
				}

				if (Type.isStringFilled(dialog.public.link))
				{
					result.public.link = dialog.public.link;
				}
			}

			if (Type.isNumber(dialog.diskFolderId))
			{
				result.diskFolderId = dialog.diskFolderId;
			}

			if (Type.isNumber(dialog.disk_folder_id))
			{
				result.diskFolderId = dialog.disk_folder_id;
			}

			if (Type.isStringFilled(dialog.aiProvider))
			{
				result.aiProvider = dialog.aiProvider;
			}

			if (Type.isStringFilled(dialog.ai_provider))
			{
				result.aiProvider = dialog.ai_provider;
			}

			if (Type.isStringFilled(dialog.role))
			{
				result.role = dialog.role;
			}

			result.permissions = {};
			if (Type.isObject(dialog.permissions))
			{
				result.permissions = this.validatePermissionsFromRest(dialog.permissions);
			}

			result.permissions = mergeImmutable(ChatPermission.getActionGroupsByChatType(result.type), result.permissions);

			return result;
		}

		/**
		 * @private
		 * @param {object} fields
		 * @return {object}
		 */
		validatePermissionsFromRest(fields)
		{
			const result = {};
			if (Type.isStringFilled(fields.manage_users_add) || Type.isStringFilled(fields.manageUsersAdd))
			{
				result.manageUsersAdd = fields.manage_users_add || fields.manageUsersAdd;
			}

			if (Type.isStringFilled(fields.manage_users_delete) || Type.isStringFilled(fields.manageUsersDelete))
			{
				result.manageUsersDelete = fields.manage_users_delete || fields.manageUsersDelete;
			}

			if (Type.isStringFilled(fields.manage_ui) || Type.isStringFilled(fields.manageUi))
			{
				result.manageUi = fields.manage_ui || fields.manageUi;
			}

			if (Type.isStringFilled(fields.manage_settings) || Type.isStringFilled(fields.manageSettings))
			{
				result.manageSettings = fields.manage_settings || fields.manageSettings;
			}

			if (Type.isStringFilled(fields.can_post) || Type.isStringFilled(fields.canPost))
			{
				fields.manageMessages = fields.can_post || fields.canPost;
			}

			if (Type.isStringFilled(fields.manage_messages) || Type.isStringFilled(fields.manageMessages))
			{
				result.manageMessages = fields.manage_messages || fields.manageMessages;
			}

			return result;
		}

		/* region internal */

		/**
		 * @param {Array<string|number>} idList
		 * @param {boolean} wasCompletelySync
		 * @return {Promise<*>}
		 */
		async setWasCompletelySyncByIdList(idList, wasCompletelySync)
		{
			return this.internal.setWasCompletelySyncByIdList(idList, wasCompletelySync);
		}

		/**
		 * @param {Array<string|number>} idList
		 */
		async getWasCompletelySyncByIdList(idList)
		{
			if (!Feature.isLocalStorageEnabled || !Type.isArrayFilled(idList))
			{
				return {
					items: [],
				};
			}

			const dialogIdList = idList.map((id) => `'${id}'`).join(',');
			const selectResult = await this.dialogTable.executeSql({
				query: `
					SELECT
						d.dialogId as dialogId,
						d.chatId as chatId,
						d.lastMessageId as lastMessageId,
						MAX(m.id) as lastSyncMessageId,
						di.wasCompletelySync as wasCompletelySync
					FROM ${this.dialogTable.getName()} as d
					LEFT JOIN ${this.internal.dialogInternalTable.getName()} as di ON di.dialogId = d.dialogId
					LEFT JOIN ${this.messageTable.getName()} as m ON m.chatId = d.chatId
					WHERE d.dialogId IN (${dialogIdList})
					GROUP BY d.dialogId
				`,
			});

			const result = this.dialogTable.convertSelectResultToGetListResult(selectResult, false);
			result.items = result.items.map((dialog) => {
				const restoredDialog = dialog;
				restoredDialog.wasCompletelySync = restoredDialog.wasCompletelySync === '1';

				return restoredDialog;
			});

			logger.log('DialogRepository.getWasCompletelySyncByIdList complete: ', idList, result);

			return result;
		}

		/* endregion internal */
	}

	module.exports = {
		DialogRepository,
	};
});
