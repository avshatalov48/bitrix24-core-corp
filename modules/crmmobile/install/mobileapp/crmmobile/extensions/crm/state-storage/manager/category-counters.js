/**
 * @module crm/state-storage/manager/category-counters
 */
jn.define('crm/state-storage/manager/category-counters', (require, exports, module) => {
	const { Base } = require('crm/state-storage/manager/base');

	/**
	 * @class CategoryCountersStoreManager
	 */
	class CategoryCountersStoreManager extends Base
	{
		getStage(id)
		{
			return this.store.getters['categoryCountersModel/getStage'](id);
		}

		getStages()
		{
			return this.store.getters['categoryCountersModel/getStages'];
		}

		updateStage(stageId, data)
		{
			this.store.dispatch({
				type: 'categoryCountersModel/updateStage',
				stageId,
				data,
			});
		}

		clear()
		{
			this.store.dispatch('categoryCountersModel/clear');
		}

		init(entityTypeId, categoryId, params = {})
		{
			this.store.dispatch('categoryCountersModel/init', {
				entityTypeId,
				categoryId,
				params,
			});
		}

		getLoading()
		{
			return this.store.getters['categoryCountersModel/getLoading'];
		}
	}

	module.exports = { CategoryCountersStoreManager };
});
