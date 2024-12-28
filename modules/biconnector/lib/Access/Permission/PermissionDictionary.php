<?php

namespace Bitrix\BIConnector\Access\Permission;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
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
	public const BIC_DASHBOARD_EDIT_SCOPE = 6;
	public const BIC_EXTERNAL_DASHBOARD_CONFIG = 7;

	public const BIC_DASHBOARD = 100;
	public const BIC_DASHBOARD_VIEW = 101;
	public const BIC_DASHBOARD_EDIT = 102;
	public const BIC_DASHBOARD_DELETE = 103;
	public const BIC_DASHBOARD_EXPORT = 104;
	public const BIC_DASHBOARD_COPY = 105;

	public static function getPermission($permissionId): array
	{
		$permission = parent::getPermission($permissionId);
		if ($permissionId === self::BIC_ACCESS)
		{
			$permission['title'] = Loc::getMessage('BIC_ACCESS_MSGVER_1');
			$permission['hint'] = Loc::getMessage('BIC_ACCESS_HINT');
		}
		$dashboardPermissions = [
			self::BIC_DASHBOARD_VIEW,
			self::BIC_DASHBOARD_EDIT,
			self::BIC_DASHBOARD_DELETE,
			self::BIC_DASHBOARD_EXPORT,
			self::BIC_DASHBOARD_COPY,
		];

		$permissionId = (int)$permissionId;
		if (in_array((int)$permissionId, $dashboardPermissions, true))
		{
			$permission['type'] = self::TYPE_MULTIVARIABLES;
			$permission['enableSearch'] = true;
			$permission['showAvatars'] = true;
			$permission['compactView'] = true;
		}

		if (
			$permissionId === self::BIC_DASHBOARD_VIEW
			|| $permissionId === self::BIC_DASHBOARD_COPY
		)
		{
			$permission['variables'] = self::getDashboardVariables();
		}

		if (
			$permissionId === self::BIC_DASHBOARD_DELETE
			|| $permissionId === self::BIC_DASHBOARD_EDIT
			|| $permissionId === self::BIC_DASHBOARD_EXPORT
		)
		{
			$permission['variables'] = self::getDashboardVariables(
				typeFilter: [SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM]
			);
		}

		return $permission;
	}

	private static function getDashboardVariables(array $typeFilter = []): array
	{
		$result = [];
		$selectParams = [
			'select' => ['ID', 'TITLE', 'TYPE'],
			'cache' => ['ttl' => 3600],
		];
		if (!empty($typeFilter))
		{
			$selectParams = [
				'filter' => ['=TYPE' => $typeFilter],
			];
		}
		$iterator = SupersetDashboardTable::getList($selectParams);
		while ($row = $iterator->fetch())
		{
			$result[] = [
				'id' => (int)$row['ID'],
				'title' => htmlspecialcharsbx($row['TITLE']),
				'entityId' => 'dashboard',
				'avatar' => self::getDashboardIcon($row['TYPE']),
				'avatarOptions' => [
					'borderRadius' => '4px',
				],
			];
		}

		return $result;
	}

	private static function getDashboardIcon(string $type): string
	{
		return match ($type)
		{
			SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM => '/bitrix/images/biconnector/superset-dashboard-selector/icon-type-system.png',
			SupersetDashboardTable::DASHBOARD_TYPE_MARKET => '/bitrix/images/biconnector/superset-dashboard-selector/icon-type-market.png',
			SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM => '',
			default => '/bitrix/images/biconnector/superset-dashboard-selector/icon-type-system.png',
		};
	}

	public static function getDefaultPermissionValue($permissionId): int
	{
		$permission = static::getPermission($permissionId);
		if ($permission['type'] === static::TYPE_MULTIVARIABLES)
		{
			return static::VALUE_VARIATION_ALL;
		}

		return static::VALUE_YES;
	}
}
