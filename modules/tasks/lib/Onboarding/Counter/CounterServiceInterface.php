<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Counter;

use Bitrix\Main\Result;
use Bitrix\Tasks\Onboarding\Transfer\JobCode;

interface CounterServiceInterface
{
	public function increment(JobCode $code): Result;
}