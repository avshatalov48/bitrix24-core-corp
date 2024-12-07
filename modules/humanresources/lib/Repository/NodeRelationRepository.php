<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Contract\Service\EventSenderService;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\NodeRelationCollection;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\NodeRelationTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Enum\EventName;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\HumanResources\Contract;

class NodeRelationRepository implements Contract\Repository\NodeRelationRepository
{
	private readonly EventSenderService $eventSenderService;
	private readonly NodeRepository $nodeRepository;

	public function __construct(
		?EventSenderService $eventSenderService = null,
		?NodeRepository $nodeRepository = null,
	)
	{
		$this->eventSenderService = $eventSenderService ?? Container::getEventSenderService();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
	}

	private function convertModelToItem(Model\NodeRelation $nodeRelation): Item\NodeRelation
	{
		return new Item\NodeRelation(
			nodeId:         $nodeRelation->getNodeId(),
			entityId:       $nodeRelation->getEntityId(),
			entityType:     RelationEntityType::tryFrom($nodeRelation->getEntityType()),
			withChildNodes: $nodeRelation->getWithChildNodes(),
			id:             $nodeRelation->getId(),
			createdBy:      $nodeRelation->getCreatedBy(),
			createdAt:      $nodeRelation->getCreatedAt(),
			updatedAt:      $nodeRelation->getUpdatedAt(),
			node:           $this->nodeRepository->getById($nodeRelation->getNodeId()),
		);
	}

	private function convertModelToItemFromArray(array $nodeRelation): Item\NodeRelation
	{
		return new Item\NodeRelation(
			nodeId: $nodeRelation['NODE_ID'],
			entityId: $nodeRelation['ENTITY_ID'],
			entityType: RelationEntityType::tryFrom($nodeRelation['ENTITY_TYPE']),
			withChildNodes: $nodeRelation['WITH_CHILD_NODES'] === 'Y',
			id: $nodeRelation['ID'],
			createdBy: $nodeRelation['CREATED_BY'],
			createdAt: $nodeRelation['CREATED_AT'],
			updatedAt: $nodeRelation['UPDATED_BY'],
			node: $this->nodeRepository->getById($nodeRelation['NODE_ID']),
		);
	}

