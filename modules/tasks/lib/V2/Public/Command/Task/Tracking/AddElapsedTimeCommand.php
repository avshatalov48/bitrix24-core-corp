<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Tracking;

use Bitrix\Main\Error;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\Entity\Task\ElapsedTime;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Exception\Task\ElapsedTimeException;
use Bitrix\Tasks\V2\Internal\Result\Result;

class AddElapsedTimeCommand extends AbstractCommand
{
	public function __construct(
		public readonly ElapsedTime $elapsedTime
	)
	{

	}
	protected function executeInternal(): Result
	{
		$result = new Result();

		$elapsedTimeService = Container::getInstance()->getElapsedTimeService();

		$handler = new AddElapsedTimeHandler($elapsedTimeService);

		try
		{
			$elapsedTime = $handler($this);

			return $result->setObject($elapsedTime);
		}
		catch (ElapsedTimeException $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
