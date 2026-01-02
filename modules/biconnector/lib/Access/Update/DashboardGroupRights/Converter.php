<?php

namespace Bitrix\BIConnector\Access\Update\DashboardGroupRights;

use Bitrix\BIConnector\Access\Permission\Permission;
use Bitrix\BIConnector\Access\Permission\PermissionCollection;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardCollection;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupBindingTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupCollection;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroup;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupScopeTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Manager;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class Converter
{
	private int $newGroupCounter = 0;

	public function __construct(
		private readonly PermissionCollection  $permissions,
		private readonly SupersetDashboardGroupCollection $dashboardGroups,
		private readonly SupersetDashboardCollection $dashboards,
		private readonly bool $isExportEnabled = true,
	)
	{
	}

	public static function updateToGroup(bool $createBackupTables = false): Result
	{
		if (SupersetDashboardGroupBindingTable::getCount() > 0)
		{
			Option::set('biconnector', Feature::CHECK_PERMISSION_BY_GROUP_OPTION, 'Y');

			return new Result();
		}

		$permissions = PermissionTable::getList()
			->fetchCollection()
		;

		$groups = SupersetDashboardGroupTable::getList([
			'select' => ['*', 'SCOPE'],
		])
			->fetchCollection()
		;

		self::prepareSystemDashboardScopes();

		$dashboards = SupersetDashboardTable::getList([
			'select' => ['*', 'SCOPE'],
		])
			->fetchCollection()
		;

		$converter = new Converter(
			$permissions,
			$groups,
			$dashboards,
			MarketDashboardManager::getInstance()->isExportEnabled(),
		);

		$convertedItems = $converter->convert();

		Option::set('biconnector', Feature::CHECK_PERMISSION_BY_GROUP_OPTION, 'Y');

		return $converter->transactConversions($convertedItems, $createBackupTables);
	}

	/**
	 * @return ConversionItem[]
	 */
	public function convert(): array
	{
		$activeRoles = $this->prepareActiveRoles();
		$groupingRoles = $this->groupRolesByDashboards($activeRoles);
		$mapDashboardAction = $this->mapDashboardActions($groupingRoles);
		$scopeGroups = $this->prepareScopeSystemGroups();
		$dashboardGroups = $this->groupDashboardsByScope($scopeGroups);

		return $this->buildConversionResult(
			$this->sortActionGroups($mapDashboardAction, $dashboardGroups)
		);
	}

	private static function prepareSystemDashboardScopes(): void
	{
		$emptySystemScopeDashboards = SupersetDashboardTable::getList([
			'select' => ['APP_ID', 'SCOPE', 'ID'],
			'filter' => [
				'=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM,
				'=SCOPE.ID' => null,
			],
		])
			->fetchCollection()
		;

		foreach ($emptySystemScopeDashboards as $dashboard)
		{
			$addictScope = match ($dashboard->getAppId())
			{
				'bitrix.bic_taskdeadline',
				'bitrix.bic_flow',
				'bitrix.bic_taskeff',
				'bitrix.bic_taskload',
				'bitrix.bic_actual_time',
				'bitrix.bic_emp_season',
				'bitrix.bic_emp_season_west' => ScopeService::BIC_SCOPE_TASKS,

				'bitrix.bic_throughput_flow',
				'bitrix.bic_flow_param' => ScopeService::BIC_SCOPE_TASKS_FLOWS_FLOW,

				'bitrix.bic_taskeff_param',
				'bitrix.bic_sum_eff',
				'bitrix.bic_perkpi' => ScopeService::BIC_SCOPE_PROFILE,

				'bitrix.bic_bizproceff',
				'bitrix.bic_bizproc_param' => ScopeService::BIC_SCOPE_BIZPROC,

				default => ScopeService::BIC_SCOPE_CRM,
			};

			ScopeService::getInstance()->saveDashboardScopes($dashboard->getId(), [$addictScope]);
		}
	}

	/**
	 * @param ConversionItem[] $convertedItems
	 *
	 * @return Result
	 */
	private function transactConversions(array $convertedItems, bool $createBackupTables): Result
	{
		$db = Application::getConnection();
		$result = new Result();
		try
		{
			$db->startTransaction();

			$helper = $db->getSqlHelper();

			if ($createBackupTables)
			{
				$tableNames = [
					PermissionTable::getTableName(),
					SupersetDashboardGroupTable::getTableName(),
					SupersetDashboardGroupScopeTable::getTableName(),
					SupersetDashboardGroupBindingTable::getTableName(),
				];

				foreach ($tableNames as $name)
				{
					$name = $helper->forSql($name);
					$newTableName = "{$name}_tmp";
					if ($db->isTableExists($name) && !$db->isTableExists($newTableName))
					{
						if ($db->getType() === 'mysql')
						{
							$db->query('CREATE TABLE ' . $newTableName . ' LIKE ' . $name);
							$db->query('INSERT INTO ' . $newTableName . ' SELECT * FROM ' . $name);
						}
					}
				}
			}

			$result = $this->commitConvertedItems($convertedItems);
			if ($result->isSuccess())
			{
				$db->commitTransaction();
			}
			else
			{
				$db->rollbackTransaction();
			}

		}
		catch (\Throwable $e)
		{
			$db->rollbackTransaction();

			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * @param ConversionItem[] $convertedItems
	 *
	 * @return Result
	 */
	private function commitConvertedItems(array $convertedItems): Result
	{
		$result = new Result();

		foreach ($this->permissions as $permission)
		{
			if (!$this->isDashboardPermission((int)$permission->getPermissionId()))
			{
				continue;
			}

			$result = $permission->delete();
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		foreach ($convertedItems as $convertedItem)
		{
			$result = $convertedItem->save();
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $result;
	}

	private function prepareActiveRoles(): array
	{
		$activeRoles = [];

		foreach ($this->permissions as $permission)
		{
			if (!$this->isDashboardPermission((int)$permission->getPermissionId()))
			{
				continue;
			}

			$roleKey = $this->getRoleKey($permission);
			$activeRoles[$roleKey] ??= [];
			$activeRoles[$roleKey] = $this->processDashboardIdsValue($permission, $activeRoles[$roleKey]);
		}

		return $activeRoles;
	}

	private function isDashboardPermission(int $permissionId): bool
	{
		return in_array($permissionId, $this->getDashboardActions(), true);
	}

	private function getDashboardActions(): array
	{
		$result = [
			PermissionDictionary::BIC_DASHBOARD_VIEW,
			PermissionDictionary::BIC_DASHBOARD_EDIT,
			PermissionDictionary::BIC_DASHBOARD_COPY,
			PermissionDictionary::BIC_DASHBOARD_DELETE,
		];

		if ($this->isExportEnabled)
		{
			$result[] = PermissionDictionary::BIC_DASHBOARD_EXPORT;
		}

		return $result;
	}

	private function getRoleKey(Permission $permission): string
	{
		return "{$permission->getRoleId()}_{$permission->getPermissionId()}";
	}

	private function processDashboardIdsValue(Permission $permission, array $roleArray): array
	{
		if ($permission->getValue() === PermissionDictionary::VALUE_VARIATION_ALL)
		{
			$roleArray = $this->dashboards->getIdList();
		}
		elseif ($permission->getValue() > 0)
		{
			$roleArray[] = $permission->getValue();
		}

		return $roleArray;
	}

	private function groupRolesByDashboards(array $activeRoles): array
	{
		$groupingRoles = [];

		foreach ($activeRoles as $activeRole => $dashboardIds)
		{
			$dashboardIds = array_unique($dashboardIds);
			sort($dashboardIds);
			$key = implode('-', $dashboardIds);

			$groupingRoles[$key][] = $activeRole;
		}

		return $groupingRoles;
	}

	private function mapDashboardActions(array $groupingRoles): array
	{
		$mapDashboardAction = [];

		foreach ($groupingRoles as $implodedDashboards => $roleActions)
		{
			$roleActionsKey = implode('|', $roleActions);
			$dashboardIds = explode('-', $implodedDashboards);

			$mapDashboardAction[$roleActionsKey] = array_unique(
				array_merge(
					$mapDashboardAction[$roleActionsKey] ?? [],
					array_map('intval', $dashboardIds)
				)
			);
		}

		return $mapDashboardAction;
	}

	private function prepareScopeSystemGroups(): array
	{
		$scopeGroups = [];

		$additionalScopeCodes = \Bitrix\BIConnector\Access\Service\DashboardGroupService::getAdditionalScopeMap();

		foreach ($this->dashboardGroups as $dashboardGroup)
		{
			if (!$dashboardGroup->isSystem())
			{
				continue;
			}

			$scopeCodeList = $dashboardGroup->getScope()?->getScopeCodeList() ?? [];
			if ($scopeCodeList)
			{
				$code = $dashboardGroup->getCode();
				if (!empty($additionalScopeCodes[$code]))
				{
					$scopeCodeList = array_merge($scopeCodeList, $additionalScopeCodes[$code]);
				}

				$scopeGroups[$dashboardGroup->getId()] = $scopeCodeList;
			}
		}

		return $scopeGroups;
	}

	private function groupDashboardsByScope(array $scopeGroups): array
	{
		$dashboardGroups = [];

		foreach ($this->dashboards as $dashboard)
		{
			$scopes = $dashboard->getScope()?->getScopeCodeList() ?? [];

			foreach ($scopeGroups as $groupId => $scopeGroup)
			{
				if (array_intersect($scopes, $scopeGroup))
				{
					$dashboardGroups[$groupId][] = $dashboard->getId();
				}
			}
		}

		return $dashboardGroups;
	}

	private function sortActionGroups(array $mapDashboardAction, array $dashboardGroups): array
	{
		$sortedActionGroups = [];

		foreach ($mapDashboardAction as $action => $actionDashboardIds)
		{
			$intersects = [];
			foreach ($dashboardGroups as $groupId => $groupDashboardIds)
			{
				$intersect = array_intersect($actionDashboardIds, $groupDashboardIds);

				if ($intersect)
				{
					$sortedActionGroups[] = [
						'groupId' => $groupId,
						'dashboardIds' => $intersect,
						'actions' => $action,
					];

					unset($dashboardGroups[$groupId]);
					$intersects[] = $intersect;
				}
			}

			$intersects = array_merge(...$intersects);
			$actionDashboardIds = array_diff($actionDashboardIds, $intersects);
			if ($actionDashboardIds)
			{
				$sortedActionGroups[] = [
					'groupId' => null,
					'dashboardIds' => $actionDashboardIds,
					'actions' => $action,
				];
			}
		}

		return $sortedActionGroups;
	}

	private function buildConversionResult(array $sortedActionGroups): array
	{
		$result = [];

		foreach ($sortedActionGroups as $groupData)
		{
			$group = $this->resolveGroup((int)$groupData['groupId']);
			$this->addDashboardsToGroup($group, $groupData['dashboardIds']);

			$rolesView = [];
			$roleActions = [];
			foreach (explode('|', $groupData['actions']) as $roleAction)
			{
				[$roleId, $actionId] = explode('_', $roleAction);
				if (!empty($roleId) && !empty($actionId))
				{
					$roleActions[] = [
						'roleId' => $roleId,
						'actionId' => $actionId,
					];

					$rolesView[$roleId] ??= false;
					if ((int)$actionId === PermissionDictionary::BIC_DASHBOARD_VIEW)
					{
						$rolesView[$roleId] = true;
					}
				}
			}

			foreach ($rolesView as $roleId => $existsViewRole)
			{
				if (!$existsViewRole)
				{
					$roleActions[] = [
						'roleId' => (string)$roleId,
						'actionId' => (string)PermissionDictionary::BIC_DASHBOARD_VIEW,
					];
				}
			}

			$result[] = new ConversionItem(
				$group,
				$roleActions
			);
		}

		return $result;
	}

	private function resolveGroup(int $groupId): SupersetDashboardGroup
	{
		if ($groupId > 0)
		{
			$group = $this->dashboardGroups->getByPrimary($groupId);

			if ($group)
			{
				return $group;
			}
		}

		$this->newGroupCounter++;

		return (new SupersetDashboardGroup)
			->setName(
				Loc::getMessage(
					"BICONNECTOR_NEW_USER_CONVERTED_GROUP_NAME",
					['#GROUP_ID#' => $this->newGroupCounter],
					$this->getDefaultLanguage(),
				)
			)
			->setCode("user_converted_group_{$this->newGroupCounter}")
			->setOwnerId(Manager::getAdminId())
		;
	}

	private function addDashboardsToGroup(SupersetDashboardGroup $group, array $dashboardIds): void
	{
		foreach ($dashboardIds as $dashboardId)
		{
			if ($dashboard = $this->dashboards->getByPrimary($dashboardId))
			{
				$group->addToDashboards($dashboard);
			}
		}
	}

	private function getDefaultLanguage(): ?string
	{
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$defaultLanguage = \CBitrix24::getLicensePrefix();
		}
		else
		{
			$defaultLanguage = \Bitrix\Main\Localization\LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
			])
				->fetch()['ID'] ?? null
			;
		}

		return $defaultLanguage;
	}
}
