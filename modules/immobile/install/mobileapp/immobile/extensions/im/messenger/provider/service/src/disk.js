/**
 * @module im/messenger/provider/service/disk
 */
jn.define('im/messenger/provider/service/disk', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const');

	/**
	 * @class DiskService
	 */
	class DiskService
	{
		delete({ chatId, fileId })
		{
			const queryParams = {
				chat_id: chatId,
				file_id: fileId,
			};

			return BX.rest.callMethod(RestMethod.imDiskFileDelete, queryParams).catch((error) => {
				Logger.error('DiskService.delete error: ', error);
			});
		}

		save(fileId)
		{
			const queryParams = {
				file_id: fileId,
			};

			return BX.rest.callMethod(RestMethod.imDiskFileSave, queryParams).catch((error) => {
				Logger.error('DiskService.save error: ', error);
			});
		}
	}

	module.exports = {
		DiskService,
	};
});
