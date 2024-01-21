/**
 * @module im/messenger/db/repository/queue
 */
jn.define('im/messenger/db/repository/queue', (require, exports, module) => {
	const { Settings } = require('im/messenger/lib/settings');
	const {
		QueueTable,
	} = require('im/messenger/db/table');

	/**
	 * @class QueueRepository
	 */
	class QueueRepository
	{
		constructor()
		{
			this.queueTable = new QueueTable();
		}

		/**
		 * @return {Promise<[]>}
		 */
		async getList()
		{
			if (!Settings.isLocalStorageEnabled)
			{
				return [];
			}

			const queueList = await this.queueTable.getList({});

			const modelMessageList = [];
			queueList.items.forEach((message) => {
				modelMessageList.push(message);
			});

			return modelMessageList;
		}

		async saveFromModel(queue)
		{
			const requestListToAdd = [];

			queue.forEach((request) => {
				const requestToAdd = this.queueTable.validate(request);

				requestListToAdd.push(requestToAdd);
			});

			return this.queueTable.add(requestListToAdd, true);
		}

		async deleteByIdList(idList)
		{
			return this.queueTable.deleteByIdList(idList);
		}
	}

	module.exports = {
		QueueRepository,
	};
});
