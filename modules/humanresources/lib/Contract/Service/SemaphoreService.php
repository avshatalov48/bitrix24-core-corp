<?php

namespace Bitrix\HumanResources\Contract\Service;

interface SemaphoreService
{
	public function lock(string $key): bool;
	public function unlock(string $key): bool;
	public function isLocked(string $key): bool;
}