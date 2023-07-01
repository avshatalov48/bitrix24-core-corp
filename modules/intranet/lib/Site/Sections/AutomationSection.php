<?php

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\Crm;
use Bitrix\Sign;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Rpa\Driver;

class AutomationSection
{
	public static function getItems(): array
	{
		return [
			static::getBizProc(),
			static::getRobots(),
			static::getSmartProcesses(),
			static::getRpa(),
			static::getOnec(),
			static::getScripts(),
			static::getLists(),
			static::getAI(),
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

	public static function getSmartProcesses(): array
	{
		$available = Loader::includeModule('crm');

		$defaultItems = [];
		$internalDynamicTypes = [];
		$externalDynamicTypes = [];
		if ($available)
		{
			$router = Container::getInstance()->getRouter();
			$defaultItems[] = [
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_DEFAULT_SUBTITLE'),
				'URL' => $router->getTypeListUrl(),
			];

			$types = Container::getInstance()->getDynamicTypesMap();
			$types->load([
				'isLoadStages' => false,
				'isLoadCategories' => false,
			]);

			$currentUser = CurrentUser::get();
			$userPermissions = Container::getInstance()->getUserPermissions($currentUser->getId());
			foreach ($types->getTypes() as $type)
			{
				if (!$userPermissions->canReadType($type->getEntityTypeId()))
				{
					continue;
				}

				$menuItem = [
					'TEXT' => $type->getTitle(),
					'URL' => $router->getItemListUrlInCurrentView($type->getEntityTypeId()),
				];

				if (Crm\Integration\IntranetManager::isEntityTypeInCustomSection($type->getEntityTypeId()))
				{
					$externalDynamicTypes[] = $menuItem;
				}
				else
				{
					$internalDynamicTypes[] = $menuItem;
				}
			}
		}

		return [
			'id' => 'crm-dynamic',
			'title' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_ITEM_TITLE'),
			'available' => $available,
			'iconClass' => 'ui-icon intranet-automation-bp-icon',
			'menuData' => [
				'menu_item_id' => 'menu_crm_dynamic',
				'top_menu_id' => 'top_menu_id_crm_dynamic',
				'sub_menu' => [
					[
						'TEXT' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_SUBTITLE_1'),
						'URL' => '/crm/',
						'ITEMS' => array_merge($externalDynamicTypes, $defaultItems),
					],
					[
						'TEXT' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_CRM_SUBTITLE'),
						'URL' => '/crm/',
						'ITEMS' => array_merge($internalDynamicTypes, $defaultItems),
					]
				],
			],
		];
	}

	public static function getRpa(): array
	{
		$available = Loader::includeModule('rpa') && Driver::getInstance()->getUserPermissions()->canViewAtLeastOneType();

		return [
			'id' => 'rpa',
			'title' => Loc::getMessage('AUTOMATION_SECTION_RPA_ITEM_TITLE_1'),
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

	public static function getRobots(): array
	{
		return [
			'id' => 'robots',
			'title' => Loc::getMessage('AUTOMATION_SECTION_ROBOTS_ITEM_TITLE'),
			'available' => true,
			// 'url' => SITE_DIR,
			'iconClass' => 'ui-icon intranet-automation-rpa-icon',
			'menuData' => [
				'menu_item_id' => 'menu_robots',
				'top_menu_id' => 'top_menu_id_robots',
				'sub_menu' => array_values(array_filter([
					self::getCrmRobots(),
					self::getTasksRobots(),
					self::getSignRobots(),
				])),
			],
		];
	}

	public static function getScripts(): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$router = Crm\Service\Container::getInstance()->getRouter();
		$items = [];
		$elements = [
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::SmartInvoice,
		];

		if (Crm\Settings\LeadSettings::isEnabled())
		{
			$elements[] = \CCrmOwnerType::Lead;
		}

		$typesMap = Crm\Service\Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$currentUser = CurrentUser::get();
		$userPermissions = Container::getInstance()->getUserPermissions($currentUser->getId());
		foreach ($typesMap->getTypes() as $type)
		{
			if (
				$userPermissions->canReadType($type->getEntityTypeId())
				&& $type->getIsAutomationEnabled()
				&& Crm\Integration\IntranetManager::isEntityTypeInCustomSection($type->getEntityTypeId())
			)
			{
				$elements[] = $type->getEntityTypeId();
			}
		}

		$elements[] = \CCrmOwnerType::Order;
		$elements[] = \CCrmOwnerType::Contact;
		$elements[] = \CCrmOwnerType::Company;

		foreach ($elements as $elementTypeId)
		{
			if (Crm\Automation\Factory::isScriptAvailable($elementTypeId))
			{
				$items[] = [
					'TEXT' => \CCrmOwnerType::GetCategoryCaption($elementTypeId),
					'URL' => $router->getItemListUrlInCurrentView($elementTypeId) . '#scripts',
				];
			}
		}

		return [
			'id' => 'bizproc_script',
			'title' => Loc::getMessage('AUTOMATION_SECTION_SCRIPT_TITLE'),
			'available' => true,
			// 'url' => SITE_DIR,
			'iconClass' => 'ui-icon intranet-automation-rpa-icon',
			'menuData' => [
				'menu_item_id' => 'menu_bizproc_script',
				'top_menu_id' => 'top_menu_id_bizproc_script',
				'sub_menu' => $items,
			],
		];
	}

	private static function getCrmRobots(): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$items = [];
		$router = Crm\Service\Container::getInstance()->getRouter();
		$elements = [
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::SmartInvoice,
			\CCrmOwnerType::Quote,
		];

		if (Crm\Settings\LeadSettings::isEnabled())
		{
			$elements[] = \CCrmOwnerType::Lead;
		}

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$elements[] = \CCrmOwnerType::Order;
		}

		foreach ($elements as $elementTypeId)
		{
			if (Crm\Automation\Factory::isAutomationAvailable($elementTypeId))
			{
				$items[] = [
					'TEXT' => \CCrmOwnerType::GetCategoryCaption($elementTypeId),
					'URL' => $router->getItemListUrlInCurrentView($elementTypeId) . '#robots',
				];
			}
		}

		return [
			'TEXT' => 'CRM',
			'URL' => '/crm/',
			'ITEMS' => $items,
		];
	}

	private static function getTasksRobots(): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		$baseUrl = '/company/personal/user/' . CurrentUser::get()->getId() . '/tasks/';

		$items = [
			[
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_TASKS_MY_TITLE'),
				'URL' => $baseUrl . '#robots',
			],
			[
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_TASKS_PROJECTS_TITLE'),
				'URL' => $baseUrl . 'projects/#robots',
			],
			[
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_TASKS_SCRUM_TITLE'),
				'URL' => $baseUrl . 'scrum/#robots',
			],
		];

		if (Loader::includeModule('crm'))
		{
			$router = Crm\Service\Container::getInstance()->getRouter();
			$items[] = [
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_TASKS_CRM_TITLE'),
				'URL' => $router->getItemListUrlInCurrentView(\CCrmOwnerType::Deal) . '#robots',
			];
		}

		return [
			'TEXT' => Loc::getMessage('AUTOMATION_SECTION_TASKS_ITEM_TITLE'),
			'URL' => $baseUrl,
			'ITEMS' => $items,
		];
	}

