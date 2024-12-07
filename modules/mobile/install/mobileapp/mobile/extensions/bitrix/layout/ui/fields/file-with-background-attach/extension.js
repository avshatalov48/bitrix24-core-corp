/**
 * @module layout/ui/fields/file-with-background-attach
 */
jn.define('layout/ui/fields/file-with-background-attach', (require, exports, module) => {
	const { FileFieldClass, itemsShape, FILE_TASK_ID_PREFIX } = require('layout/ui/fields/file');
	const { Uuid } = require('utils/uuid');
	const { uniqBy } = require('utils/array');
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { Haptics } = require('haptics');
	const { Events } = require('uploader/const');
	const {
		NativeViewerMediaTypes,
		getMimeType,
		getExtension,
		prepareObjectId,
	} = require('utils/file');
	const { UnattachedFilesStorage } = require('files/background-manager/unattached-files-storage');
	const { showToast } = require('toast');
	const { Logger, LogType } = require('utils/logger');
	const { Color } = require('tokens');

	const logger = new Logger([LogType.INFO]);

	/**
	 * @class FileWithBackgroundAttachFieldClass
	 */
	class FileWithBackgroundAttachFieldClass extends FileFieldClass
	{
		constructor(props)
		{
			super(props);

			this.alreadyMounted = false;
			this.filesFromCache = this.filterCacheFiles(this.getFilesFromCache());

			this.updateCachedFiles = this.updateCachedFiles.bind(this);

			this.showToastDebounced = debounce(
				() => {
					Haptics.notifyWarning();
					showToast(
						{
							message: BX.message('FIELDS_FILE_WITH_BACKGROUND_ATTACH_TOAST_ERROR_TEXT'),
							backgroundColor: Color.accentMainAlert.toHex(),
							backgroundOpacity: 1,
							blur: false,
							textSize: 14,
							buttonTextSize: 14,
							buttonTextColor: Color.baseWhiteFixed.toHex(),
						},
						this.fileAttachmentRef ? this.fileAttachmentWidget : this.getParentWidget(),
					);
				},
				4000,
				this,
				true,
			);
		}

		useDebounceOnChange()
		{
			return false;
		}

		cancelAllUploadTasks()
		{
			// overwrite to prevent canceling tasks for attached files
			// componentWillUnmount work correctly on Android
		}

		componentWillReceiveProps(newProps)
		{
			const filesFromCache = this.getFilesFromCache();
			const filteredFilesFromProps = newProps.value.filter((file) => Number.isInteger(file.id));
			const uploadingFilesFromProps = newProps.value.filter((file) => !Number.isInteger(file.id));

			if (isEqual(filesFromCache, uploadingFilesFromProps))
			{
				this.filesFromCache = this.filteredCachedFiles(filteredFilesFromProps, filesFromCache);
			}
			// if the field received an added file from props but the storage has not yet processed it
			else
			{
				const uploadingFilesFromCache = filesFromCache.filter((file) => !Number.isInteger(file.id));
				const uniqueUploadingFiles = this.getUniqueFiles([...uploadingFilesFromCache, ...uploadingFilesFromProps]);

				this.filesFromCache = this.filteredCachedFiles(filteredFilesFromProps, uniqueUploadingFiles);
			}

			super.componentWillReceiveProps(newProps);
		}

		get controllerEntityId()
		{
			return this.getConfig().attachToEntityController?.entityId;
		}

		/**
		 * @param {array} filesFromProps
		 * @param {array} cachedFiles
		 * @return {array}
		 */
		filteredCachedFiles(filesFromProps, cachedFiles)
		{
			const filesFromPropsSet = new Set(filesFromProps.map((file) => Number(file.id)));

			this.logger.log(
				'FileWithBackgroundAttachFieldClass filteredCachedFiles',
				filesFromProps,
				cachedFiles,
				filesFromPropsSet,
			);

			return cachedFiles.filter((cachedFile) => {
				// If the file is not in filesFromProps, add it to the new array, otherwise remove it from the cache
				if (filesFromPropsSet.has(cachedFile.id))
				{
					BX.postComponentEvent(
						'FilesBackgroundAttachManager::removeFileByEntityIdAndFileId',
						[this.controllerEntityId, cachedFile.id],
					);

					return false;
				}

				return true;
			});
		}

		filterCacheFiles(cachedFiles)
		{
			const uploadedFilesFromProps = this.getFilteredValueFromProps();

			return this.filteredCachedFiles(uploadedFilesFromProps, cachedFiles);
		}

		/**
		 *  we push cached files to the parent on cache update to update the parent state (if needed)
		 *  and should filter them out
		 * @return {array}
		 */
		getFilteredValueFromProps()
		{
			const filesFromProps = super.getValue();

			return this.filterFilesFromProps(filesFromProps);
		}

		filterFilesFromProps(filesFromProps)
		{
			const uploadedFilesObjectIds = new Set(filesFromProps.map((file) => file.objectId).filter(Boolean));

			return filesFromProps.filter((file) => {
				const preparedId = prepareObjectId(file.id);

				return preparedId && !uploadedFilesObjectIds.has(preparedId);
			});
		}

		componentDidMount()
		{
			if (this.alreadyMounted)
			{
				return;
			}

			this.alreadyMounted = true;

			super.componentDidMount();

			const { attachToEntityController, listenCacheChanges = true } = this.getConfig();
			if (attachToEntityController && listenCacheChanges)
			{
				// update cached files on save cache data event
				BX.addCustomEvent('UnattachedFilesStorage::onSaveCacheFilesData', this.updateCachedFiles);

				this.markFilesWithErrorsAsShown();
			}
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			const { attachToEntityController, listenCacheChanges = true } = this.getConfig();
			if (attachToEntityController && listenCacheChanges)
			{
				BX.removeCustomEvent('UnattachedFilesStorage::onSaveCacheFilesData', this.updateCachedFiles);
			}
		}

		markFilesWithErrorsAsShown()
		{
			const filesWithErrors = this.filesFromCache.filter((file) => file.hasError);
			const filesWithErrorsIds = filesWithErrors.map((file) => file.id);

			UnattachedFilesStorage.setErrorShownToFiles(this.controllerEntityId, filesWithErrorsIds);
		}

		/**
		 * @return {array}
		 */
		getFilesFromCache()
		{
			if (!this.controllerEntityId)
			{
				logger.error('Entity id is not defined');

				return [];
			}

			const data = UnattachedFilesStorage.getByEntityId(this.controllerEntityId);

			return data.map((file) => file.params);
		}

		getFilesFromGivenCacheObject(cache)
		{
			if (!this.controllerEntityId)
			{
				logger.error('Entity id is not defined');

				return [];
			}

			const data = Array.isArray(cache[this.controllerEntityId]) ? [...cache[this.controllerEntityId]] : [];

			return data.map((file) => file.params);
		}

		updateCachedFiles(cache)
		{
			const newCachedFiles = this.filterCacheFiles(this.getFilesFromGivenCacheObject(cache));
			if (!isEqual(this.filesFromCache, newCachedFiles, true))
			{
				this.logger.info(
					'FileWithBackgroundAttachFieldClass updateCachedFiles',
					this.filesFromCache,
					newCachedFiles,
				);

				this.filesFromCache = newCachedFiles;

				const filesWithErrors = newCachedFiles.filter((file) => file.hasError && !file.errorShown);
				if (filesWithErrors.length > 0)
				{
					this.showUploaderErrorToast(filesWithErrors);
				}

				this.handleChange(this.getValue());
			}
		}

		cancelFileUploadTask(file)
		{
			const attachToEntityController = this.getConfig().attachToEntityController;
			if (attachToEntityController)
			{
				BX.postComponentEvent(
					'FilesBackgroundAttachManager::removeFileByEntityIdAndFileId',
					[this.controllerEntityId, file.id],
				);
			}

			super.cancelFileUploadTask(file);
		}

		onUploadDone(id, eventData)
		{
			if (this.getConfig().attachToEntityController)
			{
				return;
			}

			super.onUploadDone(id, eventData);
		}

		onUploadError(id, eventData)
		{
			if (this.getConfig().attachToEntityController)
			{
				return;
			}

			super.onUploadError(id, eventData);
		}

		showUploaderErrorToast(filesWithErrors)
		{
			const sameUuid = filesWithErrors.some((file) => file.uuid === this.uuid);

			// show toast only if the component with field is visible and active
			// show toast only once for each file field (ui form has 2 file fields with same files)
			if (
				PageManager.getNavigator().isVisible()
				&& (PageManager.getNavigator().isActiveTab() || this.fileAttachmentRef)
				&& sameUuid
			)
			{
				// debounced to show 1 error toast for all files
				this.showToastDebounced();

				const fileIds = filesWithErrors.map((file) => file.id);

				BX.postComponentEvent(
					'FilesBackgroundAttachManager::setErrorShownToFiles',
					[this.controllerEntityId, fileIds],
				);
			}
		}

		prepareFileUploadTasks(files)
		{
			if (!files || !Array.isArray(files))
			{
				return [];
			}

			const { controller: controllerConfig, attachToEntityController } = this.getConfig();
			const { endpoint: controller, options: controllerOptions } = controllerConfig;

			return (
				files
					.filter((file) => !file.isDiskFile)
					.map((file) => {
						const uuid = Uuid.getV4();
						const taskId = FILE_TASK_ID_PREFIX + uuid;
						const extension = getExtension(file.name);

						let fileName = file.name;
						if (extension === 'heic')
						{
							fileName = `${fileName.slice(0, Math.max(0, fileName.length - extension.length))}jpg`;
						}

						file.id = taskId;
						file.uuid = this.uuid;
						file.isUploading = true;

						return {
							taskId,
							controller,
							controllerOptions,
							attachToEntityController,
							params: file,
							name: fileName,
							type: file.type,
							mimeType: getMimeType(file.type),
							url: file.url,
							resize: this.getResizeOptions(file.type),
							onDestroyEventName: Events.FILE_CREATED,
						};
					})
			);
		}

		/**
		 * @public
		 */
		getValue()
		{
			const value = [
				...this.getFilteredValueFromProps(),
				...this.filesFromCache,
			];

			// remove possible duplicates from cache
			return this.getUniqueFiles(value);
		}

		getUniqueFiles(files)
		{
			return uniqBy(files, (file) => {
				if (file.isDiskFile)
				{
					return prepareObjectId(file.id);
				}

				return file.id;
			});
		}

		onDeleteFile(deletedFileIndex)
		{
			const files = this.getValue();
			let filesAfterDeletion = [];

			if (this.isMultiple())
			{
				filesAfterDeletion = files.filter((file, currentIndex) => currentIndex !== deletedFileIndex);
			}

			const deletedFile = files.find((file) => !filesAfterDeletion.includes(file));

			if (!Number.isInteger(deletedFileIndex) || !deletedFile)
			{
				return;
			}

			if (!Number.isInteger(deletedFile.id))
			{
				UnattachedFilesStorage.removeFileByEntityIdAndFileId(this.controllerEntityId, deletedFile.id);
			}

			super.onDeleteFile(deletedFileIndex);
		}
	}

	FileWithBackgroundAttachFieldClass.propTypes = {
		...FileFieldClass.propTypes,
		config: PropTypes.shape({
			// base field props
			showAll: PropTypes.bool, // show more button with count if it's multiple
			styles: PropTypes.shape({
				externalWrapperBorderColor: PropTypes.string,
				externalWrapperBorderColorFocused: PropTypes.string,
				externalWrapperBackgroundColor: PropTypes.string,
				externalWrapperMarginHorizontal: PropTypes.number,
			}),
			deepMergeStyles: PropTypes.object, // override styles
			parentWidget: PropTypes.object, // parent layout widget
			copyingOnLongClick: PropTypes.bool,
			titleIcon: PropTypes.object,

			// file field props
			controller: PropTypes.shape({
				entityId: PropTypes.string,
				endpoint: PropTypes.string,
				options: PropTypes.object,
			}),
			items: PropTypes.oneOfType([
				PropTypes.object,
				PropTypes.objectOf(itemsShape),
			]),
			/**
			 * @deprecated // empty array or filled object
			 */
			fileInfo: PropTypes.oneOfType([PropTypes.array, PropTypes.objectOf(itemsShape)]),
			mediaType: PropTypes.oneOf(Object.values(NativeViewerMediaTypes)),
			enableToEdit: PropTypes.bool, // enable delete file without opening attachment list
			emptyEditableButtonStyle: PropTypes.object,

			disk: PropTypes.oneOfType([
				PropTypes.object, PropTypes.shape({
					isDiskModuleInstalled: PropTypes.bool,
					isWebDavModuleInstalled: PropTypes.bool,
					fileAttachPath: PropTypes.string,
				}),
			]),

			attachToEntityController: PropTypes.shape({
				entityId: PropTypes.string.isRequired, // entity id (should be uniq for each entity)
				actionName: PropTypes.string.isRequired,
				fieldName: PropTypes.string.isRequired, // field name for action data
				actionConfigData: PropTypes.object.isRequired, // additional data for action
				entityUrl: PropTypes.string.isRequired, // url to open entity wtih attached files
			}),
		}),
	};

	module.exports = {
		FileWithBackgroundAttachFieldClass,
	};
});
