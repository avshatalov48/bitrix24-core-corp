<?php

namespace Bitrix\BIConnector\Superset\Updater\Versions;

use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Manager;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;

/**
 * Hotfix for delete extra duplicate dashboards
 */
final class Version4 extends BaseVersion
{
	public function run(): Result
	{
		$result = new Result();

		if (
			SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_DOESNT_EXISTS
			|| SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_DELETED
		)
		{
			return $result;
		}

		if (SupersetInitializer::getSupersetStatus() !== SupersetInitializer::SUPERSET_STATUS_READY)
		{
			$result->addError(new Main\Error('Superset status is not READY'));

			return $result;
		}

		$deletingAppCodes = [
			'alaio.bic_abcanalysis',
			'alaio.bic_bizproc',
			'alaio.bic_actual_time',
		];

		$systemDashboards = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'TYPE', 'EXTERNAL_ID'],
			'filter' => [
				'=APP_ID' => $deletingAppCodes,
				'=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM,
			],
		])
			->fetchCollection()
		;

		$foundAppCodeToDelete = array_intersect($deletingAppCodes, $systemDashboards->getAppIdList());

		if (empty($foundAppCodeToDelete))
		{
			return $result;
		}

		$copies = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'TYPE'],
			'filter' => [
				'=APP_ID' => $foundAppCodeToDelete,
				'!=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM,
			],
		])
			->fetchCollection()
		;
		foreach ($copies as $copy)
		{
			$copy->setAppId(null);
			$copy->save();
		}

		foreach ($systemDashboards as $systemDashboard)
		{
			$externalDashboardId = $systemDashboard->getExternalId();
			if ($externalDashboardId)
			{
				$response = Integrator::getInstance()->deleteDashboard([$externalDashboardId]);
				if (
					$response->hasErrors()
					&& $response->getStatus() !== IntegratorResponse::STATUS_NOT_FOUND
				)
				{
					continue;
				}
			}

			$systemDashboard->delete();
		}

		$marketManager = MarketDashboardManager::getInstance();
		$oldValueOption = Option::get('biconnector', 'allow_delete_system_dashboard', 'N');
		Option::set('biconnector', 'allow_delete_system_dashboard', 'Y');
		\Bitrix\Rest\Marketplace\Application::setContextUserId(Manager::getAdminId());
		foreach ($foundAppCodeToDelete as $appCode)
		{
			$marketManager->handleUninstallMarketApp($appCode);
		}
		Option::set('biconnector', 'allow_delete_system_dashboard', $oldValueOption);

		return $result;
	}
}
