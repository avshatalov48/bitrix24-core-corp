<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Tasks\Onboarding\Validation\Rule\ArrayOfJobCodes;

final class JobCodes
{
	public function __construct(
		#[ArrayOfJobCodes]
		public readonly array $codes,
	)
	{

	}
}