<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Access\AuthProvider\StructureAuthProvider;
use Bitrix\HumanResources\Command\Structure\Node\NodeOrderCommand;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Service\EventSenderService;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\EventName;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\NodePathTable;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\AccessCodeHelper;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\HumanResources\Type\AccessCodeType;

class NodeRepository implements Contract\Repository\NodeRepository
{
	protected readonly Contract\Util\CacheManager $cacheManager;
	protected readonly EventSenderService $eventSenderService;
	private readonly StructureAuthProvider $structureAuthProvider;
	/** @var list<NodeEntityType> */
	protected array $selectableNodeEntityTypes = [NodeEntityType::DEPARTMENT];

	protected const DEFAULT_TTL = 3600;

	public function __construct(
		?StructureAuthProvider $structureAuthProvider = null,
	)
	{
		$this->cacheManager = Container::getCacheManager();
		$this->cacheManager->setTtl(86400*7);
		$this->eventSenderService = Container::getEventSenderService();
		$this->structureAuthProvider = $structureAuthProvider ?? Container::getStructureAuthProvider();
	}

	public function mapItemToModel(Model\Node $nodeEntity, Item\Node $node): Model\Node
	{
		return $nodeEntity
			->setStructureId($node->structureId)
			->setType($node->type->name)
			->setName($node->name)
			->setCreatedBy($node->createdBy)
			->setXmlId($node->xmlId)
			->setParentId($node->parentId)
			->setActive($node->active)
			->setGlobalActive($node->globalActive)
			->setSort($node->sort)
			->setDescription($node->description)
			->setColorName($node->colorName)
		;
	}

	protected function convertModelToItem(Model\Node $node): Item\Node
	{
		$nodeId = $node->getId();
		$accessCode = $node->getAccessCode()?->current();
		$depth = $node->getChildNodes()?->current();
		return new Item\Node(
			name: $node->getName(),
			type: NodeEntityType::tryFrom($node->getType()),
			structureId: $node->getStructureId(),
			accessCode: $accessCode ? $accessCode->getAccessCode() : AccessCodeHelper::makeCodeByTypeAndId($nodeId),
			id: $nodeId,
			parentId: $node->getParentId(),
			depth: $depth ? $depth->getDepth() : null,
			createdBy: $node->getCreatedBy(),
			createdAt: $node->getCreatedAt(),
			updatedAt: $node->getUpdatedAt(),
			xmlId: $node->getXmlId(),
			active: $node->getActive(),
			globalActive: $node->getGlobalActive(),
			sort: $node->getSort(),
			description: $node->getDescription(),
			colorName: $node->getColorName(),
		);
	}

