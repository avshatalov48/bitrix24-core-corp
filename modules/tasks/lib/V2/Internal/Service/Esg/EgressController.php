<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg;

use Bitrix\Tasks\V2\Internal\Service\Esg\Handler\CreateChatForExistingTaskHandler;
use Bitrix\Tasks\V2\Internal\Service\Esg\Handler\SaveCheckListHandler;
use Bitrix\Tasks\V2\Internal\Service\Esg\Handler\StartTimerHandler;
use Bitrix\Tasks\V2\Internal\Service\Esg\Handler\StopTimerHandler;
use Bitrix\Tasks\V2\Internal\Service\Esg\Handler\TaskAddHandler;
use Bitrix\Tasks\V2\Internal\Service\Esg\Handler\TaskUpdateHandler;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Public\Command\CheckList\SaveCheckListCommand;
use Bitrix\Tasks\V2\Public\Command\Task\AddTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Tracking\StartTimerCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Tracking\StopTimerCommand;
use Bitrix\Tasks\V2\Public\Command\Task\UpdateTaskCommand;
use Bitrix\Tasks\V2\Internal\Entity\Task;

class EgressController implements EgressInterface
{
	public function __construct(
		private readonly CreateChatForExistingTaskHandler $createChatForExistingTaskHandler,
		private readonly TaskAddHandler              $taskAddHandler,
		private readonly TaskUpdateHandler           $taskUpdateHandler,
		private readonly SaveCheckListHandler        $checkListHandler,
		private readonly StartTimerHandler           $startTimerHandler,
		private readonly StopTimerHandler            $stopTimerHandler,
	)
	{
	}

	public function process(AbstractCommand $command): void
	{
		match (true)
		{
			$command instanceof UpdateTaskCommand => $this->taskUpdateHandler->handle($command),
			$command instanceof SaveCheckListCommand => $this->checkListHandler->handle($command),
			$command instanceof StartTimerCommand => $this->startTimerHandler->handle($command),
			$command instanceof StopTimerCommand => $this->stopTimerHandler->handle($command),
			default => '',
		};
	}

	public function processAddTaskCommand(AddTaskCommand $command): Task
	{
		return $this->taskAddHandler->handle($command);
	}

	public function createChatForExistingTask(Task $task): Task
	{
		return $this->createChatForExistingTaskHandler->handle($task);
	}
}
