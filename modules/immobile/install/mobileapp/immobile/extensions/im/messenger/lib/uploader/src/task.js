/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/uploader/task
 */
jn.define('im/messenger/lib/uploader/task', (require, exports, module) => {
	const {
		FileType,
	} = require('im/messenger/const');
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
			this.resize = resize;
			this.type = type;
			this.mimeType = mimeType;
			this.chunk = chunk || megabyte;
			this.folderId = 1;
			this.params = params;
			this.name = name;
			this.url = url;
		}

		static createFromFile(modelFile)
		{
			const taskOptions = {
				taskId: 'im-messenger-image-upload' + modelFile.id,
				type: modelFile.extension,
				mimeType: null,
				name: modelFile.name,
				url: null,
			};

			const fileType = 'image';
			// let needConvert = fileType === FileType.image && message.file.type !== 'image/gif' || fileType === FileType.video;
			// if (needConvert)
			// {
			// 	taskOptions.resize = {
			// 		quality: 80,
			// 		width: 1920,
			// 		height: 1080,
			// 	};
			// }


			return new this(taskOptions);
		}
	}

	module.exports = {
		UploadTask,
	};
});
