/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/recent
 */
jn.define('im/messenger/db/model-writer/vuex/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');

	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class RecentWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('recentModel/add', this.addRouter)
				.on('recentModel/update', this.addRouter)
				.on('recentModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('recentModel/add', this.addRouter)
				.off('recentModel/update', this.addRouter)
				.off('recentModel/delete', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'set',
				'update',
				'clearAllCounters',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			if (!Type.isArrayFilled(data.recentItemList))
			{
				return;
			}

			const itemIdCollection = {};
			data.recentItemList.forEach((item) => {
				if (!Type.isObject(item) || !Type.isObject(item.fields))
				{
					return;
				}

				itemIdCollection[item.fields.id] = true;
			});

			const recentItemList = this.store.getters['recentModel/getCollection']();
			let recentItemsToSave = recentItemList
				.filter((item) => itemIdCollection[item.id] === true)
			;
			recentItemsToSave = clone(recentItemsToSave);

			this.repository.recent.saveFromModel(recentItemsToSave);
		}

		deleteRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'delete',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			if (!data.id)
			{
				return;
			}

			this.repository.recent.deleteById(data.id);
		}
	}

	module.exports = {
		RecentWriter,
	};
});
