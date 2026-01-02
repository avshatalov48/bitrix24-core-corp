<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Error;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\V2\Public\Command\CheckList\CollapseCheckListCommand;
use Bitrix\Tasks\V2\Public\Command\CheckList\ExpandCheckListCommand;
use Bitrix\Tasks\V2\Public\Command\CheckList\SaveCheckListCommand;
use Bitrix\Tasks\V2\Internal\Access\CheckList\Permission;
use Bitrix\Tasks\V2\Internal\Access\Task;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Public\Provider\CheckListProvider;

class CheckList extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.CheckList.get
	 */
	#[CloseSession]
	public function getAction(
		#[Task\Permission\Read]
		Entity\Task $task,
		CheckListProvider $checkListProvider,
	): ?Arrayable
	{
		return $checkListProvider->getByEntity(
			$task->getId(),
			$this->userId,
			Entity\CheckList\Type::Task
		);
	}

	/**
	 * @ajaxAction tasks.V2.CheckList.save
	 */
	public function saveAction(
		#[Permission\Save]
		Entity\Task $task,
		CheckListProvider $checkListProvider,
	): ?Arrayable
	{
		$result = (new SaveCheckListCommand(
			task: $task,
			updatedBy: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $checkListProvider->getByEntity(
			$task->getId(),
			$this->userId,
			Entity\CheckList\Type::Task
		);
	}

	/**
	 * @ajaxAction tasks.V2.CheckList.collapse
	 */
	public function collapseAction(int $taskId, int $checkListId): bool
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addErrors([new Error('Access denied')]);

			return false;
		}

		$result = (new CollapseCheckListCommand(
			checkListId: $checkListId,
			userId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return false;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.CheckList.expand
	 */
	public function expandAction(int $taskId, int $checkListId): bool
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addErrors([new Error('Access denied')]);

			return false;
		}

		$result = (new ExpandCheckListCommand(
			checkListId: $checkListId,
			userId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return false;
		}

		return true;
	}
}
