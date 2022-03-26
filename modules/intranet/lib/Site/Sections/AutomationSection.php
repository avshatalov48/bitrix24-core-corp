<?php

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UI\Extension;
use Bitrix\Rpa\Driver;

class AutomationSection
{
	public static function getItems(): array
	{
		return [
			static::getBizProc(),
			static::getRpa(),
			static::getAI(),
			static::getOnec(),
			static::getLists(),
		];
	}

	public static function getBizProc(): array
	{
		$available = Loader::includeModule('bizproc') && \CBPRuntime::isFeatureEnabled();

		return [
			'id' => 'bizproc',
			'title' => Loc::getMessage('AUTOMATION_SECTION_BIZPROC_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'bizproc/',
			'extraUrls' => [
				SITE_DIR . 'company/personal/bizproc/',
				SITE_DIR . 'company/personal/processes/',
			],
			'iconClass' => 'ui-icon intranet-automation-bp-icon',
			'menuData' => [
				'real_link' => SITE_DIR . 'company/personal/bizproc/',
				'counter_id' => 'bp_tasks',
				'menu_item_id' => 'menu_bizproc_sect',
				'top_menu_id' => 'top_menu_id_bizproc',
			],
			'tileData' => [
				'url' => SITE_DIR . 'company/personal/bizproc/',
			],
		];
	}

	public static function getRpa(): array
	{
		$available = Loader::includeModule('rpa') && Driver::getInstance()->getUserPermissions()->canViewAtLeastOneType();

		return [
			'id' => 'rpa',
			'title' => Loc::getMessage('AUTOMATION_SECTION_RPA_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'rpa/',
			'iconClass' => 'ui-icon intranet-automation-rpa-icon',
			'menuData' => [
				'counter_id' => 'rpa_tasks',
				'menu_item_id' => 'menu_rpa',
				'top_menu_id' => 'top_menu_id_rpa',
			],
		];
	}

	public static function getAI()
	{
		$available = LANGUAGE_ID === 'ru';

		return [
			'id' => 'ai',
			'title' => Loc::getMessage('AUTOMATION_SECTION_AI_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'ai/',
			'iconClass' => 'ui-icon intranet-automation-ai-icon',
			'menuData' => [
				'menu_item_id' => 'menu_ai',
			],
		];
	}


	public static function getOnec(): array
	{
		$allowedLangs = ['ru', 'kz', 'by', 'ua'];
		$available = Loader::includeModule('bitrix24') && in_array(\CBitrix24::getLicensePrefix(), $allowedLangs);
		if (!$available && !ModuleManager::isModuleInstalled('bitrix24'))
		{
			$available =
				file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . 'onec/') && in_array(LANGUAGE_ID, $allowedLangs)
			;
		}

		return [
			'id' => 'onec',
			'title' => Loc::getMessage('AUTOMATION_SECTION_ONEC_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'onec/',
			'iconClass' => 'ui-icon ui-icon-service-1c',
			'menuData' => [
				'menu_item_id' => 'menu_onec_sect',
				'top_menu_id' => 'top_menu_id_onec',
			]
		];
	}

	public static function getLists(): array
	{
		$available = ModuleManager::isModuleInstalled('lists') && ModuleManager::isModuleInstalled('bitrix24');

		$listUrl = "javascript:BX.UI.InfoHelper.show('limit_office_records_management');";
		$locked = true;
		if (Loader::includeModule('lists') && \CLists::isFeatureEnabled('lists'))
		{
			$listUrl = SITE_DIR . 'company/lists/';
			$locked = false;
		}

		return [
			'id' => 'lists',
			'title' => Loc::getMessage('AUTOMATION_SECTION_LISTS_ITEM_TITLE'),
			'available' => $available,
			'url' => $listUrl,
			'locked' => $locked,
			'menuData' => [
				'is_locked' => $locked,
			],
			'iconClass' => 'ui-icon intranet-automation-lists-icon',
		];
	}

	public static function isAvailable(): bool
	{
		$items = static::getItems();
		foreach ($items as $item)
		{
			if (isset($item['available']) && $item['available'] === true)
			{
				return true;
			}
		}

		return false;
	}

	public static function getPath(): string
	{
		return SITE_DIR . 'automation/';
	}

	public static function getRootMenuItem(): array
	{
		$extraUrls = [];
		foreach (static::getItems() as $item)
		{
			if ($item['available'])
			{
				if (isset($item['url']) && is_string($item['url']))
				{
					$extraUrls[] = $item['url'];
				}

				if (isset($item['extraUrls']) && is_array($item['extraUrls']))
				{
					$extraUrls = array_merge($extraUrls, $item['extraUrls']);
				}
			}
		}

		return [
			Loc::getMessage('AUTOMATION_SECTION_ROOT_ITEM_TITLE'),
			static::getPath(),
			$extraUrls,
			[
				'menu_item_id' => 'menu_automation',
				'top_menu_id' => 'top_menu_id_automation',
			],
			'',
		];
	}
}