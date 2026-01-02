<?php

namespace Bitrix\HumanResources\Command\Structure\Node\Handler;

use Bitrix\HumanResources\Command\Structure\Node\SaveNodeChatsCommand;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Integration\Im\ChatService;
use Bitrix\HumanResources\Integration\Socialnetwork\CollabService;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Result\Command\Structure\SaveNodeChatsResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Service\NodeRelationService;
use Bitrix\HumanResources\Type\NodeChatType;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\Engine\CurrentUser;

class SaveNodeChatsCommandHandler
{
	private ChatService $chatService;
	private CollabService $collabService;
	private NodeMemberService $nodeMemberService;
	private NodeRelationService $nodeRelationService;

	public function __construct()
	{
		$this->chatService = Container::getChatService();
		$this->nodeMemberService = Container::getNodeMemberService();
		$this->nodeRelationService = Container::getNodeRelationService();
		$this->collabService = Container::getCollabService();
	}

	public function __invoke(SaveNodeChatsCommand $command): SaveNodeChatsResult
	{
		$result = new SaveNodeChatsResult();

		$relationType = $this->getRelationEntityTypeByChatType($command->chatType);
		if (!empty($command->removeIds))
		{
			// unlink removed relations
			// no need to check permission, because we depend on _COMMUNICATION_EDIT permission
			$this->nodeRelationService->unlinkByEntityIdsAndNodeIdAndType(
				$command->node->id,
				$command->removeIds,
				$relationType,
			);
		}

		$relationSubtype = $this->getSubRelationEntityTypeByChatType($command->chatType);
		/**
		 * We filter chats & channels before creating new because current user
		 * might have no right to add department to a newly created chat or channel
		 */
		if ($relationType === RelationEntityType::COLLAB)
		{
			$filteredChatIds = $this->collabService->filterByPermissions(
				$command->ids,
				CurrentUser::get()->getId(),
			);
		}
		else
		{
			$filteredChatIds = $this->chatService
				->filterByPermissionsAndType(
					$command->ids,
					CurrentUser::get()->getId(),
					$relationSubtype,
				)
				->getChatIds()
			;
		}

		if ($command->createDefault)
		{
			$heads = $this->nodeMemberService->getDefaultHeadRoleEmployees($command->node->id);
			$headIds = $heads->map(function ($head) { return $head->entityId; });

			if (count($headIds) > 0)
			{
				if ($relationType === RelationEntityType::COLLAB)
				{
					$addResult = $this->collabService->create($command->node, $headIds, CurrentUser::get()->getId());

					if ($addResult->isSuccess())
					{
						$filteredChatIds[] = (int)$addResult->getCollabId();
					}
					else
					{
						$result->addErrors($addResult->getErrors());
					}
				}
				else
				{
					$addResult = $this->chatService->createChat($command->node, $headIds, $relationSubtype);
					if ($addResult->isSuccess())
					{
						$filteredChatIds[] = $addResult->getChatId();
					}
					else
					{
						$result->addErrors($addResult->getErrors());
					}
				}
			}
		}

		$nodeRelationCollection = new Item\Collection\NodeRelationCollection();
		foreach ($filteredChatIds as $chatId)
		{
			$nodeRelationCollection->add(
				new Item\NodeRelation(
					nodeId: $command->node->id,
					entityId: $chatId,
					entityType: $relationType,
					withChildNodes: $command->withChildren,
					entitySubtype: $relationSubtype,
				)
			);
		}

		if (!$nodeRelationCollection->empty())
		{
			$this->nodeRelationService->linkNodeRelationCollection($nodeRelationCollection);
		}

		return $result;
	}

	private function getRelationEntityTypeByChatType(NodeChatType $nodeChatType): RelationEntityType
	{
		return match ($nodeChatType)
		{
			NodeChatType::Chat, NodeChatType::Channel => RelationEntityType::CHAT,
			NodeChatType::Collab => RelationEntityType::COLLAB,
		};
	}

	private function getSubRelationEntityTypeByChatType(NodeChatType $nodeChatType): ?RelationEntitySubtype
	{
		return match ($nodeChatType)
		{
			NodeChatType::Chat => RelationEntitySubtype::Chat,
			NodeChatType::Channel  => RelationEntitySubtype::Channel,
			default => null,
		};
	}
}
