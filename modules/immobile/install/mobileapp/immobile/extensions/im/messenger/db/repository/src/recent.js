/**
 * @module im/messenger/db/repository/recent
 */
jn.define('im/messenger/db/repository/recent', (require, exports, module) => {
	const { Type } = require('type');

	const {
		RecentTable,
	} = require('im/messenger/db/table');

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

		async saveFromModel(recantList)
		{
			const recentListToAdd = [];

			recantList.forEach((item) => {
				const itemToAdd = this.recentTable.validate(item);

				recentListToAdd.push(itemToAdd);
			});

			return this.recentTable.add(recentListToAdd, true);
		}

		async saveFromRest(recantList)
		{
			const recentListToAdd = [];

			recantList.forEach((item) => {
				const itemToAdd = this.validateRestItem(item);

				recentListToAdd.push(itemToAdd);
			});

			return this.recentTable.add(recentListToAdd, true);
		}

		async deleteById(dialogId)
		{
			return this.recentTable.deleteByIdList([dialogId]);
		}

		validateRestItem(item)
		{
			const result = {};

			return result;
		}
	}

	module.exports = {
		RecentRepository,
	};
});
