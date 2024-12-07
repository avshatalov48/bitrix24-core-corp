/**
 * @module im/messenger/db/repository/recent
 */
jn.define('im/messenger/db/repository/recent', (require, exports, module) => {
	const {
		RecentTable,
	} = require('im/messenger/db/table');
	const { validateRestItem } = require('im/messenger/db/repository/validators/recent');

	/**
	 * @class RecentRepository
	 */
	class RecentRepository
	{
		constructor()
		{
			this.recentTable = new RecentTable();
		}

		async getList()
		{
			return [];
		}

		/**
		 * @param {PinnedListByDialogTypeFilter} filter
		 * @return {Promise<{items: Array, users: Array}>}
		 */
		async getPinnedListByDialogTypeFilter(filter = {})
		{
			return this.recentTable.getPinnedListByDialogTypeFilter(filter);
		}

		/**
		 * @param {ListByDialogTypeFilter} filter
		 * @return {Promise<{items: Array, users: Array, messages: Array, files: Array, hasMore: boolean}>}
		*/
		async getListByDialogTypeFilter(filter = {})
		{
			return this.recentTable.getListByDialogTypeFilter(filter);
		}

		async saveFromModel(recentList)
		{
			const recentListToAdd = [];

			recentList.forEach((item) => {
				const itemToAdd = this.recentTable.validate(item);

				recentListToAdd.push(itemToAdd);
			});

			return this.recentTable.add(recentListToAdd, true);
		}

		/**
		 * @param {SyncListResult['addedRecent']} recentList
		 * @return {Promise<*>}
		 */
		async saveFromRest(recentList)
		{
			const recentListToAdd = [];

			recentList.forEach((item) => {
				const restItemToAdd = validateRestItem(item);
				const itemToAdd = this.recentTable.validate(restItemToAdd);

				recentListToAdd.push(itemToAdd);
			});

			return this.recentTable.add(recentListToAdd, true);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		async deleteById(dialogId)
		{
			return this.recentTable.deleteByIdList([dialogId]);
		}
	}

	module.exports = {
		RecentRepository,
	};
});
