<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Event;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotAddedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Observer\ResponsibleList;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Mapper\FlowCommandMapper;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\DepartmentMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\OriginalNameMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\ProjectMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\TemplateMiddleware;
use Bitrix\Tasks\Flow\Control\Middleware\Implementation\UserMiddleware;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;
use Bitrix\Tasks\Flow\Control\Observer\Option;
use Bitrix\Tasks\Flow\Control\Observer\Search;
use Bitrix\Tasks\Flow\Control\Observer\Member;
use Bitrix\Tasks\Flow\Control\Observer\Template;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Throwable;

class AddCommandHandler extends CommandHandler
{
	/** @var AddCommand  */
	protected AbstractCommand $command;
	protected FlowCommandMapper $mapper;

	/**
	 * @throws InvalidCommandException
	 * @throws FlowNotAddedException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 */
	public function __invoke(AddCommand $command): Flow
	{
		return $this->execute($command);
	}

	/**
	 * @throws InvalidCommandException
	 * @throws FlowNotAddedException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 */
	public function execute(AddCommand $command): Flow
	{
		$this->command = $command;

		$this->command->validateAdd();

		$this->buildMiddleware();

		$this->fireBeforeEvent();

		$this->add();

		$this->notify(...$this->extraObservers);

		$flow = $this->loadFlow($this->flowEntity->getId());

		$this->sendPush($flow, PushCommand::FLOW_ADDED, PushCommand::FLOW_ADDED);

		$this->fireAfterEvent($flow);

		return $flow;
	}

	/**
	 * @throws SqlQueryException
	 * @throws FlowNotAddedException
	 */
	protected function add(): void
	{
		$this->flowEntity = $this->mapper->map($this->command);

		$this->connection->startTransaction();

		try
		{
			$result = $this->flowEntity->save();
		}
		catch (Throwable $t)
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotAddedException($t->getMessage());
		}

		if (!$result->isSuccess())
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotAddedException($result->getErrorMessages()[0]);
		}

		try
		{
			$this->notify(...$this->requiredObservers);
		}
		catch (Throwable $t)
		{
			$this->connection->rollbackTransaction();
			throw new FlowNotAddedException($t->getMessage());
		}

		$this->connection->commitTransaction();
	}

	public function addRequiredObserver(AddObserverInterface $observer): static
	{
		$this->requiredObservers[] = $observer;
		return $this;
	}

	public function addExtraObserver(AddObserverInterface $observer): static
	{
		$this->extraObservers[] = $observer;
		return $this;
	}

	protected function notify(AddObserverInterface ...$observers): void
	{
		foreach ($observers as $observer)
		{
			$observer->update($this->command, $this->flowEntity);
		}
	}

	final protected function fireBeforeEvent(): void
	{
		$event = new Event('tasks', 'onBeforeTasksFlowAdd', ['command' => $this->command]);
		$event->send();
	}

	/**
	 * @throws FlowNotFoundException
	 */
	final protected function fireAfterEvent(Flow $flow): void
	{
		$event = new Event('tasks', 'onAfterTasksFlowAdd', ['flow' => $flow]);
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

		$this->addRequiredObserver(new ResponsibleList\AddObserver());
		$this->addRequiredObserver(new ResponsibleQueue\AddObserver());
		$this->addRequiredObserver(new Option\AddObserver());
		$this->addRequiredObserver(new Member\AddObserver());
		$this->addRequiredObserver(new Search\AddObserver());

		$this->addExtraObserver(new Template\AddObserver());

		$this->middleware = new OriginalNameMiddleware();

		$this->middleware
			->setNext(new UserMiddleware())
			->setNext(new ProjectMiddleware())
			->setNext(new TemplateMiddleware())
			->setNext(new DepartmentMiddleware());
	}
}