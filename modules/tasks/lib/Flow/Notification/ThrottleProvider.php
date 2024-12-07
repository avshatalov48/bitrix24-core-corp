<?php

namespace Bitrix\Tasks\Flow\Notification;

class ThrottleProvider
{
	private const TTL = 30*24*3600;

	public function release(string $key): void
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cache->cleanDir($this->getDirectory($key));
	}

	public function attempt(string $key, callable $fn): void
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if ($cache->initCache(self::TTL, $key, $this->getDirectory($key)))
		{
			// reached the limit
			return;
		}

		$fn();

		$cache->initCache(self::TTL, $key, $this->getDirectory($key));
		$cache->startDataCache();
		$cache->forceRewriting(true);
		$cache->endDataCache(true);
	}

	private function getDirectory(string $key): string
	{
		return '/tasks/flow/notifications' . substr(md5($key),2,2) . '/' . $key . '/';
	}
}