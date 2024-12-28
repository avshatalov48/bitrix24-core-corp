/**
 * @module disk/enum
 */
jn.define('disk/enum', (require, exports, module) => {
	const FolderContextType = {
		BASIC: 'basic',
		GROUP: 'group',
		SHARED: 'shared',
		COLLAB: 'collab',
	};

	const FolderCode = {
		FOR_UPLOADED_FILES: 'FOR_UPLOADED_FILES',
		FOR_CREATED_FILES: 'FOR_CREATED_FILES',
		FOR_SAVED_FILES: 'FOR_SAVED_FILES',
		FOR_RECORDED_FILES: 'FOR_RECORDED_FILES',
		FOR_IMPORT_DROPBOX: 'FOR_DROPBOX_FILES',
		FOR_IMPORT_ONEDRIVE: 'FOR_ONEDRIVE_FILES',
		FOR_IMPORT_GDRIVE: 'FOR_GDRIVE_FILES',
		FOR_IMPORT_BOX: 'FOR_BOX_FILES',
		FOR_IMPORT_YANDEX: 'FOR_YANDEXDISK_FILES',
	};

	const FileType = {
		IMAGE: 2,
		VIDEO: 3,
		DOCUMENT: 4,
		ARCHIVE: 5,
		SCRIPTS: 6,
		UNKNOWN: 7,
		PDF: 8,
		AUDIO: 9,
		KNOWN: 10,
		VECTOR_IMAGE: 11,
	};

	const resolveFileTypeByExt = (ext) => {
		// eslint-disable-next-line no-param-reassign
		ext = String(ext || '').toLowerCase();

		if (ext === 'heic' && Application.getPlatform() === 'ios')
		{
			return FileType.IMAGE;
		}

		// eslint-disable-next-line default-case
		switch (ext)
		{
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'webp':
			case 'gif':
			case 'bmp':
			case 'heic':
				return FileType.IMAGE;

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
				return FileType.VIDEO;

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
				return FileType.DOCUMENT;

			case 'pdf':
				return FileType.PDF;

			case 'zip':
			case 'rar':
			case 'tar':
			case 'gz':
			case 'bz2':
			case 'tgz':
			case '7z':
				return FileType.ARCHIVE;

			case 'php':
			case 'js':
			case 'css':
			case 'sql':
			case 'pl':
			case 'sh':
				return FileType.SCRIPT;

			case 'mp3':
			case 'ogg':
			case 'wav':
				return FileType.AUDIO;

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
				return FileType.VECTOR_IMAGE;

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
				return FileType.KNOWN;
		}

		return FileType.UNKNOWN;
	};

	const SearchType = {
		GLOBAL: 'global',
		DIRECTORY: 'directory',
	};

	const SearchEntity = {
		USER: 'user',
		GROUP: 'group',
		COMMON: 'common',
	};

	module.exports = {
		FolderContextType,
		FolderCode,
		FileType,
		resolveFileTypeByExt,
		SearchType,
		SearchEntity,
	};
});
