<?php

namespace Bitrix\Crm\Integration\Intranet\SystemPageProvider;

use Bitrix\Crm\Integration\Intranet\CustomSectionProvider;
use Bitrix\Crm\Integration\Intranet\SystemPageProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Intranet\CustomSection\DataStructures;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

class ActivityPage extends SystemPageProvider
{
	protected const DEFAULT_SETTINGS = [];
	protected const SORT = 999998;

	public static function getComponent(string $pageSettings, Uri $url): ?Component
	{
		$sectionCode = explode(self::SEPARATOR, $pageSettings)[1];

		$router = Container::getInstance()->getRouter();
		$sefFolder = $router->getItemListUrlIntoCustomSection($sectionCode, \CCrmOwnerType::Activity);
		$componentPage = $router->getCurrentListViewInCustomSection(\CCrmOwnerType::Activity, $sectionCode) === Router::LIST_VIEW_LIST
			? 'index'
			: 'kanban'
		;

		$params = [
			'SEF_MODE' => 'Y',
			'SEF_FOLDER' => $sefFolder,
			'ENABLE_CONTROL_PANEL' => false,
			'COMPONENT_PAGE' => $componentPage,
			'CUSTOM_SECTION_CODE' => $sectionCode,
		];

		return (new Component())
			->setComponentName('bitrix:crm.activity')
			->setComponentTemplate('')
			->setComponentParams($params)
		;
	}

	public static function getPageInstance(DataStructures\CustomSection $section): ?CustomSectionPage
	{
		$router = Container::getInstance()->getRouter();

		$code = $router->getSystemPageCode(\CCrmOwnerType::Activity);

		$settingsArr = [$code, $section->getCode(), ...self::DEFAULT_SETTINGS];
		$settings = implode(self::SEPARATOR, $settingsArr);

		return (new CustomSectionPage())
			->setCode($code)
			->setTitle(Loc::getMessage('CRM_INTEGRATION_INTRANET_ACTIVITY_PAGE_TITLE'))
			->setSort(self::SORT)
			->setSettings($settings)
			->setModuleId('crm')
			->setDisabledInCtrlPanel(true)
		;
	}
}
