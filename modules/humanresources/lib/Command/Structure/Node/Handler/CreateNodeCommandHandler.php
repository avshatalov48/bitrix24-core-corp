<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Command\Structure\Node\Handler;

use Bitrix\HumanResources\Command\Structure\Node\CreateNodeCommand;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Service;
use Bitrix\HumanResources\Internals;
use Bitrix\HumanResources\Service\NodeService;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Command\Structure\Node\Enum\UserAddStrategy;
use Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Strategy\AddUsersStrategy;
use Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Strategy\MoveUsersStrategy;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Result\Command\Structure\CreateNodeCommandResult;

class CreateNodeCommandHandler
{
	private NodeService $nodeService;
	private NodeMemberService $nodeMemberService;
	private Internals\Service\Structure\NodeChatService $nodeChatService;
	private Internals\Service\Structure\NodeCollabService $nodeCollabService;
	private Internals\Service\Structure\NodeSettingsService $nodeSettingsService;

	public function __construct()
	{
		$this->nodeService = Service\Container::getNodeService();
		$this->nodeMemberService = Service\Container::getNodeMemberService();
		$this->nodeChatService = Internals\Service\Container::getNodeChatService();
		$this->nodeCollabService = Internals\Service\Container::getNodeCollabService();
		$this->nodeSettingsService = Internals\Service\Container::getNodeSettingsService();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws \Exception
	 */
	public function __invoke(CreateNodeCommand $command): CreateNodeCommandResult
	{
		Application::getConnection()->startTransaction();

		try
		{
			$node = $this->nodeService->insertNode(
				new Node(
					name: $command->name,
					type: $command->entityType,
					structureId: $command->structureId,
					parentId: $command->parentId,
					description: $command->description,
					colorName: $command->colorName,
				)
			);

			$result = new CreateNodeCommandResult($node);

			$this->nodeSettingsService->save($node->id, $command->settings);

			$userIds = $command->userIds;

			if (!empty($command->userIds))
			{
				switch ($command->usersStrategy)
				{
					case UserAddStrategy::SaveUsersStrategy:
					{
						$addUsersToDepartmentStrategyResult = (new AddUsersStrategy($node, $userIds, $this->nodeMemberService))->execute();

						$result = new CreateNodeCommandResult(
							node: $addUsersToDepartmentStrategyResult->node,
							userMovedToRootIds: $addUsersToDepartmentStrategyResult->userMovedToRootIds,
						);

						break;
					}
					case UserAddStrategy::MoveUsersStrategy:
					{
						$moveUsersToDepartmentStrategyResult = (new MoveUsersStrategy($node, $userIds))->execute();

						$result = new CreateNodeCommandResult(
							node: $moveUsersToDepartmentStrategyResult->node,
							updatedDepartmentIds: $moveUsersToDepartmentStrategyResult->updatedDepartmentIds,
							userCount: $moveUsersToDepartmentStrategyResult->userCount,
						);

						break;
					}
				}
			}

			if ($command->createChat)
			{
				$this->nodeChatService->create($node, RelationEntitySubtype::Chat);
			}

			if ($command->createChannel)
			{
				$this->nodeChatService->create($node, RelationEntitySubtype::Channel);
			}

			if ($command->createCollab)
			{
				$this->nodeCollabService->create($node);
			}

			if (count($command->bindingChatIds) > 0)
			{
				$this->nodeChatService->bind(
					$node,
					RelationEntitySubtype::Chat,
					$command->bindingChatIds,
				);
			}

			if (count($command->bindingChannelIds) > 0)
			{
				$this->nodeChatService->bind(
					$node,
					RelationEntitySubtype::Channel,
					$command->bindingChannelIds,
				);
			}

			if (count($command->bindingCollabIds) > 0)
			{
				$this->nodeCollabService->bind(
					$node,
					$command->bindingCollabIds,
				);
			}

			Application::getConnection()->commitTransaction();

			return $result;
		}
		catch (\Exception $e)
		{
			Application::getConnection()->rollbackTransaction();

			throw $e;
		}
	}
}