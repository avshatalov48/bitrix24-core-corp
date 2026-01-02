<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\NodeRelationCollection;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use InvalidArgumentException;

interface NodeRelationRepository
{
	public function create(Item\NodeRelation $nodeRelation): Item\NodeRelation;
	public function createByCollection(
		Item\Collection\NodeRelationCollection $nodeRelationCollection,
	): Item\Collection\NodeRelationCollection;
	public function remove(Item\NodeRelation $nodeRelation): void;
	public function findAllByNodeId(int $nodeId): Item\Collection\NodeRelationCollection;
	public function findAllByEntityTypeAndEntityId(
		RelationEntityType $entityType,
		int $entityId
	): Item\Collection\NodeRelationCollection;
	public function getByNodeIdAndEntityTypeAndEntityId(
		int $nodeId,
		RelationEntityType $entityType,
		int $entityId
	): ?Item\NodeRelation;

	public function getByNodeIdAndEntityTypeAndEntityIdAndWithChildNodes(
		int $nodeId,
		RelationEntityType $entityType,
		int $entityId,
		bool $withChildNodes,
	): ?Item\NodeRelation;

	public function findRelationsByNodeMemberEntityAndRelationType(
		int $memberEntityId,
		MemberEntityType $memberEntityType,
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0,
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
	): Item\Collection\NodeRelationCollection;

	public function findRelationsByNodeIdAndRelationType(
		int $nodeId,
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0,
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
	): Item\Collection\NodeRelationCollection;

	/**
	 * @param RelationEntityType $relationEntityType
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return NodeRelationCollection
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findRelationsByRelationType(
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0
	): Item\Collection\NodeRelationCollection;

	/**
	 * @param RelationEntityType $entityType
	 * @param int[] $entityIds
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function deleteRelationByEntityTypeAndEntityIds(RelationEntityType $entityType, array $entityIds): void;

	public function getByEntityIdsAndNodeIdAndType(
		int $nodeId,
		array $entityIds,
		RelationEntityType $entityType
	): Item\Collection\NodeRelationCollection;
}
