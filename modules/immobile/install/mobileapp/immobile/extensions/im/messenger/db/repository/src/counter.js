/**
 * @module im/messenger/db/repository/counter
 */
jn.define('im/messenger/db/repository/counter', (require, exports, module) => {
	const {
		CounterTable,
	} = require('im/messenger/db/table');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('repository--counter');

	/**
	 * @class CounterRepository
	 */
	class CounterRepository
	{
		constructor()
		{
			/**
			 * @type {CounterTable}
			 */
			this.counterTable = new CounterTable();
		}

		async saveFromModel(counterCollection)
		{
			const counterListToAdd = [];
			Object.values(counterCollection).forEach((counter) => {
				const counterToAdd = this.counterTable.validate(counter);

				counterListToAdd.push(counterToAdd);
			});

			logger.log('CounterRepository.saveFromModel counterListToAdd', counterListToAdd);

			return this.counterTable.add(counterListToAdd, true);
		}

		/**
		 * @return {Promise<Array>}
		 */
		async getAll()
		{
			const result = await this.counterTable.getList();

			logger.log('CounterRepository.getAll', result.items);

			return result.items;
		}

		/**
		 * @return {Promise<{items: Array}>}
		 */
		async clear()
		{
			return this.counterTable.truncate();
		}
	}

	module.exports = {
		CounterRepository,
	};
});
