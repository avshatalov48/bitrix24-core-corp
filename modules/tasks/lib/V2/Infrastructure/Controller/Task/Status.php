<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Tasks\V2\Internal\Access\Service\TaskRightService;
use Bitrix\Tasks\V2\Internal\Access\Task\ActionDictionary;
use Bitrix\Tasks\V2\Public\Command\Task\Status\ApproveTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Status\CompleteTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Status\DeferTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Status\DisapproveTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Status\PauseTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Status\RenewTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Status\StartTaskCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Status\Permission;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;

class Status extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Status.start
	 */
	public function startAction(
		#[Permission\Start]
		Entity\Task $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new StartTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Status.disapprove
	 */
	public function disapproveAction(
		#[Permission\Disapprove]
		Entity\Task $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new DisapproveTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Status.defer
	 */
	public function deferAction(
		#[Permission\Defer]
		Entity\Task $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new DeferTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Status.approve
	 */
	public function approveAction(
		#[Permission\Approve]
		Entity\Task $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new ApproveTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Status.pause
	 */
	public function pauseAction(
		#[Permission\Pause]
		Entity\Task      $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new PauseTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Status.complete
	 */
	public function completeAction(
		#[Permission\Complete]
		Entity\Task      $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new CompleteTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Status.renew
	 */
	public function renewAction(
		#[Permission\Renew]
		Entity\Task      $task,
		TaskRightService $taskRightService,
	): ?Entity\EntityInterface
	{
		$result = (new RenewTaskCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$rights = $taskRightService->get(ActionDictionary::TASK_ACTIONS, $task->getId(), $this->userId);

		/** @var Entity\Task $savedTask */
		$savedTask = $result->getObject();

		return $savedTask->cloneWith(['rights' => $rights]);
	}
}
