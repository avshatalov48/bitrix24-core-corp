<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

use Bitrix\Main;

interface Service
{
	public function getCode(): string;

	public function canConsume(int $units = 1): bool;

	public function consume(int $units = 1, ?array $attributes = null): Main\Result;

	public function forceConsume(int $units = 1, ?array $attributes = null): Main\Result;

	public function refund(string $consumptionId, ?array $attributes = null): Main\Result;
}
