<?php

namespace Bitrix\Disk\Integration\Bizproc;

use Bitrix\Main\Localization\Loc;

class Error extends \Bitrix\Bizproc\Error
{
	public const OBTAINING_STORAGE = 'OBTAINING_STORAGE';
	public const FOLDER_ERROR = 'FOLDER_ERROR';
	public const ACCESS_DENIED = 'ACCESS_DENIED';
	public const FILE_NOT_FOUND = 'FILE_NOT_FOUND';
	public const FILE_NOT_ADDED = 'FILE_NOT_ADDED';

	public static function fromCode(string $code, $customData = null): \Bitrix\Bizproc\Error
	{
		if ($code === static::FILE_NOT_ADDED)
		{
			if (is_array($customData) && ($customData['reason'] ?? null))
			{
				$message = Loc::getMessage('BIZPROC_DISK_ERROR_FILE_NOT_ADDED_FOR_REASON', ['#REASON#' => $customData['reason']]);

				return new static($message, $code, $customData);
			}
		}

		return parent::fromCode($code, $customData);
	}

	public static function getCodes(): array
	{
		return array_merge(
			parent::getCodes(),
			[
				static::OBTAINING_STORAGE,
				static::FOLDER_ERROR,
				static::ACCESS_DENIED,
				static::FILE_NOT_FOUND,
				static::FILE_NOT_ADDED,
			],
		);
	}

	public static function getLocalizationIdMap(): array
	{
		Loc::loadMessages(__FILE__);
		$prefix = 'BIZPROC_DISK_ERROR_';

		return array_merge(
			parent::getLocalizationIdMap(),
			[
				static::OBTAINING_STORAGE => $prefix . static::OBTAINING_STORAGE,
				static::FOLDER_ERROR => $prefix . static::FOLDER_ERROR,
				static::ACCESS_DENIED => $prefix . static::ACCESS_DENIED,
				static::FILE_NOT_ADDED => $prefix . static::FILE_NOT_ADDED,
			],
		);
	}
}