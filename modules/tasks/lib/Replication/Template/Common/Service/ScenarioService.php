<?php

namespace Bitrix\Tasks\Replication\Template\Common\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\V2\Internal\DI\Container;

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
			Container::getInstance()->getScenarioService()->saveDefault($this->task->getId());
		}
		catch (SystemException $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}
}