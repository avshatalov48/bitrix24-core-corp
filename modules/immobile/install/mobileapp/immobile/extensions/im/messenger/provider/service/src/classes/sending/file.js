/**
 * @module im/messenger/provider/service/classes/sending/file
 */
jn.define('im/messenger/provider/service/classes/sending/file', (require, exports, module) => {
	const { Filesystem, Reader } = require('native/filesystem');

	const {
		getExtension,
	} = require('utils/file');
	const { debounce } = require('utils/function');
	const { formatFileSize } = require('im/messenger/lib/helper');
	const { Uuid } = require('utils/uuid');

	const {
		FileStatus,
		FileType,
		ErrorCode,
	} = require('im/messenger/const');
	const { getFileTypeByExtension } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { RestMethod, SubTitleIconType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		UploadManager,
		UploaderManagerEvent,
	} = require('im/messenger/provider/service/classes/sending/upload-manager');

	const logger = LoggerManager.getInstance().getLogger('service--sending');

	/**
	 * @class FileUploadService
	 * @deprecated
	 */
	class FileUploadService
	{
		constructor()
		{
			/** @private */
			this.store = serviceLocator.get('core').getStore();

			/** @private */
			this.isRequestingDiskFolderId = false;

			/** @private */
			this.diskFolderIdRequestPromiseCollection = {};

			/** @private */
			this.uploadRegistry = {};

			/** @private */
			this.fileUploadStack = [];

			/**
			 * @desc Async generate upload task
			 * @private
			 */
			this.uploadGenerator = null;

			this.initUploadManager();
			this.updateLoadTextProgressToModelDebounce = debounce(this.updateLoadTextProgressToModel, 500, this, true);
		}

		initUploadManager()
		{
			/** @private */
			const componentName = MessengerParams.getComponentCode();
			this.uploadManager = new UploadManager(componentName);
			this.uploadManager
				.on(UploaderManagerEvent.progress, (fileId, data) => {
					logger.info('UploaderManagerEvent.progress', fileId, data);
					if (!this.#isCurrentUploadManagerEvent(fileId))
					{
						return;
					}

					const params = data.file.params;

					this.updateFileProgress(
						fileId,
						data.percent,
						data.byteSent,
						data.byteTotal,
						FileStatus.upload,
					);
					const textCurrent = formatFileSize(data.byteSent);
					const textTotal = formatFileSize(data.byteTotal);
					const textProgress = `${textCurrent} / ${textTotal}`;

					const oneMB = 1_048_576;
					if (data.byteSent < oneMB)
					{
						this.updateLoadTextProgressToModelDebounce(params.temporaryMessageId, textProgress);
					}
					else
					{
						this.updateLoadTextProgressToModel(params.temporaryMessageId, textProgress);
					}

					this.checkIsLiveMessage();
				})
				.on(UploaderManagerEvent.done, (fileId, data) => {
					logger.info('UploaderManagerEvent.done', fileId, data);
					if (!this.#isCurrentUploadManagerEvent(fileId))
					{
						return;
					}
					const params = data.file.params;
					const file = data.result.data.file;
					const size = file.size;

					this.updateFileProgress(fileId, 100, size, size, FileStatus.wait);

					if (!this.#isCurrentUploadManagerEvent(fileId))
					{
						logger.warn('UploaderManagerEvent.done: file upload was canceled: ', fileId, data);
					}

					const realFileId = file.customData.fileId;
					// eslint-disable-next-line promise/catch-or-return
					this.uploadPreview({
						fileId: file.customData.fileId,
						fileName: this.uploadRegistry[fileId]?.deviceFile?.name,
						previewLocalUrl: this.uploadRegistry[fileId]?.deviceFile?.previewUrl,
					}).finally(() => {
						this.commitFile({
							chatId: this.getDialog(params.dialogId).chatId,
							temporaryMessageId: params.temporaryMessageId,
							temporaryFileId: fileId,
							realFileId,
							fromDisk: false,
						});

						const currentRecentItem = this.store.getters['recentModel/getById'](params.dialogId);
						if (currentRecentItem && currentRecentItem.message.subTitleIcon === SubTitleIconType.wait
							&& currentRecentItem.message.id === params.temporaryMessageId)
						{
							currentRecentItem.message.subTitleIcon = SubTitleIconType.reply;
							this.store.dispatch('recentModel/set', [currentRecentItem])
								.catch((er) => logger.warn(
									'UploaderManagerEvent.done.recentModel/set.error: ',
									er,
								));
						}

						if (this.uploadGenerator)
						{
							this.uploadGenerator.next();
						}
					});
				})
				.on(UploaderManagerEvent.error, (fileId, data) => {
					logger.error('UploaderManagerEvent.error', fileId, data);
					if (!this.#isCurrentUploadManagerEvent(fileId))
					{
						return;
					}
					this.uploadGenerator = null;

					this.fileUploadStack.forEach((file) => {
						const { temporaryFileId, temporaryMessageId } = file;
						this.updateFileProgress(temporaryFileId, 0, 0, 0, FileStatus.error);

						this.store.dispatch('messagesModel/update', {
							id: temporaryMessageId,
							fields: {
								error: true,
								errorReason: ErrorCode.INTERNAL_SERVER_ERROR,
							},
						});
					});

					this.fileUploadStack = [];
				});
		}

		/**
		 * @desc Update progress text to message model
		 * @param {string} messageId
		 * @param {string} textProgress
		 */
		updateLoadTextProgressToModel(messageId, textProgress) {
			this.store.dispatch('messagesModel/updateLoadTextProgress', {
				id: messageId,
				loadText: textProgress,
			});
		}

		/**
		 * @desc Check is having message from chat collection ( if not than add it )
		 */
		checkIsLiveMessage() {
			this.fileUploadStack.forEach((file) => {
				const { temporaryFileId } = file;

				const isHasFile = this.store.getters['filesModel/hasFile'](temporaryFileId);
				if (!isHasFile)
				{
					const fileDataObj = this.uploadRegistry[temporaryFileId] || file;

					const fileData = {
						id: temporaryFileId,
						chatId: fileDataObj.chatId || fileDataObj.dialogId,
						authorId: serviceLocator.get('core').getUserId(),
						name: fileDataObj.deviceFile.name,
						type: fileDataObj.deviceFile.type,
						status: FileStatus.upload,
						progress: 0,
						authorName: this.getCurrentUser().name,
						urlPreview: fileDataObj.deviceFile.previewUrl,
						image: {
							height: fileDataObj.deviceFile.previewHeight,
							width: fileDataObj.deviceFile.previewWidth,
						},
					};

					this.store.dispatch('filesModel/set', fileData);
				}
			});
		}

		/**
		* @desc This is a rare operation that will only happen the first time you send files after creating a chat.
		* In other scenarios, diskFolderId is supplied from the server or local storage.
		 * @param {DialogId} dialogId
		*/
		async getDiskFolderId(dialogId)
		{
			const diskIdFromModel = this.getDiskFolderIdFromModel(dialogId);
			if (diskIdFromModel > 0)
			{
				return diskIdFromModel;
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
				authorId: serviceLocator.get('core').getUserId(),
				name: messageWithFile.file.name,
				type: getFileTypeByExtension(extension),
				extension,
				status: FileStatus.wait,
				progress: 0,
				authorName: this.getCurrentUser().name,
			});
		}

		/**
		 * @param {UploadingMessageWithFile} messageWithFile
		 * @returns {Promise<*>}
		 */
		async uploadFile(messageWithFile)
		{
			this.addFileToFileUploadStack(messageWithFile);
			const uploadTask = await this.uploadManager.addUploadTaskByMessage(messageWithFile);
			this.addFileToUploadRegistry(messageWithFile.temporaryFileId, messageWithFile, uploadTask.taskId);

			return this.addFileToModelByUploadTask(uploadTask, messageWithFile.dialogId);
		}

		/**
		 * @desc Start upload files generator
		 * @param {Array<Object>} files
		 * @param {() => object} callBack
		 */
		uploadFiles(files, callBack)
		{
			this.uploadGenerator = this.getUploadGenerator(files, callBack);
			this.uploadGenerator.next();
		}

		/**
		 * @desc Init async generate upload files
		 * 1 - at the beginning, prepare all the files and send them for viewing
		 * 2 - then, yield start client.addTask(task) = start upload file
		 * @param {Array<Object>} files
		 * @param {() => void} callBack
		 */
		async* getUploadGenerator(files, callBack)
		{
			const tasks = [];
			for await (const file of files)
			{
				this.addFileToFileUploadStack(file);
				const { fileData, task } = await this.uploadManager.getFileDataAndTask(file);
				this.addFileToUploadRegistry(file.temporaryFileId, file, task.taskId);
				await this.addFileToModelByUploadTask(fileData, file.dialogId);
				callBack(file);
				tasks.push(task);
			}

			for (const task of tasks)
			{
				this.uploadManager.client.addTask(task);
				yield task;
			}
		}

		/**
		 * @param {string} temporaryFileId
		 * @return {boolean}
		 */
		async cancelFileUpload(temporaryFileId)
		{
			if (this.#isCurrentUploadManagerEvent(temporaryFileId))
			{
				this.uploadManager.cancelTask(this.uploadRegistry[temporaryFileId].taskId);
				delete this.uploadRegistry[temporaryFileId];

				logger.warn('FileUploadService.cancelTask', temporaryFileId);

				this.fileUploadStack = this.fileUploadStack.filter(
					(file) => file.temporaryFileId !== temporaryFileId,
				);

				if (this.uploadGenerator)
				{
					this.uploadGenerator.next();
				}

				return true;
			}

			return false;
		}

		/**
		 * @private
		 */
		async addFileToModelByUploadTask(uploadTask, dialogId)
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

			if (fileType === FileType.video)
			{
				previewData.image = {
					width: file.previewWidth,
					height: file.previewHeight,
				};
			}

			const dialog = this.getDialog(dialogId);

			return this.store.dispatch('filesModel/set', {
				id: taskId,
				dialogId: dialog?.dialogId,
				chatId: dialog?.chatId,
				authorId: serviceLocator.get('core').getUserId(),
				name: file.name,
				type: fileType,
				extension: file.extension,
				size: file.size,
				status: FileStatus.progress,
				progress: 0,
				authorName: this.getCurrentUser().name,
				urlPreview: file.previewUrl,
				urlShow: file.previewUrl,
				localUrl: file.path,
				...previewData,
			});
		}

		/**
		 * @private
		 */
		updateFileProgress(id, progress, byteSent, byteTotal, status)
		{
			return this.store.dispatch('filesModel/updateWithId', {
				id,
				fields: {
					progress,
					id,
					uploadData: {
						byteSent,
						byteTotal,
					},
					status,
				},
			});
		}

		/**
		 * @private
		 */
		updateMessageSending(id, sending, fileId)
		{
			return this.store.dispatch('messagesModel/update', {
				id,
				fields: {
					sending,
					files: [fileId],
				},
			});
		}

		/**
		 * @private
		 */
		addFileToUploadRegistry(fileId, fileToUpload, taskId)
		{
			this.uploadRegistry[fileId] = {
				chatId: this.getChatIdByDialogId(fileToUpload.dialogId),
				...fileToUpload,
				taskId,
			};
		}

		/**
		 * @desc push to file stack
		 * @param {Object} fileData
		 * @private
		 */
		addFileToFileUploadStack(fileData)
		{
			this.fileUploadStack.push(fileData);
		}

		/**
		 * @param {string} fileId
		 * @return {boolean}
		 */
		#isCurrentUploadManagerEvent(fileId)
		{
			return Boolean(this.uploadRegistry[fileId]);
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
			const userId = serviceLocator.get('core').getUserId();

			return this.store.getters['usersModel/getById'](userId);
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
					.then(async (response) => {
						const diskFolderId = response.data().ID;
						await this.store.dispatch('dialoguesModel/update', {
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
					logger.log('FileUploadService.commitFile is done', params);
					this.store.dispatch('filesModel/updateWithId', {
						id: temporaryFileId,
						fields: {
							id: realFileId,
						},
					});

					this.updateMessageSending(temporaryMessageId, false, realFileId);

					this.fileUploadStack = this.fileUploadStack.filter(
						(fileData) => fileData.temporaryFileId !== temporaryFileId,
					);
				})
				.catch((error) => {
					logger.error('FileUploadService.commitFile: error', error);

					this.updateFileProgress(temporaryFileId, 0, 0, 0, FileStatus.error);

					this.store.dispatch('messagesModel/update', {
						id: temporaryMessageId,
						fields: {
							error: true,
							errorReason: ErrorCode.INTERNAL_SERVER_ERROR,
						},
					});
				})
			;
		}

		/**
		 * @private
		 */
		async uploadPreview({ fileId, fileName, previewLocalUrl })
		{
			if (!previewLocalUrl)
			{
				return Promise.reject(new Error('FileUploadService.uploadPreview: previewLocalUrl is empty'));
			}

			const previewName = `preview_${fileName}.jpg`;
			const previewData = await this.getPreviewByLocalUrl(previewLocalUrl);

			const boundary = `immobileFormBoundary${Uuid.getV4()}`;
			const config = {
				headers: {
					'Content-Type': `multipart/form-data; boundary=${boundary}`,
				},
				data: `--${boundary}\r\n`
					+ `Content-Disposition: form-data; name="id"\r\n\r\n${fileId}\r\n`
					+ `--${boundary}\r\n`
					+ `Content-Disposition: form-data; name="previewFile"; filename="${previewName}"\r\n`
					+ `Content-Type: image/jpeg\r\n\r\n${previewData}\r\n\r\n`
					+ `--${boundary}--`,
				binary: true,
				prepareData: false,
			};

			return BX.ajax.runAction(RestMethod.imDiskFilePreviewUpload, config).catch((error) => {
				logger.error('FileUploadService.uploadPreview: upload request error', error);
			});
		}

		async getPreviewByLocalUrl(localUrl)
		{
			const file = await Filesystem.getFile(localUrl);

			return new Promise((resolve, reject) => {
				const reader = new Reader();
				reader.on('loadEnd', (event) => {
					const previewFile = event.result;
					resolve(previewFile);
				});

				reader.on('error', () => {
					reject(new Error('FileUploadService.uploadPreview: file read error'));
				});

				reader.readAsBinaryString(file);
			});
		}
	}

	module.exports = {
		FileUploadService,
	};
});
