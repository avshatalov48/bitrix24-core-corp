<?php

namespace Bitrix\Market\Application;

use Bitrix\Main\Loader;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Engine\Access;
use Bitrix\Rest\Marketplace\Client;
use CRestUtil;

class Action
{
	public const INSTALL = 'INSTALL';
	public const NO_ACCESS_INSTALL = 'NO_ACCESS_INSTALL';
	public const UPDATE = 'UPDATE';
	public const DELETE = 'DELETE';
	public const RIGHTS_SETTING = 'RIGHTS';
	public const PROLONG_SUBSCRIPTION = 'PROLONG_SUBSCRIPTION';
	public const CONFIGURATION_IMPORT = 'CONFIGURATION_IMPORT';
	public const OPEN_PREVIEW = 'OPEN_PREVIEW';

	public static function list(array $app, bool $isTestsInstall = false): array
	{
		$actions = [];

		$isInstalledApp = isset($app['ACTIVE']) && $app['ACTIVE'] === 'Y';

		if (!empty($app['CODE']) && Loader::includeModule('rest')) {
			if (CRestUtil::canInstallApplication($app)) {
				if ($isInstalledApp) {
					if (
						Access::isAvailable($app['CODE']) &&
						Access::isAvailableCount(Access::ENTITY_TYPE_APP, $app['CODE'])
					) {
						if (isset($app['TYPE']) && $app['TYPE'] === AppTable::TYPE_CONFIGURATION) {
							$actions[Action::CONFIGURATION_IMPORT] = 'Y';
						} else if ($isTestsInstall) {
							$actions[Action::INSTALL] = 'Y';
						} else  {
							if (Client::getAvailableUpdate($app['CODE'])) {
								$actions[Action::UPDATE] = 'Y';
							}
						}
					}

					if (isset($app['BY_SUBSCRIPTION']) && $app['BY_SUBSCRIPTION'] === 'Y') {
						$appStatus = AppTable::getAppStatusInfo($app, '');
						if ($appStatus['PAYMENT_NOTIFY'] === 'Y') {
							$actions[Action::PROLONG_SUBSCRIPTION] = 'Y';
						}
					}
				} else {
					$actions[Action::INSTALL] = 'Y';
				}
			} else if (!$isInstalledApp) {
				$actions[Action::NO_ACCESS_INSTALL] = 'Y';
			}

			if (
				isset($app['TYPE']) && $app['TYPE'] === AppTable::TYPE_CONFIGURATION &&
				isset($app['MODE']) && $app['MODE'] === AppTable::MODE_SITE &&
				!empty($app['SITE_URL'])
			) {
				// TODO site template app
				// $actions[Action::OPEN_PREVIEW] = 'Y';
			}

			if (CRestUtil::isAdmin() && $isInstalledApp) {
				if (!isset($app['TYPE']) || $app['TYPE'] != AppTable::TYPE_CONFIGURATION) {
					$actions[Action::RIGHTS_SETTING] = 'Y';
				}

				$actions[Action::DELETE] = 'Y';
			}
		}

		return $actions;
	}

	public static function getJsAppData($appData, $checkHash = false, $installHash = false): array
	{
		return [
			'CODE' => $appData['CODE'],
			'ACTIVE' => isset($appData['ACTIVE']) && $appData['ACTIVE'] === 'Y',
			'VERSION' => $appData['VER_TO_INSTALL'],
			'CHECK_HASH' => $checkHash,
			'INSTALL_HASH' => $installHash,
			'SILENT_INSTALL' => isset($appData['SILENT_INSTALL']) && $appData['SILENT_INSTALL'] === 'Y',
			'REDIRECT_PRIORITY' => false,
		];
	}
}
