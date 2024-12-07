/**
 * @module layout/ui/fields/file
 */
jn.define('layout/ui/fields/file', (require, exports, module) => {
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const { clip, pen } = require('assets/common');
	const { Icon } = require('assets/icons');
	const { Haptics } = require('haptics');
	const { BaseField } = require('layout/ui/fields/base');
	const { filePreview } = require('layout/ui/fields/file/file-preview');
	const { FileAttachment } = require('layout/ui/file-attachment');
	const { UploaderClient } = require('uploader/client');
	const { Events } = require('uploader/const');
	const { uniqBy } = require('utils/array');
	const { debounce, throttle } = require('utils/function');
	const { clone, isEqual } = require('utils/object');
	const { Uuid } = require('utils/uuid');
	const { Circle } = require('utils/skeleton');
	const {
		NativeViewerMediaTypes,
		getNativeViewerMediaType,
		getMimeType,
		getExtension,
	} = require('utils/file');

	const FILE_TASK_ID_PREFIX = 'mobile-file-field-';
	const HIDDEN_FILES_COUNTER_WIDTH = 36;
	const HIDDEN_FILES_COUNTER_HEIGHT = 36;
	const FILE_PREVIEW_MEASURE = 66;
	const EDIT_BUTTON_WIDTH = 36;
	const VISIBLE_FILES_COUNT = 3;

	/**
	 * @class FileField
	 */
	class FileField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.uuid = Uuid.getV4();
			this.displayedAlertsSet = new Set();

			this.queuedValue = null;

			/** @type {FileAttachment|null} */
			this.fileAttachmentRef = null;
			this.fileAttachmentWidget = null;

			this.loadingFileResolver = new Map();

			this.onUploadDone = this.onUploadDone.bind(this);
			this.onUploadError = this.onUploadError.bind(this);
			this.openFilePicker = this.openFilePicker.bind(this);

			if (!this.isReadOnly())
			{
				this.customContentClickHandler = throttle(this.onFileFieldContentClick, 500, this);
			}

			this.deleteFileHandler = this.onDeleteFile.bind(this);
			this.throttleFileErrorNotification = throttle(() => Haptics.notifyWarning(), 3000, this);

			this.debouncedHandleChange = debounce(() => {
				super.handleChange(this.queuedValue);
			}, 100, this);

			this.isShowImagePickerOpen = false;
		}

		useDebounceOnChange()
		{
			return true;
		}

		get onFilePreviewMenuClick()
		{
			return BX.prop.getFunction(this.props, 'onFilePreviewMenuClick', null);
		}

		get onFileAttachmentViewHidden()
		{
			return BX.prop.getFunction(this.props, 'onFileAttachmentViewHidden', null);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			if (this.queuedValue !== null && isEqual(this.queuedValue, newProps.value))
			{
				this.queuedValue = null;
			}

			// workaround for Android, componentDidUpdate not working stable
			setTimeout(() => this.checkLoadingFilesResolvers(), 50);
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			this.cancelAllUploadTasks();
		}

		componentDidUpdate(prevProps, prevState)
		{
			this.onChangeAttachments(this.getFilesInfo(this.getValue()));
			this.refreshFileAttachmentWidgetTitle();
			this.checkLoadingFilesResolvers();
		}

		showFilesName()
		{
			return BX.prop.getBoolean(
				this.props,
				'showFilesName',
				true,
			);
		}

		checkLoadingFilesResolvers()
		{
			const files = this.getValue();

			files.forEach((file) => {
				if (BX.type.isPlainObject(file) && this.loadingFileResolver.has(file.id))
				{
					const { resolve, reject } = this.loadingFileResolver.get(file.id);

					if (file.hasError)
					{
						reject(file);
						this.loadingFileResolver.delete(file.id);
					}
					else if (!file.isUploading)
					{
						resolve(file);
						this.loadingFileResolver.delete(file.id);
					}
				}
			});
		}

		getConfig()
		{
			const config = super.getConfig();
			let controllerKey = 'controller';
			if (Object.prototype.hasOwnProperty.call(config, 'uploadController'))
			{
				controllerKey = 'uploadController';
			}

			const controller = BX.prop.getObject(config, controllerKey, {});

			if (!this.isReadOnly())
			{
				const entityId = BX.prop.getString(controller, 'entityId', null);
				const endpoint = BX.prop.getString(controller, 'endpoint', null);

				if (entityId && !endpoint)
				{
					controller.endpoint = this.resolveControllerEndPoint(entityId);
				}

				if (!controller.endpoint)
				{
					console.warn('FileField: "entityId" or "endpoint" option must be defined in controller config.');
				}
			}

			return {
				...config,
				controller,
				fileInfo: this.getItems(config),
				mediaType: BX.prop.getString(config, 'mediaType', NativeViewerMediaTypes.FILE),
				isEnabledToEdit: BX.prop.getBoolean(config, 'enableToEdit', !this.isReadOnly()),
				isShimmed: BX.prop.getBoolean(config, 'isShimmed', false),
				emptyEditableButtonStyle: BX.prop.getObject(config, 'emptyEditableButtonStyle', {}),
			};
		}

		/**
		 * @private
		 * @param {object} config
		 * @return {object}
		 */
		getItems(config)
		{
			if (config.items)
			{
				return BX.prop.getObject(config, 'items', {});
			}

			return BX.prop.getObject(config, 'fileInfo', {});
		}

		resolveControllerEndPoint(entityId)
		{
			// eslint-disable-next-line default-case
			switch (entityId)
			{
				case 'crm-entity':
					return 'crm.FileUploader.EntityFieldController';

				case 'catalog-product':
					return 'catalog.UI.fileUploader.productController';

				case 'catalog-document':
					return 'catalog.UI.fileUploader.documentController';
			}

			return null;
		}

		prepareValue(value)
		{
			let preparedValue = super.prepareValue(value);

			if (!preparedValue)
			{
				preparedValue = [];
			}

			if (!Array.isArray(preparedValue))
			{
				preparedValue = [preparedValue];
			}

			return preparedValue;
		}

		prepareSingleValue(value)
		{
			if (Array.isArray(value))
			{
				return value[0] || null;
			}

			return value;
		}

		getValidationError()
		{
			let error = super.getValidationError();
			if (!error)
			{
				const hasFileWithError = this.getValue().find((file) => file.hasError === true);
				if (hasFileWithError)
				{
					error = BX.message('FIELDS_FILE_VALIDATION_ERROR');
				}
			}

			return error;
		}

		isDiskEnabled()
		{
			const config = this.getConfig();

			return (
				config.disk
				&& config.disk.isDiskModuleInstalled
				&& config.disk.isWebDavModuleInstalled
			);
		}

		getFilesCount()
		{
			return this.getValue().length;
		}

		isShimmed()
		{
			return this.isReadOnly() && this.getConfig().isShimmed;
		}

		isEmpty()
		{
			return this.getFilesCount() === 0;
		}

		showAddButton()
		{
			return BX.prop.getBoolean(this.props, 'showAddButton', true);
		}

		renderEditIcon()
		{
			if (this.isEmpty())
			{
				return null;
			}

			return View(
				{
					style: {
						width: EDIT_BUTTON_WIDTH,
						alignItems: 'flex-end',
						alignSelf: 'flex-start',
					},
				},
				View(
					{
						style: {
							width: 28,
							height: 28,
							justifyContent: 'center',
							alignItems: 'center',
							marginTop: 30,
						},
						onClick: () => {
							this.onOpenAttachmentList();
						},
					},
					Image(
						{
							style: {
								width: 14,
								height: 15,
							},
							svg: {
								content: pen(),
							},
						},
					),
				),
			);
		}

		// workaround to focus if already focused (because there is no events for file picker close)
		setFocus()
		{
			return this.setFocusInternal();
		}

		renderReadOnlyContent()
		{
			if (this.isShimmed())
			{
				return this.renderShimmedReadOnlyFilesView();
			}

			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: this.styles.fieldWrapper,
				},
				this.getFilesView(),
			);
		}

		renderEditableContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyEditableContent();
			}

			return View(
				{
					style: this.styles.fieldWrapper,
					clickable: false,
				},
				this.getFilesView(),
			);
		}

		renderEmptyEditableContent()
		{
			if (this.hasHiddenEmptyView())
			{
				return null;
			}

			const { emptyEditableButtonStyle } = this.styles;

			return View(
				{
					style: {
						height: 40,
						width: '100%',
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
						borderWidth: 1,
						borderColor: (emptyEditableButtonStyle.borderColor || emptyEditableButtonStyle.backgroundColor),
						borderRadius: 6,
						backgroundColor: emptyEditableButtonStyle.backgroundColor,
					},
					clickable: false,
				},
				Image(
					{
						style: {
							width: 18,
							height: 17,
							marginRight: 7,
						},
						clickable: false,
						resizeMode: 'center',
						tintColor: emptyEditableButtonStyle.iconColor,
						svg: {
							content: svgImages.file,
						},
					},
				),
				Text(
					{
						style: {
							fontSize: 16,
							color: emptyEditableButtonStyle.textColor,
						},
						clickable: false,
						text: this.getAddButtonText(),
					},
				),
			);
		}

		/**
		 * @public
		 */
		getFilesInfo(values)
		{
			const fileInfo = this.getConfig().fileInfo;

			return (
				values
					.map((value) => {
						if (BX.type.isNumber(Number(value)))
						{
							return fileInfo[value];
						}

						return value;
					})
					.filter((file) => file)
			);
		}

		getFilePath(url)
		{
			let filePath = url;

			if (url && url.indexOf('file://') !== 0)
			{
				filePath = currentDomain + url;
			}

			return filePath;
		}

		renderShimmedReadOnlyFilesView()
		{
			return View(
				{
					style: {
						...this.styles.fieldWrapper,
					},
				},
				View(
					{
						style: {
							flexWrap: 'no-wrap',
							flexDirection: 'row',
							borderWidth: 0,
						},
					},
					View(
						{
							style: this.styles.filesListWrapper,
						},
						...Array.from({ length: 3 }).fill(
							filePreview({ isShimmed: true }, 0, [], null, true),
						),
						this.renderHiddenFilesCounter(0),
					),
				),
			);
		}

		getFilesView()
		{
			const files = this.getFilesInfo(this.getValue());

			const hiddenFilesCount = Math.max(files.length - VISIBLE_FILES_COUNT, 0);
			const visibleFiles = files.slice(0, files.length - hiddenFilesCount);
			const isEnableToEdit = this.getConfig().isEnabledToEdit;

			const canDeleteFilesInPreview = !this.isReadOnly() && isEnableToEdit;

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							flexWrap: 'no-wrap',
							flexDirection: 'row',
							borderWidth: 0,
						},
					},
					View(
						{
							style: this.styles.filesListWrapper,
						},
						...visibleFiles.map((file, index) => filePreview(
							file,
							index,
							files,
							(canDeleteFilesInPreview || file.token) && this.deleteFileHandler,
							this.showFilesName(),
						)),
						hiddenFilesCount && this.renderHiddenFilesCounter(hiddenFilesCount),
					),
				),
				this.renderAddButton(),
			);
		}

		renderAddButton()
		{
			if (this.isReadOnly() || !this.showAddButton())
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginTop: 10,
					},
					onClick: () => this.focus(),
				},
				View(
					{
						style: {
							width: 20,
							height: 18,
							justifyContent: 'center',
							alignItems: 'center',
							marginRight: 4,
						},
					},
					Image(
						{
							style: {
								width: 17,
								height: 19,
							},
							svg: {
								content: clip,
							},
						},
					),
				),
				Text(
					{
						style: {
							color: AppTheme.colors.base4,
							fontSize: 15,
						},
						text: this.getAddButtonText(),
					},
				),
			);
		}

		/**
		 * @public
		 * @return {string}
		 */
		getAddButtonText()
		{
			const { mediaType } = this.getConfig();

			if (!this.isEmpty() && !this.isMultiple())
			{
				if (mediaType === NativeViewerMediaTypes.IMAGE)
				{
					return BX.message('FIELDS_FILE_EDIT_IMAGE');
				}

				if (mediaType === NativeViewerMediaTypes.VIDEO)
				{
					return BX.message('FIELDS_FILE_EDIT_VIDEO');
				}

				return BX.message('FIELDS_FILE_EDIT_FILE');
			}

			if (mediaType === NativeViewerMediaTypes.IMAGE)
			{
				return BX.message('FIELDS_FILE_ADD_IMAGE');
			}

			if (mediaType === NativeViewerMediaTypes.VIDEO)
			{
				return BX.message('FIELDS_FILE_ADD_VIDEO');
			}

			return this.isMultiple() ? BX.message('FIELDS_FILE_ADD_FILES') : BX.message('FIELDS_FILE_ADD_FILE');
		}

		/**
		 * @public
		 */
		openFilePicker()
		{
			this.isShowImagePickerOpen = true;
			if (this.isReadOnly())
			{
				return;
			}

			const items = [
				{
					id: 'mediateka',
					name: BX.message('FIELDS_FILE_MEDIATEKA'),
				},
				{
					id: 'camera',
					name: BX.message('FIELDS_FILE_CAMERA'),
				},
			];

			if (this.isDiskEnabled())
			{
				items.push({
					id: 'disk',
					name: BX.message('FIELDS_FILE_B24_DISK_MSGVER_1'),
					dataSource: {
						multiple: this.isMultiple(),
						url: this.getConfig().disk.fileAttachPath,
					},
				});
			}

			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: this.getMediaTypeId(this.getConfig().mediaType),
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						maxAttachedFilesCount: (this.isMultiple() ? '100' : '1'),
						previewMaxWidth: 120,
						previewMaxHeight: 120,
						attachButton: { items },
					},
				},
				(data) => {
					this.isShowImagePickerOpen = false;

					this.removeFocus()
						.then(() => this.onAddFile(data))
						.catch(console.error);
				},
				() => {
					this.isShowImagePickerOpen = false;

					this.removeFocus();
				},
			);
		}

		handleChange(...values)
		{
			const value = Array.isArray(values[0]) ? [...values[0]] : [];

			if (this.useDebounceOnChange())
			{
				this.queuedValue = value;

				this.debouncedHandleChange();
			}
			else
			{
				super.handleChange(value);
			}

			return Promise.resolve();
		}

		/**
		 * @public
		 */
		getValue()
		{
			if (this.queuedValue)
			{
				this.logger.log('FileField: queuedValue', this.queuedValue.length, this.queuedValue);

				return this.queuedValue;
			}

			this.logger.log('FileField: getValue without queuedValue', super.getValue().length, super.getValue());

			return super.getValue();
		}

		hasUploadingFiles(checkUuid = false)
		{
			return (
				this
					.getValue()
					.some((file) => {
						if (BX.type.isPlainObject(file))
						{
							if (checkUuid && file.uuid !== this.uuid)
							{
								return false;
							}

							return file.isUploading;
						}

						return false;
					})
			);
		}

		hasFilesWithErrors()
		{
			const files = clone(this.getValue());

			return files.some((file) => BX.type.isPlainObject(file) && file.hasError);
		}

		getValueWhileReady()
		{
			const files = clone(this.getValue());

			const loadingFilePromises = (
				files
					.filter((file) => BX.type.isPlainObject(file) && file.isUploading)
					.map((file) => new Promise((resolve, reject) => {
						this.loadingFileResolver.set(file.id, { resolve, reject });
					}))
			);

			if (loadingFilePromises.length === 0)
			{
				return Promise.resolve(this.isMultiple() ? files : (files[0] || null));
			}

			return (
				Promise
					.all(loadingFilePromises)
					.then(() => this.getValueWhileReady())
			);
		}

		onAddFile(addedFiles)
		{
			let addedFilesWithFilter = this.filterFilesByValidMediaType(addedFiles, this.getConfig().mediaType);

			if (addedFiles.length > addedFilesWithFilter.length)
			{
				Alert.alert(
					BX.message('FIELDS_FILE_MEDIA_TYPE_ALERT_TITLE'),
					BX.message('FIELDS_FILE_MEDIA_TYPE_ALERT_DESCR'),
				);
			}

			if (addedFilesWithFilter.length === 0)
			{
				return;
			}

			addedFilesWithFilter = clone(addedFilesWithFilter);

			if (this.isDiskEnabled())
			{
				addedFilesWithFilter = this.prepareDiskFiles(addedFilesWithFilter);
			}
			const fileUploadTasks = this.prepareFileUploadTasks(addedFilesWithFilter);
			this.uploadFiles(fileUploadTasks);

			let files = [...addedFilesWithFilter];

			if (this.isMultiple())
			{
				files = [...this.getValue(), ...addedFilesWithFilter];
			}

			Haptics.impactLight();
			this.handleChange(files);
		}

		/**
		 * @param {number} hiddenFilesCount
		 */
		renderHiddenFilesCounter(hiddenFilesCount)
		{
			if (this.isShimmed())
			{
				return View(
					{
						style: this.styles.hiddenFilesCounterWrapper,
					},
					Circle(HIDDEN_FILES_COUNTER_WIDTH),
				);
			}

			let text = hiddenFilesCount > 99 ? '99+' : `+${hiddenFilesCount}`;

			if (this.props.onPrepareHiddenFilesCounterText)
			{
				text = this.props.onPrepareHiddenFilesCounterText(hiddenFilesCount, this);
			}

			return View(
				{
					style: this.styles.hiddenFilesCounterWrapper,
					onClick: () => {
						this.onOpenAttachmentList();
					},
				},
				this.hasUploadingFiles(true) ? Loader({
					style: {
						width: 18,
						height: 18,
					},
					tintColor: AppTheme.colors.base6,
					animating: true,
					size: 'small',
				}) : Text(
					{
						text,
						style: this.styles.hiddenFilesCounterText,
					},
				),
			);
		}

		onOpenAttachmentList()
		{
			return new Promise((resolve) => {
				this.getPageManager().openWidget(
					'layout',
					{
						titleParams: {
							text: BX.message('FIELDS_FILE_ATTACHMENTS_NAVIGATION_TITLE').replace(
								'#NUM#',
								this.getFilesCount(),
							),
							type: 'dialog',
						},
						modal: false,
						backdrop: {
							mediumPositionPercent: this.getFilesCount() > 8 ? 75 : 50,
							horizontalSwipeAllowed: false,
							swipeContentAllowed: true,
							navigationBarColor: AppTheme.colors.bgSecondary,
						},
						onReady: (layoutWidget) => {
							this.fileAttachmentWidget = layoutWidget;
							const imageSize = device.screen.width > 375
								? FILE_PREVIEW_MEASURE
								: device.screen.width * FILE_PREVIEW_MEASURE / 375;

							layoutWidget.enableNavigationBarBorder(false);

							layoutWidget.showComponent(
								new FileAttachment({
									ref: (ref) => {
										this.fileAttachmentRef = ref;
										resolve({ fileAttachmentRef: ref, layoutWidget });
									},
									attachments: this.getFilesInfo(this.getValue()),
									layoutWidget,
									onDeleteAttachmentItem: !this.isReadOnly() && this.onDeleteFile.bind(this),
									styles: {
										wrapper: {
											marginBottom: 12,
											marginHorizontal: 3,
											paddingRight: 9,
										},
										imagePreview: {
											width: imageSize,
											height: imageSize,
										},
										imageOutline: (hasError) => ({
											width: imageSize,
											height: imageSize,
											position: 'absolute',
											top: 8,
											right: 9,
											borderColor: hasError ? AppTheme.colors.accentMainAlert : AppTheme.colors.base1,
											backgroundColor: hasError ? AppTheme.colors.accentMainAlert : null,
											borderWidth: 1,
											opacity: hasError ? 0.5 : 0.08,
											borderRadius: 6,
										}),
										deleteButtonWrapper: this.isReadOnly() ? null : {
											width: 18,
											height: 18,
											right: 0,
										},
										menuButtonWrapper: this.isReadOnly() ? null : {
											width: 18,
											height: 18,
											right: 0,
										},
									},
									showName: this.showFilesName(),
									showAddButton: !this.isReadOnly(),
									addButtonText: this.getAddButtonText(),
									onAddButtonClick: () => this.openFilePicker(),
									onFilePreviewMenuClick: this.onFilePreviewMenuClick,
									onViewHidden: () => {
										if (!this.isShowImagePickerOpen)
										{
											this.onFileAttachmentViewHidden?.();
										}
									},
								}),
							);
						},
						onError: (error) => console.error(error),
					},
				);
			});
		}

		/**
		 * @public
		 * @param {number} deletedFileIndex
		 */
		onDeleteFile(deletedFileIndex)
		{
			const files = this.getValue();
			let filesAfterDeletion = [];

			if (this.isMultiple())
			{
				filesAfterDeletion = files.filter((file, currentIndex) => currentIndex !== deletedFileIndex);
			}

			const deletedFile = files.find((file) => !filesAfterDeletion.includes(file));
			if (deletedFile)
			{
				this.cancelFileUploadTask(deletedFile);
			}

			Haptics.impactLight();
			this.handleChange(filesAfterDeletion);
		}

		refreshFileAttachmentWidgetTitle()
		{
			this.fileAttachmentWidget?.setTitle(
				{
					text: BX.message('FIELDS_FILE_ATTACHMENTS_NAVIGATION_TITLE').replace('#NUM#', this.getFilesCount()),
				},
				true,
			);
		}

		getDefaultStyles()
		{
			const styles = this.getChildFieldStyles();

			if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return styles;
		}

		getChildFieldStyles()
		{
			const hasErrorMessage = this.hasErrorMessage();
			const paddingBottomWithoutError = this.isEmpty() ? 12 : 10;

			return {
				...super.getDefaultStyles(),
				fieldWrapper: {
					flex: 1,
					borderWidth: 0,
				},
				wrapper: {
					paddingTop: this.isEmpty() ? 12 : 7,
					paddingBottom: hasErrorMessage ? 5 : paddingBottomWithoutError,
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: hasErrorMessage ? 5 : 9,
				},
				filesListWrapper: {
					flexDirection: 'row',
					alignItems: 'flex-start',
					borderColor: AppTheme.colors.bgContentPrimary,
					flexGrow: 2,
				},
				hiddenFilesCounterWrapper: {
					borderColor: AppTheme.colors.base6,
					borderWidth: 0.5,
					borderRadius: 18,
					width: HIDDEN_FILES_COUNTER_WIDTH,
					height: HIDDEN_FILES_COUNTER_HEIGHT,
					alignItems: 'center',
					alignSelf: 'flex-start',
					justifyContent: 'center',
					marginLeft: 7,
					marginTop: 10,
				},
				hiddenFilesCounterText: {
					fontSize: 17,
					color: AppTheme.colors.base3,
				},
				emptyEditableButtonStyle: {
					borderColor: AppTheme.colors.accentMainPrimary,
					backgroundColor: AppTheme.colors.accentMainPrimary,
					iconColor: AppTheme.colors.baseWhiteFixed,
					textColor: AppTheme.colors.baseWhiteFixed,
					...this.getConfig().emptyEditableButtonStyle,
				},
			};
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			const isEmpty = this.isEmpty();

			return {
				...styles,
				wrapper: {
					paddingTop: isEmpty ? 12 : 8,
					paddingBottom: isEmpty ? 18 : 14,
				},
			};
		}

		getMediaTypeId(mediaType)
		{
			switch (mediaType)
			{
				case NativeViewerMediaTypes.IMAGE:
					return 0;
				case NativeViewerMediaTypes.VIDEO:
					return 1;
				default:
					return 2;
			}
		}

		filterFilesByValidMediaType(files, mediaType)
		{
			if (mediaType === NativeViewerMediaTypes.FILE)
			{
				return files;
			}

			return files.filter((file) => getNativeViewerMediaType(getMimeType(file.type)) === mediaType);
		}

		prepareDiskFiles(files)
		{
			return files.map((file) => {
				if (!file.dataAttributes)
				{
					return file;
				}

				return {
					id: file.dataAttributes.VALUE,
					name: file.name,
					type: file.type,
					url: file.url,
					uuid: this.uuid,
					isUploading: false,
					isDiskFile: true,
				};
			});
		}

		prepareFileUploadTasks(files)
		{
			if (!files || !Array.isArray(files))
			{
				return [];
			}

			const { controller: controllerConfig } = this.getConfig();
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

		uploadFiles(tasks)
		{
			if (tasks.length === 0)
			{
				return false;
			}

			tasks.forEach((task) => this.getUploader().addTask(task));

			return true;
		}

		cancelAllUploadTasks()
		{
			this
				.getValue()
				.forEach((file) => this.cancelFileUploadTask(file))
			;
		}

		cancelFileUploadTask(file)
		{
			if (!file || !file.id || !file.isUploading)
			{
				return;
			}

			this.getUploader().cancelTask(file.id);
		}

		/**
		 * @returns {UploaderClient}
		 */
		getUploader()
		{
			if (!this.uploader)
			{
				this.uploader = new UploaderClient(`ui/fields/file/${this.getId()}`);
				this.uploader
					.on('done', this.onUploadDone)
					.on('error', this.onUploadError)
				;
			}

			return this.uploader;
		}

		onUploadDone(id, eventData)
		{
			const { file: currentFile, result } = eventData;

			if (!currentFile || !result)
			{
				return;
			}

			if (currentFile.params.uuid !== this.uuid)
			{
				return;
			}

			if (result.status !== 'success')
			{
				this.showUploaderErrorAlert(result);
				this.processUploaderErrors(currentFile);

				return;
			}

			const files = clone(this.getValue()).filter(Boolean);
			const uploadedFile = files.find((file) => file.id === currentFile?.params?.id);

			if (uploadedFile)
			{
				uploadedFile.isUploading = false;
				uploadedFile.token = result.data.token;

				const fileInfo = result.data?.file;
				if (fileInfo)
				{
					uploadedFile.fileId = fileInfo.customData?.fileId;
					uploadedFile.serverFileId = fileInfo.serverFileId;
				}

				this.handleChange(files);
			}
		}

		onUploadError(id, eventData)
		{
			const { file: currentFile, error: result } = eventData;

			if (!currentFile || !result)
			{
				return;
			}

			if (currentFile.params.uuid !== this.uuid)
			{
				return;
			}

			this.showUploaderErrorAlert(result);

			this.processUploaderErrors(currentFile);
		}

		processUploaderErrors(currentFile)
		{
			const files = clone(this.getValue());
			const fileWithError = files.find((file) => file.id === currentFile.params.id);

			if (fileWithError)
			{
				fileWithError.isUploading = false;
				fileWithError.hasError = true;

				this.throttleFileErrorNotification();
				this.handleChange(files);
			}
		}

		showUploaderErrorAlert(result)
		{
			const firstNonSystemError = this.getFirstNonSystemError(result.errors);
			const title = firstNonSystemError && firstNonSystemError.message || BX.message(
				'FIELDS_FILE_UPLOAD_ALERT_TITLE',
			);
			const text = firstNonSystemError && firstNonSystemError.description || BX.message(
				'FIELDS_FILE_UPLOAD_ALERT_DESCR',
			);

			const hash = title + text;

			if (this.displayedAlertsSet.has(hash))
			{
				return;
			}

			this.displayedAlertsSet.add(hash);

			Alert.alert(
				title,
				text,
				() => this.displayedAlertsSet.delete(hash),
				BX.message('FIELDS_FILE_MEDIA_TYPE_ALERT_CONFIRM'),
			);

			console.error(result);
		}

		getFirstNonSystemError(errors)
		{
			if (!errors || !Array.isArray(errors))
			{
				return null;
			}

			return errors.find(({ type, system }) => type === 'file-uploader' && !system);
		}

		getResizeOptions(type)
		{
			// mobile: 0177829:
			return null;
		}

		renderLeftIcons()
		{
			if (this.isEmptyEditable())
			{
				return View(
					{
						style: {
							width: 24,
							height: 24,
							justifyContent: 'center',
							alignItems: 'center',
							marginRight: 8,
						},
					},
					Image(
						{
							tintColor: AppTheme.colors.base3,
							style: {
								width: 15,
								height: 17,
							},
							svg: {
								content: clip,
							},
						},
					),
				);
			}

			return null;
		}

		handleAdditionalFocusActions()
		{
			this.openFilePicker();
		}

		onFileFieldContentClick()
		{
			if (this.isEmpty())
			{
				this.focus();
			}
			else
			{
				this.onOpenAttachmentList();
			}
		}

		onChangeAttachments(value)
		{
			this.fileAttachmentRef?.onChangeAttachments(value);
		}

		getDefaultLeftIcon()
		{
			return Icon.ATTACH;
		}

		getDisplayedValue()
		{
			if (this.isEmpty() || (this.isMultiple() && this.getValue().length > 1))
			{
				return this.getTitleText();
			}

			return this.getFilesInfo(this.getValue())[0]?.name;
		}
	}

	const svgImages = {
		file: '<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.659 0.783569H0.15918V3.1169H12.9925L10.659 0.783569Z" fill="#828B95" /><path d="M18.8258 4.28357H0.15918V17.1169H18.8258V4.28357Z" fill="#828B95" /></svg>',
	};

	const itemsShape = PropTypes.shape({
		id: PropTypes.number,
		name: PropTypes.string,
		type: PropTypes.string,
		url: PropTypes.string,
		width: PropTypes.number,
		height: PropTypes.number,
		previewUrl: PropTypes.string,
		previewWidth: PropTypes.number,
		previewHeight: PropTypes.number,
		dataAttributes: PropTypes.object,
	});

	FileField.propTypes = {
		...BaseField.propTypes,
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
			onFilePreviewMenuClick: PropTypes.func,
		}),
	};

	FileField.defaultProps = {
		...BaseField.defaultProps,
		showFilesName: true,
		showAddButton: true,
		controller: {},
		items: {},
		fileInfo: {},
		disk: {},
	};

	module.exports = {
		FileType: 'file',
		FileFieldClass: FileField,
		FileField: (props) => new FileField(props),
		MediaType: NativeViewerMediaTypes,
		itemsShape,
		FILE_TASK_ID_PREFIX,
	};
});
