<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Task\TimeUnitType;

class PrepareDurationPlan implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$type = (string)($fields['DURATION_TYPE'] ?? '');

		$durationPlan = false;
		if (isset($fields['DURATION_PLAN_SECONDS']))
		{
			$durationPlan = $fields['DURATION_PLAN_SECONDS'];
		}
		elseif (isset($fields['DURATION_PLAN']))
		{
			$durationPlan = $this->convertDurationToSeconds((int)$fields['DURATION_PLAN'], $type);
		}

		if ($durationPlan !== false)
		{
			$fields['DURATION_PLAN'] = $durationPlan;
			unset($fields['DURATION_PLAN_SECONDS']);
		}

		return $fields;
	}

	private function convertDurationToSeconds(int $value, string $type): int
	{
		if ($type === TimeUnitType::HOUR)
		{
			return $value * 3600;
		}

		if($type === TimeUnitType::DAY || $type === '')
		{
			return $value * 86400;
		}

		return $value;
	}
}