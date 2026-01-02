<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Convert;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Public\Provider\TaskFromTemplateProvider;
use Exception;

class TemplateToTaskCommand extends AbstractCommand
{
	private readonly Container $container;

	public function __construct(
		#[PositiveNumber]
		public readonly int $templateId,
		public readonly AddConfig $config,
	)
	{
		$this->container = Container::getInstance();
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
			$handler = new TemplateToTaskHandler(
				addTaskService: $this->container->getAddTaskService(),
				provider: $this->container->getTaskFromTemplateProvider(),
			);

			$task = $handler($this);

			return $result->setObject($task);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
