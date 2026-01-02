<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

interface License
{
	public function isAvailable(): bool;

	public function isActive(): bool;

	public function isBaasAvailable(): bool;

	public function isSellableToAll(): bool;
}
