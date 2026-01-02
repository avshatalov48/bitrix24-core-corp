<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command;

use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Throwable;

abstract class AbstractCommand extends \Bitrix\Main\Command\AbstractCommand
{
	/**
	 * Executes the main business logic of the command.
	 * Must be implemented in child classes to perform the specific action.
	 *
	 * This method provides a layer between @see \Bitrix\Main\Command\AbstractCommand
	 * and the current command implementation.
	 * Currently, it is used for logging: the method is wrapped
	 * to handle and log exceptions or results as needed.
	 */
	abstract protected function executeInternal(): Result;

	/**
	 * Performs internal validation before command execution.
	 * Can be overridden in child classes to provide custom validation logic.
	 *
	 * This method provides a layer between @see \Bitrix\Main\Command\AbstractCommand
	 * and the current command implementation.
	 * Currently, it is used for logging: the method is wrapped
	 * to handle and log validation errors as needed.
	 */
	protected function validateInternal(): ValidationResult
	{
		return parent::validate();
	}

	protected function execute(): Result
	{
		try
		{
			return $this->executeInternal();
		}
		catch (Throwable $t)
		{
			Container::getInstance()->getLogger()->logError($t);

			throw $t;
		}
	}

	protected function validate(): ValidationResult
	{
		$result = $this->validateInternal();
		if (!$result->isSuccess())
		{
			Container::getInstance()->getLogger()->logValidationErrorWarning($result->getErrorCollection());
		}

		return $result;
	}
}