	protected function convertModelArrayToItem(array $node): Item\Node
	{
		$accessCode =
			$node['HUMANRESOURCES_MODEL_NODE_ACCESS_CODE_ACCESS_CODE']
			?? AccessCodeHelper::makeCodeByTypeAndId((int)($node['ID'] ?? 0))
		;

		return new Item\Node(
			name: $node['NAME'] ?? null,
			type: NodeEntityType::tryFrom($node['TYPE'] ?? '') ?? null,
			structureId: $node['STRUCTURE_ID'] ?? null,
			accessCode: $accessCode,
			id: $node['ID'] ?? null,
			parentId: $node['PARENT_ID'] ?? null,
			depth: $node['HUMANRESOURCES_MODEL_NODE_CHILD_NODES_DEPTH'] ?? null,
			createdBy: $node['CREATED_BY'] ?? null,
			createdAt: $node['CREATED_AT'] ?? null,
			updatedAt: $node['UPDATED_AT'] ?? null,
			xmlId: $node['XML_ID'] ?? null,
			active: ($node['ACTIVE'] ?? '') === 'Y',
			globalActive: ($node['GLOBAL_ACTIVE'] ?? '') === 'Y',
			sort: $node['SORT'] ?? 0,
			description: $node['DESCRIPTION'] ?? null,
			colorName: $node['COLOR_NAME'] ?? null,
		);
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 *
	 * @return \Bitrix\HumanResources\Item\Node
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function create(Item\Node $node): Item\Node
	{
		if (is_null($node->structureId))
		{
			throw (new CreationFailedException())->setErrors(
				new ErrorCollection([new Error('No structure for node')])
			);
		}
		$nodeEntity = NodeTable::getEntity()->createObject();
		$currentUserId = CurrentUser::get()->getId();
		$node->createdBy = (int)$currentUserId;

		$this->prepareSort($node);

		$result = $this->mapItemToModel($nodeEntity, $node)
			->save();

		if (!$result->isSuccess())
		{
			throw (new CreationFailedException())
				->setErrors($result->getErrorCollection());
		}

		$node->id = $result->getId();
		NodePathTable::appendNode($node->id, $node->parentId);

		$this->eventSenderService->send(
			EventName::OnNodeAdded,
			[
				'node' => $node,
			]
		);
		$this->structureAuthProvider->recalculateCodesForNode($node);

		return $node;
	}

	/**
	 * @param Node $node
	 *
	 * @return Node
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws UpdateFailedException
	 */
	public function update(Item\Node $node): Item\Node
	{
		if (!$node->id)
		{
			return $node;
		}

		$nodeCache = $this->getById($node->id);

		if (!$nodeCache)
		{
			throw (new UpdateFailedException())->addError(new Error("Node with id $node->id dont exist"));
		}

		$updatedField = [];
		if ($node->name && $node->name !== $nodeCache->name)
		{
			$nodeCache->name = $node->name;
			$updatedField['name'] = $node->name;
		}

		if ($node->type && $node->type !== $nodeCache->type)
		{
			$nodeCache->type = $node->type;
			$updatedField['type'] = $node->type;
		}

		$parentChanged = false;
		if ($node->parentId !== $nodeCache->parentId)
		{
			$nodeCache->parentId = $node->parentId;
			$updatedField['parentId'] = $node->parentId;
			$parentChanged = true;
		}

		if ($node->xmlId && $node->xmlId !== $nodeCache->xmlId)
		{
			$nodeCache->xmlId = $node->xmlId;
			$updatedField['xmlId'] = $node->xmlId;
		}

		if (
			(!is_null($node->active) && $node->active !== $nodeCache->active)
			|| $parentChanged
		)
		{
			$nodeCache->active = $node->active;

			$updateGlobalActiveStatus = true;
			$globalActive = true;
			if (
				$node->active === true
				|| $parentChanged
			)
			{
				foreach ($this->getParentOf($node) as $parent)
				{
					if (
						$parent->id !== $nodeCache->id
						&& $parent->active === false
					)
					{
						$globalActive = false;
						$updateGlobalActiveStatus = false;

						break;
					}
				}
			}

			if ($node->active === false)
			{
				$globalActive = false;
			}

			if (
				$parentChanged
				|| $updateGlobalActiveStatus
			)
			{
				$nodeCache->globalActive = $node->globalActive = $globalActive;
				$this->setGlobalActiveToNodeAndChildren($nodeCache, $globalActive);
			}
			$updatedField['active'] = $node->active;
		}

		if (!is_null($node->globalActive) && $node->globalActive !== $nodeCache->globalActive)
		{
			$nodeCache->globalActive = $node->globalActive;
		}

		if ($node->sort !== null && $node->sort !== $nodeCache->sort)
		{
			$nodeCache->sort = $node->sort;
			$updatedField['sort'] = $node->sort;
		}

		if ($node->description !== null && $node->description !== $nodeCache->description)
		{
			$nodeCache->description = $node->description === '' ? null : $node->description;
			$updatedField['description'] = $node->description;
		}

		if ($node->colorName !== null && $node->colorName !== $nodeCache->colorName)
		{
			$nodeCache->colorName = $node->colorName === '' ? null : $node->colorName;
			$updatedField['colorName'] = $node->colorName;
		}

		if (!empty($updatedField))
		{
			$nodeEntity = NodeTable::getById($nodeCache->id)->fetchObject();

			$result = $this->mapItemToModel($nodeEntity, $nodeCache)
				->save()
			;

			if (!$result->isSuccess())
			{
				throw (new UpdateFailedException())
					->setErrors($result->getErrorCollection())
				;
			}

			$this->removeNodeCache($nodeCache->id);
			$this->eventSenderService->send(EventName::OnNodeUpdated, [
				'node' => $nodeCache,
				'fields' => $updatedField,
			]);
			$this->structureAuthProvider->recalculateCodesForNode($node);
		}

		return $node;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findAllByUserId(int $userId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection
	{
		$nodeItems = new Item\Collection\NodeCollection();
		$query = $this->getNodeQueryWithPreparedTypeFilter()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->registerRuntimeField(
				'nm',
				new Reference(
					'nm',
					Model\NodeMemberTable::class,
					Join::on('this.ID', 'ref.NODE_ID'),
				),
			)
			->where('nm.ENTITY_ID', $userId)
			->where('nm.ENTITY_TYPE', MemberEntityType::USER->name)
			->cacheJoins(true)
			->setCacheTtl(86400)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$nodes = $query->fetchCollection();
		foreach ($nodes as $nodeEntity)
		{
			$node = $this->convertModelToItem($nodeEntity);
			$nodeItems->add($node);
		}

		return $nodeItems;
	}

	/**
	 * @param int $nodeId
	 * @param bool $needDepth
	 *
	 * @return \Bitrix\HumanResources\Item\Node|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getById(int $nodeId, bool $needDepth = false): ?Item\Node
	{
		if ($needDepth)
		{
			return $this->getByIdWithDepth($nodeId);
		}

		$nodeCacheKey = sprintf(self::NODE_ENTITY_CACHE_KEY, $nodeId);

		$nodeCache = $this->cacheManager->getData($nodeCacheKey);
		if ($nodeCache)
		{
			$nodeCache['type'] = NodeEntityType::tryFrom($nodeCache['type']);
			$nodeCache['createdAt'] = null;
			$nodeCache['updatedAt'] = null;

			return new Item\Node(...$nodeCache);
		}

		$query = NodeTable::query()
			->setSelect(['*', 'ACCESS_CODE',])
			->where('ID', $nodeId)
			->setLimit(1)
		;

		$node = $query->fetchObject();
		$convertedNode = $node !== null ? $this->convertModelToItem($node) : null;
		if ($convertedNode)
		{
			$this->cacheManager->setData($nodeCacheKey, $convertedNode);

			return $convertedNode;
		}

		return null;
	}

	/**
	 *
	 * returns node data with depth level
	 *
	 * @param int $nodeId
	 *
	 * @return Node|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByIdWithDepth(int $nodeId): ?Item\Node
	{
		$query = NodeTable::query()
				->setSelect(['*', 'ACCESS_CODE', 'CHILD_NODES'])
				->where('ID', $nodeId)
				->where('PARENT_NODES.CHILD_ID', $nodeId)
				->addOrder('CHILD_NODES.DEPTH', 'DESC')
				->setLimit(1)
				->setCacheTtl(86400)
				->cacheJoins(true)
		;

		$node = $query->fetchObject();

		return $node !== null ? $this->convertModelToItem($node) : null;
	}

	private function removeNodeCache(int $nodeId): void
	{
		$nodeCacheKey = sprintf(self::NODE_ENTITY_CACHE_KEY, $nodeId);
		$this->cacheManager->clean($nodeCacheKey);
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getAllChildIdsByNodeId(int $nodeId): array
	{
		$nodesList = NodePathTable::query()
			->setSelect(['CHILD_ID'])
			->where('PARENT_ID', $nodeId)
			->fetchAll()
		;

		$nodes = [];
		foreach ($nodesList as $node)
		{
			$nodes[] = $node['CHILD_ID'];
		}

		return $nodes;
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getParentOf(
		Item\Node $node,
		DepthLevel|int $depthLevel = DepthLevel::FIRST
	): Item\Collection\NodeCollection
	{
		$nodeCollection = new Item\Collection\NodeCollection();
		if (!$node->id)
		{
			return $nodeCollection;
		}

		$nodeQuery = $this->getNodeQueryWithPreparedTypeFilter()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->where('PARENT_NODES.CHILD_ID', $node->id)
			->addOrder('CHILD_NODES.DEPTH', 'DESC')
			->setCacheTtl(self::DEFAULT_TTL)
			->cacheJoins(true)
			;

		if ($depthLevel === DepthLevel::FIRST)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 1);
		}

		if (is_int($depthLevel))
		{
			if ($node->depth === null)
			{
				$node = $this->getById($node->id, true);
			}

			$nodeQuery->where('CHILD_NODES.DEPTH', '>=', $node->depth - $depthLevel);
		}

		$nodeModelCollection = $nodeQuery->fetchAll();
		foreach ($nodeModelCollection as $node)
		{
			$nodeCollection->add($this->convertModelArrayToItem($node));
		}

		return $nodeCollection;
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getChildOf(
		Item\Node $node,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		$nodeCollection = new Item\Collection\NodeCollection();
		if (!$node->id)
		{
			return $nodeCollection;
		}

		$nodeQuery = $this->getNodeQueryWithPreparedTypeFilter()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->where('CHILD_NODES.PARENT_ID', $node->id)
			->setOrder([
				'CHILD_NODES.DEPTH' => 'ASC',
				'SORT' => 'ASC',
			])
			->setCacheTtl(self::DEFAULT_TTL)
			->cacheJoins(true)
		;
		if ($depthLevel === DepthLevel::FIRST)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 1);
		}

		if (is_int($depthLevel))
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', '<=', $depthLevel);
		}

		$nodeQuery = $this->setNodeActiveFilter($nodeQuery, $activeFilter);

		$nodeModelArray = $nodeQuery->fetchAll();

		return !$nodeModelArray
			? $nodeCollection
			: $this->convertModelArrayToItemByArray($nodeModelArray)
			;
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findAllByUserIdAndRoleId(int $userId, int $roleId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection
	{
		$nodeItems = new Item\Collection\NodeCollection();
		$query =
			$this->getNodeQueryWithPreparedTypeFilter()
				->setSelect(['*'])
				->addSelect('ACCESS_CODE')
				->addSelect('CHILD_NODES')
				->registerRuntimeField(
					'nm',
					new Reference(
						'nm',
						Model\NodeMemberTable::class,
						Join::on('this.ID', 'ref.NODE_ID'),
					),
				)
				->where('nm.ENTITY_ID', $userId)
				->where('nm.ENTITY_TYPE', MemberEntityType::USER->name)
				->where('nm.ROLE.ID', $roleId)
				->setCacheTtl(86400)
				->cacheJoins(true)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$result = $query->exec();

		while ($nodeEntity = $result->fetch())
		{
			$node = $this->convertModelArrayToItem($nodeEntity);
			$nodeItems->add($node);
		}

		return $nodeItems;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByAccessCode(string $accessCode): ?Item\Node
	{
		static $nodes = [];

		if (isset($nodes[$accessCode]))
		{
			return $nodes[$accessCode];
		}

		$nodeByAccessCode = $this->extractIdByAccessCodeAndFind($accessCode);
		if ($nodeByAccessCode)
		{
			$nodes[$accessCode] = $nodeByAccessCode;

			return $nodeByAccessCode;
		}

		$accessCode = str_replace('DR', 'D', $accessCode);

		$node = NodeTable::query()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->where('ACCESS_CODE.ACCESS_CODE', $accessCode)
			->setLimit(1)
			->setCacheTtl(86400)
			->cacheJoins(true)
			->exec()
			->fetch();

		$nodes[$accessCode] = !$node ? null : $this->convertModelArrayToItem($node);

		return $nodes[$accessCode];
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getRootNodeByStructureId(int $structureId): ?Item\Node
	{
		$node = NodeTable::query()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->where('STRUCTURE_ID', $structureId)
			->where('PARENT_ID', 0)
			->setCacheTtl(86400)
			->fetchObject();

		return $node !== null ? $this->convertModelToItem($node) : null;
	}

	/**
	 * @param int $structureId
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\NodeCollection
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getAllByStructureId(int $structureId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection
	{
		$nodeItems = new Item\Collection\NodeCollection();
		$query =
			$this->getNodeQueryWithPreparedTypeFilter()
				->setSelect(['*'])
				->addSelect('ACCESS_CODE')
				->where('STRUCTURE_ID', $structureId)
				->addOrder('SORT')
				->cacheJoins(true)
				->setCacheTtl(self::DEFAULT_TTL)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$result = $query->exec();
		while ($nodeEntity = $result->fetch())
		{
			$node = $this->convertModelArrayToItem($nodeEntity);
			$nodeItems->add($node);
		}

		return $nodeItems;
	}

	/**
	 * @throws ArgumentException
	 * @throws WrongStructureItemException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAllPagedByStructureId(int $structureId, int $limit = 10, int $offset = 0, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection
	{
		$nodeItems = new Item\Collection\NodeCollection();
		$query =
			$this->getNodeQueryWithPreparedTypeFilter()
				->setSelect(['*'])
				->addSelect('ACCESS_CODE')
				->setLimit($limit)
				->setOffset($offset)
				->setOrder(['ID' => 'ASC'])
				->where('STRUCTURE_ID', $structureId)
		;
		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$nodeEntities = $query->fetchAll();

		foreach ($nodeEntities as $nodeEntity)
		{
			$nodeItems->add($this->convertModelArrayToItem($nodeEntity));
		}

		return $nodeItems;
	}

	public function hasChild(Item\Node $node): bool
	{
		$nodeQuery =
			$this->getNodeQueryWithPreparedTypeFilter()
				->where('CHILD_NODES.PARENT_ID', $node->id)
				->where('CHILD_NODES.DEPTH', 1)
				->setLimit(1)
				->exec()
		;

		return (bool)$nodeQuery->fetch();
	}

	public function isAncestor(Item\Node $node, Item\Node $targetNode): bool
	{
		if (
			!is_null($node->depth)
			&& !is_null($targetNode->depth)
			&& $node->depth >= $targetNode->depth
		)
		{
			return false;
		}

		$nodePathQuery = NodePathTable::query()
			->where('PARENT_ID', $node->id)
			->where('CHILD_ID', $targetNode->id)
			->setLimit(1)
			->exec()
		;

		return (bool)$nodePathQuery->fetch();
	}

	/**
	 * Delete a node and all associated data from the database.
	 *
	 * @param int $nodeId
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public function deleteById(int $nodeId): void
	{
		$node = $this->getById($nodeId);
		$this->structureAuthProvider->recalculateCodesForNode($node);
		$result = NodeTable::delete($nodeId);
		if (!$result->isSuccess())
		{
			throw (new DeleteFailedException())
				->setErrors($result->getErrorCollection())
			;
		}

		$this->eventSenderService->send(EventName::OnNodeDeleted, [
			'node' => $node,
		]);

		$this->removeNodeCache($nodeId);
	}

	/**
	 * @param list<string> $departments
	 *
	 * @return \Bitrix\HumanResources\Item\Collection\NodeCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws WrongStructureItemException
	 */
	public function findAllByAccessCodes(array $departments): Item\Collection\NodeCollection
	{
		if (empty($departments))
		{
			return new Item\Collection\NodeCollection();
		}

		$iBlockAccessCodes = [];
		$nodeIds = [];
		foreach ($departments as $accessCode)
		{
			$id = AccessCodeHelper::extractIdFromCode($accessCode);
			if ($id)
			{
				$nodeIds[] = $id;

				continue;
			}

			$iBlockAccessCodes[] = $accessCode;
		}

		$findByAccessCodeCollection = new Item\Collection\NodeCollection();
		$findByIdsCollection = new Item\Collection\NodeCollection();
		if (!empty($iBlockAccessCodes))
		{
			$nodeModelArray =
				$this->getNodeQueryWithPreparedTypeFilter()
					->setSelect(['*'])
					->addSelect('ACCESS_CODE')
					->addSelect('CHILD_NODES')
					->whereIn('ACCESS_CODE.ACCESS_CODE', $iBlockAccessCodes)
					->cacheJoins(true)
					->setCacheTtl(86400)
					->fetchAll()
			;

			if ($nodeModelArray)
			{
				$findByAccessCodeCollection = $this->convertModelArrayToItemByArray($nodeModelArray);
			}
		}

		if (!empty($nodeIds))
		{
			$findByIdsCollection = $this->findAllByIds($nodeIds);
		}

		if ($findByAccessCodeCollection->empty())
		{
			return $findByIdsCollection;
		}

		if ($findByIdsCollection->empty())
		{
			return $findByAccessCodeCollection;
		}

		return new Item\Collection\NodeCollection(
			...$findByAccessCodeCollection->getValues(),
			...$findByIdsCollection->getValues(),
		);
	}

	public function getNodesByName(
		int $structureId,
		?string $name,
		?int $limit = 100,
		?int $parentId = null,
		DepthLevel|int $depth = DepthLevel::FULL,
		bool $strict = false,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		$nodeCollection = new Item\Collection\NodeCollection();
		$nodeQuery = $this->getNodeQueryWithPreparedTypeFilter()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->where('STRUCTURE_ID', $structureId)
			->setCacheTtl(self::DEFAULT_TTL)
			->cacheJoins(true)
		;
		$nodeQuery = $this->setNodeActiveFilter($nodeQuery, $activeFilter);

		if (!empty($name))
		{
			if (!$strict)
			{
				$nodeQuery->whereLike('NAME', '%' . $name . '%');
			}
			else
			{
				$nodeQuery->where('NAME', $name);
			}
		}

		if ($limit)
		{
			$nodeQuery->setLimit($limit);
		}

		if (is_null($parentId) && $depth === DepthLevel::FULL)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 0);
			$nodeModelArray = $nodeQuery->fetchAll();

			return !$nodeModelArray
				? $nodeCollection
				: $this->convertModelArrayToItemByArray($nodeModelArray)
			;
		}

		if (is_null($parentId))
		{
			try
			{
				$rootNode = self::getRootNodeByStructureId($structureId);
			}
			catch (ObjectPropertyException|ArgumentException|SystemException $e)
			{
				return $nodeCollection;
			}

			if (!$rootNode)
			{
				return $nodeCollection;
			}
			$parentId = $rootNode->id;
		}
		$nodeQuery->where('CHILD_NODES.PARENT_ID', $parentId);

		if ($depth === DepthLevel::FIRST)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 1);
		}

