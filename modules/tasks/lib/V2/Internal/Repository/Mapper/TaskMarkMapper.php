<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\Internals;

class TaskMarkMapper
{
	public function mapToEnum(string $mark): Entity\Task\Mark
	{
		return match ($mark)
		{
			Internals\Task\Mark::POSITIVE => Entity\Task\Mark::Positive,
			Internals\Task\Mark::NEGATIVE => Entity\Task\Mark::Negative,
			Internals\Task\Mark::NO => Entity\Task\Mark::None,
			default => null,
		};
	}

	public function mapFromEnum(Entity\Task\Mark $mark): ?string
	{
		return match ($mark)
		{
			Entity\Task\Mark::Positive => Internals\Task\Mark::POSITIVE,
			Entity\Task\Mark::Negative => Internals\Task\Mark::NEGATIVE,
			Entity\Task\Mark::None => Internals\Task\Mark::NO,
			default => null,
		};
	}
}