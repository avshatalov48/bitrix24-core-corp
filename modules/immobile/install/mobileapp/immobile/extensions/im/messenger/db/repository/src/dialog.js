/**
 * @module im/messenger/db/repository/dialog
 */
jn.define('im/messenger/db/repository/dialog', (require, exports, module) => {
	const { Type } = require('type');

	const { Feature } = require('im/messenger/lib/feature');
	const {
		DialogTable,
	} = require('im/messenger/db/table');
	const { DateHelper } = require('im/messenger/lib/helper');

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
			this.dialogTable = new DialogTable();
		}

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

		async deleteById(dialogId)
		{
			return this.dialogTable.deleteByIdList([dialogId]);
		}

		async deleteByChatIdList(chatIdList)
		{
			return this.dialogTable.deleteByChatIdList(chatIdList);
		}

		async saveFromModel(dialogList)
		{
			const dialogListToAdd = [];

			dialogList.forEach((dialog) => {
				const dialogToAdd = this.dialogTable.validate(dialog);

				dialogListToAdd.push(dialogToAdd);
			});

			return this.dialogTable.add(dialogListToAdd, true);
		}

		async saveFromRest(dialogList)
		{
			const dialogListToAdd = [];

			dialogList.forEach((dialog) => {
				const dialogToAdd = this.validateRestDialog(dialog);

				dialogListToAdd.push(dialogToAdd);
			});

			return this.dialogTable.add(dialogListToAdd, true);
		}

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
				result.muteList = JSON.stringify(dialog.muteList);
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

			if (Type.isNumber(dialog.entityId) || Type.isStringFilled(dialog.entityId))
			{
				result.entityId = dialog.entityId.toString();
			}

			if (!Type.isUndefined(dialog.dateCreate))
			{
				result.dateCreate = DateHelper.cast(dialog.dateCreate).toISOString();
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

			return result;
		}
	}

	module.exports = {
		DialogRepository,
	};
});
