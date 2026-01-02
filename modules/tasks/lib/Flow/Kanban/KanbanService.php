<?php

namespace Bitrix\Tasks\Flow\Kanban;

use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Integration\BizProc\DocumentTrait;
use Bitrix\Tasks\Flow\Kanban\Command\AddKanbanCommand;
use Bitrix\Tasks\Flow\Kanban\Stages\CompletedStage;
use Bitrix\Tasks\Flow\Kanban\Stages\NewStage;
use Bitrix\Tasks\Flow\Kanban\Stages\ProgressStage;
use Bitrix\Tasks\Flow\Kanban\Stages\ReviewStage;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Flow\Kanban\Async\Message;
use Throwable;

class KanbanService
{
	use DocumentTrait;

	/**
	 * @throws InvalidCommandException
	 */
	public function add(AddKanbanCommand $command): void
	{
		$command->validateAdd();

		$message = new Message\AddStages(
			projectId: $command->projectId,
			ownerId: $command->ownerId,
			flowId: $command->flowId,
		);

		$message->sendByInternalQueueId();
	}

	public function addStages(int $projectId, int $ownerId, int $flowId): void
	{
		$stages = $this->getStages(
			projectId: $projectId,
			ownerId: $ownerId,
			flowId: $flowId,
		);

		foreach ($stages as $stage)
		{
			try
			{
				$result = $stage->create();
			}
			catch (Throwable $t)
			{
				Logger::logThrowable($t);
				continue;
			}

			if (!$result->isSuccess())
			{
				Logger::log($result->getErrorMessages());
			}
		}
	}

	/**
	 * @return AbstractStage[]
	 */
	protected function getStages(int $projectId, int $ownerId, int $flowId): array
	{
		return [
			new NewStage($projectId, $ownerId, $flowId),
			new ProgressStage($projectId, $ownerId, $flowId),
			// not currently used
			// new ReviewStage($projectId, $ownerId, $flowId),
			new CompletedStage($projectId, $ownerId, $flowId),
		];
	}
}
