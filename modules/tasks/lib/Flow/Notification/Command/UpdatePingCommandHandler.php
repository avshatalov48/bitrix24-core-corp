<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Notification\HimselfFlowAgent;
use Bitrix\Tasks\Flow\Notification\PingAgent;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class UpdatePingCommandHandler
{
	private ConfigRepository $configRepository;
	private TaskRegistry $taskRegistry;
	private PingAgent $pingAgent;
	private HimselfFlowAgent $himselfFlowAgent;

	public function __construct(ConfigRepository $configRepository, PingAgent $pingAgent, HimselfFlowAgent $himselfFlowAgent)
	{
		$this->taskRegistry = TaskRegistry::getInstance();
		$this->configRepository = $configRepository;
		$this->pingAgent = $pingAgent;
		$this->himselfFlowAgent = $himselfFlowAgent;
	}

	public function __invoke(UpdatePingCommand $command): void
	{
		$task = $this->taskRegistry->getObject($command->getTaskId());
		if (!$task)
		{
			return;
		}

		$this->pingAgent->removeAgents($task->getId());
		$this->himselfFlowAgent->removeAgents($task->getId());

		if (!$task->onFlow())
		{
			return;
		}

		$config = $this->configRepository->readByFlowId($task->getFlowId());

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
			}
		}
	}
}