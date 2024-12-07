<?php

namespace Bitrix\BIConnector\Access\Event;

use Bitrix\BIConnector\Access\Model\UserAccessItem;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\Main;

final class DashboardRightsSetter
{
	/**
	 * Copies permissions of parent dashboard for just copied dashboard.
	 * Permissions are copied only for current user roles.
	 *
	 * @param Main\Event $event Event with dashboard id, source dashboard id and copying user id.
	 *
	 * @return Main\EventResult
	 */
	public static function onAfterCopyDashboard(Main\Event $event): Main\EventResult
	{
		$result = $event->getParameters();

		$dashboardPermissions = [];
		$user = UserAccessItem::createFromId((int)$result['createdById']);
		$roles = $user->getRoles();

		$sourcePermissions = RoleUtil::getDashboardPermissions((int)$result['sourceDashboardId']);
		foreach ($sourcePermissions as $sourcePermission)
		{
			if (in_array($sourcePermission['ROLE_ID'], $roles, true))
			{
				$dashboardPermissions[] = [
					'ROLE_ID' => $sourcePermission['ROLE_ID'],
					'PERMISSION_ID' => $sourcePermission['PERMISSION_ID'],
					'VALUE' => (int)$result['dashboardId'],
				];
			}
		}

		RoleUtil::insertPermissions($dashboardPermissions);

		return new Main\EventResult(
			Main\EventResult::SUCCESS,
			$result,
			'biconnector'
		);
	}
}
