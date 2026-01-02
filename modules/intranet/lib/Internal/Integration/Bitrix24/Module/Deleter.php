<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Bitrix24\Module;

use Bitrix\Bitrix24\Public\Command\Module\DeleteCommand;
use Bitrix\Bitrix24\Public\Enum\ToggleableModule;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Command\Exception\CommandException;
use Bitrix\Main\Command\Exception\CommandValidationException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class Deleter
{
	private bool $isAvailable;

	public function __construct(
		private string $moduleName,
	)
	{
		$this->isAvailable = Loader::includeModule('bitrix24');
	}

	/**
	 * @throws CommandValidationException
	 * @throws CommandException|ArgumentException
	 */
	public function delete(): Result
	{
		if (!$this->isAvailable)
		{
			return (new Result())->addError(new Error('bitrix24 module is not installed'));
		}

		try
		{
			$module = ToggleableModule::from($this->moduleName);
		}
		catch (\ValueError)
		{
			throw new ArgumentException("Module $this->moduleName is not available for installation");
		}

		$command = new DeleteCommand($module);

		return $command->run();
	}
}