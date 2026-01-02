<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Main\Provider\Params\Pager;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Access\Task;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Reminder\Permission;
use Bitrix\Tasks\V2\Public\Command\Task\Reminder\AddReminderCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Reminder\DeleteReminderCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Reminder\SetRemindersCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Reminder\UpdateReminderCommand;
use Bitrix\Tasks\V2\Public\Provider\ReminderProvider;

class Reminder extends BaseController
{
	public function listAction(
		#[Task\Permission\Read]
		Entity\Task $task,
		PageNavigation $pageNavigation,
		ReminderProvider $reminderProvider,
	): Entity\Task\ReminderCollection
	{
		return $reminderProvider->getByTaskId(
			taskId: $task->getId(),
			userId: $this->userId,
			pager: Pager::buildFromPageNavigation($pageNavigation)
		);
	}

	/**
	 * @ajaxAction tasks.V2.Task.Reminder.add
	 */
	public function addAction(
		#[Permission\Add]
		Entity\Task\Reminder $reminder
	): ?Entity\Task\Reminder
	{
		$result = (new AddReminderCommand(
			reminder: $reminder->cloneWith(['userId' => $this->userId]),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $reminder;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Reminder.set
	 */
	public function setAction(
		#[Permission\Set]
		Entity\Task $task
	): ?Entity\Task
	{
		$result = (new SetRemindersCommand(
			userId: $this->userId,
			taskId: $task->getId(),
			reminders: $task->reminders ?? new Entity\Task\ReminderCollection(),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $task;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Reminder.update
	 */
	public function updateAction(
		#[Permission\Read]
		Entity\Task\Reminder $reminder
	): ?Entity\Task\Reminder
	{
		$result = (new UpdateReminderCommand(
			reminder: $reminder,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $reminder;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Reminder.delete
	 */
	public function deleteAction(
		#[Permission\Read]
		Entity\Task\Reminder $reminder
	): ?Entity\Task\Reminder
	{
		$result = (new DeleteReminderCommand(
			id: $reminder->getId(),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $reminder;
	}
}