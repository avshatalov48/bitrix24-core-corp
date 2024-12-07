<?php

namespace Bitrix\Tasks\Flow\Control\Decorator;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotAddedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\Exception\AutoCreationException;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\GroupCommand;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\GroupService;
use Bitrix\Tasks\Flow\Kanban\Command\AddKanbanCommand;
use Bitrix\Tasks\Flow\Kanban\KanbanService;
use Psr\Container\NotFoundExceptionInterface;

class ProjectProxyDecorator extends AbstractFlowServiceDecorator
{

	/**
	 * @throws AutoCreationException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws InvalidCommandException
	 * @throws FlowNotFoundException
	 * @throws FlowNotAddedException
	 * @throws NotFoundExceptionInterface
	 * @throws SqlQueryException
	 * @throws LoaderException
	 */
	public function add(AddCommand $command): Flow
	{
		if ($command->hasValidGroupId())
		{
			return parent::add($command);
		}

		$command->validateAdd('groupId');

		$command->groupId = $this->createProject($command);

		$flow = parent::add($command);

		$this->createKanban($flow);

		return $flow;
	}

	/**
	 * @throws ObjectNotFoundException
	 * @throws AutoCreationException
	 * @throws CommandNotFoundException
	 * @throws InvalidCommandException
	 * @throws FlowNotFoundException
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 */
	public function update(UpdateCommand $command): Flow
	{
		if ($command->hasValidGroupId())
		{
			return parent::update($command);
		}

		$command->validateUpdate('groupId');

		$command->groupId = $this->createProject($command);

		$flow = parent::update($command);

		$this->createKanban($flow);

		return $flow;
	}

	/**
	 * @throws AutoCreationException
	 * @throws InvalidCommandException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 * @throws LoaderException
	 */
	protected function createProject(AddCommand|UpdateCommand $command): int
	{
		$groupCommand = (new GroupCommand())
			->setName($command->name)
			->setOwnerId($command->creatorId)
			->setMembers($command->getUserIdList());

		/** @var GroupService $service */
		$service = ServiceLocator::getInstance()->get('tasks.flow.socialnetwork.project.service');

		return $service->add($groupCommand);
	}

	/**
	 * @throws InvalidCommandException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	protected function createKanban(Flow $flow): void
	{
		$kanbanCommand = (new AddKanbanCommand())
			->setProjectId($flow->getGroupId())
			->setOwnerId($flow->getOwnerId())
			->setFlowId($flow->getId());

		/** @var KanbanService $service */
		$service = ServiceLocator::getInstance()->get('tasks.flow.kanban.service');

		$service->add($kanbanCommand);
	}
}