<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Main\Provider\Params\PagerInterface;
use Bitrix\Tasks\V2\Internal\Entity\Task\ReminderCollection;
use Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepositoryInterface;

class ReminderProvider
{
	public function __construct(
		private readonly ReminderReadRepositoryInterface $reminderReadRepository,
	)
	{

	}

	public function getByTaskId(int $taskId, int $userId, PagerInterface $pager): ReminderCollection
	{
		return $this->reminderReadRepository->getByTaskId(
			taskId: $taskId,
			userId: $userId,
			offset: $pager->getOffset(),
			limit: $pager->getLimit(),
		);
	}
}