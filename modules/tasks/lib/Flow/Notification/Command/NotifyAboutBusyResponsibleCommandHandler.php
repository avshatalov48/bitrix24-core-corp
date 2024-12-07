<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Notification\ThrottleProvider;
use Bitrix\Tasks\Flow\Provider\TaskProvider;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;

class NotifyAboutBusyResponsibleCommandHandler
{
	private ConfigRepository $configRepository;
	private Manager $bizProc;
	private TaskProvider $taskProvider;
	private ThrottleProvider $throttleProvider;

	public function __construct(
		ConfigRepository $repository,
		Manager $bizProc,
		TaskProvider $taskProvider,
		ThrottleProvider $throttleProvider
	)
	{
		$this->configRepository = $repository;
		$this->bizProc = $bizProc;
		$this->taskProvider = $taskProvider;
		$this->throttleProvider = $throttleProvider;
	}

	public function __invoke(NotifyAboutBusyResponsibleCommand $command): void
	{
		$config = $this->configRepository->readByFlowId($command->getFlowId());

		foreach ($config->getItems() as $item)
		{
			switch ($item->getWhen()->getType())
			{
				case When::BUSY_RESPONSIBLE:
					$this->handleBusyResponsible($item, $command->getFlowId());
					break;
			}
		}
	}

	private function handleBusyResponsible(Item $item, int $flowId): void
	{
		$procId = $item->getIntegrationId();
		if (!$procId)
		{
			return;
		}

		$offset = (int)$item->getWhen()->getValue()['offset'];
		$key = 'NotifyAboutBusyResponsibleCommand_' . $flowId . '_' . $offset;
		$tasksAtWork = $this->taskProvider->getTotalTasksWithStatus($flowId, Status::FLOW_AT_WORK);
		if ($tasksAtWork <= $offset)
		{
			$this->throttleProvider->release($key);
			return;
		}

		$this->throttleProvider->attempt(
			$key,
			fn() => $this->bizProc->runProc($procId, [$flowId])
		);
	}
}