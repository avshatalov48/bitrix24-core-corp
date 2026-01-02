<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Main\Validation\Rule\Min;

final class Pair
{
	public function __construct(
		#[Min(0)]
		public readonly int $taskId = 0,
		#[Min(0)]
		public readonly int $userId = 0,
	)
	{

	}
}