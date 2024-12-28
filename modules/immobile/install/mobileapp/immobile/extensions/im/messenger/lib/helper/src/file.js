/**
 * @module im/messenger/lib/helper/file
 */
jn.define('im/messenger/lib/helper/file', (require, exports, module) => {
	const { Loc } = require('loc');
	const { FileType } = require('im/messenger/const');
	const {
		FileType: FileIconType,
	} = require('assets/icons');

	function formatFileSize(fileSize)
	{
		if (!fileSize || fileSize <= 0)
		{
			fileSize = 0;
		}

		const sizes = ['BYTE', 'KB', 'MB', 'GB', 'TB'];
		const KILOBYTE_SIZE = 1024;

		let position = 0;
		while (fileSize >= KILOBYTE_SIZE && position < sizes.length - 1)
		{
			fileSize /= KILOBYTE_SIZE;
			position++;
		}

		const phrase = Loc.getMessage(`IMMOBILE_HELPER_FILE_SIZE_${sizes[position]}`);
		const roundedSize = Math.round(fileSize);

		return `${roundedSize} ${phrase}`;
	}

	function getShortFileName(fileName, maxLength)
	{
		if (!fileName || fileName.length < maxLength)
		{
			return fileName;
		}

		const DOT_LENGTH = 1;
		const SYMBOLS_TO_TAKE_BEFORE_EXTENSION = 10;

		const extension = getFileExtension(fileName);
		const symbolsToTakeFromEnd = extension.length + DOT_LENGTH + SYMBOLS_TO_TAKE_BEFORE_EXTENSION;
		const secondPart = fileName.slice(-symbolsToTakeFromEnd);
		const firstPart = fileName.slice(0, maxLength - secondPart.length - DOT_LENGTH * 3);

		return `${firstPart.trim()}...${secondPart.trim()}`;
	}

	function getFileExtension(fileName)
	{
		return fileName.split('.').splice(-1)[0];
	}

	function getFileTypeByExtension(extension)
	{
		let type = FileType.file;

		switch (extension)
		{
			case 'png':
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'heic':
			case 'bmp':
			case 'webp':
				type = FileType.image;
				break;

			case 'mp4':
			case 'mkv':
			case 'webm':
			case 'mpeg':
			case 'hevc':
			case 'avi':
			case '3gp':
			case 'flv':
			case 'm4v':
			case 'ogg':
			case 'wmv':
			case 'mov':
				type = FileType.video;
				break;

			case 'mp3':
				type = FileType.audio;
				break;

			default:
				return FileType.file;
		}

		return type;
	}

	/**
	 * @param {string} extension
	 * @return {number}
	 */
	function getFileIconTypeByExtension(extension)
	{
		if (extension === 'heic' && Application.getPlatform() === 'ios')
		{
			return FileType.IMAGE;
		}

		switch (extension)
		{
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'webp':
			case 'gif':
			case 'bmp':
			case 'heic':
				return FileIconType.IMAGE;

			case 'avi':
			case 'wmv':
			case 'mp4':
			case 'mov':
			case 'webm':
			case 'flv':
			case 'm4v':
			case 'mkv':
			case 'vob':
			case '3gp':
			case 'ogv':
			case 'h264':
				return FileIconType.VIDEO;

			case 'doc':
			case 'docx':
			case 'ppt':
			case 'pptx':
			case 'xls':
			case 'xlsx':
			case 'txt':
			case 'odt':
			case 'ods':
			case 'rtf':
				return FileIconType.DOCUMENT;

			case 'pdf':
				return FileIconType.PDF;

			case 'zip':
			case 'rar':
			case 'tar':
			case 'gz':
			case 'bz2':
			case 'tgz':
			case '7z':
				return FileIconType.ARCHIVE;

			case 'php':
			case 'js':
			case 'css':
			case 'sql':
			case 'pl':
			case 'sh':
				return FileIconType.SCRIPT;

			case 'mp3':
			case 'ogg':
			case 'wav':
				return FileIconType.AUDIO;

			case 'vsd':
			case 'vsdx':
			case 'eps':
			case 'ps':
			case 'ai':
			case 'svg':
			case 'svgz':
			case 'cdr':
			case 'swf':
			case 'sketch':
				return FileIconType.VECTOR_IMAGE;

			case 'html':
			case 'htm':
			case 'xml':
			case 'csv':
			case 'fb2':
			case 'djvu':
			case 'epub':
			case 'msg':
			case 'eml':
			case 'tif':
			case 'tiff':
			case 'psd':
			case 'ttf':
			case 'otf':
			case 'eot':
			case 'woff':
			case 'pfa':
				return FileIconType.KNOWN;

			default:
				return FileIconType.UNKNOWN;
		}
	}

	module.exports = {
		formatFileSize,
		getShortFileName,
		getFileExtension,
		getFileTypeByExtension,
		getFileIconTypeByExtension,
	};
});
