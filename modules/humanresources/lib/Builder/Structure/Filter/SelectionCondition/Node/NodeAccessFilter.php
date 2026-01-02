<?php

namespace Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\BaseSelectionConditionFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Exception\NodeAccessFilterException;
use Bitrix\HumanResources\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Service\Access\Structure\StructureAccessService;
use Bitrix\HumanResources\Type\IntegerCollection;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\SystemException;

final class NodeAccessFilter extends BaseSelectionConditionFilter
{
	private StructureAccessService $structureAccessService;
	private NodeEntityTypeCollection $nodeEntityTypes;

	public function __construct(
		readonly public StructureAction $action,
		public ?int $userId = null,
		public array $allowedLevels = [
			PermissionVariablesDictionary::VARIABLE_ALL,
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
			PermissionVariablesDictionary::VARIABLE_SELF_TEAMS,
			PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
			PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
		],
	)
	{
		$this->structureAccessService = new StructureAccessService();
		$this->structureAccessService->setAction($this->action);
		if (!is_null($this->userId))
		{
			$this->structureAccessService->setUserId($this->userId);
		}
		$this->nodeEntityTypes = new NodeEntityTypeCollection();
	}

	/**
	 * @return ConditionTree
	 * @throws ArgumentException
	 * @throws NodeAccessFilterException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function prepareFilter(): ConditionTree
	{
		$conditionTree = new ConditionTree();
		$conditionTree->logic(ConditionTree::LOGIC_OR);

		if ($this->nodeEntityTypes->count() === 0)
		{
			throw new ArgumentException('Node entity types must be set for access permission check');
		}

		foreach ($this->nodeEntityTypes->getItems() as $entityType)
		{
			if ($entityType === NodeEntityType::DEPARTMENT)
			{
				$subCondition = $this->getRestrictedDepartmentCondition();

				if (!is_null($subCondition))
				{
					$conditionTree->addCondition($subCondition);
				}
			}

			if ($entityType === NodeEntityType::TEAM)
			{
				$subCondition = $this->getRestrictedTeamCondition();

				if (!is_null($subCondition))
				{
					$conditionTree->addCondition($subCondition);
				}
			}
		}

		if (!$conditionTree->hasConditions())
		{
			throw new NodeAccessFilterException('User has no access to any required node type');
		}

		return $conditionTree;
	}

	public function setNodeEntityTypes(NodeEntityTypeCollection $nodeEntityTypeCollection): NodeAccessFilter
	{
		$this->nodeEntityTypes = $nodeEntityTypeCollection;

		return $this;
	}

	public function isUserAdmin(): bool
	{
		return $this->structureAccessService->isUserAdmin();
	}

	private function getAllUserNodeIds(int $userId, NodeEntityType $nodeEntityType = NodeEntityType::DEPARTMENT): array
	{
		$nodeRepository = new NodeRepository();
		if ($nodeEntityType === NodeEntityType::TEAM)
		{
			$nodeRepository->setSelectableNodeEntityTypes([$nodeEntityType]);
		}

		$nodeCollection = $nodeRepository->findAllByUserId($userId);
		$nodeRepository->setSelectableNodeEntityTypes([NodeEntityType::DEPARTMENT]);

		$nodeIds = [];
		foreach ($nodeCollection as $node)
		{
			$nodeIds[] = $node->id;
		}

		return $nodeIds;
	}

	private function getFirstTeamChildIds(array $nodeIds): array
	{
		$teamChildIds = [];
		$nodeCollection =
			(new NodeDataBuilder())
				->addFilter(
					new NodeFilter(
						idFilter: new IdFilter(new IntegerCollection(...$nodeIds)),
						entityTypeFilter: NodeTypeFilter::fromNodeType(NodeEntityType::TEAM),
						depthLevel: DepthLevel::FIRST,
						active: true,
					),
				)
				->getAll()
		;

		foreach ($nodeCollection as $node)
		{
			$teamChildIds[] = $node->id;
		}

		return $teamChildIds;
	}

	private function getRestrictedDepartmentCondition(): ?ConditionTree
	{
		$departmentCondition = new ConditionTree();
		$isEmployee = Container::instance()->getUserService()->isEmployee($this->structureAccessService->getUserId());
		$permissionValue = $this->structureAccessService->getPermissionValue()->getFirst()->value;

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			return $departmentCondition->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::DEPARTMENT->value);
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_NONE
			|| !$isEmployee
			|| !in_array($permissionValue,
				array_intersect([
					PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
					PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
				], $this->allowedLevels), true
			)
		)
		{
			return null;
		}

		$userNodeIds = $this->getAllUserNodeIds($this->structureAccessService->getUserId());
		if (empty($userNodeIds))
		{
			return null;
		}

		$departmentCondition
			->logic(ConditionTree::LOGIC_AND)
			->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::DEPARTMENT->value)
		;

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
		{
			$departmentCondition->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $userNodeIds);
		}

		if ($permissionValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
		{
			$departmentCondition->whereIn($this->getFieldByQueryContext('ID'), $userNodeIds);
		}

		return $departmentCondition;
	}

	private function getRestrictedTeamCondition(): ?ConditionTree
	{
		$teamCondition = new ConditionTree();
		$additionalCreateCondition = new ConditionTree();
		$permissionCollection = $this->structureAccessService->getPermissionValue(NodeEntityType::TEAM);
		$permissionId = $this->structureAccessService->getPermissionId(NodeEntityType::TEAM);
		$teamValue =
			(int)$permissionCollection
				->getFirstByPermissionId($permissionId . '_' . PermissionValueType::TeamValue->value)
				?->value
		;

		$departmentValue =
			(int)$permissionCollection
				->getFirstByPermissionId($permissionId . '_' . PermissionValueType::DepartmentValue->value)
				?->value
		;

		if ($teamValue === PermissionVariablesDictionary::VARIABLE_ALL)
		{
			$teamCondition->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::TEAM->value);

			if ($this->action === StructureAction::CreateAction)
			{
				$departmentPermissionValue =
					$this->structureAccessService
						->setAction(StructureAction::ViewAction)
						->getPermissionValue()
						->getFirst()
						->value
				;
				$this->structureAccessService->setAction($this->action);
				$departmentValue = min($departmentValue, $departmentPermissionValue);
			}

			if ($this->action !== StructureAction::CreateAction || $departmentValue === PermissionVariablesDictionary::VARIABLE_NONE)
			{
				return $teamCondition;
			}

			$teamCondition->logic(ConditionTree::LOGIC_OR);
			if ($departmentValue === PermissionVariablesDictionary::VARIABLE_ALL)
			{
				$teamCondition->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::DEPARTMENT->value);

				return $teamCondition;
			}

			$additionalCreateCondition->logic(ConditionTree::LOGIC_AND);
			$additionalCreateCondition->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::DEPARTMENT->value);
			$userNodeIds = $this->getAllUserNodeIds($this->structureAccessService->getUserId());
			if (empty($userNodeIds)
				|| !in_array($departmentValue,
					array_intersect([
						PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
						PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
					], $this->allowedLevels), true
				)
			)
			{
				return $teamCondition;
			}

			if ($departmentValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
			{
				$additionalCreateCondition->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $userNodeIds);
				$additionalCreateCondition->where($this->getFieldByQueryContext('CHILD_NODES.DEPTH'), '>=', 0);
			}

			if ($departmentValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
			{
				$additionalCreateCondition->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $userNodeIds);
				$additionalCreateCondition->where($this->getFieldByQueryContext('CHILD_NODES.DEPTH'), 0);
			}

			return $teamCondition->addCondition($additionalCreateCondition);
		}

		if ($teamValue === PermissionVariablesDictionary::VARIABLE_NONE && $departmentValue === PermissionVariablesDictionary::VARIABLE_NONE)
		{
			return null;
		}

		$teamCondition
			->logic(ConditionTree::LOGIC_AND)
			->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::TEAM->value)
		;

		$subTeamCondition = (new ConditionTree())->logic(ConditionTree::LOGIC_OR);

		if ($this->action === StructureAction::CreateAction)
		{
			$departmentPermissionValue =
				$this->structureAccessService
					->setAction(StructureAction::ViewAction)
					->getPermissionValue()
					->getFirst()
					->value
			;
			$this->structureAccessService->setAction($this->action);
			$departmentValue = min($departmentValue, $departmentPermissionValue);
		}

		if($departmentValue > PermissionVariablesDictionary::VARIABLE_NONE
			&& in_array($departmentValue,
				array_intersect([
					PermissionVariablesDictionary::VARIABLE_ALL,
					PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
					PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
				], $this->allowedLevels), true
			)
		)
		{
			$userNodeIds = $this->getAllUserNodeIds($this->structureAccessService->getUserId());
			if (!empty($userNodeIds))
			{
				if ($this->action === StructureAction::CreateAction)
				{
					$typeCondition = new ConditionTree();
					$typeCondition
						->logic(ConditionTree::LOGIC_OR)
						->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::DEPARTMENT->value)
						->where($this->getFieldByQueryContext('TYPE'), NodeEntityType::TEAM->value)
					;

					$additionalCreateCondition
						->logic(ConditionTree::LOGIC_AND)
						->addCondition($typeCondition)
						->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $userNodeIds)
					;

					if ($departmentValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
					{
						$additionalCreateCondition->where($this->getFieldByQueryContext('CHILD_NODES.DEPTH'), '>=', 0);
					}

					if ($departmentValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
					{
						$additionalCreateCondition->where($this->getFieldByQueryContext('CHILD_NODES.DEPTH'), 0);
					}
				}

				if ($departmentValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS)
				{
					$subTeamCondition->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $userNodeIds);
				}

				if ($departmentValue === PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
				{
					$firstTeamChildIds = $this->getFirstTeamChildIds($userNodeIds);
					if (!empty($firstTeamChildIds))
					{
						$subTeamCondition->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $firstTeamChildIds);
					}
				}
			}
		}

		if ($teamValue > PermissionVariablesDictionary::VARIABLE_NONE
			&& in_array($teamValue,
				array_intersect([
					PermissionVariablesDictionary::VARIABLE_ALL,
					PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
					PermissionVariablesDictionary::VARIABLE_SELF_TEAMS,
				], $this->allowedLevels), true
			)
		)
		{
			$userNodeIds = $this->getAllUserNodeIds($this->structureAccessService->getUserId(), NodeEntityType::TEAM);
			if (!empty($userNodeIds))
			{
				if ($teamValue === PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS)
				{
					$subTeamCondition->whereIn($this->getFieldByQueryContext('CHILD_NODES.PARENT_ID'), $userNodeIds);
				}

				if ($teamValue === PermissionVariablesDictionary::VARIABLE_SELF_TEAMS)
				{
					$subTeamCondition->whereIn($this->getFieldByQueryContext('ID'), $userNodeIds);
				}
			}
		}

		if (!$subTeamCondition->hasConditions() && !$additionalCreateCondition->hasConditions())
		{
			return null;
		}

		if ($subTeamCondition->hasConditions())
		{
			$teamCondition->addCondition($subTeamCondition);
			if (!$additionalCreateCondition->hasConditions())
			{
				return $teamCondition;
			}
		}
		else
		{
			$teamCondition = new ConditionTree();
		}

		return
			(new ConditionTree())
				->logic(ConditionTree::LOGIC_OR)
				->addCondition($additionalCreateCondition)
				->addCondition($teamCondition)
		;
	}
}