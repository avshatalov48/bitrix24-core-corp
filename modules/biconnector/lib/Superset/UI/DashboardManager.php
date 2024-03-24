<?php

namespace Bitrix\BIConnector\Superset\UI;

use Bitrix\Main\Loader;

final class DashboardManager
{
	/**
	 * Notify client-side that batch of dashboard changed status
	 *
	 * @param array $dashboardList in format [['id' => *idOfDashboard*(int), 'status' => *DashboardStatus*(string)], ...]
	 * @return void
	 */
	public static function notifyBatchDashboardStatus(array $dashboardList): void
	{
		if (Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack('superset_dashboard', [
				'module_id' => 'biconnector',
				'command' => 'onDashboardStatusUpdated',
				'params' => [
					'dashboardList' => $dashboardList,
				],
			]);
		}
	}

	/**
	 * Notify client-side that particular dashboard changed his status
	 *
	 * @param int $dashboardId
	 * @param string $status
	 * @return void
	 */
	public static function notifyDashboardStatus(int $dashboardId, string $status): void
	{
		self::notifyBatchDashboardStatus([
			[
				'id' => $dashboardId,
				'status' => $status,
			]
		]);
	}

	public static function notifySupersetUnfreeze(): void
	{
		if (Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack('superset_dashboard', [
				'module_id' => 'biconnector',
				'command' => 'onSupersetUnfreeze',
			]);
		}
	}
}
