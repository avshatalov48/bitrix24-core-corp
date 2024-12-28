<?php

namespace Bitrix\Tasks\Manager\Task;

use Bitrix\Tasks\Manager;

class Description extends Manager
{
	public static function mergeData($primary = '', $secondary = '', bool $withAdditional = true): string
	{
		if (!$withAdditional)
		{
			return $secondary;
		}

		if (!empty($secondary))
		{
			return $secondary . PHP_EOL . PHP_EOL . $primary;
		}

		return $primary;
	}
}
