/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/sidebar/file
 */
jn.define('im/messenger/db/model-writer/vuex/sidebar/file', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('repository--sidebar-file');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class SidebarFileWriter extends Writer
	{
		subscribeEvents()
		{
			// TODO: The back is not ready yet

			// this.storeManager
			// 	.on('sidebarModel/sidebarFilesModel/set', this.addRouter)
			// 	.on('sidebarModel/sidebarFilesModel/delete', this.deleteRouter)
			// ;
		}

		unsubscribeEvents()
		{
			// TODO: The back is not ready yet

			// this.storeManager
			// 	.off('sidebarModel/sidebarFilesModel/set', this.addRouter)
			// 	.off('sidebarModel/sidebarFilesModel/delete', this.deleteRouter)
			// ;
		}

		/**
		 * @param {MutationPayload<SidebarFilesSetData, SidebarFilesSetActions>} mutation.payload
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
			const isPermittedAction = saveActions.includes(actionName);
			const isFilesMap = Type.isMap(data.files);

			if (!isPermittedAction || !isFilesMap)
			{
				return;
			}

			const fileList = [...data.files]
				.map(([_, value]) => ({
					...value,
					subType: data.subType,
				}));

			this.repository.sidebarFile.saveFromModel(fileList)
				.catch((error) => logger.error('FileWriter.addRouter.saveFromModel.catch:', error));
		}

		/**
		 * @param {MutationPayload<SidebarFilesDeleteData, SidebarFilesDeleteActions>} mutation.payload
		 */
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
			const isPermittedAction = saveActions.includes(actionName);
			const isvalidId = Type.isNumber(data.id);

			if (!isPermittedAction || !isvalidId)
			{
				return;
			}

			void this.repository.sidebarFile.deleteById(data.id);
		}
	}

	module.exports = {
		SidebarFileWriter,
	};
});
