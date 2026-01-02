<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Internals\Repository;

use Bitrix\Tasks\Deadline\Entity\DeadlineUserOption;
use Bitrix\Tasks\Deadline\Policy\DeadlinePolicy;

interface DeadlineUserOptionRepositoryInterface
{
	public function getByUserId(int $userId): DeadlineUserOption;

	public function save(DeadlineUserOption $deadlineUserOption): void;
}
