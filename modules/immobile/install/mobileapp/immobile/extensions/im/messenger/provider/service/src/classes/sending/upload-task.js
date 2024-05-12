/**
 * @module im/messenger/provider/service/classes/sending/upload-task
 */
jn.define('im/messenger/provider/service/classes/sending/upload-task', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class UploadTask
	 */
	class UploadTask
	{
		constructor({
			taskId,
			resize,
			type,
			mimeType,
			chunk,
			folderId,
			params,
			name,
			url,
		})
		{
			const megabyte = 1024 * 1024;
			const cloudChunkSize = 5 * megabyte;
			const chunkSize = serviceLocator.get('core').isCloud() || serviceLocator.get('core').hasActiveCloudStorageBucket() ? cloudChunkSize : megabyte;

			this.taskId = taskId;
			this.controller = 'disk.uf.integration.diskUploaderController';
			this.controllerOptions = {
				folderId,
			};
			this.resize = resize;
			this.type = type;
			this.mimeType = mimeType;
			this.chunk = chunkSize;
			this.params = params;
			this.name = name;
			this.url = url;
		}
	}

	module.exports = {
		UploadTask,
	};
});
