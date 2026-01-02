<?php

namespace Bitrix\BIConnector\Access\Permission;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Access\Permission;
use Bitrix\Main\Localization\Loc;

final class PermissionDictionary extends Permission\PermissionDictionary
{
	public const VALUE_VARIATION_ALL = -1;

	public const BIC_ACCESS = 1;
	public const BIC_DASHBOARD_CREATE = 2;
	public const BIC_SETTINGS_ACCESS = 3;
	public const BIC_SETTINGS_EDIT_RIGHTS = 4;
	public const BIC_DASHBOARD_TAG_MODIFY = 5;
	public const BIC_GROUP_MODIFY = 6;
	public const BIC_EXTERNAL_DASHBOARD_CONFIG = 7;
	public const BIC_DELETE_ALL_UNUSED_ELEMENTS = 8;

	public const BIC_DASHBOARD = 100;
	public const BIC_DASHBOARD_VIEW = 101;
	public const BIC_DASHBOARD_EDIT = 102;
	public const BIC_DASHBOARD_DELETE = 103;
	public const BIC_DASHBOARD_EXPORT = 104;
	public const BIC_DASHBOARD_COPY = 105;

	private static ?array $groupPermissions = null;

	public static function getPermission($permissionId): array
	{
		$dashboardPermissions = self::getDashboardGroupPermissions();
		$permission =
			!empty($dashboardPermissions[$permissionId])
				? $dashboardPermissions[$permissionId]
				: parent::getPermission($permissionId)
		;

		if ($permissionId === self::BIC_ACCESS)
		{
			$permission['title'] = Loc::getMessage('BIC_ACCESS_MSGVER_1');
			$permission['hint'] = Loc::getMessage('BIC_ACCESS_HINT_MSGVER_1');
		}

		if ($permissionId === self::BIC_DASHBOARD_EDIT)
		{
			$permission['hint'] = Loc::getMessage('HINT_BIC_DASHBOARD_EDIT_MSGVER_2');
		}

		if ($permissionId === self::BIC_DASHBOARD_EXPORT)
		{
			$permission['hint'] = Loc::getMessage('HINT_BIC_DASHBOARD_EXPORT_MSGVER_2');
		}

		if ($permissionId === self::BIC_DELETE_ALL_UNUSED_ELEMENTS)
		{
			$permission['title'] = Loc::getMessage('BIC_DELETE_ALL_UNUSED_ELEMENTS_MSGVER_1');
		}

		if ($permission['type'] === Permission\PermissionDictionary::TYPE_TOGGLER)
		{
			$permission['minValue'] = '0';
			$permission['maxValue'] = '1';
		}
		elseif ($permission['type'] === Permission\PermissionDictionary::TYPE_DEPENDENT_VARIABLES)
		{
			$permission['minValue'] = ['0'];
			$permission['maxValue'] = self::getPermissionCodes();

			$separator = '|';
			$allSelectedKey = implode($separator, self::getPermissionCodes());
			$permission['selectedVariablesAliases'] = [
				'separator' => $separator,
				$allSelectedKey => Loc::getMessage('BIC_DASHBOARD_ACCESS_ALL'),
			];
			$permission['dependentVariablesPopupHint'] = Loc::getMessage('BIC_GROUP_VARIABLES_HINT');
		}
		if (in_array($permissionId, self::getOldVariablesPermissions(), true))
		{
			$permission['type'] = Permission\PermissionDictionary::TYPE_MULTIVARIABLES;
		}

		return $permission;
	}

	public static function getDefaultPermissionValue($permissionId): int
	{
		$permission = self::getPermission($permissionId);
		if ($permission['type'] === self::TYPE_MULTIVARIABLES) // TODO change to dependent variables
		{
			return self::VALUE_VARIATION_ALL;
		}

		return self::VALUE_YES;
	}

	private static function getOldVariablesPermissions(): array
	{
		return [
			self::BIC_DASHBOARD_VIEW,
			self::BIC_DASHBOARD_EDIT,
			self::BIC_DASHBOARD_COPY,
			self::BIC_DASHBOARD_DELETE,
			self::BIC_DASHBOARD_EXPORT,
		];
	}

	public static function getDashboardGroupPermissionId(int $groupId): string
	{
		return "G{$groupId}";
	}

	public static function getDashboardGroupIdFromPermission(string $permission): int
	{
		return (int)str_replace('G', '', $permission);
	}

	public static function isDashboardGroupPermission(string $permission): bool
	{
		return str_contains($permission, 'G');
	}

	public static function clearDashboardGroupPermissions(): void
	{
		self::$groupPermissions = null;
	}

