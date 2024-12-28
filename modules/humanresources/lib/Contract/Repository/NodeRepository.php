<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Item\Node;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main;

interface NodeRepository
{
	public const NODE_CACHE_KEY = 'structure/node/%d';
	public const NODE_ENTITY_CACHE_KEY = 'structure/node/entity/%d';
	public const NODE_ENTITY_RESTRICTION_CACHE = 'structure/node/restriction';
	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 *
	 * @return \Bitrix\HumanResources\Item\Node
	 * @throws CreationFailedException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function create(Item\Node $node): Item\Node;

	/**
	 * @param Node $node
	 *
	 * @return Node
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function update(Item\Node $node): Item\Node;

	public function getParentOf(
		Item\Node $node,
		DepthLevel|int $depthLevel = DepthLevel::FIRST
	): Item\Collection\NodeCollection;

	public function getChildOf(
		Item\Node $node,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection;

	public function getChildOfNodeCollection(
		Item\Collection\NodeCollection $nodeCollection,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection;

	public function findAllByUserId(int $userId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection;

	/**
	 * @param int $nodeId
	 * @param bool $needDepth
	 *
	 * @return \Bitrix\HumanResources\Item\Node|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getById(int $nodeId, bool $needDepth = false): ?Item\Node;

	/**
	 * returns node data with depth level
	 *
	 * @param int $nodeId
	 *
	 * @return Node|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByIdWithDepth(int $nodeId): ?Item\Node;

	/**
	 * Retrieves all child ids of a given node id.
	 *
	 * @param int $nodeId
	 *
	 * @return list<int>
	 */
	public function getAllChildIdsByNodeId(int $nodeId): array;

	public function findAllByUserIdAndRoleId(int $userId, int $roleId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection;

	/**
	 * Retrieve a node by access code.
	 *
	 * @param string $accessCode The access code of the node.
	 *
	 * @return \Bitrix\HumanResources\Item\Node|null The node with the given access code, or null if not found.
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByAccessCode(string $accessCode): ?Item\Node;

	/**
	 * @param string $xmlId The XML ID of the nodes to find
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\NodeCollection The collection of nodes found
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findAllByXmlId(string $xmlId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection;

	/**
	 * Retrieves the root node by structure id.
	 *
	 * @param int $structureId
	 *
	 * @return \Bitrix\HumanResources\Item\Node|null
	 *
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws SystemException
	 */
	public function getRootNodeByStructureId(int $structureId): ?Item\Node;

	/**
	 * @param int $structureId
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\NodeCollection
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getAllByStructureId(int $structureId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection;
	public function getAllPagedByStructureId(int $structureId, int $limit = 10, int $offset = 0, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection;

	public function hasChild(Item\Node $node): bool;

	public function isAncestor(Item\Node $node, Item\Node $targetNode): bool;

	/**
	 * Delete a node and all associated data from the database.
	 *
	 * @param int $nodeId
	 *
	 * @return void
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	public function deleteById(int $nodeId): void;

	/**
	 * @param list<int> $departments
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\NodeCollection
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findAllByAccessCodes(array $departments): Item\Collection\NodeCollection;

	public function getNodesByName(
		int $structureId,
		?string $name,
		?int $limit = 100,
		?int $parentId = null,
		DepthLevel|int $depth = DepthLevel::FULL,
		bool $strict = false,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection;

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function findAllByIds(
		array $departmentIds,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection;
}