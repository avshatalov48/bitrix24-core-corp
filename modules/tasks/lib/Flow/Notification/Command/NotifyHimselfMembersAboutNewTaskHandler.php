<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage ${SUBPACKAGE}
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class NotifyHimselfMembersAboutNewTaskHandler
{
	private TaskRegistry $taskRegistry;
	private FlowRegistry $flowRegistry;

	public function __construct()
	{
		$this->taskRegistry = TaskRegistry::getInstance();
		$this->flowRegistry = FlowRegistry::getInstance();
	}

	public function __invoke(NotifyHimselfMembersAboutNewTaskCommand $command): void
	{
		$task = $this->taskRegistry->getObject($command->getTaskId());
		if (!$task || $task->getFlowId() <= 0)
		{
			return;
		}

		$flow = $this->flowRegistry->get($task->getFlowId());

		if (!$flow || $flow->getDistributionType() !== FlowDistributionType::HIMSELF->value)
		{
			return;
		}

		(new \Bitrix\Tasks\Internals\Notification\Controller())
			->onTaskAddedToFlowWithHimselfDistribution($task, $flow)
			->push();
	}
}