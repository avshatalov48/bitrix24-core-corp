<?php

namespace Bitrix\BIConnector\Integration\Superset;

use Bitrix\BIConnector\Integration\Superset\Integrator\IntegratorResponse;
use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\ActionFilter\ProxyAuth;
use Bitrix\BIConnector\Superset\Logger\SupersetInitializerLogger;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\BIConnector\Superset\UI\DashboardManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Error;

final class SupersetInitializer
{
	public const SUPERSET_STATUS_READY = 'READY';
	public const SUPERSET_STATUS_LOAD = 'LOAD';
	public const SUPERSET_STATUS_FROZEN = 'FROZEN';
	public const SUPERSET_STATUS_DISABLED = 'DISABLED';
	public const SUPERSET_STATUS_DOESNT_EXISTS = 'DOESNT_EXISTS'; // If portal startup superset first time

	private const LAST_STARTUP_ATTEMPT_OPTION = 'last_superset_startup_attempt';

	/**
	 * @return string current superset status
	 */
	public static function startupSuperset(): string
	{
		$status = self::getSupersetStatus();
		$canSendAnotherRequest = ($status === self::SUPERSET_STATUS_LOAD && self::needCheckSupersetStatus());
		if (
			($status === self::SUPERSET_STATUS_DISABLED || $status === self::SUPERSET_STATUS_DOESNT_EXISTS)
			|| $canSendAnotherRequest
		)
		{
			Option::set('biconnector', self::LAST_STARTUP_ATTEMPT_OPTION, (new DateTime())->getTimestamp());
			self::startSupersetInitialize($status !== self::SUPERSET_STATUS_LOAD);

			if ($status !== self::SUPERSET_STATUS_LOAD)
			{
				$status = self::SUPERSET_STATUS_LOAD;
				self::setSupersetStatus($status);
			}
		}

		return $status;
	}

	private static function needCheckSupersetStatus(): bool
	{
		$lastCheck = (int)Option::get('biconnector', self::LAST_STARTUP_ATTEMPT_OPTION, 0);

		if ($lastCheck <= 0)
		{
			return true;
		}

		$lastCheckTime = DateTime::createFromTimestamp($lastCheck)->add('20 minutes');

		return (new DateTime()) >= $lastCheckTime; // check again after 20 minutes
	}

	public static function createSuperset(): string
	{
		$status = self::getSupersetStatus();
		if ($status !== self::SUPERSET_STATUS_DOESNT_EXISTS && $status !== self::SUPERSET_STATUS_LOAD)
		{
			return $status;
		}

		return self::startupSuperset();
	}

	private static function startSupersetInitialize($firstRequest = true): void
	{
		self::preloadSystemDashboards();
		\Bitrix\Main\Application::getInstance()->addBackgroundJob(fn() => self::makeSupersetCreateRequest($firstRequest));
	}

