<?php

namespace Bitrix\Mobile\Menu\Service;

interface MenuListCacheInterface
{
	public function get(): ?array;
	public function set(array $data): void;
	public function clear(): void;
}
