<?php

namespace Bitrix\Crm\Kanban\Sort;

final class Type
{
	public const BY_ID = 'BY_ID';
	public const BY_LAST_ACTIVITY_TIME = 'BY_LAST_ACTIVITY_TIME';

	private function __construct()
	{
	}

	public static function isDefined(string $type): bool
	{
		return (
			$type === self::BY_ID
			|| $type === self::BY_LAST_ACTIVITY_TIME
		);
	}

	public static function getAll(): array
	{
		return [
			self::BY_ID,
			self::BY_LAST_ACTIVITY_TIME,
		];
	}
}
