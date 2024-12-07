<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Contract\Repository\NodeRelationRepository;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Exception\TooMuchDataException;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;

class NodeRelationService implements Contract\Service\NodeRelationService
{
	private readonly NodeRelationRepository $relationRepository;
	private readonly NodeMemberRepository $nodeMemberRepository;
	private readonly NodeRepository $nodeRepository;

	public function __construct(
		?NodeRelationRepository $relationRepository = null,
		?NodeRepository $nodeRepository = null,
		?NodeMemberRepository $nodeMemberRepository = null
	)
	{
		$this->relationRepository = $relationRepository ?? Container::getNodeRelationRepository();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function linkEntityToNodeByAccessCode(
		string $accessCode,
		RelationEntityType $entityType,
		int $entityId,
	): ?Item\NodeRelation
	{
		$node = $this->nodeRepository->getByAccessCode($accessCode);

		if (!$node)
		{
			return null;
		}

		return $this->relationRepository->create(
			new Item\NodeRelation(
				nodeId: $node->id,
				entityId: $entityId,
				entityType: $entityType,
				withChildNodes: $this->isRecursive($accessCode)
			)
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
		return mb_strpos($accessCode, 'DR') !== false;
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
			$usersToCompare
		);

		sort($commonUsers);
		sort($usersToCompare);

		return array_diff($usersToCompare, $commonUsers);
	}
}