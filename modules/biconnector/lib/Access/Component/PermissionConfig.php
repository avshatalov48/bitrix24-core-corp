<?php

namespace Bitrix\BIConnector\Access\Component;

use Bitrix\BIconnector\Access\Component\PermissionConfig\RoleMembersInfo;
use Bitrix\BIconnector\Access\Permission\PermissionDictionary;
use Bitrix\BIconnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\BIconnector\Access\Role\RoleUtil;
use Bitrix\BIconnector\Access\Role\RoleDictionary;

final class PermissionConfig
{
	public const SECTION_BIC_ACCESS = 'SECTION_BIC_ACCESS';
	public const SECTION_MAIN_RIGHTS = 'SECTION_RIGHTS_MAIN';
	public const SECTION_DASHBOARD_RIGHTS = 'SECTION_RIGHTS_DASHBOARD';

	/**
	 * Access rights.
	 *
	 * @return array in format for `BX.UI.AccessRights.Section` js class.
	 */
	public function getAccessRights(): array
	{
		$result = [];

		$sections = $this->getSections();

		foreach ($sections as $sectionCode => $permissions)
		{
			$rights = [];
			foreach ($permissions as $permissionId)
			{
				$rights[] = PermissionDictionary::getPermission($permissionId);
			}

			$result[] = [
				'sectionCode' => $sectionCode,
				'sectionTitle' => Loc::getMessage("BICONNECTOR_CONFIG_PERMISSION_{$sectionCode}"),
				'sectionHint' => Loc::getMessage("HINT_BICONNECTOR_CONFIG_PERMISSION_{$sectionCode}"),
				'rights' => $rights
			];
		}

		return $result;
	}

	/**
	 * Get saved user roles.
	 *
	 * @return array in format for `BX.UI.AccessRights.Grid.userGroups` js property.
	 */
	public function getUserGroups(): array
	{
		$members = $this->getRoleMembersMap();
		$accessRights = $this->getRoleAccessRightsMap();

		$roles = [];
		foreach (RoleUtil::getRoles() as $row)
		{
			$roleId = (int) $row['ID'];

			$roles[] = [
				'id' => $roleId,
				'title'  => RoleDictionary::getRoleName($row['NAME']),
				'accessRights' => $accessRights[$roleId] ?? [],
				'members' => $members[$roleId] ?? [],
			];
		}

		return $roles;
	}

	/**
	 * Get sections for view on rights settings page.
	 *
	 * @return array
	 */
	private function getSections(): array
	{
		$dashboardRights = [
			PermissionDictionary::BIC_DASHBOARD_VIEW,
			PermissionDictionary::BIC_DASHBOARD_COPY,
			PermissionDictionary::BIC_DASHBOARD_EDIT,
			PermissionDictionary::BIC_DASHBOARD_DELETE,
		];
		if (MarketDashboardManager::getInstance()->isExportEnabled())
		{
			$dashboardRights[] = PermissionDictionary::BIC_DASHBOARD_EXPORT;
		}

		$mainRights = [
			PermissionDictionary::BIC_DASHBOARD_CREATE,
			PermissionDictionary::BIC_DASHBOARD_TAG_MODIFY,
			PermissionDictionary::BIC_SETTINGS_ACCESS,
			PermissionDictionary::BIC_SETTINGS_EDIT_RIGHTS,
			PermissionDictionary::BIC_DASHBOARD_EDIT_SCOPE,
		];

		if (Feature::isExternalEntitiesEnabled())
		{
			$mainRights[] = PermissionDictionary::BIC_EXTERNAL_DASHBOARD_CONFIG;
		}

		return [
			self::SECTION_BIC_ACCESS => [
				PermissionDictionary::BIC_ACCESS,
			],
			self::SECTION_MAIN_RIGHTS => $mainRights,
			self::SECTION_DASHBOARD_RIGHTS => $dashboardRights,
		];
	}

	/**
	 * All roles members.
	 *
	 * @return array
	 */
	private function getRoleMembersMap(): array
	{
		return (new RoleMembersInfo)->getMemberInfos();
	}

	/**
	 * All roles access rights.
	 *
	 * @return array in format `[roleId => [ [id => ..., value => ...], [id => ..., value => ...], ... ]]`
	 */
	private function getRoleAccessRightsMap(): array
	{
		$result = [];

		$rows = PermissionTable::getList([
			'select' => [
				'ROLE_ID',
				'PERMISSION_ID',
				'VALUE',
			],
		]);
		foreach ($rows as $row)
		{
			$roleId = $row['ROLE_ID'];

			$result[$roleId][] = [
				'id' => $row['PERMISSION_ID'],
				'value' => $row['VALUE']
			];
		}

		return $result;
	}
}
