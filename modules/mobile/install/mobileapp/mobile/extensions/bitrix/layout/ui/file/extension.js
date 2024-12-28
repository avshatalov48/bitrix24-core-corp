(() => {
	const require = (ext) => jn.require(ext);

	const { confirmDestructiveAction } = require('alert');
	const AppTheme = require('apptheme');
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
	const { Line } = require('utils/skeleton');
	const { Loc } = require('loc');
	const { ShimmedSafeImage } = require('layout/ui/safe-image');

	const throttledNativeViewer = throttle(openNativeViewer, 500);

	function getFileIconType(extension)
	{
		let result = null;

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
				.filter(({ type, name }) => getNativeViewerMediaType(getMimeType(
					type,
					name,
				)) === NativeViewerMediaTypes.IMAGE)
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
			textLines,
			onFilePreviewMenuClick,
		} = options;

		let { url, imageUri, files } = options;

		imageUri = encodeURI(imageUri);
		imageUri = getAbsolutePath(imageUri);

		url = encodeURI(url);
		url = getAbsolutePath(url);

		files = Array.isArray(files) ? clone(files) : [];
		const images = prepareImageCollection(files, id, url);
		let ellipsize = 'middle';
		if (Application.getPlatform() === 'android' && textLines > 1)
		{
			ellipsize = 'end';
		}

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
				ShimmedSafeImage({
					testId: 'pinnedFileImage',
					style: styles.imagePreview,
					uri: imageUri,
					resizeMode: 'cover',
					clickable: false,
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
							color: hasError ? AppTheme.colors.accentMainAlert : AppTheme.colors.base4,
							fontWeight: '500',
							fontSize: 10,
							textAlign: 'center',
						},
						text: name,
						numberOfLines: textLines,
						ellipsize,
					}),
				),
			),
			isLoading && View(
				{
					style: {
						...styles.imageOutline(false),
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderColor: null,
						opacity: 0.5,
					},
				},
				Loader({
					style: {
						width: styles.imagePreview.width,
						height: styles.imagePreview.height,
					},
					tintColor: AppTheme.colors.base0,
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
			onFilePreviewMenuClick && View(
				{
					style: {
						...styles.menuButtonWrapper,
						width: styles.menuButtonWrapper.width + 8,
						height: styles.menuButtonWrapper.height + 8,
					},
					onClick: () => {
						onFilePreviewMenuClick({
							...options,
							imageUri,
							url,
							files,
							images,
						});
					},
				},
				Image({
					testId: 'filePreviewMenu',
					svg: {
						content: DEFAULT_FILE_MENU_ICON,
					},
					resizeMode: 'cover',
					style: {
						width: styles.menuButtonWrapper.width,
						height: styles.menuButtonWrapper.height,
					},
				}),
			),
			!onFilePreviewMenuClick && onDeleteAttachmentItem && View(
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
				}),
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
			textLines,
			onFilePreviewMenuClick,
		} = options;

		let { url, attachmentFileIconFolder } = options;

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
								uri: `${attachmentFileIconFolder + icon}.svg`,
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
							color: hasError ? AppTheme.colors.accentMainAlert : AppTheme.colors.base4,
							fontWeight: '500',
							fontSize: 10,
							textAlign: 'center',
						},
						text: name,
						numberOfLines: textLines,
						ellipsize: Application.getPlatform() === 'android' ? 'end' : 'middle',
					}),
				),
			),
			isLoading && View(
				{
					testId: 'pinnedFileLoader',
					style: {
						...styles.imageOutline(false),
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderColor: null,
						opacity: 0.5,
					},
				},
				Loader({
					style: {
						width: styles.imagePreview.width,
						height: styles.imagePreview.height,
					},
					tintColor: AppTheme.colors.base0,
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
			onFilePreviewMenuClick && View(
				{
					style: {
						...styles.menuButtonWrapper,
						width: styles.menuButtonWrapper.width + 8,
						height: styles.menuButtonWrapper.height + 8,
					},
					onClick: () => {
						onFilePreviewMenuClick({
							...options,
							url,
							attachmentFileIconFolder,
							extension,
							icon,
						});
					},
				},
				Image({
					testId: 'filePreviewMenu',
					svg: {
						content: DEFAULT_FILE_MENU_ICON,
					},
					resizeMode: 'cover',
					style: {
						width: styles.menuButtonWrapper.width,
						height: styles.menuButtonWrapper.height,
					},
				}),
			),
			!onFilePreviewMenuClick && onDeleteAttachmentItem && Image({
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

		let { url, attachmentFileIconFolder } = options;

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
							color: (hasError ? AppTheme.colors.accentMainAlert : AppTheme.colors.base4),
							fontWeight: '400',
							fontSize: 16,
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
						color: (hasError ? AppTheme.colors.accentMainAlert : AppTheme.colors.base4),
						fontWeight: '400',
						fontSize: 16,
					},
					text: size.toUpperCase(),
					numberOfLines: 1,
					ellipsize: 'middle',
				}),
			),
		);
	}

	function renderShimmedFile(options)
	{
		const { styles, textLines } = options;

		return View(
			{
				style: styles.wrapper,
			},
			View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				ShimmedSafeImage({
					style: styles.imagePreview,
					uri: '',
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
					...Array.from({ length: textLines }).fill(
						Line('100%', 8, 4),
					),
				),
			),
		);
	}

	function deleteItem(onDeleteAttachmentItem)
	{
		confirmDestructiveAction({
			title: '',
			description: Loc.getMessage('UI_FILE_ATTACHMENT_DELETE_CONFIRM_TITLE_MSGVER_1'),
			onDestruct: onDeleteAttachmentItem,
		});
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
				borderColor: hasError ? AppTheme.colors.accentMainAlert : AppTheme.colors.bgSeparatorPrimary,
				backgroundColor: hasError ? AppTheme.colors.accentMainAlert : null,
				borderWidth: 1,
				opacity: 0.5,
				borderRadius: 6,
			}),
			deleteButtonWrapper: {
				position: 'absolute',
				top: 0,
				right: 0,
				justifyContent: 'flex-start',
				alignItems: 'flex-end',
			},
			menuButtonWrapper: {
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

	const DEFAULT_CLOSE_ICON = `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="9" r="8.5" fill="${AppTheme.colors.base6}" stroke="${AppTheme.colors.bgContentPrimary}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M10.125 9L12.375 11.25L11.25 12.375L9 10.125L6.75 12.375L5.625 11.25L7.875 9L5.625 6.75L6.75 5.625L9 7.875L11.25 5.625L12.375 6.75L10.125 9Z" fill="${AppTheme.colors.bgContentPrimary}"/></svg>`;
	const DEFAULT_FILE_ICONS_FOLDER = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/file/images/file/';
	const DEFAULT_FILE_MENU_ICON = `	<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="8.5" fill="${AppTheme.colors.base6}" stroke="${AppTheme.colors.bgContentPrimary}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.73416 9.99977C7.73416 10.8675 7.03072 11.571 6.16298 11.571C5.29524 11.571 4.5918 10.8675 4.5918 9.99977C4.5918 9.13203 5.29524 8.42859 6.16298 8.42859C7.03072 8.42859 7.73416 9.13203 7.73416 9.99977ZM6.16298 10.7376C6.57048 10.7376 6.90082 10.4073 6.90082 9.99977C6.90082 9.59227 6.57048 9.26192 6.16298 9.26192C5.75548 9.26192 5.42513 9.59227 5.42513 9.99977C5.42513 10.4073 5.75548 10.7376 6.16298 10.7376ZM11.5709 9.99977C11.5709 10.8675 10.8675 11.571 9.99975 11.571C9.13201 11.571 8.42857 10.8675 8.42857 9.99977C8.42857 9.13203 9.13201 8.42859 9.99975 8.42859C10.8675 8.42859 11.5709 9.13203 11.5709 9.99977ZM9.99975 10.7376C10.4073 10.7376 10.7376 10.4073 10.7376 9.99977C10.7376 9.59227 10.4073 9.26192 9.99975 9.26192C9.59225 9.26192 9.2619 9.59227 9.2619 9.99977C9.2619 10.4073 9.59225 10.7376 9.99975 10.7376ZM15.4078 9.99977C15.4078 10.8675 14.7043 11.571 13.8366 11.571C12.9688 11.571 12.2654 10.8675 12.2654 9.99977C12.2654 9.13203 12.9688 8.42859 13.8366 8.42859C14.7043 8.42859 15.4078 9.13203 15.4078 9.99977ZM13.8366 10.7376C14.2441 10.7376 14.5744 10.4073 14.5744 9.99977C14.5744 9.59227 14.2441 9.26192 13.8366 9.26192C13.4291 9.26192 13.0987 9.59227 13.0987 9.99977C13.0987 10.4073 13.4291 10.7376 13.8366 10.7376Z" fill="${AppTheme.colors.bgContentPrimary}"/></svg>`;

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
	 * @param {Boolean} options.isShimmed
	 * @param {Object} options.styles
	 * @param {String} options.attachmentCloseIcon
	 * @param {String} options.attachmentFileIconFolder
	 * @param {Function} options.onDeleteAttachmentItem
	 *
	 * @returns {View}
	 */
	function File(options)
	{
		const { url, type, name, showName, isInLine, isShimmed, textLines, onFilePreviewMenuClick } = options;
		let { imageUri, attachmentCloseIcon, attachmentFileIconFolder, styles } = options;

		styles = buildStyles(styles);
		attachmentCloseIcon = attachmentCloseIcon || DEFAULT_CLOSE_ICON;
		attachmentFileIconFolder = attachmentFileIconFolder || DEFAULT_FILE_ICONS_FOLDER;

		if (isShimmed)
		{
			return renderShimmedFile({ ...options, styles });
		}

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
				textLines,
				onFilePreviewMenuClick,
			});
		}

		if (isInLine)
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
			textLines,
			onFilePreviewMenuClick,
		});
	}

	this.UI = this.UI || {};
	this.UI.File = File;
	this.UI.File.getType = getNativeViewerMediaType;
	this.UI.File.getFileMimeType = getMimeType;
})();
