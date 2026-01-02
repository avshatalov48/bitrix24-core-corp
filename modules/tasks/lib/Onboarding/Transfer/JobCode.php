<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Tasks\Onboarding\Validation\Rule;

final class JobCode
{
	public function __construct(
		#[Rule\JobCode]
		public readonly string $code,
	)
	{

	}

	public function __toString(): string
	{
		return $this->code;
	}
}