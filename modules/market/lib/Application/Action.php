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
	public const REIMPORT = 'REIMPORT';
	public const OPEN_PREVIEW = 'OPEN_PREVIEW';

	public static function getButtons(array $app, bool $isTestsInstall = false): array
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
						$appType = $app['TYPE'] ?? '';
						if ($appType === AppTable::TYPE_CONFIGURATION) {
							$actions[Action::CONFIGURATION_IMPORT] = 'Y';
						} else if ($appType === AppTable::TYPE_BIC_DASHBOARD) {
							if (Client::getAvailableUpdate($app['CODE'])) {
								$actions[Action::UPDATE] = 'Y';
							} else {
								$actions[Action::REIMPORT] = 'Y';
							}
						} else if ($isTestsInstall) {
							$actions[Action::INSTALL] = 'Y';
						} else {
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
				$appType = $app['TYPE'] ?? '';
				if (
					$appType !== AppTable::TYPE_CONFIGURATION
					&& $appType !== AppTable::TYPE_BIC_DASHBOARD
				) {
					$actions[Action::RIGHTS_SETTING] = 'Y';
				}

				$actions[Action::DELETE] = 'Y';
			}
		}

		return $actions;
	}

	public static function getInstallJsInfo($appData, $checkHash = false, $installHash = false): array
	{
		$installedMessageCode = self::isRestOnlyApp($appData) ?
			'MARKET_POPUP_INSTALL_JS_APP_WORKS_AUTOMATICALLY' :
			'MARKET_POPUP_INSTALL_JS_INSTALLED_APP_LOCATED_APP_TAB';

		return [
			'CODE' => $appData['CODE'],
			'ACTIVE' => isset($appData['ACTIVE']) && $appData['ACTIVE'] === 'Y',
			'VERSION' => $appData['VER_TO_INSTALL'],
			'CHECK_HASH' => $checkHash,
			'INSTALL_HASH' => $installHash,
			'SILENT_INSTALL' => isset($appData['SILENT_INSTALL']) && $appData['SILENT_INSTALL'] === 'Y',
			'REDIRECT_PRIORITY' => false,
			'INSTALLED_TITLE_CODE' => 'MARKET_POPUP_INSTALL_JS_APPLICATION',
			'INSTALLED_MESSAGE_CODE' => $installedMessageCode,
			'INSTALLED_IMAGE_SHOW' => 'Y',
			'PLACEMENT_OPTIONS' => [],
		];
	}

	private static function isRestOnlyApp($appData): bool
	{
		return $appData['OPEN_API'] == 'Y';
	}
}
