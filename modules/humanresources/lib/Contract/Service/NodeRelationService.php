<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\HumanResources\Item;

interface NodeRelationService
{
	public function linkEntityToNodeByAccessCode(
		string $accessCode,
		RelationEntityType $entityType,
		int $entityId,
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
	): array;
}