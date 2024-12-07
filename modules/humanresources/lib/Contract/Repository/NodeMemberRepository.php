<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main;
use Bitrix\Main\Result;

interface NodeMemberRepository
{
	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 */
	public function create(Item\NodeMember $nodeMember): Item\NodeMember;
	public function remove(Item\NodeMember $nodeMember): bool;
	public function createByCollection(Item\Collection\NodeMemberCollection $nodeMemberCollection): Item\Collection\NodeMemberCollection;
	public function findAllByNodeId(
		int $nodeId,
		bool $withAllChildNodes = false,
		int $limit = 100,
		int $offset = 0
	): Item\Collection\NodeMemberCollection;
	public function findAllByNodeIdAndEntityType(
		int $nodeId,
		MemberEntityType $entityType,
		bool $withAllChildNodes = false,
		int $limit = 100,
		int $offset = 0,
		bool $onlyActive = true,
	): Item\Collection\NodeMemberCollection;

	/**
	 * Finds a NodeMember by its ID.
	 *
	 * @param int $memberId The ID of the NodeMember to find.
	 *
	 * @return Item\NodeMember|null The found NodeMember object or null if not found.
	 */
	public function findById(int $memberId): ?Item\NodeMember;

	/**
	 * @param int $nodeId
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function removeAllMembersByNodeId(int $nodeId): void;

	public function findAllByRoleIdAndNodeId(?int $roleId, ?int $nodeId): Item\Collection\NodeMemberCollection;

	/**
	 * @param \Bitrix\HumanResources\Item\NodeMember $member
	 *
	 * @return \Bitrix\HumanResources\Item\NodeMember
	 * @throws UpdateFailedException
	 */
	public function update(Item\NodeMember $member): Item\NodeMember;

	public function setActiveByEntityTypeAndEntityId(
		MemberEntityType $entityType,
		int $entityId,
		bool $active,
	): Main\Result;

	/**
	 * @param MemberEntityType $entityType
	 * @param list<int> $entityIds
	 * @param bool $active
	 *
	 * @return Result
	 */
	public function setActiveByEntityTypeAndEntityIds(
		MemberEntityType $entityType,
		array $entityIds,
		bool $active,
	): Main\Result;

	/**
	 * Finds all NodeMembers by their entity ID and entity type.
	 *
	 * @param int $entityId
	 * @param \Bitrix\HumanResources\Type\MemberEntityType $entityType
	 *
	 * @return Item\Collection\NodeMemberCollection
	 */
	public function findAllByEntityIdAndEntityType(
		int $entityId,
		\Bitrix\HumanResources\Type\MemberEntityType $entityType
	): Item\Collection\NodeMemberCollection;

	/**
	 * Finds all NodeMembers by their entity ID and entity type.
	 *
	 * @param list<int> $entityIds
	 * @param \Bitrix\HumanResources\Type\MemberEntityType $entityType
	 *
	 * @return Item\Collection\NodeMemberCollection
	 */
	public function findAllByEntityIdsAndEntityType(
		array $entityIds,
		\Bitrix\HumanResources\Type\MemberEntityType $entityType
	): Item\Collection\NodeMemberCollection;

	public function getCommonUsersFromRelation(
		\Bitrix\HumanResources\Type\RelationEntityType $entityType,
		int $entityId,
		array $usersToCompare
	);

	public function findAllByRoleIdAndStructureId(?int $roleId, int $structureId): Item\Collection\NodeMemberCollection;

	public function findByEntityTypeAndEntityIdAndNodeId(
		MemberEntityType $entityType,
		int $entityId,
		int $nodeId
	): ?Item\NodeMember;

	/**
	 * @param \Bitrix\HumanResources\Item\Structure $structure
	 *
	 * @return array<int, int>
	 */
	public function countAllByStructureAndGroupByNode(Item\Structure $structure): array;

	/**
	 * Counts all members by the given node ID.
	 * This method takes a node ID as a parameter and returns the total count of members associated with that node.
	 *
	 * @param int $nodeId
	 *
	 * @return int The total count of members associated with the given node ID.
	 */
	public function countAllByByNodeId(int $nodeId): int;
}