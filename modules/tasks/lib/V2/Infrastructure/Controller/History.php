<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface;

class History extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.History.tail
	 */
	#[CloseSession]
	public function tailAction(
		#[Permission\Read] Entity\Task $task,
		TaskLogRepositoryInterface $historyRepository,
		int $offset = 0
	): Arrayable
	{
		return $historyRepository->tail($task->id, $offset);
	}
}
