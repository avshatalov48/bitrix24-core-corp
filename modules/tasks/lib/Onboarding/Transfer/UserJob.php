<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class UserJob
{
	public function __construct(
		#[ElementsType(className: Type::class)]
		public readonly array $types,
		#[PositiveNumber]
		public readonly int $userId,
		#[Min(0)]
		public readonly int $taskId = 0,
	)
	{

	}
}