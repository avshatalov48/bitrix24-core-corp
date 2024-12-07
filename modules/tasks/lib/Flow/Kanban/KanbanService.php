<?php

namespace Bitrix\Tasks\Flow\Kanban;

use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Integration\BizProc\DocumentTrait;
use Bitrix\Tasks\Flow\Kanban\Command\AddKanbanCommand;
use Bitrix\Tasks\Flow\Kanban\Stages\CompletedStage;
use Bitrix\Tasks\Flow\Kanban\Stages\NewStage;
use Bitrix\Tasks\Flow\Kanban\Stages\ProgressStage;
use Bitrix\Tasks\Flow\Kanban\Stages\ReviewStage;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class KanbanService
{
	use DocumentTrait;

	protected AddKanbanCommand $addCommand;

	/**
	 * @throws InvalidCommandException
	 */
	public function add(AddKanbanCommand $command): void
	{
		$this->addCommand = $command;

		$this->addCommand->validateAdd();
		
		$stages = $this->getStages();
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
	protected function getStages(): array
	{
		return [
			new NewStage($this->addCommand->projectId, $this->addCommand->ownerId, $this->addCommand->flowId),
			new ProgressStage($this->addCommand->projectId, $this->addCommand->ownerId, $this->addCommand->flowId),
			// not currently used
			// new ReviewStage($this->addCommand->projectId, $this->addCommand->ownerId, $this->addCommand->flowId),
			new CompletedStage($this->addCommand->projectId, $this->addCommand->ownerId, $this->addCommand->flowId),
		];
	}
}