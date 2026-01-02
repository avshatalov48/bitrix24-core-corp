<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Command;

use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Tasks\DI\Container;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Main\Result;

abstract class AbstractCommand implements CommandInterface
{
	abstract protected function execute(): Result;

	protected function validate(): ValidationResult
	{
		return Container::getInstance()->getValidationService()->validate($this);
	}

	/**
	 * @throws InvalidCommandException
	 */
	public function run(): Result
	{
		$validationResult = $this->validate();
		if (!$validationResult->isSuccess())
		{
			throw new InvalidCommandException($validationResult->getError()?->getMessage());
		}

		return $this->execute();
	}

	public function runInBackground(): bool
	{
		return false;
	}

	public function runWithDelay(int $milliseconds): bool
	{
		return false;
	}
}
