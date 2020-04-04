<?php

namespace Bitrix\Disk;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class TypeFile
{
	const IMAGE        = 2;
	const VIDEO        = 3;
	const DOCUMENT     = 4;
	const ARCHIVE      = 5;
	const SCRIPT       = 6;
	const UNKNOWN      = 7;
	const PDF          = 8;
	const AUDIO        = 9;
	const KNOWN        = 10;
	const VECTOR_IMAGE = 11;

	/**
	 * Allowed values.
	 * @return array
	 */
	public static function getListOfValues()
	{
		return array(
			self::IMAGE,
			self::VIDEO,
			self::DOCUMENT,
			self::ARCHIVE,
			self::SCRIPT,
			self::UNKNOWN,
			self::PDF,
			self::AUDIO,
			self::KNOWN,
			self::VECTOR_IMAGE,
		);
	}

	public static function getByFile(File $file)
	{
		return self::getByExtension($file->getExtension());
	}

	public static function getByExtension($extension)
	{
		switch(strtolower($extension))
		{
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'bmp':
				return self::IMAGE;

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
				return self::VIDEO;

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
				return self::DOCUMENT;

			case 'pdf':
				return self::PDF;

			case 'zip':
			case 'rar':
			case 'tar':
			case 'gz':
			case 'bz2':
			case 'tgz':
			case '7z':
				return self::ARCHIVE;

			case 'php':
			case 'js':
			case 'css':
			case 'sql':
			case 'pl':
			case 'sh':
				return self::SCRIPT;

			case 'mp3':
			case 'wav':
				return self::AUDIO;

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
				return self::VECTOR_IMAGE;

			// DOCUMENT
			case 'html':
			case 'htm':
			case 'xml':
			case 'csv':
			case 'fb2':
			case 'djvu':
			case 'epub':
			case 'msg':
			case 'eml':
			// IMAGES
			case 'tif':
			case 'tiff':
			case 'psd':
			// FONTS
			case 'ttf':
			case 'otf':
			case 'eot':
			case 'woff':
			case 'pfa':
				return self::KNOWN;
		}

		return self::UNKNOWN;
	}

	public static function getByFilename($filename)
	{
		return self::getByExtension(getFileExtension($filename));
	}

	protected static function getByFlexibleVar($file)
	{
		return $file instanceof File ?
			self::getByFile($file) : self::getByFilename($file);
	}

	public static function isImage($file)
	{
		return self::getByFlexibleVar($file) === self::IMAGE;
	}

	public static function isVideo($file)
	{
		return self::getByFlexibleVar($file) === self::VIDEO;
	}

	public static function isAudio($file)
	{
		return self::getByFlexibleVar($file) === self::AUDIO;
	}

	public static function isDocument($file)
	{
		return (self::getByFlexibleVar($file) === self::DOCUMENT || self::isPdf($file));
	}

	public static function isArchive($file)
	{
		return self::getByFlexibleVar($file) === self::ARCHIVE;
	}

	public static function isScript($file)
	{
		return self::getByFlexibleVar($file) === self::SCRIPT;
	}

	public static function isPdf($file)
	{
		return self::getByFlexibleVar($file) === self::PDF;
	}

	/**
	 * @param $mimeType
	 * @return string|null
	 */
	public static function getExtensionByMimeType($mimeType)
	{
		$mimes = static::getMimeTypeExtensionList();
		$mimes = array_flip($mimes);
		$mimeType = strtolower($mimeType);
		if (isset($mimes[$mimeType]))
		{
			return $mimes[$mimeType];
		}

		return null;
	}

	/**
	 * Get mimeType by filename (analyze extension of file.)
	 * Default type: 'application/octet-stream'
	 * @param $filename
	 * @return string
	 */
	public static function getMimeTypeByFilename($filename)
	{
		$mimes = static::getMimeTypeExtensionList();
		$extension = strtolower(getFileExtension($filename));
		if (isset($mimes[$extension]))
		{
			return $mimes[$extension];
		}

		return 'application/octet-stream';
	}