	private static function preloadSystemDashboards(): void
	{
		$marketManager = MarketDashboardManager::getInstance();
		$systemDashboards = $marketManager->getSystemDashboardApps();
		$existingDashboardInfoList = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'STATUS'],
			'filter' => [
				'=APP_ID' => array_column($systemDashboards, 'CODE'),
			],
		])->fetchAll();

		$existingDashboardAppIds = array_column($existingDashboardInfoList, 'APP_ID');

		foreach ($systemDashboards as $systemDashboard)
		{
			if (!in_array($systemDashboard['CODE'], $existingDashboardAppIds))
			{
				self::preloadSystemDashboard($systemDashboard['CODE'], $systemDashboard['NAME']);
			}
		}

		if (count($existingDashboardInfoList) > 0)
		{
			$notifyList = [];
			foreach ($existingDashboardInfoList as $dashboardInfo)
			{
				if ($dashboardInfo['STATUS'] === SupersetDashboardTable::DASHBOARD_STATUS_FAILED)
				{
					SupersetDashboardTable::update($dashboardInfo['ID'], [
						'STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_LOAD,
					]);
				}

				$notifyList[] = [
					'id' => $dashboardInfo['ID'],
					'status' => SupersetDashboardTable::DASHBOARD_STATUS_LOAD,
				];
			}

			DashboardManager::notifyBatchDashboardStatus($notifyList);
		}
	}

	private static function preloadSystemDashboard(string $appId, string $appTitle): void
	{
		SupersetDashboardTable::add([
			'TITLE' => $appTitle,
			'APP_ID' => $appId,
			'TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM,
			'STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_LOAD,
		]);
	}

	/**
	 * @return void
	 */
	public static function enableSuperset(): void
	{
		if (self::getSupersetStatus() === self::SUPERSET_STATUS_READY)
		{
			return;
		}

		self::setSupersetStatus(self::SUPERSET_STATUS_READY);
		self::onSupersetCreated();
	}

	public static function unfreezeSuperset(): void
	{
		self::setSupersetStatus(self::SUPERSET_STATUS_READY);
		DashboardManager::notifySupersetUnfreeze();
	}

	public static function onSupersetCreated(): void
	{
		self::installInitialDashboards();
	}

	public static function setSupersetStatus(string $status): void
	{
		Option::set('biconnector', 'superset_status', $status);
	}

	public static function getSupersetStatus(): string
	{
		return Option::get('biconnector', 'superset_status', self::SUPERSET_STATUS_DOESNT_EXISTS);
	}

	private static function makeSupersetCreateRequest(bool $firstRequest = true): int
	{
		$proxyIntegrator = ProxyIntegrator::getInstance();
		if (!$firstRequest)
		{
			$proxyIntegrator->skipRequireFields();
		}

		$user = \Bitrix\Main\Engine\CurrentUser::get();
		$accessKey = \Bitrix\BIConnector\KeyManager::getOrCreateAccessKey($user, false);

		Option::set('biconnector', '~superset_key', $accessKey);

		if ($accessKey === null)
		{
			return IntegratorResponse::STATUS_NO_ACCESS;
		}

		$response = $proxyIntegrator->startSuperset($accessKey);
		if ($response->getStatus() === IntegratorResponse::STATUS_CREATED)
		{
			self::enableSuperset();
			
			return $response->getStatus();
		}

		if (!$response->hasErrors())
		{
			Option::set('biconnector', ProxyAuth::SUPERSET_PROXY_TOKEN_OPTION, $response->getData());
		}
		else
		{
			Option::delete('biconnector', ['name' => self::LAST_STARTUP_ATTEMPT_OPTION]);
			self::onUnsuccessfulSupersetStartup(...$response->getErrors());
		}

		return $response->getStatus();
	}

	private static function installInitialDashboards(): Result
	{
		return MarketDashboardManager::getInstance()->installInitialDashboards();
	}

	public static function isSupersetActive(): bool
	{
		return self::getSupersetStatus() === self::SUPERSET_STATUS_READY;
	}

	public static function isSupersetLoad(): bool
	{
		$possibleLoadStatus = [
			self::SUPERSET_STATUS_LOAD,
			self::SUPERSET_STATUS_FROZEN,
		];

		return in_array(self::getSupersetStatus(), $possibleLoadStatus, true);
	}

	public static function onUnsuccessfulSupersetStartup(Error ...$errors): void
	{
		if (!empty($errors))
		{
			SupersetInitializerLogger::logErrors($errors);
		}
		else
		{
			SupersetInitializerLogger::logErrors([new Error('undefined error while startup superset')]);
		}

		$marketManager = MarketDashboardManager::getInstance();
		$systemDashboards = $marketManager->getSystemDashboardApps();

		$existingDashboardInfoList = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'STATUS'],
			'filter' => [
				'=APP_ID' => array_column($systemDashboards, 'CODE'),
				'=STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_LOAD,
			],
		])->fetchAll();

		if (empty($existingDashboardInfoList))
		{
			self::setSupersetStatus(self::SUPERSET_STATUS_DOESNT_EXISTS);
			return;
		}

		self::setSupersetStatus(self::SUPERSET_STATUS_DISABLED);

		SupersetDashboardTable::updateMulti(array_column($existingDashboardInfoList, 'ID'), [
			'STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_FAILED,
		]);

		$notifyList = [];
		foreach ($existingDashboardInfoList as $dashboardInfo)
		{
			$notifyList[] = [
				'id' => $dashboardInfo['ID'],
				'status' => SupersetDashboardTable::DASHBOARD_STATUS_FAILED,
			];
		}

		DashboardManager::notifyBatchDashboardStatus($notifyList);
	}
}