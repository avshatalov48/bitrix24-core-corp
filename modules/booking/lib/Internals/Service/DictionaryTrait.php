<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

trait DictionaryTrait
{
	public static function toArray(): array
	{
		$result = [];

		foreach (self::cases() as $case)
		{
			$result[$case->name] = $case->value;
		}

		return $result;
	}
}
