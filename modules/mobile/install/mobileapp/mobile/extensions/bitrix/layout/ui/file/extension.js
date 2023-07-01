(() => {

	const require = ext => jn.require(ext);

	const { Alert } = require('alert');
	const { throttle } = require('utils/function');
	const { clone, merge } = require('utils/object');
	const {
		getAbsolutePath,
		getNativeViewerMediaType,
		getMimeType,
		getExtension,
		openNativeViewer,
		NativeViewerMediaTypes,
	} = require('utils/file');

	const throttledNativeViewer = throttle(openNativeViewer, 500);

	function getFileIconType(extension)
	{
		let result;

		switch (extension)
		{
			case 'xls':
			case 'xlsx':
				result = 'xls';
				break;

			case 'doc':
			case 'docx':
				result = 'doc';
				break;

			case 'ppt':
			case 'pptx':
				result = 'ppt';
				break;

			case 'txt':
				result = 'txt';
				break;

			case 'pdf':
				result = 'pdf';
				break;

			case 'php':
				result = 'php';
				break;

			case 'rar':
				result = 'rar';
				break;

			case 'zip':
				result = 'zip';
				break;

			case 'mp4':
			case 'mpeg':
			case 'ogg':
			case 'mov':
			case '3gp':
				result = 'video';
				break;

			case 'png':
			case 'gif':
			case 'jpg':
			case 'jpeg':
			case 'heic':
				result = 'image';
				break;

			default:
				result = null;
		}

		return result;
	}

	function prepareImageCollection(files, id, url)
	{
		return (
			files
				.filter(({ type, name }) => getNativeViewerMediaType(getMimeType(type, name)) === NativeViewerMediaTypes.IMAGE)
				.map((image) => {
					// primarily - find by id (we can have same images in different positions)
					if (image.id && id)
					{
						image.default = image.id === id ? true : undefined;
					}
					else if (getAbsolutePath(encodeURI(image.url)) === url)
					{
						image.default = true;
					}
					else
					{
						image.default = undefined;
					}

					return image;
				})
		);
	}

	function renderImage(options)
	{
		const {
			id,
			name,
			fileType,
			isLoading,
			hasError,
			styles,
			attachmentCloseIcon,
			onDeleteAttachmentItem,
		} = options;

		let { url, imageUri, files } = options;

		imageUri = encodeURI(imageUri);
		imageUri = getAbsolutePath(imageUri);

		url = encodeURI(url);
		url = getAbsolutePath(url);

		files = Array.isArray(files) ? clone(files) : [];
		const images = prepareImageCollection(files, id, url);

		return View(
			{
				testId: 'pinnedFileContainer',
				style: styles.wrapper,
				onClick: () => throttledNativeViewer({ fileType, url, name, images }),
			},
			View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Image({
					testId: 'pinnedFileImage',
					style: styles.imagePreview,
					uri: imageUri,
					resizeMode: 'cover',
				}),
				View(
					{
						style: {
							marginTop: 2,
							width: 58,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Text({
						testId: 'pinnedFileName',
						style: {
							color: hasError ? '#ff615c' : '#a8adb4',
							fontWeight: '500',
							fontSize: 10,
							backgroundColor: '#00000000',
							textAlign: 'center',
						},
						text: name,
						numberOfLines: 2,
						ellipsize: Application.getPlatform() !== "android" ? 'middle' : 'end',
					}),
				),
			),
			isLoading && View(
				{
					style: {
						...styles.imageOutline(false),
						backgroundColor: '#ffffff',
						borderColor: null,
						opacity: 0.5,
					},
				},
				Loader({
					style: {
						width: styles.imagePreview.width,
						height: styles.imagePreview.height,
					},
					tintColor: '#000000',
					animating: true,
					size: 'small',
				}),
			),
			View(
				{
					testId: 'pinnedFileOutline',
					style: styles.imageOutline(hasError),
				},
			),
			onDeleteAttachmentItem && View(
				{
					style: {
						...styles.deleteButtonWrapper,
						width: styles.deleteButtonWrapper.width + 8,
						height: styles.deleteButtonWrapper.height + 8,
					},
					onClick: () => deleteItem(onDeleteAttachmentItem),
				},
				Image({
					testId: 'pinnedFileDetach',
					svg: {
						content: attachmentCloseIcon,
					},
					resizeMode: 'cover',
					style: {
						width: styles.deleteButtonWrapper.width,
						height: styles.deleteButtonWrapper.height,
					},
				})
			),
		);
	}

	function renderFile(options)
	{
		const {
			name,
			fileType,
			isLoading,
			hasError,
			styles,
			attachmentCloseIcon,
			onDeleteAttachmentItem,
		} = options;

		let {url, attachmentFileIconFolder} = options;

		url = encodeURI(url);
		attachmentFileIconFolder = getAbsolutePath(attachmentFileIconFolder);

		const extension = getExtension(name || url);
		const icon = getFileIconType(extension) || 'empty';

		return View(
			{
				testId: 'pinnedFileContainer',
				style: styles.wrapper,
				onClick: () => throttledNativeViewer({ fileType, url, name }),
			},
			View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							width: styles.imagePreview.width,
							height: styles.imagePreview.height,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Image(
						{
							testId: 'pinnedFileIcon',
							style: {
								width: styles.imagePreview.width ? styles.imagePreview.width / 2 : 20,
								height: styles.imagePreview.height ? styles.imagePreview.height / 2 : 20,
							},
							svg: {
								uri: attachmentFileIconFolder + icon + '.svg',
							},
							resizeMode: 'contain',
						},
					),
				),
				View(
					{
						style: {
							marginTop: 2,
							width: 58,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Text({
						testId: 'pinnedFileName',
						style: {
							color: hasError ? '#ff615c' : '#a8adb4',
							fontWeight: '500',
							fontSize: 10,
							backgroundColor: '#00000000',
							textAlign: 'center',
						},
						text: name,
						numberOfLines: 2,
						ellipsize: Application.getPlatform() !== "android" ? 'middle' : 'end',
					}),
				),
			),
			isLoading && View(
				{
					testId: 'pinnedFileLoader',
					style: {
						...styles.imageOutline(false),
						backgroundColor: '#ffffff',
						borderColor: null,
						opacity: 0.5,
					},
				},
				Loader({
					style: {
						width: styles.imagePreview.width,
						height: styles.imagePreview.height,
					},
					tintColor: '#000000',
					animating: true,
					size: 'small',
				}),
			),
			View(
				{
					testId: 'pinnedFileOutline',
					style: styles.imageOutline(hasError),
				},
			),
			onDeleteAttachmentItem && Image({
				testId: 'pinnedFileDetach',
				svg: {
					content: attachmentCloseIcon,
				},
				resizeMode: 'cover',
				style: styles.deleteButtonWrapper,
				onClick: () => deleteItem(onDeleteAttachmentItem),
			}),
		);
	}

	function renderFileInLine(options)
	{
		const {
			name,
			fileType,
			hasError,
			styles,
			size,
		} = options;

		let {url, attachmentFileIconFolder} = options;

		url = encodeURI(url);
		attachmentFileIconFolder = getAbsolutePath(attachmentFileIconFolder);

		const extension = (getExtension(name || url));
		const icon = (getFileIconType(extension) || 'empty');

		return View(
			{
				testId: 'pinnedFileContainer',
				style: styles.wrapper,
				onClick: () => throttledNativeViewer({ fileType, url, name }),
			},
			View(
				{
					style: {
						justifyContent: 'space-between',
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
						},
					},
					Image(
						{
							testId: 'pinnedFileIcon',
							style: {
								width: 24,
								height: 24,
							},
							svg: {
								uri: `${attachmentFileIconFolder}${icon}.svg`,
							},
							resizeMode: 'contain',
						},
					),
					Text({
						testId: 'pinnedFileName',
						style: {
							flex: 1,
							color: (hasError ? '#ff615c' : '#a8adb4'),
							fontWeight: '400',
							fontSize: 16,
							backgroundColor: '#00000000',
							marginLeft: 8,
							marginRight: 12,
						},
						text: name,
						numberOfLines: 1,
						ellipsize: 'middle',
					}),
				),
				Text({
					testId: 'pinnedFileSize',
					style: {
						color: (hasError ? '#ff615c' : '#a8adb4'),
						fontWeight: '400',
						fontSize: 16,
						backgroundColor: '#00000000',
					},
					text: size.toUpperCase(),
					numberOfLines: 1,
					ellipsize: 'middle',
				}),
			),
		);
	}

	function deleteItem(onDeleteAttachmentItem)
	{
		Alert.confirm(
			'',
			BX.message('UI_FILE_ATTACHMENT_DELETE_CONFIRM_TITLE'),
			[
				{
					text: BX.message('UI_FILE_ATTACHMENT_DELETE_CONFIRM_OK'),
					type: 'destructive',
					onPress: onDeleteAttachmentItem,
				},
				{
					type: 'cancel',
				},
			],
		);
	}

	function buildStyles(externalStyles)
	{
		let styles = {
			wrapper: {
				paddingTop: 8,
			},
			imagePreview: {
				width: 40,
				height: 40,
				backgroundColor: '#00000000',
				flexDirection: 'column',
				alignItems: 'center',
				justifyContent: 'center',
				borderRadius: 6,
			},
			imageOutline: (hasError) => ({
				width: 40,
				height: 40,
				position: 'absolute',
				top: 8,
				left: 9,
				borderColor: hasError ? '#ff5752' : '#333333',
				backgroundColor: hasError ? '#ff615c' : null,
				borderWidth: 1,
				opacity: hasError ? 0.5 : 0.08,
				borderRadius: 6,
			}),
			deleteButtonWrapper: {
				position: 'absolute',
				top: 0,
				right: 0,
				justifyContent: 'flex-start',
				alignItems: 'flex-end',
			},
		};

		if (externalStyles)
		{
			styles = merge(styles, externalStyles);
		}

		return styles;
	}

	const DEFAULT_CLOSE_ICON = `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="9" r="8.5" fill="#D5D7DB" stroke="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M10.125 9L12.375 11.25L11.25 12.375L9 10.125L6.75 12.375L5.625 11.25L7.875 9L5.625 6.75L6.75 5.625L9 7.875L11.25 5.625L12.375 6.75L10.125 9Z" fill="white"/></svg>`;
	const DEFAULT_FILE_ICONS_FOLDER = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/file/images/file/';

	/**
	 * @function UI.File
	 *
	 * @param {Object} options
	 * @param {String} options.url
	 * @param {String} options.imageUri
	 * @param {String} options.type
	 * @param {String} options.size
	 * @param {String} options.name
	 * @param {Boolean} options.showName
	 * @param {Boolean} options.isInLine
	 * @param {Boolean} options.isLoading
	 * @param {Object} options.styles
	 * @param {String} options.attachmentCloseIcon
	 * @param {String} options.attachmentFileIconFolder
	 * @param {Function} options.onDeleteAttachmentItem
	 *
	 * @returns {View}
	 */
	function File(options)
	{
		const { url, type, name, showName } = options;
		let { imageUri, attachmentCloseIcon, attachmentFileIconFolder, styles } = options;

		styles = buildStyles(styles);
		attachmentCloseIcon = attachmentCloseIcon || DEFAULT_CLOSE_ICON;
		attachmentFileIconFolder = attachmentFileIconFolder || DEFAULT_FILE_ICONS_FOLDER;

		const fileType = getNativeViewerMediaType(getMimeType(type, name));

		if (fileType === NativeViewerMediaTypes.IMAGE && url.indexOf('file://') === 0)
		{
			imageUri = url;
		}

		const isImage = (fileType === NativeViewerMediaTypes.IMAGE);
		const isVideo = (fileType === NativeViewerMediaTypes.VIDEO && imageUri.indexOf('file://') === 0);

		if ((isImage || isVideo) && imageUri.length > 0)
		{
			return renderImage({
				...options,
				name: showName && name,
				imageUri,
				styles,
				attachmentCloseIcon,
				attachmentFileIconFolder,
				fileType,
			});
		}

		if (options.isInLine)
		{
			return renderFileInLine({
				...options,
				styles,
				attachmentCloseIcon,
				attachmentFileIconFolder,
				fileType,
			});
		}

		return renderFile({
			...options,
			styles,
			attachmentCloseIcon,
			attachmentFileIconFolder,
			fileType,
		});
	}

	this.UI = this.UI || {};
	this.UI.File = File;
	this.UI.File.getType = getNativeViewerMediaType;
	this.UI.File.getFileMimeType = getMimeType;
})();