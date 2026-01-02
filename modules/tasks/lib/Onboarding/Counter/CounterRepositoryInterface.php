<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Counter;

use Bitrix\Tasks\Onboarding\Internal\Type;

interface CounterRepositoryInterface
{
	public function getByCode(string $code): ?int;
	public function isLimitReachedByType(Type $type, int $userId): bool;
}