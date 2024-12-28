<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class PingManualDistributorAboutNewTaskCommandHandler
{
	private TaskRegistry $taskRegistry;
	private FlowRegistry $flowRegistry;

	public function __construct()
	{
		$this->taskRegistry = TaskRegistry::getInstance();
		$this->flowRegistry = FlowRegistry::getInstance();
	}

	public function __invoke(PingManualDistributorAboutNewTaskCommand $command): void
	{
		$task = $this->taskRegistry->getObject($command->getTaskId());
		if (!$task || $task->getFlowId() <= 0)
		{
			return;
		}

		$flow = $this->flowRegistry->get($task->getFlowId());

		if (!$flow || $flow->getDistributionType() !== FlowDistributionType::MANUALLY->value)
		{
			return;
		}

		(new \Bitrix\Tasks\Internals\Notification\Controller())
			->onTaskAddedToFlowWithManualDistribution($task, $flow)
			->push()
		;
	}
}