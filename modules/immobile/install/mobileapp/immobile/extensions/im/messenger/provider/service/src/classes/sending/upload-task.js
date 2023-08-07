/**
 * @module im/messenger/provider/service/classes/sending/upload-task
 */
jn.define('im/messenger/provider/service/classes/sending/upload-task', (require, exports, module) => {

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

			this.taskId = taskId;
			this.controller = 'disk.uf.integration.diskUploaderController';
			this.controllerOptions = {
				folderId,
			};
			this.resize = resize;
			this.type = type;
			this.mimeType = mimeType;
			this.chunk = chunk || megabyte;
			this.params = params;
			this.name = name;
			this.url = url;
		}
	}

	module.exports = {
		UploadTask,
	};
});
