/**
 * @module im/messenger/provider/service/classes/sending/upload-manager
 */
jn.define('im/messenger/provider/service/classes/sending/upload-manager', (require, exports, module) => {
	const {
		FileType,
	} = require('im/messenger/const');
	const { UploaderClient } = require('uploader/client');
	const { UploadTask } = require('im/messenger/provider/service/classes/sending/upload-task');
	const { Filesystem } = require('native/filesystem');

	const UploaderManagerEvent = Object.freeze({
		done: 'done',
		progress: 'progress',
		error: 'error',
	});

	const UploaderClientEvent = Object.freeze({
		done: 'done',
		progress: 'progress',
		error: 'error',
	});

	/**
	 * @class UploadManager
	 */
	class UploadManager
	{
		constructor()
		{
			this.client = new UploaderClient('im-messenger');
			this.eventEmitter = new JNEventEmitter();
			this.onFileUploadDone = this.fileUploadDoneHandler.bind(this);
			this.onFileUploadProgress = this.fileUploadProgressHandler.bind(this);
			this.onFileUploadError = this.fileUploadErrorHandler.bind(this);

			this.subscribeClientEvents();
		}

		subscribeClientEvents()
		{
			this.client
				.on(UploaderClientEvent.done, this.onFileUploadDone)
				.on(UploaderClientEvent.progress, this.onFileUploadProgress)
				.on(UploaderClientEvent.error, this.onFileUploadError)
			;
		}

		fileUploadProgressHandler(id, data)
		{
			this.eventEmitter.emit(UploaderManagerEvent.progress, [id, data]);
		}

		fileUploadDoneHandler(id, data)
		{
			this.eventEmitter.emit(UploaderManagerEvent.done, [id, data]);
		}

		fileUploadErrorHandler(id, data)
		{
			this.eventEmitter.emit(UploaderManagerEvent.error, [id, data]);
		}

		async addUploadTaskByMessage(messageWithFile)
		{
			const {
				dialogId,
				temporaryMessageId,
				temporaryFileId,
				deviceFile,
				diskFolderId,
			} = messageWithFile;

			const fileInfo = await Filesystem.getFile(deviceFile.url);

			const taskOptions = {
				taskId: temporaryFileId,
				resize: false,
				type: fileInfo.type,
				mimeType: fileInfo.type,
				folderId: diskFolderId,
				name: fileInfo.name,
				url: deviceFile.url,
				params: {
					dialogId,
					temporaryMessageId,
				},
			};

			const fileType = fileInfo.type.split('/')[0];
			if (
				fileType === FileType.image
				|| fileType === FileType.video
			)
			{
				taskOptions.resize = {
					height: 1080,
					width: 1920,
					quality: 80,
				};
			}

			const task = new UploadTask(taskOptions);
			this.client.addTask(task);

			return {
				taskId: temporaryFileId,
				file: {
					...deviceFile,
					name: fileInfo.name,
					size: fileInfo.size,
					extension: fileInfo.extension,
					path: fileInfo.path,
					type: fileInfo.type,
					start: fileInfo.start,
					end: fileInfo.end,
				},
			};
		}

		cancelTask(id)
		{
			this.client.cancelTask(id);
		}

		on(eventName, eventHandler)
		{
			this.eventEmitter.on(eventName, eventHandler);

			return this;
		}

		once(eventName, eventHandler)
		{
			this.eventEmitter.once(eventName, eventHandler);

			return this;
		}

		off(eventName, eventHandler)
		{
			this.eventEmitter.off(eventName, eventHandler);

			return this;
		}
	}

	module.exports = {
		UploadManager,
		UploaderManagerEvent,
	};
});
