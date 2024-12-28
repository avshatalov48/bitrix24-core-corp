/**
 * @module im/messenger/provider/service/classes/sending/upload-manager
 */
jn.define('im/messenger/provider/service/classes/sending/upload-manager', (require, exports, module) => {
	/* global include, MediaConverter */
	include('MediaConverter');

	const { Type } = require('type');
	const { Filesystem } = require('native/filesystem');
	const { UploaderClient } = require('uploader/client');
	const {
		FileType,
		ComponentCode,
	} = require('im/messenger/const');
	const { UploadTask } = require('im/messenger/provider/service/classes/sending/upload-task');

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
		constructor(componentName = ComponentCode.imMessenger)
		{
			this.client = new UploaderClient(componentName);
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

		/**
		 * @param {UploadingMessageWithFile} messageWithFile
		 * @returns {Promise<{file: (*&{path, extension, size, name, start, end, type}), taskId}>}
		 */
		async addUploadTaskByMessage(messageWithFile)
		{
			const {
				dialogId,
				temporaryMessageId,
				temporaryFileId,
				deviceFile,
				diskFolderId,
			} = messageWithFile;

			let deviceFileUrl = deviceFile.url;
			const isiCloudFile = (
				deviceFile.url.startsWith('icloudvideo://')
				|| deviceFile.url.startsWith('icloudimage://')
			);

			if (
				Application.getPlatform() === 'ios'
				&& isiCloudFile
				&& Type.isFunction(MediaConverter.getUrliCloudFile)
			)
			{
				const iCloudFile = await MediaConverter.getUrliCloudFile(deviceFile.url);
				deviceFileUrl = iCloudFile.url;
			}

			const fileInfo = await Filesystem.getFile(deviceFileUrl);

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
				fileType === FileType.image && (deviceFile.height > 1080 && deviceFile.width > 1080)
			)
			{
				taskOptions.resize = {
					height: 1080,
					width: 1920,
					quality: 80,
				};
			}

			if (
				fileType === FileType.video
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

		/**
		 * @desc Returns prepare file data and done task
		 * @param {Object} file
		 * @return {object} {fileData: object, task: UploadTask}
		 */
		async getFileDataAndTask(file)
		{
			const {
				dialogId,
				temporaryMessageId,
				temporaryFileId,
				deviceFile,
				diskFolderId,
			} = file;

			let deviceFileUrl = deviceFile.url;
			const isiCloudFile = (
				deviceFile.url.startsWith('icloudvideo://')
				|| deviceFile.url.startsWith('icloudimage://')
			);

			if (
				Application.getPlatform() === 'ios'
				&& isiCloudFile
				&& Type.isFunction(MediaConverter.getUrliCloudFile)
			)
			{
				const iCloudFile = await MediaConverter.getUrliCloudFile(deviceFile.url);
				deviceFileUrl = iCloudFile.url;
			}

			const fileInfo = await Filesystem.getFile(deviceFileUrl);
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
				fileType === FileType.image && (deviceFile.height > 1080 && deviceFile.width > 1080)
			)
			{
				taskOptions.resize = {
					height: 1080,
					width: 1920,
					quality: 80,
				};
			}

			if (
				fileType === FileType.video
			)
			{
				taskOptions.resize = {
					height: 1080,
					width: 1920,
					quality: 80,
				};
			}

			const task = new UploadTask(taskOptions);

			return {
				fileData: {
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
				},
				task,
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
