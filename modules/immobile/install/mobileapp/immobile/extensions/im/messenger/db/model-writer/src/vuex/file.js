/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/file
 */
jn.define('im/messenger/db/model-writer/vuex/file', (require, exports, module) => {
	const { Type } = require('type');

	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class FileWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('filesModel/add', this.addRouter)
				.on('filesModel/update', this.updateRouter)
				.on('filesModel/updateWithId', this.updateWithIdRouter)
				.on('filesModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('filesModel/add', this.addRouter)
				.off('filesModel/update', this.updateRouter)
				.off('filesModel/updateWithId', this.updateWithIdRouter)
				.off('filesModel/delete', this.deleteRouter)
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
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			if (!Type.isArrayFilled(data.fileList))
			{
				return;
			}

			const fileIdList = [];
			data.fileList.forEach((file) => {
				fileIdList.push(file.id);
			});
			const fileList = this.store.getters['filesModel/getByIdList'](fileIdList);

			this.repository.file.saveFromModel(fileList);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		updateRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const updateActions = [
				'set',
			];
			if (!updateActions.includes(actionName))
			{
				return;
			}

			if (!Type.isArrayFilled(data.fileList))
			{
				return;
			}

			const fileIdList = [];
			data.fileList.forEach((file) => {
				if (!Type.isNumber(file.id))
				{
					return;
				}

				fileIdList.push(file.id);
			});

			const fileList = this.store.getters['filesModel/getByIdList'](fileIdList);
			if (!Type.isArrayFilled(fileList))
			{
				return;
			}

			this.repository.file.saveFromModel(fileList);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		updateWithIdRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const updateActions = [
				'updateWithId',
			];
			if (!updateActions.includes(actionName))
			{
				return;
			}

			const fileId = data.fields?.id;
			if (!Type.isNumber(fileId))
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](fileId);
			if (!file)
			{
				return;
			}

			this.repository.file.saveFromModel([file]);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		deleteRouter(mutation)
		{}
	}

	module.exports = {
		FileWriter,
	};
});
