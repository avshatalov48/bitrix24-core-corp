<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\V2\Public\Command\Task\AddTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\DeleteTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\UpdateTaskCommand;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config\DeleteConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Public\Provider\Params\TaskParams;
use Bitrix\Tasks\V2\Public\Provider\TaskProvider;

class Task extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read]
		Entity\Task $task,
		TaskProvider $taskProvider,
	): ?Entity\Task
	{
		return $taskProvider->get(
			new TaskParams(taskId: $task->getId(), userId: $this->userId, checkTaskAccess: false)
		);
	}

	/**
	 * @restMethod tasks.V2.Task.add
	 */
	public function addAction(
		#[Permission\Add]
		Entity\Task $task,
		TaskProvider $taskProvider,
	): ?Arrayable
	{
		$result = (new AddTaskCommand(
			task: $task,
			config: new AddConfig($this->userId))
		)->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $taskProvider->get(new TaskParams(taskId: $result->getId(), userId: $this->userId));
	}

	/**
	 * @restMethod tasks.V2.Task.update
	 */
	public function updateAction(
		#[Permission\Update]
		Entity\Task $task,
		TaskProvider $taskProvider,
	): ?Arrayable
	{
		$result = (new UpdateTaskCommand(
			task: $task,
			config: new UpdateConfig($this->userId))
		)->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $taskProvider->get(new TaskParams(taskId: $result->getId(), userId: $this->userId));
	}

	/**
	 * @restMethod tasks.V2.Task.delete
	 */
	public function deleteAction(
		#[Permission\Delete]
		Entity\Task $task
	): ?bool
	{
		$result = (new DeleteTaskCommand(
			taskId: $task->getId(),
			config: new DeleteConfig($this->userId))
		)->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}
}
