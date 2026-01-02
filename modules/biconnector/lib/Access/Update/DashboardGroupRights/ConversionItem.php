<?php

namespace Bitrix\BIConnector\Access\Update\DashboardGroupRights;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroup;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupBindingTable;
use Bitrix\Main\Result;

final class ConversionItem
{
	public function __construct(
		public readonly SupersetDashboardGroup $dashboardGroup,
		public readonly array $roleActions
	)
	{
	}

	public function save(): Result
	{
		$result = new Result();

		$dashboards = $this->dashboardGroup->getDashboards();
		$this->dashboardGroup->unsetDashboards();
		if (!$this->dashboardGroup->isIdFilled())
		{
			$this->dashboardGroup->save();
		}

		// hack for issue saving new entity with many to many bindings
		if (!empty($dashboards))
		{
			foreach ($dashboards as $dashboard)
			{
				$resultAddBinding = SupersetDashboardGroupBindingTable::add([
					'GROUP_ID' => $this->dashboardGroup->getId(),
					'DASHBOARD_ID' => $dashboard->getId(),
				]);

				if (!$resultAddBinding->isSuccess())
				{
					$result->addErrors($resultAddBinding->getErrors());

					return $result;
				}
			}
		}

		foreach ($this->roleActions as $roleAction)
		{
			$resultAdd = PermissionTable::add([
				'ROLE_ID' => $roleAction['roleId'],
				'PERMISSION_ID' => PermissionDictionary::getDashboardGroupPermissionId($this->dashboardGroup->getId()),
				'VALUE' => $roleAction['actionId'],
			]);

			if (!$resultAdd->isSuccess())
			{
				$result->addErrors($resultAdd->getErrors());

				return $result;
			}
		}

		return $result;
	}
}
