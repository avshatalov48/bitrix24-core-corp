<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Recurring;

class RRuleService
{
	public function getNextDate(int $time, array $rrule): int
	{
		return $time + 3600; // stub
	}
}