	private static function getSignRobots(): array
	{
		if (!Loader::includeModule('sign') || !Sign\Config\Storage::instance()->isAvailable())
		{
			return [];
		}

		$items = [
			[
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_SIGN_SIGN_TITLE'),
				'URL' => '/sign/#robots',
			],
		];

		if (Loader::includeModule('crm'))
		{
			$router = Crm\Service\Container::getInstance()->getRouter();
			$items[] = [
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_SIGN_CRM_TITLE'),
				'URL' => $router->getItemListUrlInCurrentView(\CCrmOwnerType::Deal) . '#robots',
			];
		}

		return [
			'TEXT' => Loc::getMessage('AUTOMATION_SECTION_SIGN_ITEM_TITLE'),
			'URL' => '/sign/',
			'ITEMS' => $items,
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
			'title' => Loc::getMessage('AUTOMATION_SECTION_ONEC_ITEM_TITLE_2'),
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

		$listUrl = SITE_DIR . 'company/lists/';
		$locked = false;
		$onclick = '';

		if (!Loader::includeModule('lists') || !\CLists::isFeatureEnabled('lists'))
		{
			$onclick = "javascript:BX.UI.InfoHelper.show('limit_office_records_management');";
			$listUrl = '';
			$locked = true;
		}

		return [
			'id' => 'lists',
			'title' => Loc::getMessage('AUTOMATION_SECTION_LISTS_ITEM_TITLE'),
			'available' => $available,
			'url' => $listUrl,
			'locked' => $locked,
			'menuData' => [
				'is_locked' => $locked,
				'onclick' => $onclick,
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
		$firstItemUrl = '';
		foreach (static::getItems() as $item)
		{
			if ($item['available'])
			{
				if (isset($item['menuData']['real_link']) && is_string($item['menuData']['real_link']))
				{
					if (empty($firstItemUrl))
					{
						$firstItemUrl = $item['menuData']['real_link'];
					}
				}

				if (isset($item['url']) && is_string($item['url']))
				{
					$extraUrls[] = $item['url'];

					if (empty($firstItemUrl))
					{
						$firstItemUrl = $item['url'];
					}
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
				'counter_id' => 'bp_tasks',
				'first_item_url' => $firstItemUrl,
			],
			'',
		];
	}
}