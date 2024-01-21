<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use Exception;

class StageService
{
	public function __construct(
		private RepositoryInterface $repository,
		private TaskObject $task
	)
	{
	}

	public function insert(): Result
	{
		$result = new Result();
		try
		{
			StagesTable::pinInStage($this->task->getId());
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}
}