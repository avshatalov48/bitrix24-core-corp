<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Plan;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class UpdatePlanCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		public readonly UpdateConfig $config,
		public readonly ?int $startPlanTs = null,
		public readonly ?int $endPlanTs = null,
		public readonly ?int $duration = null,
		public readonly ?bool $matchesWorkTime = null,
		public readonly ?bool $matchesSubTasksTime = null,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$updateTaskService = Container::getInstance()->getUpdateTaskService();

		$handler = new UpdatePlanHandler($updateTaskService);

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
