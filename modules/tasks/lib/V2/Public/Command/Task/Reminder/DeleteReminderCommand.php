<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Reminder;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class DeleteReminderCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $id
	)
	{
	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$reminderRepository = Container::getInstance()->getReminderRepository();

		$handler = new DeleteReminderHandler($reminderRepository);

		try
		{
			$handler($this);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}

		return $result;
	}
}