	/**
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function create(Item\NodeRelation $nodeRelation): Item\NodeRelation
	{
		$nodeRelationEntity = NodeRelationTable::getEntity()->createObject();
		$currentUserId = CurrentUser::get()->getId();

		$existed = $this->getByNodeIdAndEntityTypeAndEntityIdAndWithChildNodes(
			$nodeRelation->nodeId,
			$nodeRelation->entityType,
			$nodeRelation->entityId,
			$nodeRelation->withChildNodes,
		);

		if ($existed)
		{
			return $existed;
		}

		$nodeRelationCreateResult = $nodeRelationEntity
			->setNodeId($nodeRelation->nodeId)
			->setCreatedBy($currentUserId)
			->setEntityId($nodeRelation->entityId)
			->setEntityType($nodeRelation->entityType->name)
			->setWithChildNodes($nodeRelation->withChildNodes)
			->save()
		;

		if (!$nodeRelationCreateResult->isSuccess())
		{
			throw (new CreationFailedException())
				->setErrors($nodeRelationCreateResult->getErrorCollection());
		}

		$nodeRelation->id = $nodeRelationCreateResult->getId();
		$nodeRelation->node = $this->nodeRepository->getById($nodeRelation->nodeId);

		$this->eventSenderService->send(EventName::RELATION_ADDED, [
			'relation' => $nodeRelation,
		]);

		return $nodeRelation;
	}

	public function remove(Item\NodeRelation $nodeRelation): void
	{
		if (!$nodeRelation->id)
		{
			return;
		}

		$result = NodeRelationTable::delete($nodeRelation->id);
		if (!$result->isSuccess())
		{
			throw (new DeleteFailedException())
				->setErrors($result->getErrorCollection())
			;
		}

		$this->eventSenderService->send(EventName::RELATION_DELETED, [
			'relation' => $nodeRelation,
		]);
	}

	public function findAllByNodeId(int $nodeId): Item\Collection\NodeRelationCollection
	{
		$relations =
			NodeRelationTable::query()
				->setSelect(['*'])
				->where('NODE_ID', $nodeId)
				->fetchAll()
		;

		$nodeRelations = new Item\Collection\NodeRelationCollection();
		foreach ($relations as $nodeRelationEntity)
		{
			$nodeRelations->add($this->convertModelToItem($nodeRelationEntity));
		}

		return $nodeRelations;
	}

	public function getByNodeIdAndEntityTypeAndEntityId(
		int $nodeId,
		RelationEntityType $entityType,
		int $entityId
	): ?Item\NodeRelation
	{
		$relation =
			NodeRelationTable::query()
				->setSelect(['*'])
				->where('NODE_ID', $nodeId)
				->where('ENTITY_TYPE', $entityType->name)
				->where('ENTITY_ID', $entityId)
				->fetchObject()
		;

		if ($relation)
		{
			return $this->convertModelToItem($relation);
		}

		return null;
	}

	public function getByNodeIdAndEntityTypeAndEntityIdAndWithChildNodes(
		int $nodeId,
		RelationEntityType $entityType,
		int $entityId,
		bool $withChildNodes,
	): ?Item\NodeRelation
	{
		$relation =
			NodeRelationTable::query()
				->setSelect(['*'])
				->where('NODE_ID', $nodeId)
				->where('ENTITY_TYPE', $entityType->name)
				->where('ENTITY_ID', $entityId)
				->where('WITH_CHILD_NODES', $withChildNodes)
				->fetchObject()
		;

		if ($relation)
		{
			return $this->convertModelToItem($relation);
		}

		return null;
	}

	/**
	 * @throws SqlQueryException
	 * @throws WrongStructureItemException
	 */
	public function findRelationsByNodeMemberEntityAndRelationType(
		int $memberEntityId,
		MemberEntityType $memberEntityType,
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0
	): Item\Collection\NodeRelationCollection
	{
		$connection = Application::getConnection();

		$query = $this->prepareFindRelationByNodeMemberQuery(
			'DISTINCT nr.*',
			$memberEntityId,
			$memberEntityType,
			$relationEntityType,
		);

		$nodeRelations = $this->getLimitedNodeRelationCollection($query, $offset, $limit);

		$countQuery = $this->prepareFindRelationByNodeMemberQuery(
			'COUNT(DISTINCT nr.ID) as CNT',
			$memberEntityId,
			$memberEntityType,
			$relationEntityType,
		);

		$count = $connection
			->query($countQuery)
			->fetch()
		;

		$nodeRelations->setTotalCount(
			$count['CNT'] ?? 0
		);

		return $nodeRelations;
	}

	/**
	 * @throws SqlQueryException
	 * @throws WrongStructureItemException
	 */
	public function findRelationsByNodeIdAndRelationType(
		int $nodeId,
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0
	): Item\Collection\NodeRelationCollection
	{
		$connection = Application::getConnection();

		$query = $this->prepareFindRelationByNodeIdQuery(
			'DISTINCT nr.*',
			$nodeId,
			$relationEntityType,
		);

		$nodeRelations = $this->getLimitedNodeRelationCollection($query, $offset, $limit);

		$countQuery = $this->prepareFindRelationByNodeIdQuery(
			'COUNT(DISTINCT nr.ID) as CNT',
			$nodeId,
			$relationEntityType,
		);

		$count =
			$connection->query($countQuery)
				->fetch()
		;

		$nodeRelations->setTotalCount(
			$count['CNT'] ?? 0
		);

		return $nodeRelations;
	}

