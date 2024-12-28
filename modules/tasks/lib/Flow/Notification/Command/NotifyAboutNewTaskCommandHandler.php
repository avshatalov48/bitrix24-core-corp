<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Notification\HimselfFlowAgent;
use Bitrix\Tasks\Flow\Notification\PingAgent;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class NotifyAboutNewTaskCommandHandler
{
	private ConfigRepository $configRepository;
	private TaskRegistry $taskRegistry;
	private PingAgent $pingAgent;
	private Manager $bizProc;
	private HimselfFlowAgent $himselfFlowAgent;

	public function __construct(
		ConfigRepository $repository,
		PingAgent $pingAgent,
		Manager $bizProc,
		HimselfFlowAgent $himselfFlowAgent
	)
	{
		$this->taskRegistry = TaskRegistry::getInstance();
		$this->configRepository = $repository;
		$this->pingAgent = $pingAgent;
		$this->bizProc = $bizProc;
		$this->himselfFlowAgent = $himselfFlowAgent;
	}

	public function __invoke(NotifyAboutNewTaskCommand $command): void
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
					$this->pingAgent->addAgent($task, $config->getFlowId(), $item);
					break;
				case When::HIMSELF_FLOW_TASK_NOT_TAKEN:
					$this->himselfFlowAgent->addAgent($task, $config->getFlowId(), $item);
					break;
				case When::ON_TASK_ADDED:
					$this->bizProc->runProc($item->getIntegrationId(), [$command->getTaskId()]);
					break;
			}
		}
	}
}
