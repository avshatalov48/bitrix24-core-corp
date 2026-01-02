<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Gantt;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Internal\Entity\Task\Gantt\LinkType;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class UpdateDependenceCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $dependentId,
		public readonly LinkType $linkType,
	)
	{
	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$ganttDependenceService = Container::getInstance()->getGanttDependenceService();
		$consistencyResolver = Container::getInstance()->getConsistencyResolver();

		$handler = new UpdateDependenceHandler($ganttDependenceService, $consistencyResolver);

		try
		{
			$handler($this);

			return $result;
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
