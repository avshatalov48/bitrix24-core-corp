<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Service\EventSenderService;
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
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\RelationEntitySubtype;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\HumanResources\Contract;
use InvalidArgumentException;

class NodeRelationRepository implements Contract\Repository\NodeRelationRepository
{
	private readonly EventSenderService $eventSenderService;
	private readonly NodeRepository $nodeRepository;

	public function __construct(
		?NodeRepository $nodeRepository = null,
	)
	{
		$this->eventSenderService = Container::getEventSenderService();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
	}

	private function convertModelToItem(Model\NodeRelation $nodeRelation): Item\NodeRelation
	{
		return new Item\NodeRelation(
			nodeId:         $nodeRelation->getNodeId(),
			entityId:       $nodeRelation->getEntityId(),
			entityType:     RelationEntityType::tryFrom($nodeRelation->getEntityType()),
			withChildNodes: $nodeRelation->getWithChildNodes(),
			entitySubtype:  RelationEntitySubtype::tryFrom($nodeRelation->getEntitySubtype() ?? ''),
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
			entitySubtype: RelationEntitySubtype::tryFrom($nodeRelation['ENTITY_SUBTYPE'] ?? ''),
			id: $nodeRelation['ID'],
			createdBy: $nodeRelation['CREATED_BY'],
			createdAt: $nodeRelation['CREATED_AT'],
			updatedAt: $nodeRelation['UPDATED_AT'],
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
			->setEntityType($nodeRelation->entityType->value)
			->setWithChildNodes($nodeRelation->withChildNodes)
			->setEntitySubtype($nodeRelation->entitySubtype?->value)
			->save()
		;

		if (!$nodeRelationCreateResult->isSuccess())
		{
			throw (new CreationFailedException())
				->setErrors($nodeRelationCreateResult->getErrorCollection());
		}

		$nodeRelation->id = $nodeRelationCreateResult->getId();
		$nodeRelation->node = $this->nodeRepository->getById($nodeRelation->nodeId);
		$nodeRelation->createdBy = $currentUserId;

		$this->eventSenderService->send(EventName::OnRelationAdded, [
			'relation' => $nodeRelation,
		]);

		return $nodeRelation;
	}

	public function createByCollection(
		Item\Collection\NodeRelationCollection $nodeRelationCollection,
	): Item\Collection\NodeRelationCollection
	{
		$connection = Application::getConnection();
		try
		{
			$connection->startTransaction();
			foreach ($nodeRelationCollection as $nodeRelation)
			{
				$this->create($nodeRelation);
			}
			$connection->commitTransaction();
		}
		catch (\Exception $exception)
		{
			$connection->rollbackTransaction();
			throw $exception;
		}

		return $nodeRelationCollection;
	}

	public function remove(Item\NodeRelation $nodeRelation): void
	{
		if (!$nodeRelation->id)
		{
			return;
		}

		if (!$nodeRelation->node)
		{
			$nodeRelation->node = $this->nodeRepository->getById($nodeRelation->nodeId);
		}

		$result = NodeRelationTable::delete($nodeRelation->id);
		if (!$result->isSuccess())
		{
			throw (new DeleteFailedException())
				->setErrors($result->getErrorCollection())
			;
		}

		$this->eventSenderService->send(EventName::OnRelationDeleted, [
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
			$nodeRelations->add($this->convertModelToItemFromArray($nodeRelationEntity));
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
				->where('ENTITY_TYPE', $entityType->value)
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
				->where('ENTITY_TYPE', $entityType->value)
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
		int $offset = 0,
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
	): Item\Collection\NodeRelationCollection
	{
		$connection = Application::getConnection();

		$query = $this->prepareFindRelationByNodeMemberQuery(
			'DISTINCT nr.*',
			$memberEntityId,
			$memberEntityType,
			$relationEntityType,
			$nodeEntityTypeCollection
		);

		$nodeRelations = $this->getLimitedNodeRelationCollection($query, $offset, $limit);

		$countQuery = $this->prepareFindRelationByNodeMemberQuery(
			'COUNT(DISTINCT nr.ID) as CNT',
			$memberEntityId,
			$memberEntityType,
			$relationEntityType,
			$nodeEntityTypeCollection
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
	public function findRelationsByNodeId(
		int $nodeId,
		int $limit = 100,
		int $offset = 0,
	): Item\Collection\NodeRelationCollection
	{
		$query = $this->prepareFindRelationByNodeIdQuery(
			'DISTINCT nr.*',
			$nodeId,
		);
		$nodeRelations = $this->getLimitedNodeRelationCollection($query, $offset, $limit);

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
		int $offset = 0,
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
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
		NodeEntityTypeCollection $nodeEntityTypeCollection = new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
	): string
	{
		$relationEntityType = $relationEntityType->value;
		$memberEntityType = $memberEntityType->value;
		$nodeTableName = Model\NodeTable::getTableName();
		$nodePathTableName = Model\NodePathTable::getTableName();
		$nodeRelationTableName = Model\NodeRelationTable::getTableName();
		$nodeMemberTableName = Model\NodeMemberTable::getTableName();

		if (empty($nodeEntityTypeCollection->getItems()))
		{
			$nodeEntityTypeCollection = new NodeEntityTypeCollection(
				NodeEntityType::DEPARTMENT,
				NodeEntityType::TEAM
			);
		}

		$helper = Application::getConnection()->getSqlHelper();

		$types = implode(
			',',
			array_map(
				static function(NodeEntityType $type) use ($helper) {
					$type = $helper->forSql($type->value);
					return "'$type'";
				},
				$nodeEntityTypeCollection->getItems(),
			),
		);

		return <<<SQL
SELECT $select
	FROM $nodeTableName n
		   INNER JOIN $nodePathTableName np ON np.CHILD_ID = n.ID
	  	   INNER JOIN $nodeTableName n2 ON (n2.ID = np.PARENT_ID AND n2.TYPE = n.TYPE)
		   INNER JOIN $nodeRelationTableName nr ON (
	nr.WITH_CHILD_NODES = 'Y' AND (np.PARENT_ID = nr.NODE_ID OR nr.NODE_ID = n.ID) AND n.TYPE in ($types)
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
				->where('ENTITY_TYPE', $entityType->value)
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

	/**
	 * Finds relations by node ID and relation type,
	 * including parent nodes' relations if WITH_CHILD_NODES is 'Y'
	 * AND if a parent has the same type as the node.
	 *
	 * @param string $select
	 * @param int $nodeId
	 * @param RelationEntityType $relationEntityType
	 * @return string
	 */
	private function prepareFindRelationByNodeIdQuery(
		string $select,
		int $nodeId,
		RelationEntityType $relationEntityType = null,
	): string
	{
		$relationEntityType = $relationEntityType?->value;
		$nodeTableName = Model\NodeTable::getTableName();
		$nodePathTableName = Model\NodePathTable::getTableName();
		$nodeRelationTableName = Model\NodeRelationTable::getTableName();

		$helper = Application::getConnection()->getSqlHelper();

		$relationEntityTypeCondition = '';
		if ($relationEntityType !== null)
		{
			$relationEntityTypeValue = $helper->forSql($relationEntityType);
			$relationEntityTypeCondition = "nr.ENTITY_TYPE = '$relationEntityTypeValue' AND ";
		}

		return <<<SQL
SELECT $select
  from $nodeTableName n
		   INNER JOIN $nodePathTableName np ON np.CHILD_ID = n.ID
	  	   INNER JOIN $nodeTableName n2 ON (n2.ID = np.PARENT_ID AND n2.TYPE = n.TYPE)
		   INNER JOIN $nodeRelationTableName nr ON (
	  nr.WITH_CHILD_NODES = 'Y' AND (np.PARENT_ID = nr.NODE_ID OR nr.NODE_ID = n.ID)
		  OR
	  nr.WITH_CHILD_NODES = 'N' AND nr.NODE_ID = n.ID
	  )
  WHERE
	  $relationEntityTypeCondition
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

		if ($limit && $offset)
		{
			if ($connection->getType() === 'mysql')
			{
				$query .= " LIMIT $offset, $limit";
			}
			else
			{
				$query .= " LIMIT $limit OFFSET $offset";
			}
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

	/**
	 * @inheritDoc
	 */
	public function findRelationsByRelationType(
		RelationEntityType $relationEntityType,
		int $limit = 100,
		int $offset = 0
	): Item\Collection\NodeRelationCollection
	{
		$query =
			NodeRelationTable::query()
			 ->setSelect(['*'])
			->where('ENTITY_TYPE', $relationEntityType->value)
		;

		if ($limit)
		{
			$query->setLimit($limit);
		}

		if ($offset)
		{
			$query->setOffset($offset);
		}

		$relations = $query->fetchAll();
		$nodeRelations = new Item\Collection\NodeRelationCollection();
		foreach ($relations as $nodeRelationEntity)
		{
			$nodeRelations->add($this->convertModelToItemFromArray($nodeRelationEntity));
		}

		return $nodeRelations;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteRelationByEntityTypeAndEntityIds(RelationEntityType $entityType, array $entityIds): void
	{
		if (array_filter($entityIds, 'is_int') !== $entityIds)
		{
			throw new InvalidArgumentException("All entity IDs must be integers.");
		}

		try
		{
			Model\NodeRelationTable::deleteList([
				'=ENTITY_TYPE' => $entityType->value,
				'@ENTITY_ID' => $entityIds,
			]);
		}
		catch (\Exception)
		{}
	}

	public function getByEntityIdsAndNodeIdAndType(
		int $nodeId,
		array $entityIds,
		RelationEntityType $entityType
	): Item\Collection\NodeRelationCollection
	{
		$relations =
			NodeRelationTable::query()
				->setSelect(['*'])
				->where('NODE_ID', $nodeId)
				->where('ENTITY_TYPE', $entityType->value)
				->whereIn('ENTITY_ID', $entityIds)
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
