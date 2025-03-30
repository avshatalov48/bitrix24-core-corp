<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Model\NodeCollection;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Repository\NodeRepository;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;

final class PermissionRestrictedNodeRepository extends NodeRepository
{
	private StructureAccessController $accessController;

	/**
	 * @throws AccessDeniedException
	 */
	public function __construct()
	{
		$userId = CurrentUser::get()->getId();
		if (!$userId)
		{
			throw new \Bitrix\Main\AccessDeniedException();
		}

		parent::__construct();
		$this->accessController = StructureAccessController::getInstance($userId);
	}

	public function getAllChildIdsByNodeId(int $nodeId): array
	{
		if (
			!$this->accessController->check(
				StructureActionDictionary::ACTION_STRUCTURE_VIEW,
				NodeModel::createFromId($nodeId),
			)
		)
		{
			return [];
		}

		$permissionValue = $this->accessController->getUser()->getPermission(
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
		);

		return match ($permissionValue)
		{
			PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS => parent::getAllChildIdsByNodeId(
				$nodeId,
			),
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS => [$nodeId],
			default => [],
		};
	}

	public function getChildOf(
		Node $node,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if (
			!$this->accessController->check(
				StructureActionDictionary::ACTION_STRUCTURE_VIEW,
				NodeModel::createFromId($node->id),
			)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->accessController->getUser()->getPermission(
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
		);

		return match ($permissionValue)
		{
			PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS => parent::getChildOf(
				$node,
				$depthLevel,
				$activeFilter,
			),
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS => parent::getChildOf($node, 0, $activeFilter),
			default => new Item\Collection\NodeCollection(),
		};
	}

	public function findAllByUserIdAndRoleId(
		int $userId,
		int $roleId,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		$permissionValue = $this->accessController->getUser()->getPermission(
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
		);

		if (!$permissionValue)
		{
			return new Item\Collection\NodeCollection();
		}

		return $this->filterNodeCollection(parent::findAllByUserIdAndRoleId($userId, $roleId, $activeFilter));
	}

	public function getByAccessCode(string $accessCode): ?Item\Node
	{
		$node = parent::getByAccessCode($accessCode);
		if (
			!$node
			|| !$this->accessController->check(
				StructureActionDictionary::ACTION_STRUCTURE_VIEW,
				NodeModel::createFromId($node->id),
			)
		)
		{
			return null;
		}

		return $node;
	}

	public function getAllByStructureId(
		int $structureId,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		return $this->filterNodeCollection(parent::getAllByStructureId($structureId, $activeFilter));
	}

	public function getAllPagedByStructureId(
		int $structureId,
		int $limit = 10,
		int $offset = 0,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		return $this->filterNodeCollection(
			parent::getAllPagedByStructureId(
				$structureId,
				$limit,
				$offset,
				$activeFilter,
			),
		);
	}

	public function getNodesByName(
		int $structureId,
		?string $name,
		?int $limit = 100,
		?int $parentId = null,
		DepthLevel|int $depth = DepthLevel::FULL,
		bool $strict = false,
		$activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		return $this->filterNodeCollection(
			parent::getNodesByName(
				$structureId,
				$name,
				$limit,
				$parentId,
				$depth,
				$strict,
				$activeFilter
			),
		);
	}

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

		$permissionValue = $this->accessController->getUser()->getPermission(
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
		);

		$parentIds = array_column(
			$nodeCollection->filter(
				fn(Item\Node $node) => $this->accessController->check(
					StructureActionDictionary::ACTION_STRUCTURE_VIEW,
					NodeModel::createFromId($node->id),
				),
			)->getItemMap(),
			'id',
		);

		$nodeQuery = NodeTable::query()
			->setSelect(['*'])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->setCacheTtl(self::DEFAULT_TTL)
			->whereIn('CHILD_NODES.PARENT_ID', $parentIds)
			->cacheJoins(true)
		;

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 0);
		}
		elseif ($depthLevel === DepthLevel::FIRST)
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', 1);
		}
		elseif (is_int($depthLevel))
		{
			$nodeQuery->where('CHILD_NODES.DEPTH', '<=' ,$depthLevel);
		}

		$nodeQuery = $this->setNodeActiveFilter($nodeQuery, $activeFilter);

		$nodeModelArray = $nodeQuery->fetchAll();

		return !$nodeModelArray
			? $resultNodeCollection
			: $this->convertModelArrayToItemByArray($nodeModelArray);
	}

	public function findAllByXmlId(
		string $xmlId,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		return $this->filterNodeCollection(parent::findAllByXmlId($xmlId, $activeFilter));
	}

	private function filterNodeCollection(
		Item\Collection\NodeCollection $nodeCollection,
	): Item\Collection\NodeCollection
	{
		$cacheCollection = $this->cacheManager->getData(self::NODE_ENTITY_RESTRICTION_CACHE) ?? [];

		$changed = false;

		$result = $nodeCollection->filter(
			function(Item\Node $node) use (&$cacheCollection, &$changed) {
				$value = $cacheCollection[CurrentUser::get()->getId()][$node->id] ?? null;
				if ($value !== null)
				{
					return $value;
				}

				$value = $this->accessController->check(
					StructureActionDictionary::ACTION_STRUCTURE_VIEW,
					NodeModel::createFromId($node->id),
				);

				try
				{
					$cacheCollection[CurrentUser::get()->getId()][$node->id] = $value;
				}
				catch (ArgumentException $e)
				{
				}
				$changed = true;

				return $value;
			},
		);

		if ($changed)
		{
			$this->cacheManager->setData(self::NODE_ENTITY_RESTRICTION_CACHE, $cacheCollection);
		}

		return $result;
	}
}