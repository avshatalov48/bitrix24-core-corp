<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Repository\NodeRelationRepository;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Exception\TooMuchDataException;
use Bitrix\HumanResources\Integration\Pull\PushMessageService;
use Bitrix\HumanResources\Type\AccessCodeType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;

class NodeRelationService implements Contract\Service\NodeRelationService
{
	private const LINK_CHATS_TO_NODES_COMMAND = 'linkChatsToNodes';

	private readonly NodeRelationRepository $relationRepository;
	private readonly NodeMemberRepository $nodeMemberRepository;
	private readonly NodeRepository $nodeRepository;
	private readonly PushMessageService $pushMessageService;

	public function __construct(
		?NodeRepository $nodeRepository = null,
		?NodeMemberRepository $nodeMemberRepository = null
	)
	{
		$this->relationRepository = Container::getNodeRelationRepository();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
		$this->pushMessageService = Container::getPushMessageService();
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function linkEntityToNodeByAccessCode(
		string $accessCode,
		RelationEntityType $entityType,
		int $entityId,
		?RelationEntitySubtype $subtype = null,
	): ?Item\NodeRelation
	{
		$node = $this->nodeRepository->getByAccessCode($accessCode);

		if (!$node)
		{
			return null;
		}

		return $this->linkEntityByNodeId(
			nodeId: $node->id,
			entityType: $entityType,
			entityId: $entityId,
			withChildNodes: $this->isRecursive($accessCode),
			entitySubtype: $subtype,
		);
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function unlinkEntityFromNodeByAccessCode(
		string $accessCode,
		RelationEntityType $entityType,
		int $entityId,
	): void
	{
		$node = $this->nodeRepository->getByAccessCode($accessCode);

		if (!$node)
		{
			return;
		}

		$this->relationRepository->remove(
			$this->relationRepository->getByNodeIdAndEntityTypeAndEntityIdAndWithChildNodes(
				nodeId: $node->id,
				entityType: $entityType,
				entityId: $entityId,
				withChildNodes: $this->isRecursive($accessCode)
			)
		);
	}

	public function findAllRelationsByEntityTypeAndEntityId(
		RelationEntityType $entityType,
		int $entityId
	): Item\Collection\NodeRelationCollection
	{
		return
			$this->relationRepository
				->findAllByEntityTypeAndEntityId($entityType, $entityId)
			;
	}

	private function isRecursive(string $accessCode): bool
	{
		foreach (AccessCodeType::getRecursiveTypesPrefixes() as $prefix)
		{
			if (str_starts_with($accessCode, $prefix))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param \Bitrix\HumanResources\Type\RelationEntityType $entityType
	 * @param int $entityId
	 * @param array<int> $usersToCompare
	 *
	 * @return array<int>
	 * @throws \Bitrix\HumanResources\Exception\TooMuchDataException
	 */
	public function getUsersNotInRelation(
		RelationEntityType $entityType,
		int $entityId,
		array $usersToCompare,
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(
			NodeEntityType::DEPARTMENT,
			NodeEntityType::TEAM,
		),
	): array
	{
		if (count($usersToCompare) === 0)
		{
			return [];
		}

		if (count($usersToCompare) > 500)
		{
			throw new TooMuchDataException();
		}

		$commonUsers = $this->nodeMemberRepository->getCommonUsersFromRelation(
			$entityType,
			$entityId,
			$usersToCompare,
			$nodeEntityTypeCollection
		);

		sort($commonUsers);
		sort($usersToCompare);

		return array_diff($usersToCompare, $commonUsers);
	}

	public function linkEntityByNodeId(
		int $nodeId,
		RelationEntityType $entityType,
		int $entityId,
		bool $withChildNodes = false,
		?RelationEntitySubtype $entitySubtype = null
	): ?Item\NodeRelation
	{
		$node = $this->nodeRepository->getById($nodeId);

		if (!$node)
		{
			return null;
		}

		$nodeRelation = $this->relationRepository->create(
			new Item\NodeRelation(
				nodeId: $node->id,
				entityId: $entityId,
				entityType: $entityType,
				withChildNodes: $withChildNodes,
				entitySubtype: $entitySubtype,
			)
		);

		if ($entityType === RelationEntityType::CHAT)
		{
			$this->pushMessageService->send(
				self::LINK_CHATS_TO_NODES_COMMAND,
				[
					$node->id => [
						'entityId' => $entityId,
						'entitySubtype' => 	$entitySubtype?->name,
					],
				]
			);
		}

		return $nodeRelation;
	}

	/**
	 * @inheritDoc
	 */
	public function linkNodeRelationCollection(
		$nodeRelationCollection
	): ?Item\Collection\NodeRelationCollection
	{
		foreach ($nodeRelationCollection as $nodeRelation)
		{
			if ($nodeRelation->entityType === RelationEntityType::CHAT)
			{
				$linkedSendResult[$nodeRelation->nodeId][$nodeRelation->entityType->name][] = [
					'entityId' => $nodeRelation->entityId,
					'entitySubtype' => $nodeRelation->entitySubtype->name ?? null,
				];
			}
		}

		$nodeRelationCollection = $this->relationRepository->createByCollection($nodeRelationCollection);
		if (!empty($linkedSendResult))
		{
			$this->pushMessageService->send(
				self::LINK_CHATS_TO_NODES_COMMAND,
				$linkedSendResult
			);
		}

		return $nodeRelationCollection;
	}

	public function unlinkByEntityIdsAndNodeIdAndType(int $nodeId, array $entityIds, RelationEntityType $entityType): void
	{
		$nodeRelationCollection = $this->relationRepository->getByEntityIdsAndNodeIdAndType($nodeId, $entityIds, $entityType);

		foreach ($nodeRelationCollection as $nodeRelation)
		{
			$this->relationRepository->remove($nodeRelation);
		}
	}
}
