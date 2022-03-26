(() => {
	function getAbsolutePath(url)
	{
		if (url && url.indexOf('file://') !== 0 && url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0)
		{
			url = currentDomain + url;
		}

		return url;
	}

	function getType(mimeType)
	{
		let result = mimeType.substring(0, mimeType.indexOf('/'));

		if (!['image', 'video', 'audio'].includes(result))
		{
			result = 'file';
		}

		return result;
	}

	function getFileMimeType(fileType)
	{
		fileType = fileType.toString().toLowerCase();

		if (fileType.indexOf('/') !== -1) // iOS old form
		{
			return fileType;
		}

		const mimeTypeMap = {
			'png': 'image/png',
			'gif': 'image/gif',
			'jpg': 'image/jpeg',
			'jpeg': 'image/jpeg',
			'heic': 'image/heic',
			'mp3': 'audio/mpeg',
			'mp4': 'video/mp4',
			'mpeg': 'video/mpeg',
			'ogg': 'video/ogg',
			'mov': 'video/quicktime',
			'zip': 'application/zip',
			'php': 'text/php'
		}

		return mimeTypeMap[fileType] ? mimeTypeMap[fileType] : '';
	}

	function getExtension(uri)
	{
		return (
			uri && uri.indexOf('.') >= 0
				? uri.split('.').pop().toLowerCase()
				: ''
		);
	}

	function getFileType(extension)
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

	function openViewer({fileType, url, name, images})
	{
		if (!url)
		{
			return;
		}

		if (fileType === 'video')
		{
			viewer.openVideo(url);
		}
		else if (fileType === 'image')
		{
			if (Array.isArray(images) && images.length)
			{
				viewer.openImageCollection(images)
			}
			else
			{
				viewer.openImage(url, name);
			}
		}
		else
		{
			viewer.openDocument(url, name);
		}
	}

	function prepareImageCollection(files, id, url)
	{
		return (
			files
				.filter((file) => {
					return getType(getFileMimeType(file.type)) === 'image';
				})
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
		let {
			id,
			url,
			imageUri,
			name,
			fileType,
			styles,
			attachmentCloseIcon,
			onDeleteAttachmentItem,
			files
		} = options;

		imageUri = encodeURI(imageUri);
		imageUri = getAbsolutePath(imageUri);

		url = encodeURI(url);
		url = getAbsolutePath(url);

		files = Array.isArray(files) ? CommonUtils.objectClone(files) : [];
		const images = prepareImageCollection(files, id, url);

		return View(
			{
				testId: 'pinnedFileContainer',
				style: styles.wrapper,
				onClick: () => openViewer({fileType, url, name, images})
			},
			Image({
				testId: 'pinnedFileImage',
				style: styles.imagePreview,
				uri: imageUri,
				resizeMode: 'cover'
			}),
			onDeleteAttachmentItem && Image({
				testId: 'pinnedFileDetach',
				svg: {
					content: attachmentCloseIcon
				},
				resizeMode: 'cover',
				style: styles.deleteButtonWrapper,
				onClick: onDeleteAttachmentItem
			}),
		);
	}

	function renderFile(options)
	{
		let {
			url,
			name,
			fileType,
			styles,
			attachmentCloseIcon,
			attachmentFileIconFolder,
			onDeleteAttachmentItem
		} = options;

		url = encodeURI(url);
		attachmentFileIconFolder = getAbsolutePath(attachmentFileIconFolder);

		const extension = getExtension(name || url);
		const icon = getFileType(extension) || 'empty';

		return View(
			{
				testId: 'pinnedFileContainer',
				style: styles.wrapper,
				onClick: () => openViewer({fileType, url, name})
			},
			View({
					style: styles.imagePreview
				},
				Image(
					{
						testId: 'pinnedFileIcon',
						style: {
							marginTop: 3,
							width: styles.imagePreview.width?  styles.imagePreview.width / 2 : 20,
							height: styles.imagePreview.height?  styles.imagePreview.height / 2 : 20,
						},
						svg: {
							uri: attachmentFileIconFolder + icon + '.svg'
						},
						resizeMode: 'contain'
					}
				),
				Text({
					testId: 'pinnedFileName',
					style: {
						marginTop: 2,
						color: '#a8adb4',
						fontWeight: 'normal',
						fontSize: 8,
						textAlign: 'center',
						backgroundColor: '#00000000'
					},
					text: name.substring(0, 4) + (name.length > 4 ? '...' : '')
				})
			),
			onDeleteAttachmentItem && Image({
				testId: 'pinnedFileDetach',
				svg: {
					content: attachmentCloseIcon
				},
				resizeMode: 'cover',
				style: styles.deleteButtonWrapper,
				onClick: onDeleteAttachmentItem
			})
		);
	}

	function buildStyles(externalStyles)
	{
		let styles = {
			wrapper: {
				paddingTop: 3,
				paddingRight: 3,
			},
			imagePreview: {
				width: 40,
				height: 40,
				backgroundColor: '#00000000',
				flexDirection: 'column',
				alignItems: 'center',
				justifyContent: 'center',
				borderWidth: 0.5,
				borderRadius: 6,
				borderColor: '#525C69',
			},
			deleteButtonWrapper: {
				position: 'absolute',
				top: 0,
				right: 0
			}
		};

		if (externalStyles)
		{
			styles = CommonUtils.objectMerge(styles, externalStyles);
		}

		return styles;
	}

	const DEFAULT_CLOSE_ICON = `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="7.5" fill="#B9C0CA" stroke="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9 8L11 10L10 11L8 9L6 11L5 10L7 8L5 6L6 5L8 7L10 5L11 6L9 8Z" fill="white"/></svg>`;
	const DEFAULT_FILE_ICONS_FOLDER = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/file/images/file/';

	/**
	 * @function UI.File
	 *
	 * @param {Object} options
	 * @param {String} options.url
	 * @param {String} options.imageUri
	 * @param {String} options.type
	 * @param {String} options.name
	 * @param {Object} options.styles
	 * @param {String} options.attachmentCloseIcon
	 * @param {String} options.attachmentFileIconFolder
	 * @param {Function} options.onDeleteAttachmentItem
	 *
	 * @returns {View}
	 */
	function File(options)
	{
		let {id, imageUri, type, attachmentCloseIcon, attachmentFileIconFolder, styles, files} = options;

		attachmentCloseIcon = attachmentCloseIcon || DEFAULT_CLOSE_ICON;
		attachmentFileIconFolder = attachmentFileIconFolder || DEFAULT_FILE_ICONS_FOLDER;
		styles = buildStyles(styles);

		const fileType = getType(getFileMimeType(type));

		if (
			(fileType === 'image' || (fileType === 'video' && imageUri.indexOf('file://') === 0))
			&& imageUri.length > 0
		)
		{
			return renderImage({
				...options,
				styles,
				attachmentCloseIcon,
				attachmentFileIconFolder,
				fileType,
				id,
				files
			});
		}

		return renderFile({
			...options,
			styles,
			attachmentCloseIcon,
			attachmentFileIconFolder,
			fileType
		});
	}

	this.UI = this.UI || {};
	this.UI.File = File;
	this.UI.File.getType = getType;
	this.UI.File.getFileMimeType = getFileMimeType;
})();