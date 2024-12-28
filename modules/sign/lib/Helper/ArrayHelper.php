<?php

namespace Bitrix\Sign\Helper;

class ArrayHelper
{
	/**
	 * @template T
	 * @psalm-type NestedArray<T> = array<array-key, T|NestedArray<T>>
	 * @psalm-param NestedArray<T> $array
	 *
	 * @psalm-return list<T>
	 */
	public static function flatten(array $array): array
	{
		$flattened = [];
		array_walk_recursive($array, function ($a) use (&$flattened) {
			$flattened[] = $a;
		});

		return $flattened;
	}
}