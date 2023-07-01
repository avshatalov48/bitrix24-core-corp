<?php

namespace Bitrix\Crm\Activity\Provider\Tasks;

trait ActivityTrait
{
	public static function invalidate(string $key): void
	{
		unset(static::$cache[$key]);
	}

	public static function invalidateAll(): void
	{
		static::$cache = [];
	}

	public function getCacheKey(int $entityId): string
	{
		return static::getSubject() . '_' . $entityId;
	}
}