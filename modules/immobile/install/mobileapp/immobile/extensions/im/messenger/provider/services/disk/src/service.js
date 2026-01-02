/**
 * @module im/messenger/provider/services/disk/service
 */
jn.define('im/messenger/provider/services/disk/service', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');

	/**
	 * @class DiskService
	 */
	class DiskService
	{
		/**
		 * @param {ChatId} chatId
		 * @param {FileId} fileId
		 */
		delete({ chatId, fileId })
		{
			const queryParams = {
				chat_id: chatId,
				file_id: fileId,
			};

			return BX.rest.callMethod(RestMethod.imDiskFileDelete, queryParams).catch((error) => {
				Logger.error(`${this.constructor.name}.delete error: `, error);
				throw new Error(error);
			});
		}

		/**
		 * @param {Array<FileId>} fileIds
		 * @returns {*}
		 */
		save(fileIds)
		{
			const ids = fileIds && !Array.isArray(fileIds) ? [fileIds] : fileIds;
			const queryParams = { ids };

			return BX.rest.callMethod(RestMethod.imV2DiskFileSave, queryParams).catch((error) => {
				Logger.error(`${this.constructor.name}.save error: `, error);
				throw new Error(error);
			});
		}

		/**
		 * @param {ChatId} chatId
		 * @param {FileId} fileId
		 * @returns {Promise}
		 */
		transcribe({ chatId, fileId })
		{
			if (!chatId)
			{
				return Promise.reject(new Error(`${this.constructor.name}.transcribe: chatId not found`));
			}

			if (!fileId)
			{
				return Promise.reject(new Error(`${this.constructor.name}.transcribe: fileId not found`));
			}

			const data = { chatId, fileId };

			return runAction(RestMethod.imV2DiskFileTranscribe, { data });
		}
	}

	module.exports = {
		DiskService,
	};
});
