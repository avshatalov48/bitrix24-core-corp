<?php

namespace Bitrix\Tasks\Flow\Control\Middleware\Implementation;

trait CacheTrait
{
	private function getNotLoaded(int ...$ids): array
	{
		return array_filter($ids, static fn (int $id): bool => !isset(static::$cache[$id]));
	}

	private function store(int ...$ids): void
	{
		foreach ($ids as $id)
		{
			static::$cache[$id] = true;
		}
	}

	private function has(int $id): bool
	{
		return (static::$cache[$id] ?? false) === true;
	}
}