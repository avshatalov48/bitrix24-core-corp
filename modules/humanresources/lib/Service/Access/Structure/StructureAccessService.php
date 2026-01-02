<?php

namespace Bitrix\HumanResources\Service\Access\Structure;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Model;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Type\IntegerCollection;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Access\Permission;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Util;
use Bitrix\Main\Access\Exception\UnknownActionException;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\SystemException;

class StructureAccessService
{
	private StructureAccessController $accessController;
	private StructureAction $action;
	private int $userId;
	private static array $cache = [];

	public function __construct(?int $userId = null)
	{
		if (!$userId)
		{
			$userId = (int)CurrentUser::get()->getId();
		}

		$this->userId = $userId;
		$this->action = StructureAction::ViewAction;
		$this->accessController = StructureAccessController::getInstance($userId);
	}

	//region Setters and getters
	public function setUserId(int $userId): StructureAccessService
	{
		$this->userId = $userId;
		$this->accessController = StructureAccessController::getInstance($userId);

		return $this;
	}

	public function getUserId(): int
	{
		return (int)$this->userId;
	}

	public function setAction(StructureAction $action): StructureAccessService
	{
		$this->action = $action;

		return $this;
	}
	//endregion

	//region Public methods
	/**
	 * Checks if user can do structure action with any Node
	 *
	 * @return bool
	 */
	public function canDoActionWithAnyNode(): bool
	{
		$this->checkHrAccessCodesUpdate();

		if ($this->accessController->getUser()->isAdmin())
		{
			return true;
		}

		$methodCacheKey = 'anyNode';
		$permissionId = $this->action->getAccessInfoByEntityType()->permissionId;
		if (isset(self::$cache[$methodCacheKey][$this->userId][$permissionId]))
		{
			return (bool)self::$cache[$methodCacheKey][$this->userId][$permissionId];
		}

		if ($this->isUserAdmin())
		{
			self::$cache[$methodCacheKey][$this->userId][$permissionId] = true;

			return self::$cache[$methodCacheKey][$this->userId][$permissionId];
		}

		$permissionValue = $this->accessController->getUser()->getPermission($permissionId);
		self::$cache[$methodCacheKey][$this->userId][$permissionId] = (int)$permissionValue > 0;

		return self::$cache[$methodCacheKey][$this->userId][$permissionId];
	}

	/**
	 * Checks if user can do structure action with the Node. To check create action use parentId as nodeId
	 *
	 * @param int $nodeId
	 *
	 * @return bool
	 * @throws UnknownActionException
	 */
	public function canDoActionWithTheNode(int $nodeId): bool
	{
		$item = $this->prepareAccessNodeItem($nodeId);
		$node = $item?->getNode();
		if (!$node)
		{
			return false;
		}

		$actionId = $this->action->getAccessInfoByEntityType($node->type)->actionId;

		return $item && $this->accessController->check($actionId, $item);
	}

	public function findFirstPossibleAvailableNode(NodeEntityType $nodeEntityType = NodeEntityType::DEPARTMENT): ?Item\Node
	{
		$permissionId = $this->action->getAccessInfoByEntityType()->permissionId;
		$permissionType = PermissionDictionary::getType($permissionId);
		if (
			$permissionType !== PermissionDictionaryAlias::TYPE_VARIABLES
			&& $permissionType !== PermissionDictionaryAlias::TYPE_DEPENDENT_VARIABLES
		)
		{
			return null;
		}

		$structure = Util\StructureHelper::getDefaultStructure();
		if (!$structure)
		{
			return null;
		}

		return
			(new NodeDataBuilder())
				->addFilter(
					new NodeFilter(
						entityTypeFilter: NodeTypeFilter::fromNodeType($nodeEntityType),
						active: true,
						accessFilter: new NodeAccessFilter($this->action, $this->getUserId()),
					),
				)
				->setLimit(1)
				->get()
		;
	}

