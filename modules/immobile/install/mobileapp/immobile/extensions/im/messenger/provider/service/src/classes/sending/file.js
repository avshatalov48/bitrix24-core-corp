/**
 * @module im/messenger/provider/service/classes/sending/file
 */
jn.define('im/messenger/provider/service/classes/sending/file', (require, exports, module) => {
	const {
		getExtension,
	} = require('utils/file');

	const {
		FileStatus,
		FileType,
	} = require('im/messenger/const');
	const { getFileTypeByExtension } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const');
	const { core } = require('im/messenger/core');
	const {
		UploadManager,
		UploaderManagerEvent,
	} = require('im/messenger/provider/service/classes/sending/upload-manager');

	/**
	 * @class FileService
	 */
	class FileService
	{
		constructor()
		{
			/** @private */
			this.store = core.getStore();

			/** @private */
			this.isRequestingDiskFolderId = false;

			/** @private */
			this.diskFolderIdRequestPromiseCollection = {};

			/** @private */
			this.uploadRegistry = {};

			this.initUploadManager();
		}

		initUploadManager()
		{
			/** @private */
			this.uploadManager = new UploadManager();
			this.uploadManager
				.on(UploaderManagerEvent.progress, (fileId, data) => {
					Logger.info('UploaderManagerEvent.progress', fileId, data);
					const params = data.file.params;

					this.updateFileProgress(fileId, data.percent, FileStatus.upload);
					// to remove the cross to cancel the download
					if (data.percent === 100)
					{
						this.updateMessageSending(params.temporaryMessageId, false);
					}
				})
				.on(UploaderManagerEvent.done, (fileId, data) => {
					Logger.info('UploaderManagerEvent.done', fileId, data);

					const params = data.file.params;
					const file = data.result.data.file;

					this.updateFileProgress(fileId, 100, FileStatus.wait);
					if (!this.uploadRegistry[fileId])
					{
						Logger.warn('UploaderManagerEvent.done: file upload was canceled: ', fileId, data);

						return;
					}

					this.commitFile({
						chatId: this.getDialog(params.dialogId).chatId,
						temporaryMessageId: params.temporaryMessageId,
						temporaryFileId: fileId,
						realFileId: file.customData.fileId,
						fromDisk: false,
					});
				})
				.on(UploaderManagerEvent.error, (fileId, data) => {
					Logger.error('UploaderManagerEvent.error', fileId, data);

					this.updateFileProgress(fileId, 0, FileStatus.error);
				})
			;
		}

		getDiskFolderId(dialogId)
		{
			if (this.getDiskFolderIdFromModel(dialogId) > 0)
			{
				return Promise.resolve(this.getDiskFolderIdFromModel(dialogId));
			}

			if (this.isRequestingDiskFolderId)
			{
				return this.diskFolderIdRequestPromiseCollection[dialogId];
			}

			this.diskFolderIdRequestPromiseCollection[dialogId] = this.requestDiskFolderId(dialogId);

			return this.diskFolderIdRequestPromiseCollection[dialogId];
		}

		uploadFileFromDisk(messageWithFile)
		{
			return this.addFileFromDiskToModel(messageWithFile);
		}

		/**
		 * @private
		 */
		addFileFromDiskToModel(messageWithFile)
		{
			const extension = getExtension(messageWithFile.file.name);

			return this.store.dispatch('filesModel/set', {
				id: messageWithFile.temporaryFileId,
				chatId: messageWithFile.chatId,
				authorId: core.getUserId(),
				name: messageWithFile.file.name,
				type: getFileTypeByExtension(extension),
				extension,
				status: FileStatus.wait,
				progress: 0,
				authorName: this.getCurrentUser().name,
			});
		}

		async uploadFile(messageWithFile)
		{
			this.addFileToUploadRegistry(messageWithFile.temporaryFileId, messageWithFile);
			const uploadTask = await this.uploadManager.addUploadTaskByMessage(messageWithFile);

			return this.addFileToModelByUploadTask(uploadTask);
		}

		cancelFileUpload(temporaryMessageId, temporaryFileId)
		{
			Object.entries(this.uploadRegistry).some(([taskId, task]) => {
				if (task.temporaryMessageId === temporaryMessageId && task.temporaryFileId === temporaryFileId)
				{
					this.uploadManager.cancelTask(taskId);
					delete this.uploadRegistry[taskId];

					Logger.warn('FileService.cancelFileUpload', temporaryMessageId, temporaryFileId, taskId, task);

					return true;
				}

				return false;
			});

			// eslint-disable-next-line promise/catch-or-return
			this.store.dispatch('messagesModel/delete', { id: temporaryMessageId }).then(() => {
				this.store.dispatch('filesModel/delete', { id: temporaryFileId });
			});
		}

		/**
		 * @private
		 */
		async addFileToModelByUploadTask(uploadTask)
		{
			const { taskId, file } = uploadTask;

			const fileType = this.getFileType(file);
			const previewData = {};
			if (fileType === FileType.image)
			{
				previewData.image = {
					width: file.width,
					height: file.height,
				};
			}

			return this.store.dispatch('filesModel/set', {
				id: taskId,
				dialogId: this.getDialog().dialogId,
				chatId: this.getDialog().chatId,
				authorId: core.getUserId(),
				name: file.name,
				type: this.getFileType(file),
				extension: file.extension,
				size: file.size,
				status: FileStatus.progress,
				progress: 0,
				authorName: this.getCurrentUser().name,
				urlPreview: file.previewUrl,
				localUrl: file.path,
				...previewData,
			});
		}

		/**
		 * @private
		 */
		updateFileProgress(id, progress, status)
		{
			return this.store.dispatch('filesModel/update', {
				id,
				fields: {
					progress: (progress === 100 ? 99 : progress),
					status,
				},
			});
		}

		/**
		 * @private
		 */
		updateMessageSending(id, sending)
		{
			return this.store.dispatch('messagesModel/update', {
				id,
				fields: {
					sending,
				},
			});
		}

		/**
		 * @private
		 */
		addFileToUploadRegistry(fileId, fileToUpload)
		{
			this.uploadRegistry[fileId] = {
				chatId: this.getChatIdByDialogId(fileToUpload.dialogId),
				...fileToUpload,
			};
		}

		/**
		 * @private
		 */
		getMessageWithFile(taskId)
		{
			return this.uploadRegistry[taskId];
		}

		/**
		 * @private
		 */
		getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId) || {};
		}

		/**
		 * @private
		 */
		getCurrentUser()
		{
			const userId = core.getUserId();

			return this.store.getters['usersModel/getUserById'](userId);
		}

		/**
		 * @private
		 */
		getChatIdByDialogId(dialogId)
		{
			return this.getDialog(dialogId).chatId || null;
		}

		/**
		 * @private
		 */
		getDiskFolderIdFromModel(dialogId)
		{
			return this.getDialog(dialogId).diskFolderId || 0;
		}

		/**
		 * @private
		 */
		getFileType(file)
		{
			let fileType = FileType.file;
			if (file.type.startsWith('image'))
			{
				fileType = FileType.image;
			}
			else if (file.type.startsWith('video'))
			{
				fileType = FileType.video;
			}
			else if (file.type.startsWith('audio'))
			{
				fileType = FileType.audio;
			}

			return fileType;
		}

		/**
		 * @private
		 */
		requestDiskFolderId(dialogId)
		{
			return new Promise((resolve, reject) => {
				this.isRequestingDiskFolderId = true;

				const diskFolderGetOptions = {
					chat_id: this.getChatIdByDialogId(dialogId),
				};
				BX.rest.callMethod(RestMethod.imDiskFolderGet, diskFolderGetOptions)
					.then((response) => {
						const diskFolderId = response.data().ID;
						this.store.commit('dialoguesModel/update', {
							dialogId,
							fields: {
								diskFolderId,
							},
						});

						this.isRequestingDiskFolderId = false;

						resolve(diskFolderId);
					})
					.catch((error) => {
						this.isRequestingDiskFolderId = false;

						reject(error);
					})
				;
			});
		}

		commitFile(params)
		{
			const {
				chatId,
				temporaryMessageId,
				temporaryFileId,
				realFileId,
				fromDisk,
			} = params;

			const fileIdParams = {};
			if (fromDisk)
			{
				fileIdParams.disk_id = realFileId;
			}
			else
			{
				fileIdParams.upload_id = realFileId;
			}

			BX.rest.callMethod(RestMethod.imDiskFileCommit, {
				chat_id: chatId,
				message: '', // we don't have feature to send files with text right now
				template_id: temporaryMessageId,
				file_template_id: temporaryFileId,
				...fileIdParams,
			})
				.then(() => {
					this.store.dispatch('filesModel/updateWithId', {
						id: temporaryFileId,
						fields: {
							id: realFileId,
						},
					});
				})
				.catch((error) => {
					Logger.error('FileService.commitFile: error', error);
				})
			;
		}
	}

	module.exports = {
		FileService,
	};
});
