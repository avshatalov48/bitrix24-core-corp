/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/uploader/uploader
 */
jn.define('im/messenger/lib/uploader/uploader', (require, exports, module) => {
	const { UploaderClient } = require('uploader/client');
	const { UploadTask } = require('im/messenger/lib/uploader/task');

	const ClientEvent = Object.freeze({
		done: 'done',
		progress: 'progress',
		error: 'error'
	});

	class Uploader
	{
		constructor()
		{
			this.client = new UploaderClient('im-messenger');
			this.onFileUploadDone = this.fileUploadDoneHandler.bind(this);
			this.onFileUploadProgress = this.fileUploadProgressHandler.bind(this);
			this.onFileUploadError = this.fileUploadErrorHandler.bind(this);

			this.subscribeClientEvents();
		}

		subscribeClientEvents()
		{
			this.client
				.on(ClientEvent.done, this.onFileUploadDone)
				.on(ClientEvent.progress, this.onFileUploadProgress)
				.on(ClientEvent.error, this.onFileUploadError)
			;
		}

		fileUploadDoneHandler(id, data)
		{
			console.warn('fileUploadDoneHandler:', id, data);
		}

		fileUploadProgressHandler(id, data)
		{
			console.warn('fileUploadProgressHandler:', id, data);
		}

		fileUploadErrorHandler(id, data)
		{
			console.error('fileUploadErrorHandler:', id, data);
		}

		addTask(task)
		{
			if (!(task instanceof UploadTask))
			{
				throw new Error('Uploader.addTask: task should be an instance of UploadTask');
			}

			this.client.addTask(task);
		}
	}

	module.exports = {
		Uploader,
		uploader: new Uploader(),
	};
});
