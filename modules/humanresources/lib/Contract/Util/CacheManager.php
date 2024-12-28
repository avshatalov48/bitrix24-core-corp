<?php

namespace Bitrix\HumanResources\Contract\Util;

interface CacheManager
{
	public function getData(string $key, string $cacheSubDir = ''): mixed;

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function setData(string $key, mixed $data, string $cacheSubDir = ''): static;

	public function clean(string $key, string $cacheSubDir = ''): static;
	public function cleanDir(string $cacheSubDir): static;

	public function setTtl(int $ttl): static;
}