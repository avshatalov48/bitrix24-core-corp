/**
 * @module im/messenger/db/repository/copilot
 */
jn.define('im/messenger/db/repository/copilot', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');
	const {
		CopilotTable,
	} = require('im/messenger/db/table');
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('repository--copilot');

	/**
	 * @class CopilotRepository
	 */
	class CopilotRepository
	{
		constructor()
		{
			this.copilotTable = new CopilotTable();
		}

		/**
		 * @return {Promise<[]>}
		 */
		async getList()
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return [];
			}

			const copilotItemsData = await this.copilotTable.getList({});

			const copilotItems = [];
			copilotItemsData.items.forEach((copilotItem) => {
				copilotItems.push(copilotItem);
			});
			logger.log(`${this.constructor.name}.getList.items:`, copilotItems);

			return copilotItems;
		}

		async saveFromModel(copilotItems)
		{
			const copilotItemsToAdd = [];

			copilotItems.forEach((copilotItem) => {
				const requestToAdd = this.validateCopilotItem(copilotItem);

				copilotItemsToAdd.push(requestToAdd);
			});
			logger.log(`${this.constructor.name}.saveFromModel.items:`, copilotItemsToAdd);

			return this.copilotTable.add(copilotItemsToAdd, true);
		}

		/**
		 * @param {Array<DialogId>} idList
		 */
		async deleteByIdList(idList)
		{
			return this.copilotTable.deleteByIdList(idList);
		}

		/**
		 * @return {object}
		 */
		validateCopilotItem(copilotItem)
		{
			const result = {
				dialogId: 'chat0',
				roles: '{}',
				aiProvider: '',
				chats: '[]',
				messages: '[]',
			};

			if (Type.isStringFilled(copilotItem.aiProvider))
			{
				result.aiProvider = copilotItem.aiProvider;
			}

			if (Type.isStringFilled(copilotItem.dialogId))
			{
				result.dialogId = copilotItem.dialogId;
			}

			if (Type.isPlainObject(copilotItem.roles) && Object.keys(copilotItem.roles).length > 0)
			{
				result.roles = JSON.stringify(copilotItem.roles);
			}

			if (Type.isArrayFilled(copilotItem.chats))
			{
				result.chats = JSON.stringify(copilotItem.chats);
			}

			if (Type.isArrayFilled(copilotItem.messages))
			{
				result.messages = JSON.stringify(copilotItem.messages);
			}

			return result;
		}
	}

	module.exports = {
		CopilotRepository,
	};
});
