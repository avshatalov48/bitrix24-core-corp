/**
 * @module im/messenger/db/repository/src/draft
 */
jn.define('im/messenger/db/repository/src/draft', (require, exports, module) => {

	const { DraftTable } = require('im/messenger/db/table');

	/**
	 * @class DraftRepository
	 */
	class DraftRepository
	{
		constructor()
		{
			this.draftTable = new DraftTable();
		}

		/**
		 * @param {DraftAddData} draftItem
		 * @returns {Promise<void>}
		 */
		async saveFromModel(draftItem)
		{
			const { fields } = draftItem;
			const items = [this.draftTable.validate(fields)];

			return this.draftTable.add(items, true);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		async deleteById(dialogId)
		{
			return this.draftTable.deleteByIdList([dialogId]);
		}

		/**
		 * @returns {Promise<DraftStoredData[]>}
		 */
		async getDraftList()
		{
			const { items } = await this.draftTable.getDraftList() || {};

			return items;
		}
	}

	module.exports = {
		DraftRepository,
	};
});
