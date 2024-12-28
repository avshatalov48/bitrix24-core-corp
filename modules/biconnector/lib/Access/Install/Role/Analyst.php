<?php

namespace Bitrix\BIConnector\Access\Install\Role;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Loader;

class Analyst extends Base
{
	public function getPermissions(): array
	{
		return [
			PermissionDictionary::BIC_ACCESS,
			PermissionDictionary::BIC_SETTINGS_ACCESS,
			PermissionDictionary::BIC_DASHBOARD_CREATE,
			PermissionDictionary::BIC_DASHBOARD_TAG_MODIFY,
			PermissionDictionary::BIC_EXTERNAL_DASHBOARD_CONFIG,
			PermissionDictionary::BIC_DASHBOARD_VIEW,
			PermissionDictionary::BIC_DASHBOARD_EDIT,
			PermissionDictionary::BIC_DASHBOARD_COPY,
			PermissionDictionary::BIC_DASHBOARD_DELETE,
			PermissionDictionary::BIC_DASHBOARD_EXPORT,
		];
	}

	protected function getRelationUserGroups(): array
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return [];
		}

		if ($this->isNewPortal)
		{
			return [AccessCode::ACCESS_EMPLOYEE . '0'];
		}

		$groups = [];
		$dashboards = SupersetDashboardTable::getList([
			'select' => ['OWNER_ID'],
			'group' => ['OWNER_ID'],
		]);

		while ($dashboard = $dashboards->fetch())
		{
			if (!empty($dashboard['OWNER_ID']))
			{
				$groups[] = "U{$dashboard['OWNER_ID']}";
			}
		}

		return $groups;
	}
}