	public static function getDashboardGroupPermissions(): array
	{
		if (self::$groupPermissions !== null)
		{
			return self::$groupPermissions;
		}

		self::$groupPermissions = [];
		$groups = SupersetDashboardGroupTable::getList([
			'select' => ['ID', 'NAME', 'TYPE', 'DASHBOARDS', 'SCOPE'],
			'cache' => ['ttl' => 3600],
		]);

		while ($group = $groups->fetchObject())
		{
			$id = self::getDashboardGroupPermissionId($group['ID']);
			self::$groupPermissions[$id] = [
				'id' => $id,
				'title' => $group->getName(),
				'type' => self::TYPE_DEPENDENT_VARIABLES,
				'variables' => self::getPermissionVariables(),
				'emptyValue' => 0,
				'groupHead' => false,
				'subtitle' => Loc::getMessagePlural('BIC_DASHBOARD_GROUP_SUBTITLE', count($group->getDashboards()), ['#COUNT#' => count($group->getDashboards())]),
				'iconClass' => self::getGroupIconClass($group->getType()),
				'isClickable' => true,
				'isDeletable' => $group->getType() === SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM,
			];
		}

		return self::$groupPermissions;
	}

	public static function getPermissionCodes(): array
	{
		$permissionCodes = [
			self::BIC_DASHBOARD_VIEW,
			self::BIC_DASHBOARD_EDIT,
			self::BIC_DASHBOARD_COPY,
			self::BIC_DASHBOARD_DELETE,
		];
		if (MarketDashboardManager::getInstance()->isExportEnabled())
		{
			$permissionCodes[] = self::BIC_DASHBOARD_EXPORT;
		}

		return $permissionCodes;
	}

	private static function getPermissionVariables(): array
	{
		$permissionCodes = self::getPermissionCodes();

		$variables = [
			[
				'id' => '0',
				'title' => Loc::getMessage('BIC_DASHBOARD_NO_ACCESS'),
				'useAsNothingSelectedInSubsection' => true,
				'useAsEmpty' => true,
				'conflictsWith' => $permissionCodes,
			],
		];


		foreach ($permissionCodes as $permissionCode)
		{
			$permission = self::getPermission($permissionCode);
			$variable = [
				'id' => $permissionCode,
				'title' => $permission['title'],
				'hint' => $permission['hint'],
			];
			if ($permissionCode === self::BIC_DASHBOARD_EDIT || $permissionCode === self::BIC_DASHBOARD_COPY)
			{
				$variable['requires'] = [
					self::BIC_DASHBOARD_VIEW,
				];
			}
			if ($permissionCode === self::BIC_DASHBOARD_DELETE)
			{
				$variable['requires'] = [
					self::BIC_DASHBOARD_VIEW,
					self::BIC_DASHBOARD_EDIT,
				];
			}
			if (MarketDashboardManager::getInstance()->isExportEnabled())
			{
				if ($permissionCode === self::BIC_DASHBOARD_EXPORT)
				{
					$variable['requires'] = [
						self::BIC_DASHBOARD_VIEW,
						self::BIC_DASHBOARD_EDIT,
						self::BIC_DASHBOARD_DELETE,
					];
				}
			}
			$variables[] = $variable;
		}

		return $variables;
	}

	private static function getGroupIconClass(string $groupType): string
	{
		return match ($groupType)
		{
			SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM => 'ui-icon ui-icon-file-air-folder-24',
			SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM => 'ui-icon ui-icon-file-air-folder-person',
		};
	}

	public static function getNewGroupPermissions(): array
	{
		$separator = '|';
		$allSelectedKey = implode($separator, self::getPermissionCodes());

		return [
			'id' => 'new_G0',
			'title' => Loc::getMessage('BIC_DASHBOARD_GROUP_TITLE'),
			'type' => self::TYPE_DEPENDENT_VARIABLES,
			'variables' => self::getPermissionVariables(),
			'subtitle' => Loc::getMessagePlural('BIC_DASHBOARD_GROUP_SUBTITLE', 0, ['#COUNT#' => 0]),
			'minValue' => ['0'],
			'maxValue' => self::getPermissionCodes(),
			'selectedVariablesAliases' => [
				'separator' => $separator,
				$allSelectedKey => Loc::getMessage('BIC_DASHBOARD_ACCESS_ALL'),
			],
			'emptyValue' => 0,
			'groupHead' => false,
			'iconClass' => self::getGroupIconClass(SupersetDashboardGroupTable::GROUP_TYPE_CUSTOM),
			'isClickable' => true,
			'isDeletable' => true,
			'isNew' => true,
			'isModified' => true,
			'dependentVariablesPopupHint' => Loc::getMessage('BIC_GROUP_VARIABLES_HINT'),
		];
	}
}
