<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Tracking;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Exception\Task\TimerNotFoundException;
use Bitrix\Tasks\V2\Internal\Result\Result;

class StopTimerCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $userId,
		#[Min(0)]
		public readonly int $taskId,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$timeManagementService = Container::getInstance()->getTimeManagementService();
		$consistencyResolver = Container::getInstance()->getConsistencyResolver();

		$handler = new StopTimerHandler($timeManagementService, $consistencyResolver);

		try
		{
			$timer = $handler($this);
			if ($timer !== null)
			{
				$result->setObject($timer);
			}

			return $result;
		}
		catch (TimerNotFoundException $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
