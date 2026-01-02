<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\Entity;
use Exception;

class AddTaskCommand extends AbstractCommand
{
	public function __construct(
		#[Validatable]
		public readonly Entity\Task $task,
		public readonly AddConfig $config,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		try
		{
			$addTaskService = Container::getInstance()->getAddTaskService();

			$handler = new AddTaskHandler($addTaskService);

			$task = $handler($this);

			return $result->setObject($task);
		}
		catch (Exception $e)
		{
			if (!$e instanceof TaskAddException)
			{
				Container::getInstance()->getLogger()->logError($e);
			}

			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
