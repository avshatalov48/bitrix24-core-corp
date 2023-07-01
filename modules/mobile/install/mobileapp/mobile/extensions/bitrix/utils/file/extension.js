/**
 * @module utils/file
 */
jn.define('utils/file', (require, exports, module) => {

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
		if (url && url.indexOf('file://') !== 0 && url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0)
		{
			url = currentDomain + url;
		}

		return url;
	}

	/**
	 * @param {string} mimeType
	 * @return {"image"|"video"|"file"}
	 */
	function getNativeViewerMediaType(mimeType)
	{
		let result = mimeType.substring(0, mimeType.indexOf('/'));

		if (!Object.values(NativeViewerMediaTypes).includes(result))
		{
			result = NativeViewerMediaTypes.FILE;
		}

		return result;
	}

	/**
	 * @param {string} ext
	 * @return {"image"|"video"|"file"}
	 */
	function getNativeViewerMediaTypeByFileExt(ext)
	{
		return getNativeViewerMediaType(getMimeType(ext));
	}

	/**
	 * @param {string} name
	 * @return {"image"|"video"|"file"}
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
			'php': 'text/php',
		};

		if (mimeTypeMap[mimeType])
		{
			return mimeTypeMap[mimeType];
		}

		if (fileExtOrMimeType.indexOf('/') !== -1) // iOS old form
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
		return (uri && uri.indexOf('.') >= 0)
			? uri.split('.').pop().toLowerCase()
			: '';
	}

	/**
	 * @param {"image"|"video"|"file"} fileType
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

		url = getAbsolutePath(url);

		if (fileType === NativeViewerMediaTypes.VIDEO)
		{
			viewer.openVideo(url);

			return;
		}

		if (fileType === NativeViewerMediaTypes.IMAGE)
		{
			if (Array.isArray(images) && images.length)
			{
				viewer.openImageCollection(images);
			}
			else
			{
				viewer.openImage(url, name);
			}

			return;
		}

		viewer.openDocument(url, name);
	}

	module.exports = {
		NativeViewerMediaTypes,
		getAbsolutePath,
		getNativeViewerMediaType,
		getNativeViewerMediaTypeByFileExt,
		getNativeViewerMediaTypeByFileName,
		getMimeType,
		getExtension,
		openNativeViewer,
	};

});