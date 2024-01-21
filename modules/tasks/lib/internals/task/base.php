<?php

namespace Bitrix\Tasks\Internals\Task;

use ReflectionClass;

abstract class Base
{
	public static function getAll(bool $asStrings = false): array
	{
		$reflection = new ReflectionClass(static::class);
		$constants = $reflection->getConstants();
		if (!$asStrings)
		{
			return $constants;
		}

		$stringConstants = [];
		foreach ($constants as $constant => $value)
		{
			$stringConstants[$constant] = (string)$value;
		}

		return $stringConstants;
	}
}