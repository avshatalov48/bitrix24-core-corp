<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\V2\Internal\Entity;

class TaskStatusMapper
{
	public function mapFromEnum(Entity\Task\Status $status): ?int
	{
		return match ($status)
		{
			Entity\Task\Status::Pending => Status::PENDING,
			Entity\Task\Status::InProgress => Status::IN_PROGRESS,
			Entity\Task\Status::SupposedlyCompleted => Status::SUPPOSEDLY_COMPLETED,
			Entity\Task\Status::Completed => Status::COMPLETED,
			Entity\Task\Status::Deferred => Status::DEFERRED,
			Entity\Task\Status::Declined => Status::DECLINED,
			default => null,
		};
	}

	public function mapToEnum(int $status): ?Entity\Task\Status
	{
		return match ($status)
		{
			Status::PENDING => Entity\Task\Status::Pending,
			Status::IN_PROGRESS => Entity\Task\Status::InProgress,
			Status::SUPPOSEDLY_COMPLETED => Entity\Task\Status::SupposedlyCompleted,
			Status::COMPLETED => Entity\Task\Status::Completed,
			Status::DEFERRED => Entity\Task\Status::Deferred,
			Status::DECLINED => Entity\Task\Status::Declined,
			default => null,
		};
	}
}