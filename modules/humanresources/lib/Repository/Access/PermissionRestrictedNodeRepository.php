<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\HumanResources\Access\Model\NodeModel;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Model\NodeMemberTable;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Repository\NodeRepository;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;

final class PermissionRestrictedNodeRepository extends NodeRepository
{
	private StructureAccessController $accessController;
	private readonly int $structurePermissionId;
	private readonly string $structureActionId;
	private bool $canSeeTeams;
	private int $userId;

	public function __construct(StructureAction $structureAction = StructureAction::ViewAction)
	{
		parent::__construct();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->setUserId($this->userId);
		$this->setPermissionSettings($structureAction);
	}

	public function setUserId(int $userId): void
	{
		$this->accessController = StructureAccessController::getInstance($userId);
		$accessibleUser = $this->accessController->getUser();
		if (Feature::instance()->isCrossFunctionalTeamsAvailable())
		{
			$isAdmin = $this->accessController->getUser()->isAdmin();
			$this->canSeeTeams = $isAdmin || (int)$accessibleUser->getPermission(PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW) > 0;
		}
	}

	private function setPermissionSettings(StructureAction $structureAction): void
	{
		$accessibleUser = $this->accessController->getUser();
		$this->canSeeTeams = false;
		if (Feature::instance()->isCrossFunctionalTeamsAvailable())
		{
			$isAdmin = $this->accessController->getUser()->isAdmin();
			$this->canSeeTeams = $isAdmin || (int)$accessibleUser->getPermission(PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW) > 0;
		}

		$structureAccessInfo = $structureAction->getAccessInfoByEntityType();
		if ($structureAccessInfo->permissionId === PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW)
		{
			$this->structurePermissionId = PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW;
			$this->structureActionId = StructureActionDictionary::ACTION_STRUCTURE_VIEW;

			return;
		}

		$permissionValue = (int)$accessibleUser->getPermission($structureAccessInfo->permissionId);
		$viewPermissionValue = (int)$accessibleUser->getPermission(PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW);

		if (
			$viewPermissionValue <= $permissionValue
		)
		{
			$this->structurePermissionId = PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW;
			$this->structureActionId = StructureActionDictionary::ACTION_STRUCTURE_VIEW;

			return;
		}

		$this->structurePermissionId = $structureAccessInfo->permissionId;
		$this->structureActionId = $structureAccessInfo->actionId;
	}

	public function getById(int $nodeId, bool $needDepth = false): ?Item\Node
	{
		if ($this->userId <= 0)
		{
			return null;
		}

		$node = parent::getById($nodeId, $needDepth);

		return ($node && $this->checkAccessToNodeById($node->id)) ? $node : null;
	}

	public function getByIdWithDepth(int $nodeId): ?Item\Node
	{
		if ($this->userId <= 0)
		{
			return null;
		}

		$node = parent::getByIdWithDepth($nodeId);

		return ($node && $this->checkAccessToNodeById($node->id)) ? $node : null;
	}

	public function getAllChildIdsByNodeId(int $nodeId): array
	{
		if($this->userId <= 0 || !$this->checkAccessToNodeById($nodeId))
		{
			return [];
		}

		$permissionValue = $this->getPermissionValue();

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
		if($this->userId <= 0 || !$this->checkAccessToNodeById($node->id))
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();

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

	public function getParentOf(
		Node $node,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
	): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			parent::getParentOf($node, $depthLevel);
		}

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
		$nodeQuery = $this->setAdditionalFilterByPermission($nodeQuery, $permissionValue);

		$nodeModelCollection = $nodeQuery->fetchAll();
		foreach ($nodeModelCollection as $node)
		{
			$nodeCollection->add($this->convertModelArrayToItem($node));
		}

