<?php

namespace Bitrix\Sign\Util\Request;

use Bitrix\Main\Security\Random;

class File
{
	private const RANDOM_FILE_NAME_LENGTH = 8;

	/**
	 * Return flat request files data by array with shape like $_FILES
	 *
	 * @param array $requestFilesData
	 * @return array{name: ?string, type: ?string, tmp_name: ?string, error: int, size: ?int}[]
	 */
	public static function flatRequestFilesData(array $requestFilesData): array
	{
		$result = [];

		foreach ($requestFilesData as $requestFilesDataPerName)
		{
			if (!is_array($requestFilesDataPerName['name']))
			{
				$result[] = $requestFilesDataPerName;
				continue;
			}

			for (
				$filesPerNameIndex = 0, $maxIndex = count($requestFilesDataPerName['name']);
				$filesPerNameIndex < $maxIndex;
				$filesPerNameIndex++
			)
			{
				$result[] = [
					'name' => $requestFilesDataPerName['name'][$filesPerNameIndex],
					'type' => $requestFilesDataPerName['type'][$filesPerNameIndex],
					'tmp_name' => $requestFilesDataPerName['tmp_name'][$filesPerNameIndex],
					'error' => $requestFilesDataPerName['error'][$filesPerNameIndex],
					'size' => $requestFilesDataPerName['size'][$filesPerNameIndex],
				];
			}
		}

		return $result;
	}

	public static function sanitizeFilename(string $filename): ?string
	{
		$sanitizedName = \Bitrix\Main\IO\Path::replaceInvalidFilename($filename, fn() => '');
		$sanitizedName = trim($sanitizedName, ". \t\n\r\0\x0B");

		if ($sanitizedName === '')
		{
			return null;
		}

		$extension = \Bitrix\Main\IO\Path::getExtension($sanitizedName);
		if ($extension !== '')
		{
			$nameWithoutExtension= pathinfo($sanitizedName, PATHINFO_FILENAME);
			$sanitizedName = trim($nameWithoutExtension) . '.' . $extension;
		}

		$sanitizedName = preg_replace("/\s{2,}/", ' ', $sanitizedName);

		return RemoveScriptExtension($sanitizedName);
	}

	public static function getRandomName(?int $length = null): string
	{
		return Random::getString($length ?? self::RANDOM_FILE_NAME_LENGTH);
	}
}
