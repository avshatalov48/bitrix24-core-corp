<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskObject;

class NotifyHimselfFlowAdminCommandHandler
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

	public function __invoke(NotifyHimselfFlowAdminCommand $command): void
	{
		$task = $this->taskRegistry->getObject($command->getTaskId());
		if (!$task)
		{
			return;
		}

		$config = $this->configRepository->readByFlowId($command->getFlowId());

		foreach ($config->getItems() as $item)
		{
			if ($item->getWhen()->getType() == When::HIMSELF_FLOW_TASK_NOT_TAKEN)
			{
				$this->onTaskNotTakenFromHimselfFlow($task, $item);
			}
		}
	}

	private function onTaskNotTakenFromHimselfFlow(TaskObject $task, Item $item): void
	{
		if (!$item->getIntegrationId())
		{
			return;
		}

		if($task->isExpired())
		{
			return;
		}

		if (in_array((int)$task->getStatus(), [Status::IN_PROGRESS, Status::DEFERRED, Status::SUPPOSEDLY_COMPLETED, Status::COMPLETED], true))
		{
			return;
		}

		if ($task->getFlowId() <= 0)
		{
			return;
		}

		if ($task->getResponsibleId() !== $task->getCreatedBy())
		{
			return;
		}

		$flow = FlowRegistry::getInstance()->get($task->getFlowId());
		if ($flow->getDistributionType() !== FlowDistributionType::HIMSELF->value)
		{
			return;
		}

		$this->bizProc->runProc($item->getIntegrationId(), [$task->getId()]);
	}
}