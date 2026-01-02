<?php

namespace Bitrix\HumanResources\Controller;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionHelper;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Builder\Structure\Sort\NodeSort;
use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Enum\SortDirection;
use Bitrix\HumanResources\Service\Access\Structure\StructureAccessService;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Internals\Attribute;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Internals\Service\Container as InternalContainer;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Request;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Type\StructureAction;

final class Structure extends Controller
{
	private readonly NodeRepository $nodeRepository;
	private readonly StructureAccessService $accessService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->nodeRepository = Container::getNodeRepository(true);
		$this->accessService = new StructureAccessService();
	}

	#[Attribute\StructureActionAccess(permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS)]
	public function getAction(Item\Structure $structure): ?array
	{
		$result = [];
		$rootNode = $this->nodeRepository->getRootNodeByStructureId($structure->id);
		if (!$rootNode)
		{
			return $result;
		}

		$nodes =
			(new NodeDataBuilder())
				->addFilter(
					new NodeFilter(
						entityTypeFilter: NodeTypeFilter::fromNodeTypes([NodeEntityType::DEPARTMENT, NodeEntityType::TEAM]),
						structureId: $rootNode->structureId,
						depthLevel: DepthLevel::FULL,
						active: true,
						accessFilter: new NodeAccessFilter(StructureAction::ViewAction),
					),
				)
				->setSort(new NodeSort(sort: SortDirection::Asc))
				->getAll()
		;

		$result[] = StructureHelper::getNodeInfo($rootNode);
		foreach ($nodes as $node)
		{
			if ($node->id === $rootNode->id)
			{
				continue;
			}

			if ((int)$node->parentId !== 0 && $nodes->getItemById($node->parentId) === null)
			{
				$node->parentId = $rootNode->id;
			}

			$result[] = StructureHelper::getNodeInfo($node);
		}

		$internalNodeRepository = InternalContainer::getNodeRepository();
		$internalNodeMemberRepository = InternalContainer::getNodeMemberRepository();
		return [
			'structure' => $result,
			'map' => $internalNodeRepository->getStructuresNodeMap($structure->id),
			'multipleMembers' => $internalNodeMemberRepository->getMultipleNodeMembers(NodeEntityType::DEPARTMENT),
		];
	}

	#[Attribute\StructureActionAccess(permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS)]
	public function dictionaryAction(): array
	{
		$userId = (int)CurrentUser::get()->getId();
		if (!$userId)
		{
			return [];
		}

		return [
			'currentUserPermissions' => [
				StructureActionDictionary::ACTION_STRUCTURE_VIEW => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_DELETE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_CREATE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE,
					$userId
				),
				StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT,
					$userId
				),
				StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
					$userId
				),
				StructureActionDictionary::ACTION_USERS_ACCESS_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_USERS_ACCESS_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_USER_INVITE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_USER_INVITE,
					$userId
				),
				StructureActionDictionary::ACTION_FIRE_EMPLOYEE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_FIRE_EMPLOYEE,
					$userId
				) > 0 ? PermissionVariablesDictionary::VARIABLE_ALL : PermissionVariablesDictionary::VARIABLE_NONE,
				StructureActionDictionary::ACTION_TEAM_VIEW => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_CREATE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_CREATE,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_DELETE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_DELETE,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_MEMBER_ADD => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_ADD,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_MEMBER_REMOVE => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_SETTINGS_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_CHAT_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_CHANNEL_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_COLLAB_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_COLLAB_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_TEAM_ACCESS_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_TEAM_ACCESS_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_CHAT_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_CHANNEL_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_COLLAB_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT,
					$userId
				),
				StructureActionDictionary::ACTION_DEPARTMENT_SETTINGS_EDIT => $this->getVariablePermissionValue(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_SETTINGS_EDIT,
					$userId
				),
			],
			'permissionVariablesDictionary' => PermissionVariablesDictionary::getVariables(),
			'firstTimeOpened' => \CUserOptions::GetOption("humanresources", 'first_time_opened', 'N'),
			'teamsAvailable' => Feature::instance()->isCrossFunctionalTeamsAvailable(),
			'collabsAvailable' => Feature::instance()->isCollabsAvailable(),
			'deputyApprovesBP' => Feature::instance()->isDeputyApprovesBPAvailable(),
			'departmentSettingsAvailable' => Feature::instance()->isDepartmentSettingsAvailable(),
		];
	}

	private function getVariablePermissionValue(string $permissionId, int $userId): int|array
	{
		$permissionCollection = PermissionHelper::getPermissionValue($permissionId, $userId);
		if (PermissionDictionary::isTeamDependentVariablesPermission($permissionId))
		{
			$permissionValue = [];
			foreach ($permissionCollection as $permission)
			{
				$value = $permission->value;
				if (
					$value === PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS
					|| $value === PermissionVariablesDictionary::VARIABLE_SELF_TEAMS
				)
				{
					$value++;
				}

				$permissionValueType = TeamPermissionMapper::getTeamValueTypeByPermissionId($permission->permissionId);
				if ($permissionValueType === PermissionValueType::TeamValue)
				{
					$permissionId = NodeEntityType::TEAM->value;
				}

				if ($permissionValueType === PermissionValueType::DepartmentValue)
				{
					$permissionId = NodeEntityType::DEPARTMENT->value;
				}

				$permissionValue[$permissionId] = Feature::instance()->isCrossFunctionalTeamsAvailable()
					? $value
					: PermissionVariablesDictionary::VARIABLE_NONE
				;
			}

			return $permissionValue;
		}

		if ($permissionCollection->count() === 0)
		{
			return PermissionVariablesDictionary::VARIABLE_NONE;
		}

		if (
			$permissionCollection->getFirst()?->permissionId === PermissionDictionary::HUMAN_RESOURCES_TEAM_ACCESS_EDIT
			&& !Feature::instance()->isCrossFunctionalTeamsAvailable()
		)
		{
			return PermissionVariablesDictionary::VARIABLE_NONE;
		}

		return (int)$permissionCollection->getFirst()?->value;
	}
}