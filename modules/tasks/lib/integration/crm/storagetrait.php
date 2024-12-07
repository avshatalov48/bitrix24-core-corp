<?php

namespace Bitrix\Tasks\Integration\CRM;

trait StorageTrait
{
	private static array $storage = [];

	public static function get(int|string $key): static
	{
		static::$storage[$key] ??= new static($key);
		return static::$storage[$key];
	}
}