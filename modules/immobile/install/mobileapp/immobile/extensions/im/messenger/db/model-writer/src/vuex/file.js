/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/file
 */
jn.define('im/messenger/db/model-writer/vuex/file', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('repository--file');
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
		 * @param {MutationPayload<FilesAddData, FilesAddActions>} mutation.payload
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
			const fileList = [];

			this.store.getters['filesModel/getByIdList'](fileIdList).forEach((file) => {

				const dialogHelper = DialogHelper.createByChatId(file.chatId);
				if (!dialogHelper?.isLocalStorageSupported)
				{
					return;
				}

				fileList.push(file);
			});

			if (!Type.isArrayFilled(fileList))
			{
				return;
			}

			this.repository.file.saveFromModel(fileList)
				.catch((error) => logger.error('FileWriter.addRouter.saveFromModel.catch:', error));
		}

		/**
		 * @param {MutationPayload<FilesUpdateData, FilesUpdateActions>} mutation.payload
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

			const fileList = [];

			this.store.getters['filesModel/getByIdList'](fileIdList).forEach((file) => {
				const dialogHelper = DialogHelper.createByDialogId(file.dialogId);
				if (!dialogHelper?.isLocalStorageSupported)
				{
					return;
				}

				fileList.push(file);
			});

			if (!Type.isArrayFilled(fileList))
			{
				return;
			}

			this.repository.file.saveFromModel(fileList)
				.catch((error) => logger.error('FileWriter.updateRouter.saveFromModel.catch:', error));
		}

		/**
		 * @param {MutationPayload<FilesUpdateWithIdData, FilesUpdateWithIdActions>} mutation.payload
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

			const dialogHelper = DialogHelper.createByDialogId(file.dialogId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			this.repository.file.saveFromModel([file])
				.catch((error) => logger.error('FileWriter.updateWithIdRouter.saveFromModel.catch:', error));
		}

		/**
		 * @param {MutationPayload<FilesDeleteData, FilesDeleteActions>} mutation.payload
		 */
		deleteRouter(mutation)
		{}
	}

	module.exports = {
		FileWriter,
	};
});
