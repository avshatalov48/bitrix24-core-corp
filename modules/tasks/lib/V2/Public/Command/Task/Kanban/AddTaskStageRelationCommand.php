<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Kanban;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;

class AddTaskStageRelationCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $stageId,
	)
	{

	}
	protected function executeInternal(): Result
	{
		$taskStageService = Container::getInstance()->getTaskStageService();

		$handler = new AddTaskStageRelationHandler($taskStageService);

		$result = new Result();
		try
		{
			$id = $handler($this);

			return $result->setId($id);
		}
		catch (SqlQueryException $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
