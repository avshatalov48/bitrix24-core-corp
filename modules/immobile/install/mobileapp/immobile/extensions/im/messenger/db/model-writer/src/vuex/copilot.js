/**
 * @module im/messenger/db/model-writer/vuex/copilot
 */
jn.define('im/messenger/db/model-writer/vuex/copilot', (require, exports, module) => {
	const { Type } = require('type');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class CopilotWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();
			this.addCollectionRouter = this.addCollectionRouter.bind(this);
		}

		subscribeEvents()
		{
			this.storeManager
				.on('dialoguesModel/copilotModel/add', this.addRouter)
				.on('dialoguesModel/copilotModel/addCollection', this.addCollectionRouter)
				.on('dialoguesModel/copilotModel/update', this.addRouter)
				.on('dialoguesModel/copilotModel/updateCollection', this.addCollectionRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('dialoguesModel/copilotModel/add', this.addRouter)
				.off('dialoguesModel/copilotModel/addCollection', this.addCollectionRouter)
				.off('dialoguesModel/copilotModel/update', this.addRouter)
				.off('dialoguesModel/copilotModel/updateCollection', this.addCollectionRouter)
			;
		}

		/**
		 * @param {
		 * MutationPayload<CopilotAddData|CopilotUpdateData, CopilotAddActions|CopilotUpdateActions>
		 *     } mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const data = mutation?.payload?.data || {};
			if (!Type.isStringFilled(data.dialogId))
			{
				return;
			}

			const copilotModelState = this.store.getters['dialoguesModel/copilotModel/getByDialogId'](data.dialogId);

			this.repository.copilot.saveFromModel([copilotModelState]);
		}

		/**
		 * @param {
		 * MutationPayload<CopilotAddCollectionData|CopilotUpdateCollectionData, CopilotAddActions|CopilotUpdateActions>
		 *     } mutation.payload
		 */
		addCollectionRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const data = mutation?.payload?.data || {};
			const copilotItems = [];
			if (data.addItems)
			{
				data.addItems.forEach((item) => {
					if (Type.isStringFilled(item.dialogId))
					{
						const copilotModelState = this.store.getters['dialoguesModel/copilotModel/getByDialogId'](item.dialogId);
						copilotItems.push(copilotModelState);
					}
				});
			}

			if (data.updateItems)
			{
				data.updateItems.forEach((item) => {
					if (Type.isStringFilled(item.dialogId))
					{
						const copilotModelState = this.store.getters['dialoguesModel/copilotModel/getByDialogId'](item.dialogId);
						copilotItems.push(copilotModelState);
					}
				});
			}

			if (!Type.isArrayFilled(copilotItems))
			{
				return;
			}

			this.repository.copilot.saveFromModel(copilotItems);
		}
	}

	module.exports = {
		CopilotWriter,
	};
});
