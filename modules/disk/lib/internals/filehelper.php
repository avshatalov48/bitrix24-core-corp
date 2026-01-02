<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals;

class FileHelper
{
	/**
	 * Checks if the file array has keys for file upload.
	 * @param array $fileArray Structure like $_FILES or result of CFile::MakeFileArray, or an array with the content key for uploading from a string.
	 * @return bool
	 */
	public static function hasValidFileKeys(array $fileArray): bool
	{
		$hasTmpName = !empty($fileArray['tmp_name']);

		$hasContent = !empty($fileArray['content']);

		return $hasTmpName || $hasContent;
	}
}