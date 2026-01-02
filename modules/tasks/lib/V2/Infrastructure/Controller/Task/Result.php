<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Internal\Access\Task;
use Bitrix\Tasks\V2\Public\Command\Task\Result\AddResultCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Result\UpdateResultCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Access\Task\Result\Permission;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Public\Provider\TaskResultProvider;

class Result extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Result.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read]
		Entity\Result $result,
		TaskResultProvider $taskResultProvider,
	): ?Entity\Result
	{
		return $taskResultProvider->getResultById($result->id);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Result.list
	 */
	public function listAction(
		#[Task\Permission\Read]
		Entity\Task $task,
		TaskResultProvider $taskResultProvider,
	): ?Entity\ResultCollection
	{
		return $taskResultProvider->getTaskResults($task->id);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Result.add
	 */
	public function addAction(
		#[Permission\Read]
		Entity\Result $result,
	): ?Entity\Result
	{
		$commandResult = (new AddResultCommand(
			result: $result,
			userId: $this->userId,
		))->run();

		if (!$commandResult->isSuccess())
		{
			$this->addErrors($commandResult->getErrors());

			return null;
		}

		return $commandResult->getObject();
	}

	/**
	 * @ajaxAction tasks.V2.Task.Result.update
	 */
	public function updateAction(
		#[Permission\Update]
		Entity\Result $result,
	): ?Entity\Result
	{
		$commandResult = (new UpdateResultCommand(
			result: $result,
			userId: $this->userId,
		))->run();

		if (!$commandResult->isSuccess())
		{
			$this->addErrors($commandResult->getErrors());

			return null;
		}

		return $commandResult->getObject();
	}
}
