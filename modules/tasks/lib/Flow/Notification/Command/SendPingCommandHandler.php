<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskObject;

class SendPingCommandHandler
{
	private ConfigRepository $configRepository;
	private TaskRegistry $taskRegistry;
	private Manager $bizProc;

	public function __construct()
	{
		$this->configRepository = new ConfigRepository();
		$this->taskRegistry = TaskRegistry::getInstance();
		$this->bizProc = new Manager();
	}

	public function __invoke(SendPingCommand $command): void
	{
		$task = $this->taskRegistry->getObject($command->getTaskId());
		if (!$task)
		{
			return;
		}

		$config = $this->configRepository->readByFlowId($command->getFlowId());

		foreach ($config->getItems() as $item)
		{
			switch ($item->getWhen()->getType())
			{
				case When::BEFORE_EXPIRE:
				case When::BEFORE_EXPIRE_HALF_TIME:
					$this->onBeforeExpire($task, $item, $command->getOffset());
					break;
			}
		}
	}

	private function onBeforeExpire(TaskObject $task, Item $item, int $offset): void
	{
		if (!in_array($item->getWhen()->getType(), [When::BEFORE_EXPIRE, When::BEFORE_EXPIRE_HALF_TIME]))
		{
			return;
		}

		if (!$task->getDeadline())
		{
			return;
		}

		if (!$item->getIntegrationId())
		{
			return;
		}

		if (in_array((int)$task->getStatus(), [Status::DEFERRED, Status::SUPPOSEDLY_COMPLETED, Status::COMPLETED], true))
		{
			return;
		}

		if ($task->getCreatedBy() === $task->getResponsibleId())
		{
			return;
		}

		$this->bizProc->runProc($item->getIntegrationId(), [$task->getId()]);
	}
}
