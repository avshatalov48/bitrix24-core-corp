<?php

namespace Bitrix\Sign\Contract\Util;

interface Cache
{
	public function get(string $key, mixed $default = null): mixed;

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function set(string $key, mixed $data, ?int $ttl = null): static;

	public function delete(string $key): static;

	public function setTtl(int $ttl): static;
}