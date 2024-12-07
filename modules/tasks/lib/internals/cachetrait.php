<?php

namespace Bitrix\Tasks\Internals;

trait CacheTrait
{
	private bool $usePrefix = true;

	private static string $cachePrefix = 'CACHED_';

	private function getPrefix(): string
	{
		return $this->usePrefix ? static::$cachePrefix : '';
	}

	private function disablePrefix(): void
	{
		$this->usePrefix = false;
	}

	private function enablePrefix(): void
	{
		$this->usePrefix = true;
	}

	private function getCached(string $key): mixed
	{
		return $this->customData->get($this->getPrefix() . $key);
	}

	private function isCached(string $key): bool
	{
		return !is_null($this->getCached($key));
	}

	private function fillCache(string $key, mixed $value): mixed
	{
		if (!$this->isCached($key))
		{
			$this->cache($key, $value);
		}

		return $this->getCached($key);
	}

	private function cache(string $key, mixed $value): void
	{
		$this->customData->set($this->getPrefix() . $key, $value);
	}
}