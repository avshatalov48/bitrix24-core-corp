<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Factory;

use Bitrix\Tasks\Onboarding\Transfer\Pair;

final class PairFactory
{
	public static function createPair(int $taskId, int $userId = 0): Pair
	{
		return new Pair($taskId, $userId);
	}
}