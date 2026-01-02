<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Reminder;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\MinValidator;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class UpdateReminderCommand extends AbstractCommand
{
	public function __construct(
		#[Validatable]
		public readonly Reminder $reminder,
	)
	{
	}

	protected function validateInternal(): ValidationResult
	{
		$validationResult = new ValidationResult();

		$current = Container::getInstance()->getReminderReadRepository()->getById($this->reminder->getId());
		if ($current === null)
		{
			return $validationResult->addError(new Error('Reminder not found'));
		}

		$props = array_filter($current->toArray());
		$reminder = $current->cloneWith($props);

		$validationResult = Container::getInstance()->getValidationService()->validate($reminder);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		return (new MinValidator(min: 0))->validate($this->reminder->id);
	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$reminderRepository = Container::getInstance()->getReminderRepository();

		$handler = new UpdateReminderHandler($reminderRepository);

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
