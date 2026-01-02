<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Reminder;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class AddReminderCommand extends AbstractCommand
{
	public function __construct(
		#[Validatable]
		public readonly Reminder $reminder,
	)
	{
	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$reminderService = Container::getInstance()->getReminderService();

		$handler = new AddReminderHandler($reminderService);

		try
		{
			$reminder = $handler($this);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}

		return $result->setObject($reminder);
	}
}
