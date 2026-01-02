<?php

namespace Bitrix\Tasks\Flow\Control\Decorator;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\AddGroupCommand;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\GroupService;
use Bitrix\Tasks\Flow\Kanban\Command\AddKanbanCommand;
use Psr\Container\NotFoundExceptionInterface;
use Bitrix\Tasks\Flow\Internal\DI\Container;

class ProjectProxyDecorator extends AbstractFlowServiceDecorator
{
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

	protected function createProject(AddCommand|UpdateCommand $command): int
	{
		$memberIds = [];

		if (!empty($command->responsibleList))
		{
			$memberIds = (new AccessCodeConverter(...$command->responsibleList))->getUserIds();
		}

		if (empty($memberIds))
		{
			$memberIds = [$command->creatorId];
		}

		$groupCommand =
			(new AddGroupCommand())
				->setName($command->name)
				->setOwnerId($command->creatorId)
				->setMembers($memberIds)
		;

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

		$service = Container::getInstance()->getKanbanService();

		$service->add($kanbanCommand);
	}
}