	public function getPermissionValue(NodeEntityType $nodeEntityType = NodeEntityType::DEPARTMENT): PermissionCollection
	{
		$permissionId = (string)$this->action->getAccessInfoByEntityType($nodeEntityType)->permissionId;
		$permissionCollection = new PermissionCollection();

		if ($nodeEntityType === NodeEntityType::DEPARTMENT)
		{
			if ($this->isUserAdmin())
			{
				$value = PermissionVariablesDictionary::VARIABLE_ALL;
			}
			else
			{
				$value = (int)$this->accessController->getUser()->getPermission($permissionId);

				$viewPermission = PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW;
				if ($permissionId !== $viewPermission)
				{
					$value = min($value, (int)$this->accessController->getUser()->getPermission($viewPermission));
				}
			}

			return $permissionCollection->add(
				Permission::getWithoutRoleId($permissionId, $value)
			);
		}

		if ($nodeEntityType === NodeEntityType::TEAM)
		{
			return $this->getTeamPermissionCollectionById($permissionId);
		}

		return $permissionCollection;
	}

	public function getPermissionId(NodeEntityType $nodeEntityType = NodeEntityType::DEPARTMENT): string
	{
		return (string)$this->action->getAccessInfoByEntityType($nodeEntityType)->permissionId;
	}

	public function isUserAdmin(): bool
	{
		return $this->accessController->getUser()->isAdmin();
	}

	public function clearStaticCache(): void
	{
		self::$cache = [];
	}
	//endregion

	private function prepareAccessNodeItem(int $nodeId): ?Model\NodeModel
	{
		$permissionId = $this->action->getAccessInfoByEntityType()->permissionId;
		$permissionType = PermissionDictionary::getType($permissionId);
		if ($permissionType !== PermissionDictionaryAlias::TYPE_VARIABLES)
		{
			return null;
		}

		if ($this->action === StructureAction::CreateAction)
		{
			$item = Model\NodeModel::createFromId(null);
			$item->setTargetNodeId($nodeId);

			return $item;
		}

		return Model\NodeModel::createFromId($nodeId);
	}

	private function getTeamPermissionCollectionById(string $permissionId): PermissionCollection
	{
		$teamPermissionId = $permissionId . '_' . PermissionValueType::TeamValue->value;
		$departmentPermissionId = $permissionId . '_' . PermissionValueType::DepartmentValue->value;
		if ($this->isUserAdmin())
		{
			$teamValue = PermissionVariablesDictionary::VARIABLE_ALL;
			$departmentValue = PermissionVariablesDictionary::VARIABLE_ALL;
		}
		else
		{
			$teamValue = (int)$this->accessController->getUser()->getPermission($teamPermissionId);
			$departmentValue = (int)$this->accessController->getUser()->getPermission($departmentPermissionId);
		}

		$viewPermissionId = PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW;
		if (!$this->isUserAdmin())
		{
			$teamViewPermissionId = $viewPermissionId . '_' . PermissionValueType::TeamValue->value;
			$departmentViewPermissionId = $viewPermissionId . '_' . PermissionValueType::DepartmentValue->value;
			$teamValue = min($teamValue, (int)$this->accessController->getUser()->getPermission($teamViewPermissionId));
			$departmentValue = min(
				$departmentValue,
				(int)$this->accessController->getUser()->getPermission($departmentViewPermissionId),
			);
		}

		$permissionCollection = new PermissionCollection();
		$permissionCollection->add(
			Permission::getWithoutRoleId($teamPermissionId, $teamValue)
		);

		return $permissionCollection->add(
			Permission::getWithoutRoleId($departmentPermissionId, $departmentValue)
		);
	}

	private function checkHrAccessCodesUpdate(): void
	{
		if (\COption::GetOptionInt("humanresources", "re_hr_access_user_update") !== 1)
		{
			\CAgent::AddAgent(
				name: 'Bitrix\HumanResources\Install\Agent\AccessCodes\HrAccessCodeUpdate::run();',
				module: 'humanresources',
				interval: 20,
				next_exec: \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 600, 'FULL'),
				existError: false,
			);

			\COption::SetOptionInt("humanresources", "re_hr_access_user_update", 1);
		}
	}
}