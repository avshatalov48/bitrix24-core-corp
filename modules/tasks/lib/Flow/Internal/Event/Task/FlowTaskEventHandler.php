<?php

namespace Bitrix\Tasks\Flow\Internal\Event\Task;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Flow\Internal\Link\FlowLink;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\InvalidCommandException;
use Exception;
use Psr\Container\NotFoundExceptionInterface;

class FlowTaskEventHandler
{
	private int $currentFlowId = 0;
	private int $previousFlowId = 0;
	private int $taskId = 0;
	private array $changedFields = [];
	private array $previousFields = [];

	public function withCurrentFlowId(int $currentFlowId): static
	{
		$this->currentFlowId = $currentFlowId;
		return $this;
	}

	public function withPreviousFlowId(int $previousFlowId): static
	{
		$this->previousFlowId = $previousFlowId;
		return $this;
	}

	public function withTaskId(int $taskId): static
	{
		$this->taskId = $taskId;
		return $this;
	}

	public function withChangedFields(array $changedFields): static
	{
		$this->changedFields = $changedFields;
		return $this;
	}

	public function withPreviousFields(array $previousFields): static
	{
		$this->previousFields = $previousFields;
		return $this;
	}

	/**
	 * @throws Exception
	 * @throws NotFoundExceptionInterface
	 */
	public function onTaskAdd(): void
	{
		FlowLink::link($this->currentFlowId, $this->taskId);

		$this->sendAddNotification();
		$this->upActivity(
			(new FlowProvider())->preparePushParamsForActivity(
				$this->currentFlowId,
				\Bitrix\Tasks\Flow\Task\Status::FLOW_PENDING,
			)
		);
	}

	/**
	 * @throws Exception
	 * @throws NotFoundExceptionInterface
	 */
	public function onTaskUpdate(): void
	{
		if ($this->isLinkDeleted())
		{
			FlowLink::unlink($this->taskId);
		}
		elseif ($this->isLinkChanged())
		{
			FlowLink::unlink($this->taskId);
			FlowLink::link($this->currentFlowId, $this->taskId);

			$this->sendOnFlowChangedNotification();
			$this->upActivity();
		}
		elseif ($this->isLinkAdded())
		{
			FlowLink::link($this->currentFlowId, $this->taskId);

			$this->sendOnFlowChangedNotification();
			$this->upActivity();
		}
		elseif ($this->isGroupChanged())
		{
			FlowLink::unlink($this->taskId);
		}
	}

	public function onTaskDelete(): void
	{
		FlowLink::unlink($this->taskId);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 */
	public function onFlowTaskUpdate(): void
	{
		$this->sendUpdateNotification();

		if (
			$this->isStatusChanged()
			&& isset($this->previousFields['REAL_STATUS'])
		)
		{
			$this->upActivity(
				(new FlowProvider())->preparePushParamsForActivity(
					$this->currentFlowId,
					\Bitrix\Tasks\Flow\Task\Status::getFlowStatus($this->changedFields['STATUS']),
					\Bitrix\Tasks\Flow\Task\Status::getFlowStatus($this->previousFields['REAL_STATUS']),
				)
			);
		}
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	private function sendUpdateNotification(): void
	{
		$notificationService = ServiceLocator::getInstance()->get('tasks.flow.notification.service');

		if (isset($this->changedFields['DEADLINE']))
		{
			$notificationService->onTaskExpireTimeChange($this->taskId);
		}

		if (isset($this->changedFields['STATUS']))
		{
			$notificationService->onTaskStatusChanged($this->taskId);
		}
	}

	private function sendOnFlowChangedNotification(): void
	{
		$notificationService = ServiceLocator::getInstance()->get('tasks.flow.notification.service');

		$notificationService->onTaskToFlowAdded($this->taskId, $this->currentFlowId);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	private function sendAddNotification(): void
	{
		$notificationService = ServiceLocator::getInstance()->get('tasks.flow.notification.service');
		$notificationService->onTaskToFlowAdded($this->taskId, $this->currentFlowId);
	}

	/**
	 * @param array $pushParams Push params.
	 * @return void
	 * @throws CommandNotFoundException
	 * @throws FlowNotFoundException
	 * @throws FlowNotUpdatedException
	 * @throws InvalidCommandException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 * @throws SqlQueryException
	 */
	private function upActivity(array $pushParams = []): void
	{
		$flowService = ServiceLocator::getInstance()->get('tasks.flow.service');

		$updateCommand = (new UpdateCommand())
			->setId($this->currentFlowId)
			->setActivity(new DateTime())
			->setPushParams($pushParams);

		$flowService->update($updateCommand);
	}

	private function isGroupChanged(): bool
	{
		$groupId = (isset($this->changedFields['GROUP_ID']) ? (int)$this->changedFields['GROUP_ID'] : null);
		$previousGroupId = (isset($this->previousFields['GROUP_ID']) ? (int)$this->previousFields['GROUP_ID'] : null);

		return (($groupId && $groupId !== $previousGroupId)
			|| ($groupId === 0 && $previousGroupId > 0));
	}

	private function isStatusChanged(): bool
	{
		return isset($this->changedFields['STATUS'])
			&& in_array((int)$this->changedFields['STATUS'], Status::STATUSES_CHANGING_ACTIVITY, true);
	}

	private function isLinkAdded(): bool
	{
		return $this->previousFlowId === 0 && $this->currentFlowId > 0;
	}

	private function isLinkChanged(): bool
	{
		return $this->previousFlowId > 0 && $this->currentFlowId > 0;
	}

	private function isLinkDeleted(): bool
	{
		return $this->currentFlowId === 0 && $this->previousFlowId > 0;
	}
}