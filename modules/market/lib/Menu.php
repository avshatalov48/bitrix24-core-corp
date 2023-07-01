<?php

namespace Bitrix\Market;

use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Marketplace\Client;
use CRestUtil;

class Menu
{
	private const PARAM_DATASET = 'DATASET';
	private const PARAM_LOAD_CONTENT = 'LOAD_CONTENT';
	private const PARAM_IGNORE_AUTOBINDING = 'IGNORE_AUTOBINDING';

	public static function getList(): array
	{
		$menu = [];

		if (CRestUtil::isAdmin()) {
			$menu[] = Menu::getItem(
				Loc::getMessage('MARKET_MENU_INSTALLED_APPS'),
				SITE_DIR . 'market/installed/',
				[
					Menu::PARAM_DATASET => [
						Menu::PARAM_LOAD_CONTENT => 'list',
						Menu::PARAM_IGNORE_AUTOBINDING => 'true',
					],
					'INSTALLED_LIST' => 'Y',
				]
			);

			$numUpdates = Client::getAvailableUpdateNum();
			if ($numUpdates > 0) {
				$menu[] = Menu::getItem(
					Loc::getMessage('MARKET_MENU_REQUIRE_UPDATE', ['#NUM_UPDATES#' => $numUpdates]),
					SITE_DIR . 'market/installed/?updates=Y',
					[
						Menu::PARAM_DATASET => [
							Menu::PARAM_LOAD_CONTENT => 'list',
							Menu::PARAM_IGNORE_AUTOBINDING => 'true',
						],
						'NEED_UPDATE_LIST' => 'Y',
					]
				);
			}
		}

		$menu[] = Menu::getItem(
			Loc::getMessage('MARKET_MENU_MY_REVIEWS'),
			SITE_DIR . 'market/reviews/',
		);

		$apps = Menu::getInstalledApps();
		if (!empty($apps)) {
			$menu[] = Menu::getItem(
				'',
				'',
				[
					'DELIMITER' => 'Y',
				],
			);

			foreach ($apps as $app) {
				$menu[] = $app;
			}
		}

		return $menu;
	}

	private static function getItem(string $name, string $path, array $params = []): array
	{
		return [
			'NAME' => $name,
			'PATH' => $path,
			'PARAMS' => $params,
		];
	}

	public static function getInstalledApps(int $filterAppId = 0): array
	{
		global $USER;

		$apps = [];
		$userGroupCodes = $USER->GetAccessCodes();

		$filter = [
			'=ACTIVE' => AppTable::ACTIVE,
		];
		if ($filterAppId > 0) {
			$filter['=ID'] = $filterAppId;
		}

		$dbApps = AppTable::getList([
			'order' => [
				'ID' => 'ASC'
			],
			'filter' => $filter,
			'select' => [
				'ID',
				'CODE',
				'CLIENT_ID',
				'STATUS',
				'ACTIVE',
				'ACCESS',
				'MENU_NAME' => 'LANG.MENU_NAME',
				'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
				'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
			],
		]);
		foreach ($dbApps->fetchCollection() as $app) {
			$appInfo = [
				'ID' => $app->getId(),
				'CODE' => $app->getCode(),
				'ACTIVE' => $app->getActive(),
				'CLIENT_ID' => $app->getClientId(),
				'ACCESS' => $app->getAccess(),
				'MENU_NAME' => !is_null($app->getLang()) ? $app->getLang()->getMenuName() : '',
				'MENU_NAME_DEFAULT' => !is_null($app->getLangDefault()) ? $app->getLangDefault()->getMenuName() : '',
				'MENU_NAME_LICENSE' => !is_null($app->getLangLicense()) ? $app->getLangLicense()->getMenuName() : ''
			];

			if($appInfo['CODE'] === CRestUtil::BITRIX_1C_APP_CODE) {
				continue;
			}

			$lang = in_array(LANGUAGE_ID, ['ru', 'en', 'de']) ? LANGUAGE_ID : Loc::getDefaultLang(LANGUAGE_ID);

			if ($appInfo['MENU_NAME'] === '' && $appInfo['MENU_NAME_DEFAULT'] === '' && $appInfo['MENU_NAME_LICENSE'] === '') {
				$app->fillLangAll();
				if (!is_null($app->getLangAll())) {
					$langList = [];
					foreach ($app->getLangAll() as $appLang) {
						if ($appLang->getMenuName() !== '') {
							$langList[$appLang->getLanguageId()] = $appLang->getMenuName();
						}
					}

					if (isset($langList[$lang]) && $langList[$lang]) {
						$appInfo['MENU_NAME'] = $langList[$lang];
					} elseif (isset($langList['en']) && $langList['en']) {
						$appInfo['MENU_NAME'] = $langList['en'];
					} elseif (count($langList) > 0) {
						$appInfo['MENU_NAME'] = reset($langList);
					}
				}
			}

			if($appInfo['MENU_NAME'] <> '' || $appInfo['MENU_NAME_DEFAULT'] <> '' || $appInfo['MENU_NAME_LICENSE'] <> '') {
				$appRightAvailable = false;
				if(CRestUtil::isAdmin()){
					$appRightAvailable = true;
				} elseif(!empty($appInfo['ACCESS'])) {
					$rights = explode(',', $appInfo['ACCESS']);
					foreach($rights as $rightID)
					{
						if(in_array($rightID, $userGroupCodes))
						{
							$appRightAvailable = true;
							break;
						}
					}
				} else {
					$appRightAvailable = true;
				}

				if($appRightAvailable) {
					$appName = $appInfo['MENU_NAME'];

					if($appName == '') {
						$appName = $appInfo['MENU_NAME_DEFAULT'];
					}

					if($appName == '') {
						$appName = $appInfo['MENU_NAME_LICENSE'];
					}

					$apps[] = Menu::getItem(
						htmlspecialcharsbx($appName),
						CRestUtil::getApplicationPage($appInfo['ID'], 'ID', $appInfo),
					);
				}
			}
		}

		return $apps;
	}
}