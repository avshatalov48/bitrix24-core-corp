<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Tracking;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Exception\Task\TimerNotFoundException;
use Bitrix\Tasks\V2\Internal\Result\Result;

class StartTimerCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $userId,
		#[PositiveNumber]
		public readonly int $taskId,
		public readonly bool $syncPlan = false,
		public readonly bool $canStart = false,
		public readonly bool $canRenew = false,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$timeManagementService = Container::getInstance()->getTimeManagementService();
		$consistencyResolver = Container::getInstance()->getConsistencyResolver();

		$handler = new StartTimerHandler($timeManagementService, $consistencyResolver);

		try
		{
			$timer = $handler($this);

			return $result->setObject($timer);
		}
		catch (TimerNotFoundException $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
