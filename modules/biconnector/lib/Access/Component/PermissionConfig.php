<?php

namespace Bitrix\BIConnector\Access\Component;

use Bitrix\BIConnector\Access\Component\PermissionConfig\RoleMembersInfo;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Access\Role\RoleDictionary;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\Main\Localization\Loc;

final class PermissionConfig
{
	public const SECTION_MAIN_RIGHTS = 'SECTION_RIGHTS_MAIN';
	public const SECTION_DASHBOARD_GROUP_RIGHTS = 'SECTION_RIGHTS_GROUP';

	/**
	 * Access rights.
	 *
	 * @return array in format of ExternalAccessRightSection type of BX.UI.AccessRights.V2.App.
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

			$section = [
				'sectionCode' => $sectionCode,
				'sectionTitle' => Loc::getMessage("BICONNECTOR_CONFIG_PERMISSION_{$sectionCode}"),
				'sectionHint' => Loc::getMessage("HINT_BICONNECTOR_CONFIG_PERMISSION_{$sectionCode}"),
				'rights' => $rights,
			];

			if ($sectionCode === self::SECTION_DASHBOARD_GROUP_RIGHTS)
			{
				$section['action'] = [
					'buttonText' => Loc::getMessage('BICONNECTOR_CONFIG_PERMISSION_SECTION_BUTTON_ADD_GROUP'),
				];
			}

			$result[] = $section;
		}

		return $result;
	}

	/**
	 * Get saved user roles.
	 *
	 * @return array in format of ExternalUserGroup type of BX.UI.AccessRights.V2.App.
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
		$dashboardPermissions = PermissionDictionary::getDashboardGroupPermissions();

		$mainRights = [
			PermissionDictionary::BIC_ACCESS,
			PermissionDictionary::BIC_DASHBOARD_CREATE,
			PermissionDictionary::BIC_DASHBOARD_TAG_MODIFY,
			PermissionDictionary::BIC_SETTINGS_ACCESS,
			PermissionDictionary::BIC_SETTINGS_EDIT_RIGHTS,
			PermissionDictionary::BIC_GROUP_MODIFY,
		];

		if (Feature::isExternalEntitiesEnabled())
		{
			$mainRights[] = PermissionDictionary::BIC_EXTERNAL_DASHBOARD_CONFIG;
			$mainRights[] = PermissionDictionary::BIC_DELETE_ALL_UNUSED_ELEMENTS;
		}

		return [
			self::SECTION_MAIN_RIGHTS => $mainRights,
			self::SECTION_DASHBOARD_GROUP_RIGHTS => array_keys($dashboardPermissions),
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