		if ($depth === DepthLevel::FULL)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', '>', 0);
		}

		if (is_int($depth))
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', '<', $depth + 1);
		}

		$nodeModelArray = $nodeQuery->fetchAll();

		return !$nodeModelArray
			? new Item\Collection\NodeCollection()
			: $this->convertModelArrayToItemByArray($nodeModelArray)
		;
	}

	/**
	 * @param list<NodeEntityType> $selectableNodeEntityTypes
	 */
	public function setSelectableNodeEntityTypes(array $selectableNodeEntityTypes): static
	{
		$this->selectableNodeEntityTypes = $selectableNodeEntityTypes;

		return $this;
	}

	protected function convertModelArrayToItemByCollection(Model\NodeCollection $models): Item\Collection\NodeCollection
	{
		return new Item\Collection\NodeCollection(
			...array_map([$this, 'convertModelToItem'],
			$models->getAll()
		));
	}

	protected function convertModelArrayToItemByArray(array $nodeModelArray)
	{
		return new Item\Collection\NodeCollection(
			...array_map([$this, 'convertModelArrayToItem'],
			$nodeModelArray
		));
	}

	/**
	 * Get child nodes of a node collection.
	 *
	 * @param Item\Collection\NodeCollection $nodeCollection The parent node collection.
	 * @param DepthLevel $depthLevel [optional] The depth level of child nodes. Default is DepthLevel::FIRST.
	 *
	 * @return Item\Collection\NodeCollection The child node collection.
	 *
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getChildOfNodeCollection(
		Item\Collection\NodeCollection $nodeCollection,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		$resultNodeCollection = new Item\Collection\NodeCollection();
		if ($nodeCollection->empty())
		{
			return $resultNodeCollection;
		}

		$parentIds = array_column($nodeCollection->getItemMap(), 'id');
		$nodeQuery = $this->getNodeQueryWithPreparedTypeFilter()
			  ->setSelect(['*'])
			  ->addSelect('ACCESS_CODE')
			  ->addSelect('CHILD_NODES')
			  ->whereIn('CHILD_NODES.PARENT_ID', $parentIds)
			  ->setCacheTtl(self::DEFAULT_TTL)
			  ->cacheJoins(true)
		;
		if ($depthLevel === DepthLevel::FIRST)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 1);
		}

		if (is_int($depthLevel))
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', '<=', $depthLevel);
		}

		$nodeQuery = $this->setNodeActiveFilter($nodeQuery, $activeFilter);

		$nodeModelArray = $nodeQuery->fetchAll();

		return !$nodeModelArray
			? $resultNodeCollection
			: $this->convertModelArrayToItemByArray($nodeModelArray)
		;
	}

	public function findAllByXmlId(string $xmlId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection
	{
		$query = NodeTable::query()
			->setSelect(['*', 'ACCESS_CODE', 'CHILD_NODES'])
			->where('XML_ID', $xmlId)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$nodeModelArray = $query->fetchAll();

		return !$nodeModelArray
			? new Item\Collection\NodeCollection()
			: $this->convertModelArrayToItemByArray($nodeModelArray)
		;
	}

	protected function setNodeActiveFilter(Query $query, NodeActiveFilter $activeFilter): Query
	{
		return match ($activeFilter)
		{
			NodeActiveFilter::ONLY_ACTIVE => $query->where('ACTIVE', true),
			NodeActiveFilter::ONLY_GLOBAL_ACTIVE => $query->where('GLOBAL_ACTIVE', true),
			default => $query,
		};
	}

	/**
	 * @throws UpdateFailedException
	 * @throws ArgumentException
	 * @throws WrongStructureItemException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function setGlobalActiveToNodeAndChildren(Node $parentNode, bool $active): void
	{
		$childCollection = $this->getChildOf(
			$parentNode,
			DepthLevel::FULL,
			NodeActiveFilter::ALL,
		);
		$nodeIdsToChangeGlobalActive = [];
		$inactiveParentIds = [];
		foreach ($childCollection as $child)
		{
			if ($active === false)
			{
				$nodeIdsToChangeGlobalActive[] = $child->id;

				continue;
			}

			if ($child->id === $parentNode->id)
			{
				$child->active = $active;
			}

			if (
				$child->active === true
				&& !in_array($child->parentId, $inactiveParentIds, true)
			)
			{
				$nodeIdsToChangeGlobalActive[] = $child->id;
			}

			if (
				$child->active === false
				|| in_array($child->parentId, $inactiveParentIds, true)
			)
			{
				$inactiveParentIds[] = $child->id;
			}
		}

		if (empty($nodeIdsToChangeGlobalActive))
		{
			return;
		}

		try
		{
			NodeTable::updateMulti(
				$nodeIdsToChangeGlobalActive,
				[
					'GLOBAL_ACTIVE' => $active === true ? 'Y' : 'N',
				],
			);
		}
		catch (\Exception)
		{
			throw (new UpdateFailedException())->addError(new Main\Error('Failed to update global active status for child nodes'));
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function findAllByIds(
		array $departmentIds,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if (empty($departmentIds))
		{
			return new Item\Collection\NodeCollection();
		}

		$query = $this->getNodeQueryWithPreparedTypeFilter()
			->setSelect([
				'ID',
				'TYPE',
				'PARENT_ID',
				'STRUCTURE_ID',
				'ACTIVE',
				'GLOBAL_ACTIVE',
				'NAME'
			])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->whereIn('ID', $departmentIds)
			->setCacheTtl(self::DEFAULT_TTL)
			->cacheJoins(true)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$nodeModelArray = $query->fetchAll();

		return !$nodeModelArray
			? new Item\Collection\NodeCollection()
			: $this->convertModelArrayToItemByArray($nodeModelArray);
	}

	/**
	 * @param Node $node
	 *
	 * @return void
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function prepareSort(Node $node): void
	{
		$lastSibling = $this->getNodeQueryWithPreparedTypeFilter()
			->where('PARENT_ID', $node->parentId)
			->setSelect(['SORT'])
			->addOrder('SORT', 'DESC')
			->setLimit(1)
			->fetchObject()
		;

		if ($lastSibling)
		{
			$node->sort = $lastSibling->getSort() + NodeOrderCommand::ORDER_STEP;
		}
	}

	protected function getNodeQueryWithPreparedTypeFilter(): Query
	{
		$query = NodeTable::query();
		if (!empty($this->selectableNodeEntityTypes))
		{
			$query->whereIn(
				'TYPE',
				array_map(static fn(NodeEntityType $type) => $type->value, $this->selectableNodeEntityTypes),
			);
		}

		return $query;
	}

	private function extractIdByAccessCodeAndFind(string $accessCode): ?Node
	{
		foreach (AccessCodeType::getTeamTypes() as $type)
		{
			if (!str_starts_with($accessCode, $type->value))
			{
				continue;
			}

			$id = AccessCodeHelper::extractIdFromCode($accessCode, $type);
			if (!$id)
			{
				continue;
			}

			$node = $this->getById($id);
			if (!$node?->isTeam())
			{
				return null;
			}

			return $node;
		}

		foreach (AccessCodeType::getDepartmentTypes() as $type)
		{
			if (!str_starts_with($accessCode, $type->value))
			{
				continue;
			}

			$id = (int)AccessCodeHelper::extractIdFromCode($accessCode, $type);
			if (!$id)
			{
				continue;
			}

			$node = $this->getById($id);
			if (!$node?->isDepartment())
			{
				return null;
			}

			return $node;
		}

		$id = AccessCodeHelper::extractIdFromCode($accessCode);
		if ($id)
		{
			return $this->getById($id);
		}

		return null;
	}
}
