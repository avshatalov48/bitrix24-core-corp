(() => {
	const require = (ext) => jn.require(ext);

	const { UnattachedFilesStorage } = require('files/background-manager/unattached-files-storage');
	const { debounce } = require('utils/function');
	const { prepareObjectId } = require('utils/file');
	const { Logger, LogType } = require('utils/logger');
	const { UploaderClient } = require('uploader/client');
	const { isOnline } = require('device/connection');
	const { showToast } = require('toast');
	const { inAppUrl } = require('in-app-url');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Haptics } = require('haptics');

	const logger = new Logger([LogType.INFO]);

	class FilesBackgroundAttachManager
	{
		constructor()
		{
			this.uploadQueue = [];
			this.isUploading = false;

			this.requestQueue = [];
			this.isRequesting = false;

			this.uploader = new UploaderClient('fileBackgroundAttachManager');
			/**
			 * debounced to give a time to show toast in the field
			 */
			this.checkErrorToastShownDebounced = debounce(this.checkErrorToastShown.bind(this), 5000);
			BX.addCustomEvent('onFileUploadStatusChanged', this.listener.bind(this));
			BX.addCustomEvent('onFileUploadError', this.listener.bind(this));
			// try to scan cache when online
			BX.addCustomEvent('online', this.scanCache.bind(this));

			BX.addCustomEvent(
				'FilesBackgroundAttachManager::removeFileByEntityIdAndFileId',
				this.removeFileByEntityIdAndFileId.bind(this),
			);
			BX.addCustomEvent(
				'FilesBackgroundAttachManager::setErrorShownToFiles',
				this.setErrorShownToFiles.bind(this),
			);

			this.scanCache();
		}

		removeFileByEntityIdAndFileId(entityId, fileId)
		{
			UnattachedFilesStorage.removeFileByEntityIdAndFileId(entityId, fileId);
		}

		setErrorShownToFiles(entityId, filesIds)
		{
			UnattachedFilesStorage.setErrorShownToFiles(entityId, filesIds);
		}

		/**
		 * @param {string} event
		 * @param {object} data
		 */
		listener(event, data)
		{
			switch (event)
			{
				case BX.FileUploadEvents.FILE_UPLOAD_START:
					this.addFileToStorage(data?.file);
					break;
				case BX.FileUploadEvents.FILE_CREATED:
					this.handleFileUploaded(data?.file, data?.result);
					break;
				case BX.FileUploadEvents.FILE_UPLOAD_FAILED:
				case BX.FileUploadEvents.TASK_STARTED_FAILED:
				case BX.FileUploadEvents.FILE_CREATED_FAILED:
				case BX.FileUploadEvents.FILE_READ_ERROR:
					this.handleError(data?.file, event);
					break;
				default:
					break;
			}
		}

		handleError(file, event = null)
		{
			logger.error('Error during file upload:', file, event);
			if (!file)
			{
				return;
			}

			this.setErrorToFile(file);
			this.showErrorPopup(file, event);

			const fileId = file?.params?.id;
			this.scanUploadQueue(fileId);
		}

		showErrorPopup(file, event)
		{
			const entityUrl = file?.attachToEntityController?.entityUrl;
			if (entityUrl)
			{
				// ToDo why debounce? maybe queue + setTimeout?
				this.checkErrorToastShownDebounced(file, event);
			}
		}

		checkErrorToastShown(file, event)
		{
			const entityId = file?.attachToEntityController?.entityId;
			const fileId = file?.params.id;
			const fileDataFromCache = UnattachedFilesStorage.getFileByEntityIdAndFileId(entityId, fileId);

			if (
				(fileDataFromCache && !fileDataFromCache?.params.errorShown)
				|| event === BX.FileUploadEvents.FILE_READ_ERROR
			)
			{
				this.showErrorToast(file, event !== BX.FileUploadEvents.FILE_READ_ERROR);
				this.setErrorShownToFiles(entityId, [fileId]);
			}
		}

		/**
		 * @param {object} file
		 * @param {boolean} showButton
		 */
		showErrorToast(file, showButton = true)
		{
			const entityUrl = file?.attachToEntityController?.entityUrl;
			if (entityUrl)
			{
				Haptics.notifyWarning();
				showToast({
					message: Loc.getMessage('BACKGROUND_ATTACHMENT_MANAGER_TOAST_UPLOAD_ERROR_TITLE'),
					buttonText: showButton && Loc.getMessage('BACKGROUND_ATTACHMENT_MANAGER_TOAST_UPLOAD_ERROR_BUTTON'),
					backgroundColor: Color.accentMainAlert.toHex(),
					backgroundOpacity: 1,
					blur: false,
					textSize: 14,
					buttonTextSize: 14,
					buttonTextColor: Color.baseWhiteFixed.toHex(),
					onButtonTap: () => {
						inAppUrl.open(entityUrl);
					},
					onTap: () => {
						inAppUrl.open(entityUrl);
					},
				});
			}
		}

		setErrorToFile(file)
		{
			const entityId = file?.attachToEntityController?.entityId;
			const fileId = file?.params.id;

			if (entityId && fileId)
			{
				UnattachedFilesStorage.setErrorParamsByEntityId(entityId, fileId);
			}
		}

		/**
		 * @param {object} fileData

		 * @param {object} fileData.attachToEntityController
		 * @param {string} fileData.attachToEntityController.entityId
		 * @param {object} fileData.attachToEntityController.actionConfigData
		 * @param {string} fileData.attachToEntityController.actionName
		 * @param {string} fileData.attachToEntityController.fieldName
		 *
		 * @param {object} fileData.params
		 * @param {string} fileData.params.id
		 */
		addFileToStorage(fileData)
		{
			const entityId = fileData?.attachToEntityController?.entityId;
			const fileId = fileData?.params?.id;
			if (entityId && fileId)
			{
				UnattachedFilesStorage.addByEntityId(entityId, fileId, fileData);
			}
		}

		/**
		 * @param {object} fileDataFromEvent
		 * @param {object} result
		 */
		async handleFileUploaded(fileDataFromEvent, result)
		{
			const entityId = fileDataFromEvent?.attachToEntityController?.entityId;
			const fileId = fileDataFromEvent?.params?.id;
			if (entityId)
			{
				const token = result?.data?.token;
				if (!token)
				{
					logger.error('Token is not defined');

					return;
				}

				UnattachedFilesStorage.setFileParamByEntityIdAndFileId(entityId, fileId, 'token', token);

				this.requestQueue.push(() => this.attachFile(fileDataFromEvent, token));
				await this.processRequestQueue();
			}
		}

		async processRequestQueue()
		{
			if (this.isRequesting || this.requestQueue.length === 0)
			{
				return;
			}

			this.isRequesting = true;
			const request = this.requestQueue.shift();

			try
			{
				await request();
			}
			catch (error)
			{
				logger.error('Error during file attachment:', error);
			}
			finally
			{
				this.isRequesting = false;
				await this.processRequestQueue();
			}
		}

		/**
		 * @param {object} fileDataFromEvent
		 * @param {string} token
		 * @return {Promise<void>}
		 */
		async attachFile(fileDataFromEvent, token)
		{
			const fileId = fileDataFromEvent?.params?.id;
			const entityId = fileDataFromEvent?.attachToEntityController?.entityId;
			if (!entityId)
			{
				throw new Error('Entity ID is not defined');
			}

			const actionName = fileDataFromEvent?.attachToEntityController?.actionName;
			if (!actionName)
			{
				throw new Error('Attach action name is not defined');
			}

			const actionConfigData = fileDataFromEvent?.attachToEntityController?.actionConfigData ?? {};
			const fieldName = fileDataFromEvent?.attachToEntityController?.fieldName;
			if (!fieldName)
			{
				throw new Error('Attach field name is not defined');
			}

			const config = {
				data: {
					...actionConfigData,
					[fieldName]: token,
				},
			};

			try
			{
				logger.info('Try to attach file:', fileDataFromEvent);
				const response = await this.attachFilesAction(actionName, config);
				this.onAjaxResponse(response, fileDataFromEvent, entityId);
			}
			catch (error)
			{
				this.onAjaxError(fileDataFromEvent, error);
			}
			finally
			{
				this.scanUploadQueue(fileId);
			}
		}

		/**
		 * @param actionName
		 * @param config
		 * @return {Promise}
		 */
		attachFilesAction(actionName, config)
		{
			return BX.ajax.runAction(actionName, config);
		}

		/**
		 * @param {object} response
		 * @param {object} fileData
		 * @param {string} entityId
		 */
		onAjaxResponse(response, fileData, entityId)
		{
			if (response.status === 'success')
			{
				this.onAjaxSuccess(response, fileData, entityId);
			}
			else
			{
				this.onAjaxError(fileData, response);
			}
		}

		/**
		 * @param {object} response
		 * @param {object} fileData
		 * @param {string} entityId
		 */
		onAjaxSuccess(response, fileData, entityId)
		{
			logger.info('Successful attach:', response);

			const fileDto = response.data;
			const objectId = fileDto?.objectId;
			const fileId = fileData?.params?.id;
			const fileUuid = fileData?.params?.uuid;

			const preparedObjectId = prepareObjectId(objectId);
			if (preparedObjectId)
			{
				fileDto.objectId = preparedObjectId;
				fileDto.uuid = fileUuid;
				UnattachedFilesStorage.setObjectIdToFile(entityId, fileId, fileDto);
			}
			else
			{
				this.handleError(fileData);
			}
		}

		/**
		 * @param {object} fileData
		 * @param {object} response
		 */
		onAjaxError(fileData, response)
		{
			this.handleError(fileData, response);
		}

		async scanCache()
		{
			if (!isOnline())
			{
				return;
			}

			if (UnattachedFilesStorage.isEmpty())
			{
				logger.info('File cache is empty');

				return;
			}

			const filesData = UnattachedFilesStorage.getCache();
			logger.info('Cached Files:', filesData);

			for (const [entityId, entityFiles] of Object.entries(filesData))
			{
				for (const fileData of entityFiles)
				{
					// eslint-disable-next-line no-await-in-loop
					await this.uploadFile(fileData, entityId);
				}
			}
		}

		async uploadFile(fileData)
		{
			try
			{
				const fileId = fileData.params.id;
				const entityId = fileData.attachToEntityController?.entityId;
				if (entityId)
				{
					const objectId = fileData?.params?.objectId;
					const hasError = fileData.params.hasError;
					if (objectId || hasError)
					{
						UnattachedFilesStorage.removeFileByEntityIdAndFileId(entityId, fileId);

						return;
					}

					const token = fileData?.token;
					// if file was uploaded but not attached
					if (token)
					{
						await this.attachFile(fileData, token);

						return;
					}

					this.uploadQueue.push({
						taskId: fileId,
						...fileData,
						url: fileData.params.url,
					});

					if (!this.isUploading)
					{
						this.processUploadQueue();
					}
				}
			}
			catch (error)
			{
				logger.error('Error during file upload:', error);
			}
		}

		processUploadQueue()
		{
			if (this.isQueueEmpty())
			{
				this.isUploading = false;

				return;
			}

			this.isUploading = true;
			const fileData = this.uploadQueue[0];

			logger.warn('Try to upload file:', fileData);
			this.uploader.addTaskFromCache(fileData);
		}

		scanUploadQueue(fileId)
		{
			const isFileInUploadQueue = this.uploadQueue.some((file) => file.params.id === fileId);

			if (isFileInUploadQueue)
			{
				this.uploadQueue.shift();
				this.processUploadQueue();
			}

			this.isUploading = !this.isQueueEmpty();
		}

		isQueueEmpty()
		{
			return this.uploadQueue.length === 0;
		}
	}

	new FilesBackgroundAttachManager();
})();
