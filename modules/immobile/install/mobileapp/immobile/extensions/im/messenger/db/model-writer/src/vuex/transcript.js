/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/transcript
 */
jn.define('im/messenger/db/model-writer/vuex/transcript', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('repository--file');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class TranscriptWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('filesModel/transcriptModel/add', this.addRouter)
				.on('filesModel/transcriptModel/update', this.updateRouter)
				.on('filesModel/transcriptModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('filesModel/transcriptModel/add', this.addRouter)
				.off('filesModel/transcriptModel/update', this.updateRouter)
				.off('filesModel/transcriptModel/delete', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload<TranscriptAddData, TranscriptAddActions>} mutation.payload
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

			if (!Type.isArrayFilled(data.transcriptList))
			{
				return;
			}

			const transcriptList = data.transcriptList.filter((transcript) => {
				const dialogHelper = DialogHelper.createByChatId(transcript.chatId);

				return dialogHelper?.isLocalStorageSupported;
			});

			if (!Type.isArrayFilled(transcriptList))
			{
				return;
			}

			this.repository.transcript.saveFromModel(transcriptList)
				.catch((error) => logger.error(`${this.constructor.name}.addRouter.saveFromModel.catch:`, error));
		}

		/**
		 * @param {MutationPayload<TranscriptUpdateData, TranscriptUpdateActions>} mutation.payload
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
				'toggleText',
			];
			if (!updateActions.includes(actionName))
			{
				return;
			}

			if (!Type.isArrayFilled(data.transcriptList))
			{
				return;
			}

			const transcriptList = data.transcriptList.filter((transcript) => {
				const dialogHelper = DialogHelper.createByChatId(transcript.chatId);

				return dialogHelper?.isLocalStorageSupported;
			});

			if (!Type.isArrayFilled(transcriptList))
			{
				return;
			}

			this.repository.transcript.saveFromModel(transcriptList)
				.catch((error) => logger.error(`${this.constructor.name}.updateRouter.saveFromModel.catch:`, error));
		}

		/**
		 * @param {MutationPayload<TranscriptDeleteData, TranscriptDeleteActions>} mutation.payload
		 */
		deleteRouter(mutation)
		{
			if (!this.checkIsValidMutation(mutation))
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const deleteActions = [
				'delete',
			];
			if (!deleteActions.includes(actionName))
			{
				return;
			}

			if (!Type.isNumber(data.fileId))
			{
				return;
			}

			this.repository.transcript.deleteByFileId(data.fileId)
				.catch((error) => logger.error(`${this.constructor.name}.deleteRouter.deleteByFileId.catch:`, error));
		}
	}

	module.exports = {
		TranscriptWriter,
	};
});
