<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\Internals\Task\TimeUnitType;
use Bitrix\Tasks\V2\Internal\Entity;

class TaskDurationMapper
{
	public function mapFromEnum(Entity\Task\Duration $duration): ?string
	{
		return match ($duration)
		{
			Entity\Task\Duration::Seconds => TimeUnitType::SECOND,
			Entity\Task\Duration::Minutes => TimeUnitType::MINUTE,
			Entity\Task\Duration::Hours => TimeUnitType::HOUR,
			Entity\Task\Duration::Days => TimeUnitType::DAY,
			Entity\Task\Duration::Weeks => TimeUnitType::WEEK,
			Entity\Task\Duration::Months => TimeUnitType::MONTH,
			Entity\Task\Duration::Years => TimeUnitType::YEAR,
			default => null,
		};
	}
}