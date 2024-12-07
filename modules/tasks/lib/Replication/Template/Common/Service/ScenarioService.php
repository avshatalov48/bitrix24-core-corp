<?php

namespace Bitrix\Tasks\Replication\Template\Common\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\TaskObject;

class ScenarioService
{
	public function __construct(private TaskObject $task)
	{
	}

	public function insert(): Result
	{
		$result = new Result();
		try
		{
			ScenarioTable::insertIgnore($this->task->getId(), [ScenarioTable::SCENARIO_DEFAULT]);
		}
		catch (SystemException $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}
}