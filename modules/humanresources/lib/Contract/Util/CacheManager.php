<?php

namespace Bitrix\HumanResources\Contract\Util;

interface CacheManager
{
	public function getData(string $key): mixed;

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function setData(string $key, mixed $data): static;

	public function clean(string $key): static;
	public function cleanDir(): static;

	public function setTtl(int $ttl): static;

	public function setDir(string $dir): static;
}