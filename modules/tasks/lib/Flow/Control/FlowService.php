<?php

namespace Bitrix\Tasks\Flow\Control;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\AddCommandHandler;
use Bitrix\Tasks\Flow\Control\Command\CommandLocator;
use Bitrix\Tasks\Flow\Control\Command\DeleteCommand;
use Bitrix\Tasks\Flow\Control\Command\DeleteCommandHandler;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommandHandler;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotAddedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotDeletedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class FlowService
{
	protected CommandLocator $locator;
	protected int $userId;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
		$this->init();
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws InvalidCommandException
	 * @throws FlowNotAddedException
	 */
	public function add(AddCommand $command): Flow
	{
		/** @var AddCommandHandler $commandHandler */
		$commandHandler = $this->locator->get('addCommandHandler');
		return $commandHandler($command);
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws FlowNotUpdatedException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws InvalidCommandException
	 */
	public function update(UpdateCommand $command): Flow
	{
		/** @var UpdateCommandHandler $commandHandler */
		$commandHandler = $this->locator->get('updateCommandHandler');
		return $commandHandler($command);
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws InvalidCommandException
	 * @throws FlowNotDeletedException
	 */
	public function delete(DeleteCommand $command): bool
	{
		/** @var DeleteCommandHandler $commandHandler */
		$commandHandler = $this->locator->get('deleteCommandHandler');
		return $commandHandler($command);
	}

	protected function init(): void
	{
		$this->locator = CommandLocator::getInstance();
	}
}
