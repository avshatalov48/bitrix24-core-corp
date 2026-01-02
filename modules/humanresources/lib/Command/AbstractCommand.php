<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Command;

use Bitrix\HumanResources\Contract\Command\CommandInterface;
use Bitrix\HumanResources\Exception\CommandException;
use Bitrix\HumanResources\Exception\CommandValidateException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

/**
 * @implements CommandInterface<V>
 * @template V of Result
 */
abstract class AbstractCommand implements CommandInterface
{
	protected array $errors = [];
	abstract protected function execute(): Result;

	protected function validate(): bool
	{
		return true;
	}

	/**
	 * @throws ArgumentException
	 */
	public function toArray(): array
	{
		return Json::decode(Json::encode($this));
	}

	protected function beforeRun(): void
	{
	}

	protected function afterRun(): void
	{
	}

	/**
	 * @return V
	 * @throws CommandValidateException
	 * @throws CommandException
	 */
	public function run(): mixed
	{
		if (!$this->validate())
		{
			throw new CommandValidateException(
				$this->getValidationErrors(),
			);
		}
		$this->beforeRun();
		try
		{
			return $this->execute();
		}
		catch (\Exception $e)
		{
			throw new CommandException("Command " . static::class . " execution error", 0, $e);
		}
		finally
		{
			$this->afterRun();
		}
	}

	/**
	 * @return Error[]
	 */
	protected function getValidationErrors(): array
	{
		return $this->errors;
	}

	public function runInBackground(): bool
	{
		\Bitrix\Main\Application::getInstance()->addBackgroundJob(
			function() {
				$this->run();
			},
		);

		return true;
	}

	public function runWithDelay(int $milliseconds): bool
	{
		//todo realize this method in the future
		return true;
	}
}