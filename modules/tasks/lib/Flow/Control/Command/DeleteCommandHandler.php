<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Event;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotDeletedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Mapper\FlowCommandMapper;
use Bitrix\Tasks\Flow\Control\Observer\DeleteObserverInterface;
use Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;
use Bitrix\Tasks\Flow\Control\Observer\Option;
use Bitrix\Tasks\Flow\Control\Observer\Search;
use Bitrix\Tasks\Flow\Control\Observer\Member;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Throwable;

class DeleteCommandHandler extends CommandHandler
{
	/** @var DeleteCommand  */
	protected AbstractCommand $command;
	protected FlowCommandMapper $mapper;

	/**
	 * @throws InvalidCommandException
	 * @throws FlowNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotDeletedException
	 */
	public function __invoke(DeleteCommand $command): bool
	{
		return $this->execute($command);
	}

	/**
	 * @throws InvalidCommandException
	 * @throws FlowNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotDeletedException
	 */
	public function execute(DeleteCommand $command): bool
	{
		$this->command = $command;

		$this->command->validateDelete();

		$flow = $this->loadFlow($command->id);

		$this->fireBeforeEvent();

		$this->delete();

		$this->notify(...$this->extraObservers);

		$this->flowRegistry->invalidate($this->command->id);

		$this->sendPush($flow, PushCommand::FLOW_DELETED, PushCommand::FLOW_DELETED);

		$this->fireAfterEvent();

		return true;
	}

	/**
	 * @throws SqlQueryException
	 * @throws FlowNotDeletedException
	 */
	protected function delete(): void
	{
		$this->flowEntity = $this->mapper->map($this->command);

		$this->connection->startTransaction();

		try
		{
			$result = $this->flowEntity->delete();
		}
		catch (Throwable $t)
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotDeletedException($t->getMessage());
		}

		if (!$result->isSuccess())
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotDeletedException($result->getErrorMessages()[0]);
		}

		try
		{
			$this->notify(...$this->requiredObservers);
		}
		catch (Throwable $t)
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotDeletedException($t->getMessage());
		}

		$this->flowRegistry->invalidate($this->command->id);

		$this->connection->commitTransaction();
	}

	public function addRequiredObserver(DeleteObserverInterface $observer): static
	{
		$this->requiredObservers[] = $observer;
		return $this;
	}

	public function addExtraObserver(DeleteObserverInterface $observer): static
	{
		$this->extraObservers[] = $observer;
		return $this;
	}

	protected function notify(DeleteObserverInterface ...$observers): void
	{
		foreach ($observers as $observer)
		{
			$observer->update($this->flowEntity);
		}
	}

	/**
	 * @throws FlowNotFoundException
	 */
	final protected function fireBeforeEvent(): void
	{
		$event = new Event('tasks', 'onBeforeTasksFlowDelete', ['command' => $this->command]);
		$event->send();
		if (!empty($event->getResults()))
		{
			$this->flowRegistry->invalidate($this->command->id);
			$this->loadFlow($this->command->id);
		}
	}

	final protected function fireAfterEvent(): void
	{
		$event = new Event('tasks', 'onAfterTasksFlowDelete', ['flow' => $this->command->id]);
		$event->send();
	}

	protected function init(): void
	{
		parent::init();
		$this->mapper = new FlowCommandMapper();

		$this->addRequiredObserver(new ResponsibleQueue\DeleteObserver());
		$this->addRequiredObserver(new Option\DeleteObserver());
		$this->addRequiredObserver(new Member\DeleteObserver());
		$this->addRequiredObserver(new Search\DeleteObserver());
	}
}