	private function prepareFindRelationByNodeMemberQuery(
		string $select,
		int $memberEntityId,
		MemberEntityType $memberEntityType,
		RelationEntityType $relationEntityType,
	): string
	{
		$relationEntityType = $relationEntityType->name;
		$memberEntityType = $memberEntityType->name;
		$nodeTableName = Model\NodeTable::getTableName();
		$nodePathTableName = Model\NodePathTable::getTableName();
		$nodeRelationTableName = Model\NodeRelationTable::getTableName();
		$nodeMemberTableName = Model\NodeMemberTable::getTableName();

		return <<<SQL
SELECT $select
	FROM $nodeTableName n
		   INNER JOIN $nodePathTableName np ON np.CHILD_ID = n.ID
		   INNER JOIN $nodeRelationTableName nr ON (
	nr.WITH_CHILD_NODES = 'Y' AND (np.PARENT_ID = nr.NODE_ID OR nr.NODE_ID = n.ID)
		  OR
	nr.WITH_CHILD_NODES = 'N' AND nr.NODE_ID = n.ID
    )
		INNER JOIN $nodeMemberTableName nm ON nm.NODE_ID = n.ID
	WHERE
	nr.ENTITY_TYPE = '$relationEntityType' AND
	nm.ENTITY_ID = $memberEntityId
	AND nm.ENTITY_TYPE = '$memberEntityType'
	ORDER BY nr.ID ASC 
SQL;
	}

	public function findAllByEntityTypeAndEntityId(
		RelationEntityType $entityType,
		int $entityId
	): Item\Collection\NodeRelationCollection
	{
		$relations =
			NodeRelationTable::query()
				->setSelect(['*'])
				->where('ENTITY_TYPE', $entityType->name)
				->where('ENTITY_ID', $entityId)
				->fetchAll()
		;

		$nodeRelations = new Item\Collection\NodeRelationCollection();
		foreach ($relations as $nodeRelationEntity)
		{
			$nodeRelations->add($this->convertModelToItemFromArray($nodeRelationEntity));
		}

		return $nodeRelations;
	}

	private function prepareFindRelationByNodeIdQuery(
		string $select,
		int $nodeId,
		RelationEntityType $relationEntityType
	): string
	{
		$relationEntityType = $relationEntityType->name;
		$nodeTableName = Model\NodeTable::getTableName();
		$nodePathTableName = Model\NodePathTable::getTableName();
		$nodeRelationTableName = Model\NodeRelationTable::getTableName();

		return <<<SQL
SELECT $select
  from $nodeTableName n
		   INNER JOIN $nodePathTableName np ON np.CHILD_ID = n.ID
		   INNER JOIN $nodeRelationTableName nr ON (
	  nr.WITH_CHILD_NODES = 'Y' AND (np.PARENT_ID = nr.NODE_ID OR nr.NODE_ID = n.ID)
		  OR
	  nr.WITH_CHILD_NODES = 'N' AND nr.NODE_ID = n.ID
	  )
  WHERE
	  nr.ENTITY_TYPE = '$relationEntityType' AND
	  n.ID = $nodeId
SQL;
	}

	/**
	 * @param string $query
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return NodeRelationCollection
	 * @throws SqlQueryException
	 * @throws WrongStructureItemException
	 */
	private function getLimitedNodeRelationCollection(
		string $query,
		int $offset,
		int $limit,
	): Item\Collection\NodeRelationCollection
	{
		$connection = Application::getConnection();

		if ($connection->getType() === 'mysql')
		{
			$query .= " LIMIT $offset, $limit";
		}
		else
		{
			$query .= " LIMIT $limit OFFSET $offset";
		}

		$relations =
			$connection->query($query)
				->fetchAll()
		;

		$nodeRelations = new Item\Collection\NodeRelationCollection();
		foreach ($relations as $nodeRelationEntity)
		{
			$nodeRelations->add($this->convertModelToItemFromArray($nodeRelationEntity));
		}

		return $nodeRelations;
	}
}