		return $nodeCollection;
	}

	public function findAllByUserIdAndRoleId(
		int $userId,
		int $roleId,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if (
			$permissionValue === PermissionVariablesDictionary::VARIABLE_ALL
			|| $this->accessController->getUser()->getUserId() === $userId
		)
		{
			return parent::findAllByUserIdAndRoleId($userId, $roleId, $activeFilter);
		}

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
						NodeMemberTable::class,
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
		$query = $this->setAdditionalFilterByPermission($query, $permissionValue);

		$result = $query->exec();

		while ($nodeEntity = $result->fetch())
		{
			$node = $this->convertModelArrayToItem($nodeEntity);
			$nodeItems->add($node);
		}

		return $nodeItems;
	}

	public function findAllByUserId(int $userId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if (
			$permissionValue === PermissionVariablesDictionary::VARIABLE_ALL
			|| $this->accessController->getUser()->getUserId() === $userId
		)
		{
			return parent::findAllByUserId($userId, $activeFilter);
		}

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
						 NodeMemberTable::class,
						 Join::on('this.ID', 'ref.NODE_ID'),
					 ),
				 )
				 ->where('nm.ENTITY_ID', $userId)
				 ->where('nm.ENTITY_TYPE', MemberEntityType::USER->name)
				 ->cacheJoins(true)
				 ->setCacheTtl(86400)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$query = $this->setAdditionalFilterByPermission($query, $permissionValue);

		$nodes = $query->fetchCollection();
		foreach ($nodes as $nodeEntity)
		{
			$node = $this->convertModelToItem($nodeEntity);
			$nodeItems->add($node);
		}

		return $nodeItems;
	}

	public function getByAccessCode(string $accessCode): ?Item\Node
	{
		if ($this->userId <= 0)
		{
			return null;
		}

		$node = parent::getByAccessCode($accessCode);

		return ($node && $this->checkAccessToNodeById($node->id)) ? $node : null;
	}

	public function getAllByStructureId(
		int $structureId,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL && $this->canSeeTeams)
		{
			return parent::getAllByStructureId($structureId, $activeFilter);
		}

		$nodeItems = new Item\Collection\NodeCollection();
		$query =
			$this->getNodeQueryWithPreparedTypeFilter()
				->setSelect(['*'])
				->addSelect('ACCESS_CODE')
				->addOrder('SORT')
				->where('STRUCTURE_ID', $structureId)
				->cacheJoins(true)
				->setCacheTtl(self::DEFAULT_TTL)
		;

		$query = $this->setAdditionalFilterByPermission($query, $permissionValue);
		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$result = $query->exec();
		while ($nodeEntity = $result->fetch())
		{
			$node = $this->convertModelArrayToItem($nodeEntity);
			if (!$this->canSeeTeams && $node->type === NodeEntityType::TEAM)
			{
				continue;
			}

			$nodeItems->add($node);
		}

		return $nodeItems;
	}

	public function getAllPagedByStructureId(
		int $structureId,
		int $limit = 10,
		int $offset = 0,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return parent::getAllPagedByStructureId($structureId, $limit, $offset, $activeFilter);
		}

		$nodeItems = new Item\Collection\NodeCollection();
		$query = $this->getNodeQueryWithPreparedTypeFilter()
				->setSelect(['*'])
				->addSelect('ACCESS_CODE')
				->setLimit($limit)
				->setOffset($offset)
				->addOrder('SORT')
				->where('STRUCTURE_ID', $structureId)
		;
		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$query = $this->setAdditionalFilterByPermission($query, $permissionValue);
		$nodeEntities = $query->fetchAll();

		foreach ($nodeEntities as $nodeEntity)
		{
			$nodeItems->add($this->convertModelArrayToItem($nodeEntity));
		}

		return $nodeItems;
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
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();

		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return parent::getNodesByName(
				$structureId,
				$name,
				$limit,
				$parentId,
				$depth,
				$strict,
				$activeFilter
			);
		}

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
		$nodeQuery = $this->setAdditionalFilterByPermission($nodeQuery, $permissionValue);

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
			if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
			{
				$nodeQuery->where('CHILD_NODES.DEPTH', 0);
			}
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

	public function getChildOfNodeCollection(
		Item\Collection\NodeCollection $nodeCollection,
		DepthLevel|int $depthLevel = DepthLevel::FIRST,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		$resultNodeCollection = new Item\Collection\NodeCollection();
		if ($this->userId <= 0)
		{
			return $resultNodeCollection;
		}


		if ($nodeCollection->empty())
		{
			return $resultNodeCollection;
		}

		$permissionValue = $this->getPermissionValue();

		$parentIds = array_column(
			$nodeCollection->filter(
				fn(Item\Node $node) => $this->accessController->check(
					$this->structureActionId,
					NodeModel::createFromId($node->id),
				),
			)->getItemMap(),
			'id',
		);

		$nodeQuery = $this->getNodeQueryWithPreparedTypeFilter()
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
			: $this->convertModelArrayToItemByArray($nodeModelArray)
		;
	}

	public function findAllByXmlId(
		string $xmlId,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0)
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			parent::findAllByXmlId($xmlId, $activeFilter);
		}

		$query = NodeTable::query()
			->setSelect(['*', 'ACCESS_CODE', 'CHILD_NODES'])
			->where('XML_ID', $xmlId)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$query = $this->setAdditionalFilterByPermission($query, $permissionValue);
		$nodeModelArray = $query->fetchAll();

		return !$nodeModelArray
			? new Item\Collection\NodeCollection()
			: $this->convertModelArrayToItemByArray($nodeModelArray)
		;
	}

	public function findAllByIds(
		array $departmentIds,
		NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE,
	): Item\Collection\NodeCollection
	{
		if ($this->userId <= 0 || empty($departmentIds))
		{
			return new Item\Collection\NodeCollection();
		}

		$permissionValue = $this->getPermissionValue();
		if (
			!$permissionValue
			|| !in_array($permissionValue, $this->getAvailablePermissionValues(), true)
		)
		{
			return new Item\Collection\NodeCollection();
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return parent::findAllByIds($departmentIds, $activeFilter);
		}

		$query = NodeTable::query()
			->setSelect([
				'ID',
				'TYPE',
				'STRUCTURE_ID',
				'ACTIVE',
				'GLOBAL_ACTIVE',
				'NAME',
			])
			->addSelect('ACCESS_CODE')
			->addSelect('CHILD_NODES')
			->whereIn('ID', $departmentIds)
			->setCacheTtl(self::DEFAULT_TTL)
			->cacheJoins(true)
		;

		$query = $this->setNodeActiveFilter($query, $activeFilter);
		$query = $this->setAdditionalFilterByPermission($query, $permissionValue);
		$nodeModelArray = $query->fetchAll();

		return !$nodeModelArray
			? new Item\Collection\NodeCollection()
			: $this->convertModelArrayToItemByArray($nodeModelArray)
		;
	}

	private function checkAccessToNodeById(int $nodeId): bool
	{
		$node = parent::getById($nodeId);
		if (!$node)
		{
			return false;
		}

		$actionId = $this->structureActionId;
		if ($node->type === NodeEntityType::TEAM)
		{
			$actionId = $this->getTeamActionIdByStructureId($this->structureActionId);
			if (!$this->canSeeTeams || !$actionId)
			{
				return false;
			}
		}

		return $this->accessController->check(
			$actionId,
			NodeModel::createFromId($nodeId)
		);
	}

	private function getTeamActionIdByStructureId(string $actionId): ?string
	{
		return match ($actionId) {
			StructureActionDictionary::ACTION_STRUCTURE_VIEW => StructureActionDictionary::ACTION_TEAM_VIEW,
			StructureActionDictionary::ACTION_DEPARTMENT_CREATE => StructureActionDictionary::ACTION_TEAM_CREATE,
			StructureActionDictionary::ACTION_DEPARTMENT_DELETE => StructureActionDictionary::ACTION_TEAM_DELETE,
			StructureActionDictionary::ACTION_DEPARTMENT_EDIT => StructureActionDictionary::ACTION_TEAM_EDIT,
			StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT => StructureActionDictionary::ACTION_TEAM_MEMBER_ADD,
			StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT => StructureActionDictionary::ACTION_TEAM_MEMBER_REMOVE,
			StructureActionDictionary::ACTION_DEPARTMENT_CHAT_EDIT => StructureActionDictionary::ACTION_TEAM_CHAT_EDIT,
			default => null,
		};
	}

	private function getPermissionValue(): int
	{
		if ($this->accessController->getUser()->isAdmin())
		{
			return PermissionVariablesDictionary::VARIABLE_ALL;
		}

		return (int)$this->accessController->getUser()->getPermission($this->structurePermissionId);
	}

	private function getAvailablePermissionValues(): array
	{
		return [
			PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
		];
	}

	private function getAllUserNodeIds(): array
	{
		$userId = $this->accessController->getUser()->getUserId();

		$nodeCollection = parent::findAllByUserId($userId);
		$nodeIds = [];
		foreach ($nodeCollection as $node)
		{
			$nodeIds[] = $node->id;
		}

		return $nodeIds;
	}

	private function setAdditionalFilterByPermission(Query $query, int $permissionValue): Query
	{
		$userNodeIds = $this->getAllUserNodeIds();
		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
		{
			$query->whereIn('CHILD_NODES.PARENT_ID', $userNodeIds);
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
		{
			$query->whereIn('ID', $userNodeIds);
		}

		return $query;
	}
}