<?php

namespace Bitrix\BIConnector\Access\Install\Role;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\Loader;

class Administrator extends Base
{
	public function getPermissions(): array
	{
		return [
			PermissionDictionary::BIC_ACCESS,
			PermissionDictionary::BIC_SETTINGS_ACCESS,
			PermissionDictionary::BIC_SETTINGS_EDIT_RIGHTS,
			PermissionDictionary::BIC_DASHBOARD_EDIT_SCOPE,
		];
	}

	protected function getRelationUserGroups(): array
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return [];
		}

		$groups = [];
		$adminGroups = UserGroupTable::getList([
			'filter' => [
				'=GROUP_ID' => 1
			],
			'select' => [ 'USER_ID' ]
		]);

		while ($adminRelations = $adminGroups->fetch())
		{
			$groups[] = "U{$adminRelations['USER_ID']}";
		}

		return $groups;
	}
}