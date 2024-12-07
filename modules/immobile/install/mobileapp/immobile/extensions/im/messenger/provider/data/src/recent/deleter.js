/**
 * @module im/messenger/provider/data/recent/deleter
 */
jn.define('im/messenger/provider/data/recent/deleter', (require, exports, module) => {
	const { BaseDataProvider } = require('im/messenger/provider/data/base');

	/**
	 * @class RecentDeleter
	 */
	class RecentDeleter extends BaseDataProvider
	{
		async delete(dialogId)
		{
			await this.deleteFromModel(dialogId);
			await this.deleteFromDatabase(dialogId);
		}

		async deleteFromModel(dialogId)
		{
			return this.store.dispatch('recentModel/deleteFromModel', {
				id: dialogId,
			});
		}

		async deleteFromDatabase(dialogId)
		{
			return this.repository.recent.deleteById(dialogId);
		}
	}

	module.exports = { RecentDeleter };
});
