<?php

namespace Bitrix\BIConnector\Superset\UI;

use Bitrix\BIConnector\Integration\Pull\PullManager;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

final class DashboardManager
{
	private const DASHBOARD_NOTIFY_TAG = 'superset_dashboard';

	/**
	 * Notify client-side that batch of dashboard changed status
	 *
	 * @param array $dashboardList in format [['id' => *idOfDashboard*(int), 'status' => *DashboardStatus*(string)], ...]
	 * @return void
	 */
	public static function notifyBatchDashboardStatus(array $dashboardList): void
	{
		PullManager::getNotifyer()->notifyByTag(
			self::DASHBOARD_NOTIFY_TAG,
			'onDashboardStatusUpdated',
			[
				'dashboardList' => $dashboardList,
			]
		);
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
			],
		]);
	}

	public static function notifySupersetStatus(string $status): void
	{
		PullManager::getNotifyer()->notifyByTag(
			self::DASHBOARD_NOTIFY_TAG,
			'onSupersetStatusUpdated',
			[
				'status' => $status,
			]
		);
	}

	/**
	 * @deprecated Will be removed in future updates.
	 * Notify client side when all system dashboards had installed to reload grid.
	 *
	 * @return void
	 */
	public static function notifyInitialDashboardsInstalled(): void
	{
		PullManager::getNotifyer()->notifyByTag(
			self::DASHBOARD_NOTIFY_TAG,
			'onInitialDashboardsInstalled',
		);
	}

	public static function notifySupersetCreated(int $userId, int $dashboardId): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		if (!$userId || !$dashboardId)
		{
			return;
		}

		$dashboard = SupersetDashboardTable::getByPrimary($dashboardId)->fetchObject();
		if ($dashboard)
		{
			$title = htmlspecialcharsbx($dashboard->getTitle());
			$urlService = new UrlParameter\Service($dashboard);
			$url = $urlService->getEmbeddedUrl();
			$link = "<a href='{$url}' target='_blank'>{$title}</a>";

			$notificationCallback = static fn(?string $languageId = null) => Loc::getMessage(
				'BI_SUPERSET_CREATED_NOTIFICATION_TEXT',
				['#LINK#' => $link],
				$languageId
			);

			\CIMNotify::Add([
				'TO_USER_ID' => $userId,
				'FROM_USER_ID' => 0,
				'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
				'NOTIFY_MODULE' => 'biconnector',
				'NOTIFY_TITLE' => Loc::getMessage('BI_SUPERSET_CREATED_NOTIFICATION_TITLE'),
				'NOTIFY_MESSAGE' => $notificationCallback,
			]);
		}
	}
}
