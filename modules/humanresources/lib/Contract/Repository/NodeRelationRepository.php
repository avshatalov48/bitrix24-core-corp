<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\RelationEntityType;

interface NodeRelationRepository
{
	public function create(Item\NodeRelation $nodeRelation): Item\NodeRelation;
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
		int $offset = 0
	): Item\Collection\NodeRelationCollection;

	public function findRelationsByNodeIdAndRelationType(
		int $nodeId,
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0
	): Item\Collection\NodeRelationCollection;
}