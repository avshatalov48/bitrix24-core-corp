<?php

namespace Bitrix\AI\Facade;

use Bitrix\Main\IO\File as FileCore;
use CFile;

class File
{
	/**
	 * Retrieves external image and saves to DB. Returns local file id.
	 *
	 * @param string $pictureUrl Picture external URL.
	 * @param string $moduleId File's module id.
	 * @return int|null
	 */
	public static function saveImageByURL(string $pictureUrl, string $moduleId): ?int
	{
		if (!$pictureUrl)
		{
			return null;
		}

		if (!preg_match('/^[a-z]+$/i', $moduleId))
		{
			return null;
		}

		$file = CFile::makeFileArray($pictureUrl);
		if (!$file)
		{
			return null;
		}

		$file['MODULE_ID'] = $moduleId;
		$isImage = CFile::checkImageFile($file, 0, 0, 0, ['IMAGE']) === null;
		if (!$isImage)
		{
			return null;
		}

		return CFile::saveFile($file, $moduleId);
	}

	/**
	 * Returns file's content.
	 *
	 * @param string $path Local file path.
	 * @return string
	 */
	public static function getContents(string $path): string
	{
		return FileCore::getFileContents($path) ?: '';
	}
}
