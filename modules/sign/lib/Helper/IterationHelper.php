<?php

namespace Bitrix\Sign\Helper;

use Closure;

abstract class IterationHelper
{
	/**
	 * @template Tv
	 * @template Tk
	 * @template TIn of iterable<Tk, Tv>
	 * @param TIn $in
	 * @param Closure(Tv, Tk, TIn): bool $rule
	 * @return bool
	 */
	public static function all(iterable $in, Closure $rule): bool
	{
		foreach ($in as $key => $item)
		{
			if (!$rule($item, $key, $in))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * @template Tv
	 * @template Tk
	 * @template TIn of iterable<Tk, Tv>
	 * @param TIn $in
	 * @param Closure(Tv, Tk, TIn): bool $rule
	 * @return bool
	 */
	public static function any(iterable $in, Closure $rule): bool
	{
		foreach ($in as $key => $item)
		{
			if ($rule($item, $key, $in))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @template K
	 * @template V
	 * @param iterable<K, V> $iter
	 * @return array<K, V>
	 */
	public static function getArrayByIterable(iterable $iter): array
	{
		$result = [];
		foreach ($iter as $key => $value)
		{
			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * @template Tv
	 * @template Tk
	 * @param iterable<Tk, Tv> $in
	 * @param Closure(Tv, Tk, iterable<Tk, Tv>): bool $rule
	 * @return Tv
	 */
	public static function findFirstByRule(iterable $in, Closure $rule): mixed
	{
		foreach ($in as $key => $item)
		{
			if ($rule($item, $key, $in))
			{
				return $item;
			}
		}

		return null;
	}
}