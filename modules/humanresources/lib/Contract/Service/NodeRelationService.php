<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Item\Collection\NodeRelationCollection;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\HumanResources\Item;
use Bitrix\Main\ArgumentException;

interface NodeRelationService
{
	public function linkEntityToNodeByAccessCode(
		string $accessCode,
		RelationEntityType $entityType,
		int $entityId,
		?RelationEntitySubtype $subtype = null,
	): ?Item\NodeRelation;

	public function unlinkEntityFromNodeByAccessCode(
		string $accessCode,
		RelationEntityType $entityType,
		int $entityId,
	): void;

	public function findAllRelationsByEntityTypeAndEntityId(
		RelationEntityType $entityType,
		int $entityId
	): Item\Collection\NodeRelationCollection;

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
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
	): array;

	/**
	 * Create NodeRelation
	 *
	 * @param int $nodeId
	 * @param RelationEntityType $entityType
	 * @param int $entityId
	 * @param RelationEntitySubtype|null $entitySubtype
	 * @return Item\NodeRelation|null
	 */
	public function linkEntityByNodeId(
		int $nodeId,
		RelationEntityType $entityType,
		int $entityId,
		bool $withChildNodes = false,
		?RelationEntitySubtype $entitySubtype = null
	) : ?Item\NodeRelation;

	/**
	 * @param NodeRelationCollection $nodeRelationCollection
	 *
	 * @return NodeRelationCollection|null
	 * @psalm-type entityId = int
	 */
	public function linkNodeRelationCollection(
		Item\Collection\NodeRelationCollection $nodeRelationCollection,
	): ?Item\Collection\NodeRelationCollection;

	public function unlinkByEntityIdsAndNodeIdAndType(int $nodeId, array $entityIds, RelationEntityType $entityType): void;
}