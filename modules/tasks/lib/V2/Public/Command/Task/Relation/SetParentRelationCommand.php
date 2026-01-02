<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Relation;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Exception;

class SetParentRelationCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $userId,
		#[PositiveNumber]
		public readonly int $parentId,
	)
	{
	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$parentService = Container::getInstance()->getParentService();
		$consistencyResolver = Container::getInstance()->getConsistencyResolver();

		$handler = new SetParentRelationHandler($parentService, $consistencyResolver);

		try
		{
			$task = $handler($this);

			return $result->setObject($task);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
