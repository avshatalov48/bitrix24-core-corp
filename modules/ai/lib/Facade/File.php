<?php

namespace Bitrix\AI\Facade;

use Bitrix\Main\IO\File as FileCore;
use Bitrix\Main\Security\Random;
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
	 * Save base64coded image.
	 *
	 * @param string $imageBase64
	 * @param string $ext
	 * @param string $moduleId
	 *
	 * @return int|null
	 */
	public static function saveImageByBase64Content(string $imageBase64, string $moduleId): ?int
	{
		$name = Random::getString(32, true);
		$filePath = \CTempFile::GetFileName($name);
		$file = new FileCore($filePath);
		$file->putContents(base64_decode($imageBase64));
		$mimeType = $file->getContentType();

		$allowedImageTypes = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
		];

		if (!array_key_exists($mimeType, $allowedImageTypes))
		{
			return null;
		}
		$extension = $allowedImageTypes[$mimeType];

		$file = CFile::MakeFileArray($filePath);
		$file['MODULE_ID'] = $moduleId;
		$file['name'] = $name . '.' . $extension;
		if (CFile::CheckImageFile($file) !== null)
		{
			return null;
		}

		return CFile::SaveFile($file, $moduleId);
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
