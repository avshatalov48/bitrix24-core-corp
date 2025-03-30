<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;

interface NodeMemberRepository
{
	/**
	 * @param \Bitrix\HumanResources\Item\NodeMember $nodeMember
	 *
	 * @return \Bitrix\HumanResources\Item\NodeMember
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\DB\DuplicateEntryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function create(Item\NodeMember $nodeMember): Item\NodeMember;
	public function remove(Item\NodeMember $nodeMember): bool;
	public function createByCollection(Item\Collection\NodeMemberCollection $nodeMemberCollection): Item\Collection\NodeMemberCollection;
	public function findAllByNodeId(
		int $nodeId,
		bool $withAllChildNodes = false,
		int $limit = 100,
		int $offset = 0,
		bool $onlyActive = true,
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

	public function findAllByRoleIdAndNodeId(?int $roleId, ?int $nodeId, ?int $limit, ?int $offset, bool $ascendingSort = true): Item\Collection\NodeMemberCollection;

	public function findAllByRoleIdAndNodeCollection(
		?int $roleId,
		Item\Collection\NodeCollection $nodeCollection,
		int $limit = 0,
		int $offset = 0,
		bool $ascendingSort = true,
	): Item\Collection\NodeMemberCollection;

	/**
	 * @param \Bitrix\HumanResources\Item\NodeMember $member
	 *
	 * @return \Bitrix\HumanResources\Item\NodeMember
	 * @throws UpdateFailedException
	 */
	public function update(Item\NodeMember $member): Item\NodeMember;

	public function updateByCollection(
		Item\Collection\NodeMemberCollection $nodeMemberCollection
	): Item\Collection\NodeMemberCollection;

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

	/**
	 * Finds the first NodeMember by the given entity ID, entity type, node type, and active status.
	 *
	 * @param int $entityId
	 * @param MemberEntityType $entityType
	 * @param NodeEntityType $nodeType
	 * @param bool|null $active
	 *
	 * @return NodeMember|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findFirstByEntityIdAndEntityTypeAndNodeTypeAndActive(
		int $entityId,
		MemberEntityType $entityType,
		NodeEntityType $nodeType,
		?bool $active = null
	): ?Item\NodeMember;

	public function findAllByEntityIdAndEntityTypeAndNodeType(
		int $entityId,
		MemberEntityType $entityType,
		NodeEntityType $nodeType,
		int $limit = 0,
		int $offset = 0,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE
	): item\Collection\NodeMemberCollection;

	public function findAllByEntityIdsAndEntityTypeAndNodeType(
		array $entityIds,
		MemberEntityType $entityType,
		NodeEntityType $nodeType,
	): Item\Collection\NodeMemberCollection;

	/**
	 * @param Item\Collection\NodeMemberCollection $nodeMemberCollection
	 *
	 * @return bool
	 * @throws Main\DB\SqlQueryException
	 */
	public function removeByCollection(
		Item\Collection\NodeMemberCollection $nodeMemberCollection
	): bool;
}