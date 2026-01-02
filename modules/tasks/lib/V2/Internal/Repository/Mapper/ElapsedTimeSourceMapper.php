<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;
use CTaskElapsedItem;

class ElapsedTimeSourceMapper
{
	public function mapFromEnum(Entity\Task\Elapsed\Source $source): ?int
	{
		return match ($source)
		{
			Entity\Task\Elapsed\Source::System => CTaskElapsedItem::SOURCE_SYSTEM,
			Entity\Task\Elapsed\Source::Manual => CTaskElapsedItem::SOURCE_MANUAL,
			default => CTaskElapsedItem::SOURCE_UNDEFINED,
		};
	}

	public function mapToEnum(int $source): ?Entity\Task\Elapsed\Source
	{
		return match ($source)
		{
			CTaskElapsedItem::SOURCE_SYSTEM => Entity\Task\Elapsed\Source::System,
			CTaskElapsedItem::SOURCE_MANUAL => Entity\Task\Elapsed\Source::Manual,
			default => Entity\Task\Elapsed\Source::Unknown,
		};
	}
}