	/**
	 * @return array
	 */
	public static function getMimeTypeExtensionList()
	{
		static $mimeTypeList = array(
			// IMAGE
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'bmp' => 'image/bmp',
			'png' =>'image/png',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'psd' => 'image/vnd.adobe.photoshop',
			// VECTOR IMAGE
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			'cdr' => 'image/vnd.coreldraw',
			'swf' => 'application/x-shockwave-flash',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
			'ai' => 'application/postscript',
			'sketch' => 'application/octet-stream',

			// DOCUMENT
			'html' => 'text/html',
			'htm' => 'text/html',
			'txt' => 'text/plain',
			'xml' => 'application/xml',
			'pdf' => 'application/pdf',
			'doc' => 'application/msword',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			'vsd' => 'application/vnd.ms-visio',
			'vsdx' => 'application/vnd.ms-visio.drawing',
			'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
			'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'rtf' => 'application/msword',
			'csv' => 'application/vnd.ms-excel',
			'fb2' => 'application/xml',
			'djvu' => 'image/vnd.djvu',
			'epub' => 'application/epub+zip',
			'msg' => 'message/rfc822',
			'eml' => 'message/rfc822',
			'odt' => 'application/vnd.oasis.opendocument.text',

			// ARCHIVE
			'rar' => 'application/x-rar-compressed',
			'zip' => 'application/zip',
			'tgz' => 'application/x-gzip',
			'gz' => 'application/x-gzip',
			'7z' => 'application/x-7z-compressed',
			'tar' => 'application/x-tar',
			'bz2' => 'application/x-bzip2',

			// VIDEO
			'mp4' => 'video/mp4',
			'mp4v' => 'video/mp4',
			'mpg4' => 'video/mp4',
			'webm' => 'video/webm',
			'ogv' => 'video/ogg',
			'3gp' => 'video/3gpp',
			'mov' => 'video/quicktime',
			'flv' => 'video/x-flv',
			'avi' => 'video/x-msvideo',
			'mkv' => 'video/x-matroska',
			'm4v' => 'video/x-m4v',
			'h264' => 'video/h264',
			'wmv' => 'video/x-ms-wmv',

			// AUDIO
			'mp3' => 'audio/mpeg',
			'wav' => 'audio/wav',

			// SCRIPT
			'php' => 'text/php',
			'js' => 'text/javascript',
			'css' => 'text/css',
			'sql' => 'text/plain',
			'pl' => 'text/plain',
			'sh' => 'text/plain',

			// FONTS
			'ttf' => 'application/x-font-ttf',
			'otf' => 'application/vnd.ms-opentype',
			'eot' => 'application/vnd.ms-fontobject',
			'woff' => 'application/font-woff',
			'pfa' => 'application/x-font-type1',
		);

		return $mimeTypeList;
	}

	/**
	 * @param string $mimeType
	 * @param string $filename
	 * @return string
	 */
	public static function normalizeMimeType($mimeType, $filename)
	{
		switch($mimeType)
		{
			case '':
			case 'application/zip':
			case 'application/octet-stream':
				if(self::isDocument($filename))
				{
					return self::getMimeTypeByFilename($filename);
				}
				break;
		}

		return $mimeType;
	}

	/**
	 * Gets name for specific file type by code.
	 * @return string
	 * @param int $type Type code.
	 */
	public static function getName($type)
	{
		switch($type)
		{
			case self::IMAGE:
				return Loc::getMessage("DISK_TYPE_FILE_IMAGE");
			case self::VECTOR_IMAGE:
				return Loc::getMessage("DISK_TYPE_FILE_VECTOR_IMAGE");
			case self::VIDEO:
				return Loc::getMessage("DISK_TYPE_FILE_VIDEO");
			case self::AUDIO:
				return Loc::getMessage("DISK_TYPE_FILE_AUDIO");
			case self::DOCUMENT:
			case self::PDF:
			case self::KNOWN:
				return Loc::getMessage("DISK_TYPE_FILE_DOCUMENT");
			case self::ARCHIVE:
				return Loc::getMessage("DISK_TYPE_FILE_ARCHIVE");
			case self::SCRIPT:
				return Loc::getMessage("DISK_TYPE_FILE_SCRIPT");
			case self::UNKNOWN:
			default:
				return Loc::getMessage("DISK_TYPE_FILE_UNKNOWN");
		}
	}

