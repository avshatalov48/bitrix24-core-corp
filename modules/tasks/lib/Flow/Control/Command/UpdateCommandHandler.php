<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Event;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Mapper\FlowCommandMapper;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\DepartmentMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\OriginalNameMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\ProjectMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\TemplateMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\UserMiddleware;
use Bitrix\Tasks\Flow\Control\Observer\Search;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;
use Bitrix\Tasks\Flow\Control\Observer\ResponsibleList;
use Bitrix\Tasks\Flow\Control\Observer\Option;
use Bitrix\Tasks\Flow\Control\Observer\Member;
use Bitrix\Tasks\Flow\Control\Observer\Robot;
use Bitrix\Tasks\Flow\Control\Observer\Template;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Exception;
use Throwable;

class UpdateCommandHandler extends CommandHandler
{
	/** @var UpdateCommand  */
	protected AbstractCommand $command;
	protected FlowCommandMapper $mapper;
	protected FlowEntity $flowEntityBeforeUpdate;

	/**
	 * @throws SqlQueryException
	 * @throws InvalidCommandException
	 * @throws FlowNotUpdatedException
	 * @throws FlowNotFoundException
	 */
	public function __invoke(UpdateCommand $command): Flow
	{
		return $this->execute($command);
	}

	/**
	 * @throws SqlQueryException
	 * @throws InvalidCommandException
	 * @throws FlowNotUpdatedException
	 * @throws FlowNotFoundException
	 */
	public function execute(UpdateCommand $command): Flow
	{
		$this->command = $command;

		$this->command->validateUpdate();

		$this->loadFlow($this->command->id);

		$this->flowEntityBeforeUpdate = $this->flowEntity;

		$this->buildMiddleware();

		$this->fireBeforeEvent();

		$this->update();

		$this->notify(...$this->extraObservers);

		$flow = $this->loadFlow($this->command->id);

		$this->sendPush($flow, PushCommand::FLOW_UPDATED, PushCommand::FLOW_UPDATED);

		$this->fireAfterEvent($flow);

		return $flow;
	}

	/**
	 * @throws SqlQueryException
	 * @throws FlowNotUpdatedException
	 * @throws Exception
	 */
	protected function update(): void
	{
		$changes = $this->getChanges();

		$this->connection->startTransaction();

		if (!empty($changes))
		{
			try
			{
				$result = FlowTable::update($this->flowEntity->getId(), $changes);
			}
			catch (Throwable $t)
			{
				$this->connection->rollbackTransaction();
				throw new FlowNotUpdatedException($t->getMessage());
			}

			if (!$result->isSuccess())
			{
				$this->connection->rollbackTransaction();
				throw new FlowNotUpdatedException($result->getErrorMessages()[0]);
			}

			$this->flowRegistry->invalidate($this->command->id);
		}

		$this->flowEntity = $this->flowRegistry->get($this->command->id);

		try
		{
			$this->notify(...$this->requiredObservers);
		}
		catch (Throwable $t)
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotUpdatedException($t->getMessage());
		}

		$this->flowRegistry->invalidate($this->command->id);

		$this->connection->commitTransaction();
	}

	public function addRequiredObserver(UpdateObserverInterface $observer): static
	{
		$this->requiredObservers[] = $observer;
		return $this;
	}

	public function addExtraObserver(UpdateObserverInterface $observer): static
	{
		$this->extraObservers[] = $observer;
		return $this;
	}

	protected function notify(UpdateObserverInterface ...$observers): void
	{
		foreach ($observers as $observer)
		{
			$observer->update($this->command, $this->flowEntity, $this->flowEntityBeforeUpdate);
		}
	}

	protected function getChanges(): array
	{
		$oldValues = $this->flowEntityBeforeUpdate->toArray();
		$newValues = $this->mapper->map($this->command)->toArray();

		$changes = [];
		foreach ($newValues as $key => $value)
		{
			if ($oldValues[$key] !== $value)
			{
				$changes[$key] = $value;
			}
		}

		return $changes;
	}

	/**
	 * @throws FlowNotFoundException
	 */
	final protected function fireBeforeEvent(): void
	{
		$event = new Event('tasks', 'onBeforeTasksFlowUpdate', ['command' => $this->command]);
		$event->send();

		if (!empty($event->getResults()))
		{
			$this->flowRegistry->invalidate($this->command->id);
			$this->loadFlow($this->command->id);
		}
	}

	/**
	 * @throws FlowNotFoundException
	 */
	final protected function fireAfterEvent(Flow $flow): void
	{
		$event = new Event('tasks', 'onAfterTasksFlowUpdate', ['flow' => $flow]);
		$event->send();

		if (!empty($event->getResults()))
		{
			$this->flowRegistry->invalidate($flow->getId());
			$this->loadFlow($flow->getId());
		}
	}

	protected function init(): void
	{
		parent::init();
		$this->mapper = new FlowCommandMapper();

		$this->addRequiredObserver(new ResponsibleList\UpdateObserver());
		$this->addRequiredObserver(new ResponsibleQueue\UpdateObserver());
		$this->addRequiredObserver(new Option\UpdateObserver());
		$this->addRequiredObserver(new Member\UpdateObserver());
		$this->addRequiredObserver(new Search\UpdateObserver());

		$this->addExtraObserver(new Robot\UpdateObserver());
		$this->addExtraObserver(new Template\UpdateObserver());

		$this->middleware = new OriginalNameMiddleware();

		$this->middleware
			->setNext(new UserMiddleware())
			->setNext(new ProjectMiddleware())
			->setNext(new TemplateMiddleware())
			->setNext(new DepartmentMiddleware());
	}
}