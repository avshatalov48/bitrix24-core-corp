<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command;

use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Main\Result;

abstract class AbstractCommand implements CommandInterface
{
	public function run(): Result
	{
		if (!$this->validate())
		{
			// todo: throw new CommandValidateException
			throw new Exception('validation exception');
		}

		$this->beforeRun();

		try
		{
			return $this->execute();
		}
		catch (Exception $e)
		{
			// todo: throw new CommandException
			throw new Exception('command exception');
		}
		finally
		{
			$this->afterRun();
		}
	}

	public function runInBackground(): bool
	{
		// TODO: Implement runInBackground() method.
		return false;
	}

	public function runWithDelay(int $milliseconds): bool
	{
		// TODO: Implement runWithDelay() method.
		return false;
	}

	abstract protected function execute(): Result;

	protected function validate(): bool
	{
		// validation service
		return true;
	}

	protected function beforeRun(): void
	{
	}

	protected function afterRun(): void
	{
	}
}
