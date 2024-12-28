/**
 * @module utils/file
 */
jn.define('utils/file', (require, exports, module) => {
	const { Loc } = require('loc');
	const { showSafeToast } = require('toast');
	const { RequestExecutor } = require('rest');

	const NativeViewerMediaTypes = {
		IMAGE: 'image',
		VIDEO: 'video',
		FILE: 'file',
	};

	/**
	 * @param {string} url
	 * @return {string}
	 */
	function getAbsolutePath(url)
	{
		let absolutePath = url;
		if (url && url.indexOf('file://') !== 0 && url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0)
		{
			absolutePath = currentDomain + url;
		}

		return absolutePath;
	}

	/**
	 * @param {string} mimeType
	 * @return {'image'|'video'|'file'}
	 */
	function getNativeViewerMediaType(mimeType)
	{
		let result = mimeType.slice(0, Math.max(0, mimeType.indexOf('/')));

		if (!Object.values(NativeViewerMediaTypes).includes(result))
		{
			result = NativeViewerMediaTypes.FILE;
		}

		return result;
	}

	/**
	 * @param {string} ext
	 * @return {'image'|'video'|'file'}
	 */
	function getNativeViewerMediaTypeByFileExt(ext)
	{
		return getNativeViewerMediaType(getMimeType(ext));
	}

	/**
	 * @param {string} name
	 * @return {'image'|'video'|'file'}
	 */
	function getNativeViewerMediaTypeByFileName(name)
	{
		const ext = getExtension(name);
		const mimeType = getMimeType(ext);

		return getNativeViewerMediaType(mimeType);
	}

	/**
	 * @param {string} fileExtOrMimeType
	 * @param {string} fileName
	 * @return {string}
	 */
	function getMimeType(fileExtOrMimeType, fileName = '')
	{
		let mimeType = fileExtOrMimeType.toString().toLowerCase();

		if (mimeType === 'application/octet-stream')
		{
			mimeType = fileName.split('.').pop().toLowerCase();
		}

		const mimeTypeMap = {
			png: 'image/png',
			gif: 'image/gif',
			jpg: 'image/jpeg',
			jpeg: 'image/jpeg',
			heic: 'image/heic',
			mp3: 'audio/mpeg',
			mp4: 'video/mp4',
			mpeg: 'video/mpeg',
			ogg: 'video/ogg',
			mov: 'video/quicktime',
			zip: 'application/zip',
			php: 'text/php',
		};

		if (mimeTypeMap[mimeType])
		{
			return mimeTypeMap[mimeType];
		}

		if (fileExtOrMimeType.includes('/')) // iOS old form
		{
			return fileExtOrMimeType;
		}

		return '';
	}

	/**
	 * @param {string} uri
	 * @return {string}
	 */
	function getExtension(uri)
	{
		return (uri && uri.includes('.'))
			? uri.split('.').pop().toLowerCase()
			: '';
	}

	/**
	 * @param {string} filename
	 * @return {string}
	 */
	function getNameWithoutExtension(filename)
	{
		return (filename.includes('.')) ? filename.split('.').slice(0, -1).join('.') : filename;
	}

	/**
	 * @param {'image'|'video'|'file'} fileType
	 * @param {string} url
	 * @param {string} name
	 * @param {{url: string, default: bool, description: string}[]} images
	 */
	function openNativeViewer({ fileType, url, name, images })
	{
		if (!url)
		{
			return;
		}

		const absoluteUrl = getAbsolutePath(url);

		if (fileType === NativeViewerMediaTypes.VIDEO)
		{
			viewer.openVideo(absoluteUrl);

			return;
		}

		if (fileType === NativeViewerMediaTypes.IMAGE)
		{
			if (Array.isArray(images) && images.length > 0)
			{
				viewer.openImageCollection(images);
			}
			else
			{
				viewer.openImage(absoluteUrl, name);
			}

			return;
		}

		viewer.openDocument(absoluteUrl, name);
	}

	async function openNativeViewerByFileId(objectId, layoutWidget)
	{
		if (!objectId)
		{
			return;
		}

		const { result: file } = await new RequestExecutor(
			'mobile.disk.getFileByObjectId',
			{ objectId },
		).call(true).catch(({ error }) => {
			let message = Loc.getMessage('USER_DISK_FILE_VIEW_ERROR');
			if (error?.code === 'ACCESS_DENIED')
			{
				message = Loc.getMessage('USER_DISK_FILE_VIEW_ACCESS_DENIED');
			}

			showSafeToast({ message }, layoutWidget);

			console.error(error);
		});

		if (file)
		{
			const url = `/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=${file.id}`;
			openNativeViewer({
				url,
				name: file.name,
				fileType: getNativeViewerMediaType(file.type),
			});
		}
	}

	/**
	 * @param objectId
	 * @return {number|null}
	 */
	function prepareObjectId(objectId)
	{
		if (!objectId)
		{
			return null;
		}

		if (Number.isInteger(objectId))
		{
			return objectId;
		}

		const match = objectId.match(/^n(\d+)$/);
		if (match)
		{
			return parseInt(match[1], 10);
		}

		if (Number.isNaN(Number(objectId)))
		{
			return null;
		}

		return parseInt(objectId, 10);
	}

	/**
	 * @param {number} bytes
	 * @param {number} precision
	 * @param {Object.<string, string>} phrases
	 * @return {string}
	 */
	function formatFileSize(bytes, precision = 1, phrases = {})
	{
		let fileSize = (!bytes || bytes <= 0) ? 0 : Number(bytes);

		const sizes = ['BYTE', 'KB', 'MB', 'GB', 'TB'];
		const KILOBYTE_SIZE = 1024;

		let position = 0;
		while (fileSize >= KILOBYTE_SIZE && position < sizes.length - 1)
		{
			fileSize /= KILOBYTE_SIZE;
			position++;
		}

		const phraseCode = `M_UTILS_FILE_SIZE_${sizes[position]}`;
		const phrase = phrases[phraseCode]
			?? Loc.getMessage(phraseCode)
			?? formatFileSize.defaultPhrases[phraseCode];

		const roundedSize = Number(fileSize.toFixed(precision));

		return `${roundedSize} ${phrase}`;
	}

	formatFileSize.defaultPhrases = {
		M_UTILS_FILE_SIZE_BYTE: 'bytes',
		M_UTILS_FILE_SIZE_GB: 'GB',
		M_UTILS_FILE_SIZE_KB: 'KB',
		M_UTILS_FILE_SIZE_MB: 'MB',
		M_UTILS_FILE_SIZE_TB: 'TB',
	};

	module.exports = {
		NativeViewerMediaTypes,
		getAbsolutePath,
		getNativeViewerMediaType,
		getNativeViewerMediaTypeByFileExt,
		getNativeViewerMediaTypeByFileName,
		getMimeType,
		getExtension,
		getNameWithoutExtension,
		openNativeViewer,
		prepareObjectId,
		openNativeViewerByFileId,
		formatFileSize,
	};
});
