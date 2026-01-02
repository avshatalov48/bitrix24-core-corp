<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Public\Command\Task\Attention\MuteTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Attention\PinTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Attention\UnmuteTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Attention\UnpinTaskCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;

class Attention extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Attention.mute
	 */
	public function muteAction(
		#[Permission\Read]
		Entity\Task $task
	): ?bool
	{
		$result = (new MuteTaskCommand(
			taskId: $task->getId(),
			userId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Attention.unmute
	 */
	public function unmuteAction(
		#[Permission\Read]
		Entity\Task $task
	): ?bool
	{
		$result = (new UnmuteTaskCommand(
			taskId: $task->getId(),
			userId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Attention.pin
	 */
	public function pinAction(
		#[Permission\Read]
		Entity\Task $task
	): ?bool
	{
		$result = (new PinTaskCommand(
			taskId: $task->getId(),
			userId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Attention.unpin
	 */
	public function unpinAction(
		#[Permission\Read]
		Entity\Task $task
	): ?bool
	{
		$result = (new UnpinTaskCommand(
			taskId: $task->getId(),
			userId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}
}
