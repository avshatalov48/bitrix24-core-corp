<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Exception\Task\UserOptionException;
use Bitrix\Tasks\V2\Internal\Result\Result;

class PinTaskCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $userId,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$userOptionService = Container::getInstance()->getUserOptionService();

		$handler = new PinTaskHandler($userOptionService);

		$result = new Result();

		try
		{
			$handler($this);
		}
		catch (UserOptionException $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}

		return $result;
	}
}
