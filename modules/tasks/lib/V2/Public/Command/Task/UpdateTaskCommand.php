<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Exception\WrongTaskIdException;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Error\ErrorCode;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\Entity;
use Exception;

class UpdateTaskCommand extends AbstractCommand
{
	public function __construct(
		#[Validatable]
		public readonly Entity\Task $task,
		public readonly UpdateConfig $config,
		public readonly null|Entity\Task $taskBeforeUpdate = null,
	)
	{

	}

	protected function validateInternal(): ValidationResult
	{
		return new ValidationResult();
	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		try
		{
			$updateTaskService = Container::getInstance()->getUpdateTaskService();

			$handler = new UpdateTaskHandler($updateTaskService);

			$task = $handler($this);

			return $result->setObject($task);
		}
		catch (Exception $e)
		{
			if (!$e instanceof TaskUpdateException)
			{
				Container::getInstance()->getLogger()->logError($e);
			}

			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