	/**
	 * Set up TYPE_FILE by extension from original name of the file.
	 * @param int $startFileId Start offset of the file id.
	 * @return string
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function reindexTypeFile($startFileId = 0)
	{
		$maxNumberRowsUpdate = 100000;

		$connection = \Bitrix\Main\Application::getConnection();
		$fileTableName = \Bitrix\Main\FileTable::getTableName();
		$diskFileTableName = \Bitrix\Disk\Internals\ObjectTable::getTableName();

		$maxFileId = -1;
		$row = $connection->query("SELECT MAX(ID) as ID FROM {$fileTableName}")->fetch();
		if ($row)
		{
			$maxFileId = $row['ID'];
		}

		if ($maxFileId < 0 || $startFileId >= $maxFileId)
		{
			return '';
		}

		$extensionList = array_keys(self::getMimeTypeExtensionList());
		$updateTypeQuery = "";
		foreach ($extensionList as $extension)
		{
			if (self::getByExtension($extension) != self::UNKNOWN)
			{
				$updateTypeQuery .= " WHEN '{$extension}' THEN ".self::getByExtension($extension)." \n";
			}
		}

		$mimeTypeExtensionList = array_unique(self::getMimeTypeExtensionList());

		$updateTypeByContentTypeQuery = "";
		foreach ($mimeTypeExtensionList as $extension => $mimeType)
		{
			if ($mimeType == 'text/plain' || $mimeType == 'application/octet-stream')
			{
				continue;
			}
			if (self::getByExtension($extension) != self::UNKNOWN)
			{
				$updateTypeByContentTypeQuery .= " WHEN '{$mimeType}' THEN ".self::getByExtension($extension)." \n";
			}
		}

		// review order:
		// 1 - b_disk_object.NAME
		// 2 - b_file.ORIGINAL_NAME
		// 3 - b_file.FILE_NAME
		// 4 - b_file.CONTENT_TYPE

		$updateQuery = "
			UPDATE {$diskFileTableName} AS o, {$fileTableName} AS f
			SET 
				o.TYPE_FILE = (
					CASE lower(substring_index(o.NAME,'.', -1)) 
						{$updateTypeQuery}
						ELSE 
							CASE lower(f.ORIGINAL_NAME)
								{$updateTypeQuery}
								ELSE
									CASE lower(substring_index(f.FILE_NAME,'.', -1)) 
										{$updateTypeQuery}
										ELSE 
											CASE lower(f.CONTENT_TYPE)
												{$updateTypeByContentTypeQuery}
												ELSE 
													". self::UNKNOWN. "
											END
									END
							END
					END
				)
			WHERE 
				o.FILE_ID = f.ID 
				AND o.ID = o.REAL_OBJECT_ID
				AND o.TYPE = ". \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE. " 
				AND ifnull(o.TYPE_FILE , 0) IN(0, ". self::KNOWN. ", ". self::UNKNOWN. ")
				AND {$startFileId} <= o.ID 
				AND o.ID < {$startFileId} + {$maxNumberRowsUpdate}
		";

		$connection->queryExecute($updateQuery);

		if ($startFileId + $maxNumberRowsUpdate < $maxFileId)
		{
			return get_called_class().'::'.__FUNCTION__.'('. ($startFileId + $maxNumberRowsUpdate).');';
		}
		else
		{
			return '';
		}
	}
}