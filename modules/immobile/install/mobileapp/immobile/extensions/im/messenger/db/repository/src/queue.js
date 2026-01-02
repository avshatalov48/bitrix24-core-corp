/**
 * @module im/messenger/db/repository/queue
 */
jn.define('im/messenger/db/repository/queue', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');
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
			if (!Feature.isLocalStorageEnabled)
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

		/**
		 * @param {Array<QueueModelState>} queue
		 */
		async saveFromModel(queue)
		{
			const requestListToAdd = [];
			queue.forEach((request) => {
				const requestToAdd = this.queueTable.validate(request);

				requestListToAdd.push(requestToAdd);
			});

			return this.queueTable.add(requestListToAdd, true);
		}

		/**
		* @param {Array<string>} idList
		*/
		async deleteByIdList(idList)
		{
			return this.queueTable.deleteByIdList(idList);
		}
	}

	module.exports = {
		QueueRepository,
	};
});
