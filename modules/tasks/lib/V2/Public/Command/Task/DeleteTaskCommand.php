<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\Control\Exception\TaskDeleteException;
use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Control\Exception\TaskStopDeleteException;
use Bitrix\Tasks\Control\Exception\WrongTaskIdException;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config\DeleteConfig;
use Bitrix\Tasks\V2\Internal\Error\ErrorCode;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class DeleteTaskCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		public readonly DeleteConfig $config,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		try
		{
			$deleteTaskService = Container::getInstance()->getDeleteTaskService();

			$handler = new DeleteTaskHandler($deleteTaskService);

			$handler($this);

			return $result;
		}
		catch (Exception $e)
		{
			if (!$e instanceof TaskDeleteException)
			{
				Container::getInstance()->getLogger()->logError($e);
			}

			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
