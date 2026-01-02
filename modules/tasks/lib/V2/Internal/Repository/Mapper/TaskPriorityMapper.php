<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\Internals;

class TaskPriorityMapper
{
	public function mapToEnum(int $priority): ?Entity\Task\Priority
	{
		return match ($priority) {
			Internals\Task\Priority::LOW => Entity\Task\Priority::Low,
			Internals\Task\Priority::AVERAGE => Entity\Task\Priority::Average,
			Internals\Task\Priority::HIGH => Entity\Task\Priority::High,
			default => null,
		};
	}

	public function mapFromEnum(Entity\Task\Priority $priority): ?int
	{
		return match ($priority) {
			Entity\Task\Priority::Low => Internals\Task\Priority::LOW,
			Entity\Task\Priority::Average => Internals\Task\Priority::AVERAGE,
			Entity\Task\Priority::High => Internals\Task\Priority::HIGH,
			default => null,
		};
	}
}