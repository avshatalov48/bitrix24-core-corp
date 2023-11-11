<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Time\Enum;

use ReflectionClass;

abstract class Base
{
	public static function getAll(): array
	{
		$reflection = new ReflectionClass(static::class);
		return array_values($reflection->getConstants());
	}

	public static function get(string $name)
	{
		$constants = static::getAll();
		return $constants[$name];
	}
}