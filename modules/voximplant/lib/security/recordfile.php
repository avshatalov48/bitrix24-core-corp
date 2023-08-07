<?php

namespace Bitrix\Voximplant\Security;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

class RecordFile
{
	private static $availableMimeType = 'audio/';
	private static $availableExtensionTypes = [
		'mp3',
		'flac',
		'wav',
	];

	/**
	 * @param array $file
	 * @see \CFile::MakeFileArray()
	 * @return Result
	 */
	public static function isCorrectFromArray(array $file): Result
	{
		$errorMessage =
			\CFile::CheckFile(
				$file,
				0,
				self::$availableMimeType,
				implode(',', self::$availableExtensionTypes),
			)
		;
		$result = new Result();

		if ($errorMessage !== '')
		{
			$result->addError(new Error($errorMessage));
		}

		return $result;

	}
}