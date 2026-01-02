<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Tasks\Onboarding\Validation\Rule\ArrayOfPositiveNumbers;

final class JobIds
{
	public function __construct(
		#[ArrayOfPositiveNumbers]
		public readonly array $jobIds
	)
	{

	}
}