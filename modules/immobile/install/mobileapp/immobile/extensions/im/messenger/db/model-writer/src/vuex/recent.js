/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/recent
 */
jn.define('im/messenger/db/model-writer/vuex/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const logger = getLoggerWithContext('writer--recent', 'RecentWriter');

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
				'setFirstPageByTab',
			];

			if (!saveActions.includes(actionName))
			{
				logger.warn('addRouter skip: unknown action', actionName);

				return;
			}

			if (!Type.isArrayFilled(data.recentItemList))
			{
				logger.error('addRouter skip: empty recentItemList');

				return;
			}

			const itemIdCollection = {};
			data.recentItemList.forEach((item) => {
				if (!Type.isObject(item) || !Type.isObject(item.fields))
				{
					return;
				}

				const dialogHelper = DialogHelper.createByDialogId(item.fields.id);
				if (!dialogHelper?.isLocalStorageSupported)
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

			logger.log('addRouter saving recent items...', recentItemsToSave.length);

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
				logger.warn('deleteRouter skip: unknown action', actionName);

				return;
			}

			if (!data.id)
			{
				logger.error('deleteRouter skip: no data id');

				return;
			}

			logger.log('deleteRouter deleting recent item id...', data.id);

			this.repository.recent.deleteById(data.id);
		}
	}

	module.exports = {
		RecentWriter,
	};
});
