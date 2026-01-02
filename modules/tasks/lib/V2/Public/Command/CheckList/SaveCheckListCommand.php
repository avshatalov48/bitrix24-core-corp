<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\CheckList;

use Bitrix\Main\Error;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class SaveCheckListCommand extends AbstractCommand
{
	public function __construct(
		public readonly Entity\Task $task,
		public readonly int         $updatedBy,
		public readonly ?Entity\Task $taskBeforeUpdate = null,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$handler = new SaveCheckListCommandHandler(
			checkListService: Container::getInstance()->getCheckListService(),
			consistencyResolver: Container::getInstance()->getConsistencyResolver(),
			egressController: Container::getInstance()->getEgressController(),
			taskRepository: Container::getInstance()->getTaskRepository(),
			checkListProvider: Container::getInstance()->getCheckListProvider(),
		);

		try
		{
			$entity = $handler($this);

			return $result->setObject($entity);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
