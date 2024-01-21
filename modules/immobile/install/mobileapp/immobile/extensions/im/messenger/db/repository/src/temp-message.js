/**
 * @module im/messenger/db/repository/temp-message
 */
jn.define('im/messenger/db/repository/temp-message', (require, exports, module) => {
	const { Settings } = require('im/messenger/lib/settings');
	const {
		TempMessageTable,
	} = require('im/messenger/db/table');

	/**
	 * @class TempMessageRepository
	 */
	class TempMessageRepository
	{
		constructor()
		{
			this.tempMessageTable = new TempMessageTable();
		}

		/**
		 * @return {Promise<{messageList: []}>}
		 */
		async getList()
		{
			if (!Settings.isLocalStorageEnabled)
			{
				return {
					messageList: [],
				};
			}

			const messageList = await this.tempMessageTable.getList({});

			const modelMessageList = [];
			messageList.items.forEach((message) => {
				modelMessageList.push(message);
			});

			return {
				messageList: modelMessageList,
			};
		}

		async saveFromModel(messageList)
		{
			const messageListToAdd = [];

			messageList.forEach((message) => {
				const messageToAdd = this.tempMessageTable.validate(message);

				messageListToAdd.push(messageToAdd);
			});

			return this.tempMessageTable.add(messageListToAdd, true);
		}

		async deleteByIdList(idList)
		{
			return this.tempMessageTable.deleteByIdList(idList);
		}
	}

	module.exports = {
		TempMessageRepository,
	};
});
