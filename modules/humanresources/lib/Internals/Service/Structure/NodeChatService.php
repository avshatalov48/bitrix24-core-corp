<?php

namespace Bitrix\HumanResources\Internals\Service\Structure;

use Bitrix\HumanResources\Contract\Service\NodeRelationService;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\NodeRelationCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Integration\Im\ChatService;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class NodeChatService
{
	private NodeMemberService $nodeMemberService;
	private ChatService $chatService;
	private NodeRelationService $nodeRelationService;

	public function __construct(?NodeRelationService $nodeRelationService = null, ?NodeMemberService $nodeMemberService = null, ?ChatService $chatService = null)
	{
		$this->nodeRelationService = $nodeRelationService ?? Container::getNodeRelationService();
		$this->nodeMemberService = $nodeMemberService ?? Container::getNodeMemberService();
		$this->chatService = $chatService ?? Container::getChatService();
	}

	/**
	 * @param Item\Node $node
	 * @param RelationEntitySubtype $relationEntitySubtype
	 * @return int (chatId)
	 */
	public function create(Item\Node $node, RelationEntitySubtype $relationEntitySubtype): int
	{
		$heads = $this->nodeMemberService->getDefaultHeadRoleEmployees($node->id);

		$headIds = $heads->map(
			function ($head)
			{
				return $head->entityId;
			}
		);

		if (count($headIds) > 0)
		{
			$addResult = $this->chatService->createChat($node, $headIds, $relationEntitySubtype);

			if ($addResult->isSuccess())
			{
				$newChatId = (int)$addResult->getChatId();

				if ($newChatId > 0)
				{
					$this->bind($node, $relationEntitySubtype, [$newChatId], false);
				}

				return $newChatId;
			}
		}

		return 0;
	}

	/**
	 * @param Node $node
	 * @param RelationEntitySubtype $relationEntitySubtype
	 * @param array $ids
	 * @param bool $checkPermissions
	 * @param int $userId
	 *
	 * @return NodeRelationCollection
	 *
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws WrongStructureItemException
	 */
	public function bind(Item\Node $node, RelationEntitySubtype $relationEntitySubtype, array $ids, bool $checkPermissions = true, int $userId = 0): NodeRelationCollection
	{
		$nodeRelationCollection = new NodeRelationCollection();
		if (!$userId && $checkPermissions)
		{
			$userId = (int)CurrentUser::get()->getId();
		}

		if (empty($ids))
		{
			return $nodeRelationCollection;
		}

		if ($checkPermissions)
		{
			$ids = $this->chatService->filterByPermissionsAndType($ids, $userId, $relationEntitySubtype)->getChatIds();
		}

		foreach ($ids as $id)
		{
			$nodeRelationCollection->add(
				new Item\NodeRelation(
					nodeId: $node->id,
					entityId: $id,
					entityType: RelationEntityType::CHAT,
					entitySubtype: $relationEntitySubtype,
				)
			);
		}

		if (!$nodeRelationCollection->empty())
		{
			return $this->nodeRelationService->linkNodeRelationCollection($nodeRelationCollection) ?? new NodeRelationCollection();
		}

		return $nodeRelationCollection;
	}
}