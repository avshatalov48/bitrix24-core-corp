/**
 * @module im/messenger/provider/service/classes/sending/files
 */
jn.define('im/messenger/provider/service/classes/sending/files', (require, exports, module) => {
	const { Type } = require('type');
	const { debounce } = require('utils/function');
	const { Uuid } = require('utils/uuid');
	const { getExtension } = require('utils/file');
	const { Filesystem, Reader } = require('native/filesystem');

	const {
		FileStatus,
		FileType,
		RestMethod,
		ErrorCode,
		SubTitleIconType
	} = require('im/messenger/const');
	const { formatFileSize } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('service--sending');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getFileTypeByExtension } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		UploadManager,
		UploaderManagerEvent,
	} = require('im/messenger/provider/service/classes/sending/upload-manager');

	/**
	 * @class FilesUploadService
	 */
	class FilesUploadService
	{
		#isRequestingDiskFolderId = false;
		/** @typedef {DiskFolderIdRequestPromiseCollection} */
		#diskFolderIdRequestPromiseCollection = {};

		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			/** @type {UploadRegistry} */
			this.uploadRegistry = {};
			/** @type {Array<PreparedTasks>} */
			this.tasks = [];
			/**  @type {AsyncGenerator} */
			this.uploadGenerator = this.getUploadGenerator();
			/**  @type {debounce<onFileUploadProgress>} */
			this.onFileUploadProgressDebounce = debounce(this.onFileUploadProgress, 100, this, true);

			this.initUploadManager();
		}

		/**
		 * @void
		 */
		initUploadManager()
		{
			this.uploadManager = new UploadManager(MessengerParams.getComponentCode());

			this.uploadManager.on(UploaderManagerEvent.progress, this.onFileUploadProgressDebounce);
			this.uploadManager.on(UploaderManagerEvent.done, this.fileUploadDoneHandler.bind(this));
			this.uploadManager.on(UploaderManagerEvent.error, this.fileUploadErrorHandler.bind(this));
		}

		/**
		 * @param {FileId} fileId
		 * @param {object} data
		 * @return {Promise}
		 */
		async onFileUploadProgress(fileId, data)
		{
			logger.log(`${this.constructor.name}.onFileUploadProgress:`, fileId, data);
			if (!this.#isCurrentUploadManagerEvent(fileId))
			{
				return;
			}
			const params = data.file.params;
			const textCurrent = formatFileSize(data.byteSent);
			const textTotal = formatFileSize(data.byteTotal);
			const textProgress = `${textCurrent} / ${textTotal}`;

			if (this.#isCheckCompleteProgressByDebounce(fileId))
			{
				logger.warn(`${this.constructor.name}.onFileUploadProgress was canceled because the file has a wait or done status`, fileId, data);

				return;
			}

			await this.#updateFileProgress(
				fileId,
				data.percent,
				data.byteSent,
				data.byteTotal,
				FileStatus.progress,
			);
			await this.#updateLoadTextProgressToModel(params.temporaryMessageId, textProgress, fileId);
			this.#updateUploadRegistryData(fileId, { status: FileStatus.progress });
		}

		/**
		 * @param {number} fileId
		 * @param {object} data
		 * @return {Promise}
		 */
		async fileUploadDoneHandler(fileId, data)
		{
			logger.log(`${this.constructor.name}.fileUploadDoneHandler:`, fileId, data);
			if (!this.#isCurrentUploadManagerEvent(fileId))
			{
				return;
			}

			const params = data.file.params;
			const file = data.result.data?.file;
			const realFileIdInt = file?.customData?.fileId;
			const size = file.size;
			const temporaryMessageId = params?.temporaryMessageId;

			this.#updateUploadRegistryData(fileId, { status: FileStatus.wait, realFileIdInt });
			const registerDataArray = this.#getRegisterDataByMessageId(temporaryMessageId);

			await this.#updateFileProgress(fileId, 100, size, size, FileStatus.wait);
			await this.#updateWithIdFileModel(fileId, { id: realFileIdInt });

			if (registerDataArray.length > 1)
			{
				await this.#updateMessageModel(temporaryMessageId, { uploadFileId: '' });
			}
			else
			{
				await this.#updateDoneProgressToModel(temporaryMessageId);
			}

			await this.#uploadPreview({
				fileId: realFileIdInt,
				fileName: this.uploadRegistry[fileId].deviceFile.name,
				previewLocalUrl: this.uploadRegistry[fileId].deviceFile.previewUrl,
			});

			const isAllFilesWait = registerDataArray.every((registerData) => registerData.status === FileStatus.wait);
			if (isAllFilesWait)
			{
				this.#commitFromUploadRegister(temporaryMessageId);
			}

			await this.#startNextTask();
		}

		/**
		 * @param {FileId} fileId
		 * @param {object} data
		 * @return {Promise}
		 */
		async fileUploadErrorHandler(fileId, data)
		{
			logger.log(`${this.constructor.name}.fileUploadErrorHandler:`, fileId, data);
			if (!this.#isCurrentUploadManagerEvent(fileId))
			{
				return;
			}
			this.#updateUploadRegistryData(fileId, { status: FileStatus.error });
			await this.#updateFileProgress(fileId, 0, 0, 0, FileStatus.error);
			await this.#updateMessageModel(
				data.file.params.temporaryMessageId,
				{ error: true, errorReason: ErrorCode.INTERNAL_SERVER_ERROR, loadText: '' },
			);
			await this.#updateRecentStatusByError(data.file.params);
			await this.#startNextTask();
		}

		/**
		 * @param {Array<PreparedUploadFile>} files
		 * @return {Promise<Array<PreparedTasks>>}
		 */
		async prepareTasks(files)
		{
			const tasksWithFile = await this.createTasks(files);
			await this.addFilesToStore(tasksWithFile);

			return tasksWithFile.map((taskWithFile) => taskWithFile.task);
		}

		/**
		 * @desc Start upload files generator
		 * @param {Array<PreparedTasks>} tasks
		 * @void
		 */
		async startUploadFiles(tasks)
		{
			logger.log(`${this.constructor.name}.startUploadFiles.tasks:`, tasks, this.tasks);
			const isEmptyTasks = this.tasks.length === 0;
			this.tasks.push(...tasks);
			this.uploadGenerator = this.getUploadGenerator();

			if (isEmptyTasks)
			{
				await this.#startNextTask();
			}
		}

		/**
		 * @param {Array<PreparedUploadFile>} files
		 * @return {Promise<Array<PreparedTasks|Promise>>}
		 */
		async createTasks(files)
		{
			const createTasksPromises = [];
			for (const file of files)
			{
				this.#addFileToUploadRegistry(file.temporaryFileId, file);
				createTasksPromises.push(this.uploadManager.getFileDataAndTask(file));
			}

			return Promise.all(createTasksPromises);
		}

		/**
		 * @param {Array<Object>} tasksWithFile
		 * @return {Promise<Array>}
		 */
		async addFilesToStore(tasksWithFile)
		{
			const addFileToModelPromises = [];
			for (const taskWithFile of tasksWithFile)
			{
				addFileToModelPromises.push(this.#addFileToModelByTask(taskWithFile));
			}

			return Promise.all(addFileToModelPromises);
		}

		/**
		 * @param {FileId} fileId
		 * @param {PreparedUploadFile} fileToUpload
		 */
		#addFileToUploadRegistry(fileId, fileToUpload)
		{
			this.uploadRegistry[fileId] = {
				chatId: this.getChatIdByDialogId(fileToUpload.dialogId),
				...fileToUpload,
				taskId: fileToUpload.temporaryFileId,
				status: FileStatus.upload,
				realFileIdInt: 0,
			};
		}

		/**
		 * @param {FileId} fileId
		 * @param {object} fields
		 */
		#updateUploadRegistryData(fileId, fields)
		{
			this.uploadRegistry[fileId] = {
				...this.uploadRegistry[fileId],
				...fields,
			};
		}

		/**
		 * @param {object} taskWithFile
		 */
		async #addFileToModelByTask(taskWithFile)
		{
			const { taskId, file } = taskWithFile.fileData;
			const { params } = taskWithFile.task;

			const fileType = getFileTypeByExtension(file.extension.toLowerCase());
			const previewData = {};
			let urlShow = null;
			if (fileType === FileType.image)
			{
				previewData.image = {
					width: file.width,
					height: file.height,
				};
				urlShow = file.previewUrl;
			}

			if (fileType === FileType.video)
			{
				previewData.image = {
					width: file.previewWidth,
					height: file.previewHeight,
				};
				urlShow = file.url;
			}

			const dialog = this.getDialog(params.dialogId);
			const fields = {
				id: taskId,
				templateId: taskId,
				dialogId: dialog?.dialogId,
				chatId: dialog?.chatId,
				authorId: serviceLocator.get('core').getUserId(),
				name: file.name,
				type: fileType,
				extension: file.extension,
				size: file.size,
				status: FileStatus.upload,
				progress: 0,
				authorName: this.getCurrentUser()?.name,
				urlPreview: file.previewUrl,
				urlShow,
				localUrl: file.path,
				...previewData,
			};

			return this.#setFileModel(fields);
		}

		/**
		 * @param {object} fileData
		 */
		addFileToModelFromDisk(fileData)
		{
			const extension = getExtension(fileData.file.name);
			const image = {
				width: fileData.file?.previewWidth ?? 0,
				height: fileData.file?.previewHeight ?? 0,
			};

			const fields = {
				id: fileData.realFileIdInt,
				templateId: fileData.temporaryFileId,
				chatId: fileData.chatId,
				dialogId: fileData.dialogId,
				authorId: serviceLocator.get('core').getUserId(),
				name: fileData.file.name,
				type: getFileTypeByExtension(extension),
				extension,
				status: FileStatus.upload,
				progress: 0,
				authorName: this.getCurrentUser()?.name,
				urlShow: fileData.file.url,
				urlPreview: fileData.file.url,
				image,
			};

			return this.#setFileModel(fields);
		}

		/**
		 * @desc Init async generate upload files
		 * @return {AsyncGenerator}
		 */
		async* getUploadGenerator()
		{
			for (const task of this.tasks)
			{
				this.tasks = this.tasks.filter(
					(addedTask) => addedTask.taskId !== task.taskId,
				);

				if (this.uploadRegistry[task.taskId])
				{
					this.uploadManager.client.addTask(task);
				}
				else
				{
					this.#startNextTask()
						.catch(
							(error) => logger.error(`${this.constructor.name}.getUploadGenerator.startNextTask catch:`, error),
						);
				}

				yield task;
			}
		}

		/**
		 * @desc do yield in uploadGenerator - new iterate by process task
		 * @return {Promise}
		 */
		async #startNextTask()
		{
			await this.uploadGenerator.next()
				.catch((error) => logger.error(`${this.constructor.name}.startNextTask catch:`, error));
		}

		/**
		 * @desc This is a rare operation that will only happen the first time you send files after creating a chat.
		 * In other scenarios, diskFolderId is supplied from the server or local storage.
		 * @param {DialogId} dialogId
		 * @return Promise<DiskFolderId>
		 */
		getDiskFolderId(dialogId)
		{
			const diskIdFromModel = this.#getDiskFolderIdFromModel(dialogId);
			if (diskIdFromModel > 0)
			{
				return Promise.resolve(diskIdFromModel);
			}

			if (this.#isRequestingDiskFolderId)
			{
				return this.#diskFolderIdRequestPromiseCollection[dialogId];
			}

			this.#diskFolderIdRequestPromiseCollection[dialogId] = this.#requestDiskFolderId(dialogId);

			return this.#diskFolderIdRequestPromiseCollection[dialogId];
		}

		/**
		 * @param {DialogId} dialogId
		 */
		#getDiskFolderIdFromModel(dialogId)
		{
			return this.getDialog(dialogId).diskFolderId || 0;
		}

		/**
		 * @param {DialogId} dialogId
		 * @return Promise<DiskFolderId>
		 */
		#requestDiskFolderId(dialogId)
		{
			return new Promise((resolve, reject) => {
				this.isRequestingDiskFolderId = true;

				const options = {
					chat_id: this.getChatIdByDialogId(dialogId),
				};
				BX.rest.callMethod(RestMethod.imDiskFolderGet, options)
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

		/**
		 * @param {string} temporaryMessageId
		 * @void
		 */
		#commitFromUploadRegister(temporaryMessageId)
		{
			const registerDataArray = this.#getRegisterDataByMessageId(temporaryMessageId);
			const filesIdsCollection = registerDataArray.map((data) => {
				return { realFileIdInt: data.realFileIdInt, temporaryFileId: data.temporaryFileId };
			});

			const chatId = registerDataArray[0].chatId;
			this.#commitFiles(chatId, temporaryMessageId, filesIdsCollection);
		}

		/**
		 * @param {number} chatId
		 * @param {string} temporaryMessageId
		 * @param {FilesIdsCollection} filesIdsCollection
		 * @void
		 */
		commitFromDisk(chatId, temporaryMessageId, filesIdsCollection)
		{
			this.#commitFiles(chatId, temporaryMessageId, filesIdsCollection, true);
		}

		/**
		 * @param {number} chatId
		 * @param {string} temporaryMessageId
		 * @param {FilesIdsCollection} filesIdsCollection
		 * @param {boolean}fromDisk
		 * @void
		 */
		#commitFiles(chatId, temporaryMessageId, filesIdsCollection, fromDisk = false)
		{
			const realFileIdsInt = filesIdsCollection.map((fileId) => fileId.realFileIdInt);
			const fileIdParams = {};
			if (fromDisk)
			{
				fileIdParams.disk_id = realFileIdsInt;
			}
			else
			{
				fileIdParams.upload_id = realFileIdsInt;
			}

			BX.rest.callMethod(RestMethod.imDiskFileCommit, {
				chat_id: chatId,
				message: '', // we don't have to send files with text right now
				template_id: temporaryMessageId,
				as_file: false ? 'Y' : 'N', // TODO
				...fileIdParams,
			})
				.then(async (res) => {
					logger.log(`${this.constructor.name}.commitFile is done`, res);

					filesIdsCollection.forEach((fileIds) => {
						this.#updateUploadRegistryData(fileIds.realFileIdInt, { status: FileStatus.done });
					});

					await this.#updateMessageModel(
						temporaryMessageId,
						{ sending: false, files: realFileIdsInt, loadText: '', uploadFileId: '' },
					);
				})
				.catch(async (error) => {
					logger.error(`${this.constructor.name}.commitFiles: error`, error);

					await this.#updateMessageModel(temporaryMessageId, {
						error: true,
						errorReason: ErrorCode.INTERNAL_SERVER_ERROR,
						loadText: '',
					});
				});
		}

		/**
		 * @param {string} temporaryMessageId
		 * @return {Array<UploadRegistryData>}
		 */
		#getRegisterDataByMessageId(temporaryMessageId)
		{
			const register = { ...this.uploadRegistry };
			const registerDataArray = [];
			Object.values(register).forEach((regData) => {
				if (regData.temporaryMessageId === temporaryMessageId)
				{
					registerDataArray.push(regData);
				}
			});

			return registerDataArray;
		}

		/**
		 * @param {number} id
		 * @param {number} progress
		 * @param {number} byteSent
		 * @param {number} byteTotal
		 * @param {string} status
		 * @return {Promise}
		 */
		#updateFileProgress(id, progress, byteSent, byteTotal, status)
		{
			const fields = {
				progress,
				id,
				uploadData: {
					byteSent,
					byteTotal,
				},
				status,
			};

			return this.#updateWithIdFileModel(id, fields);
		}

		/**
		 * @desc Update progress text to message model
		 * @param {string} messageId
		 * @param {string} textProgress
		 * @param {string} uploadFileId
		 * @return Promise
		 */
		#updateLoadTextProgressToModel(messageId, textProgress, uploadFileId)
		{
			return this.store.dispatch('messagesModel/updateLoadTextProgress', {
				id: messageId,
				loadText: textProgress,
				uploadFileId,
			});
		}

		async #updateDoneProgressToModel(messageId)
		{
			const messagesModel = this.store.getters['messagesModel/getByTemplateId'](messageId);

			return this.store.dispatch('messagesModel/updateLoadTextProgress', {
				id: messageId,
				loadText: messagesModel?.loadText,
				uploadFileId: '',
			});
		}

		/**
		 * @desc checking when it is possible to update
		 * the progress text after the DONE or WAIT status
		 * @param {string} uploadFileId
		 * @return {boolean}
		 */
		#isCheckCompleteProgressByDebounce(uploadFileId)
		{
			return [FileStatus.wait, FileStatus.done].includes(this.uploadRegistry[uploadFileId]?.status);
		}

		/**
		 * @desc Update message model
		 * @param {string} messageId
		 * @param {object} fields
		 * @return Promise
		 */
		#updateMessageModel(messageId, fields)
		{
			return this.store.dispatch('messagesModel/update', {
				id: messageId,
				fields,
			});
		}

		async #updateRecentStatusByError({ dialogId, temporaryMessageId })
		{
			const recentItem = this.store.getters['recentModel/getById'](dialogId);
			if (!recentItem)
			{
				return;
			}

			if (recentItem.uploadingState?.message?.id !== temporaryMessageId)
			{
				return;
			}

			recentItem.uploadingState.message.subTitleIcon = SubTitleIconType.error;

			await this.store.dispatch('recentModel/set', [recentItem]);
		}

		/**
		 * @desc set file model
		 * @param {object} fields
		 * @return Promise
		 */
		#setFileModel(fields)
		{
			return this.store.dispatch('filesModel/set', fields);
		}

		/**
		 * @desc update file model with id
		 * @param {string} id
		 * @param {object} fields
		 * @return {Promise}
		 */
		#updateWithIdFileModel(id, fields)
		{
			return this.store.dispatch('filesModel/updateWithId', { id, fields });
		}

		/**
		 * @param {DialogId} dialogId
		 * @return {DialoguesModelState|{}}
		 */
		getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId) || {};
		}

		/**
		 * @param {DialogId} dialogId
		 * @return {number}
		 */
		getChatIdByDialogId(dialogId)
		{
			return this.getDialog(dialogId).chatId || 0;
		}

		/**
		 * @return {UsersModelState}
		 */
		getCurrentUser()
		{
			const userId = serviceLocator.get('core').getUserId();

			return this.store.getters['usersModel/getById'](userId);
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
		 * @param {string} temporaryFileId
		 * @return {Promise<boolean>}
		 */
		async cancelTask(temporaryFileId)
		{
			const uploadRegistryData = this.uploadRegistry[temporaryFileId];
			if (uploadRegistryData)
			{
				this.uploadManager.cancelTask(uploadRegistryData.taskId);
				delete this.uploadRegistry[temporaryFileId];
				this.tasks = this.tasks.filter(
					(addedTask) => addedTask.taskId !== uploadRegistryData.taskId,
				);
				logger.warn(`${this.constructor.name}.cancelTask`, temporaryFileId);

				if (uploadRegistryData.status === FileStatus.progress)
				{
					await this.#startNextTask();

					// this check is needed if the canceled task was the last uploaded file
					// and the "Done" event will not be received and, accordingly, there will be no commit
					const registerDataArray = this.#getRegisterDataByMessageId(uploadRegistryData.temporaryMessageId);
					if (registerDataArray.length === 0)
					{
						return true;
					}

					const isAllFilesWait = registerDataArray.every((registerData) => registerData.status === FileStatus.wait);
					if (isAllFilesWait)
					{
						this.#commitFromUploadRegister(uploadRegistryData.temporaryMessageId);
					}
				}

				return true;
			}

			return false;
		}

		/**
		 * @param {string} ileId
		 * @param {string} fileName
		 * @param {string} previewLocalUrl
		 * @return {Promise}
		 */
		async #uploadPreview({ fileId, fileName, previewLocalUrl })
		{
			if (!previewLocalUrl)
			{
				logger.error(`${this.constructor.name}.uploadPreview: previewLocalUrl is empty`);

				return Promise.resolve();
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
				logger.error(`${this.constructor.name}.uploadPreview: upload request error`, error, config);
			});
		}

		/**
		 * @param {string} localUrl
		 * @return {Promise<string>}
		 */
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
					reject(new Error(`${this.constructor.name}.uploadPreview.getPreviewByLocalUrl: file read error`));
				});

				reader.readAsBinaryString(file);
			});
		}
	}

	module.exports = {
		FilesUploadService,
	};
});
