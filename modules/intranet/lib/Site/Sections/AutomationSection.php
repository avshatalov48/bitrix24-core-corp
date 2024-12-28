<?php

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Rpa\Driver;
use Bitrix\Sign;

class AutomationSection
{
	private const WORKFLOW_COUNTER_CODE = 'bp_workflow';
	private const TASK_COUNTER_CODE = 'bp_tasks';

	private static array $items;
	private static array $bpSubMenu;

	public const MENU_ITEMS_ID = [
		'robots' => 'menu_robots',
		'bizproc_automation' => 'menu_bizproc_sect',
		'smart_process' => 'menu_crm_dynamic',
		'scripts' => 'menu_bizproc_script',
		'rpa' => 'rpa_tasks',
		'lists' => 'lists',
		'onec' => 'menu_onec_sect',
		'ai' => 'menu_ai',
	];

	public static function getItems(): array
	{
		if (!isset(self::$items))
		{
			$items = [
				static::getBizProc(),
				static::getRobots(),
				static::getSmartProcesses(),
				static::getRpa(),
				static::getOnec(),
				static::getBIBuilder(),
				static::getScripts(),
				static::getLists(),
				static::getAI(),
			];

			self::$items = array_filter($items, function($item) {
				return !empty($item);
			});
		}

		return self::$items;
	}

	public static function getBizProc(): array
	{
		$available = Loader::includeModule('bizproc')
			&& (
				!Loader::includeModule('bitrix24')
				|| \Bitrix\Bitrix24\Feature::isFeatureEnabled('bizproc')
			);
		$subMenu = self::getBizProcSubMenu();
		$urls = array_unique(
			array_column($subMenu, 1)
		);

		$counterId = self::getCounterCode();

		return [
			'id' => 'bizproc',
			'title' => Loc::getMessage('AUTOMATION_SECTION_BIZPROC_ITEM_TITLE'),
			'available' => $available,
			'url' => SITE_DIR . 'bizproc/',
			'extraUrls' => $urls,
			'iconClass' => 'ui-icon intranet-automation-bp-icon',
			'menuData' => [
				'real_link' => $urls[0] ?? (SITE_DIR . 'company/personal/bizproc/'),
				'counter_id' => $counterId,
				'menu_item_id' => self::MENU_ITEMS_ID['bizproc_automation'],
				'top_menu_id' => 'top_menu_id_bizproc',
			],
			'tileData' => [
				'url' => SITE_DIR . 'company/personal/bizproc/',
			],
		];
	}

	public static function getBizProcSubMenu(): array
	{
		if (isset(self::$bpSubMenu))
		{
			return self::$bpSubMenu;
		}

		Loc::loadMessages(
			$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/bizproc/.left.menu_ext.php'
		);

		$userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		$counterId = self::getCounterCode();
		$counter = (int)\CUserCounter::getValue($userId, $counterId);
		$menu = [];

		$menu[] = [
			Loc::getMessage('MENU_USER_PROCESSES'),
			SITE_DIR . 'bizproc/userprocesses/',
			[],
			[
				'counter_id' => $counterId,
				'counter_num' => $counter,
				'menu_item_id' => 'menu_processes_and_tasks',
			],
		];

		if (Loader::includeModule('lists') && \CLists::isFeatureEnabled())
		{
			$menu[] = [
				Loc::getMessage('MENU_PROCESS_STREAM2'),
				SITE_DIR . 'bizproc/processes/',
				[],
				['menu_item_id' => 'menu_processes'],
				'',
			];
		}

		$menu[] = [
			Loc::getMessage('MENU_BIZPROC_ACTIVE'),
			SITE_DIR . 'bizproc/bizproc/',
			[],
			['menu_item_id' => 'menu_bizproc_active'],
			'',
		];

		if (ModuleManager::isModuleInstalled('crm'))
		{
			$menu[] = [
				Loc::getMessage('MENU_BIZPROC_CRM'),
				SITE_DIR . 'crm/configs/bp/',
				[],
				['menu_item_id' => 'menu_bizproc_crm'],
				'',
			];
		}

		if (Loader::includeModule('disk'))
		{
			$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByCommonId('shared_files_' . SITE_ID);
			if ($storage)
			{
				$menu[] = [
					Loc::getMessage('MENU_BIZPROC_DISK'),
					$storage->getProxyType()->getStorageBaseUrl(),
					[],
					['menu_item_id' => 'menu_bizproc_disk'],
					'',
				];
			}
		}

		self::$bpSubMenu = $menu;

		return self::$bpSubMenu;
	}

	public static function getSmartProcesses(): array
	{
		$available = Loader::includeModule('crm');
		if (!$available)
		{
			return [];
		}

		$router = Container::getInstance()->getRouter();

		$currentUser = CurrentUser::get();
		$userPermissions = Container::getInstance()->getUserPermissions($currentUser->getId());

		$menuSubItems = self::getAutomatedSolutionsMenuSubItems($router, $userPermissions);

		if (empty($menuSubItems))
		{
			return [];
		}

		return [
			'id' => 'crm-dynamic',
			'title' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_SUBTITLE_1'),
			'available' => true,
			'iconClass' => 'ui-icon intranet-automation-bp-icon',
			'menuData' => [
				'menu_item_id' => self::MENU_ITEMS_ID['smart_process'],
				'top_menu_id' => 'top_menu_id_crm_dynamic',
				'sub_menu' => $menuSubItems,
				'is_new' => true,
			],
		];
	}

	private static function getSmartProcessesMenuSubItems(
		Router $router,
		Crm\Service\UserPermissions $userPermissions,
	): array
	{
		$menuDefaultItems = [];
		$menuCustomSections = [];

		$customSections = Crm\Integration\IntranetManager::getCustomSections() ?? [];
		foreach ($customSections as $customSection)
		{
			$pageItems = [];

			$pages = $customSection->getPages();
			foreach ($pages as $page)
			{
				$pageSettings = $page->getSettings();
				$entityTypeId = Crm\Integration\IntranetManager::getEntityTypeIdByPageSettings($pageSettings);
				if (!$userPermissions->canReadType($entityTypeId))
				{
					continue;
				}

				$pageUrl = $router->getItemListUrlInCurrentView($entityTypeId);
				$pageItems[] = [
					'TEXT' => $page->getTitle(),
					'URL' => $pageUrl,
				];
			}
			if (!empty($pageItems))
			{
				$menuCustomSections[] = [
					'TEXT' => $customSection->getTitle(),
					'ITEMS' => $pageItems,
				];
			}
		}

		if ($userPermissions->canWriteConfig())
		{
			if (!empty($menuCustomSections))
			{
				$menuDefaultItems[] = [
					'IS_DELIMITER' => true,
				];
			}

			$externalTypeUrl = method_exists(Router::class, 'getExternalTypeListUrl')
				? $router->getExternalTypeListUrl()
				: $router->getTypeListUrl()
			;

			$menuDefaultItems[] = [
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_DEFAULT_SUBTITLE'),
				'URL' => $externalTypeUrl,
			];
		}

		return array_merge($menuCustomSections, $menuDefaultItems);
	}

	private static function getAutomatedSolutionsMenuSubItems(
		Router $router,
		Crm\Service\UserPermissions $userPermissions,
	): array
	{
		$container = Container::getInstance();
		$automatedSolutionManager = $container->getAutomatedSolutionManager();

		$menuItems = [];

		$canEditAutomatedSolutions =
			method_exists($userPermissions, 'canEditAutomatedSolutions')
			? $userPermissions->canEditAutomatedSolutions()
			: $userPermissions->canWriteConfig()
		;

		foreach ($automatedSolutionManager->getExistingAutomatedSolutions() as $automatedSolution)
		{
			$automatedSolutionSubItems = [];
			foreach ($automatedSolution['TYPE_IDS'] as $typeId)
			{
				$type = $container->getType($typeId);
				if ($userPermissions->canReadType($type->getEntityTypeId()))
				{
					$automatedSolutionSubItems[] = [
						'TEXT' => $type->getTitle(),
						'URL' => $router->getItemListUrlInCurrentView($type->getEntityTypeId()),
					];
				}
			}

			// user can read at least one type in the solution
			if (!empty($automatedSolutionSubItems))
			{
				if ($canEditAutomatedSolutions)
				{
					$automatedSolutionSubItems[] = [
						'IS_DELIMITER' => true,
					];

					$automatedSolutionSubItems[] = [
						'TEXT' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_DEFAULT_SUBTITLE'),
						'URL' => $router->getExternalTypeListUrl()->addParams([
							'AUTOMATED_SOLUTION' => $automatedSolution['ID'],
							'apply_filter' => 'Y',
						]),
					];
				}

				$menuItems[] = [
					'TEXT' => $automatedSolution['TITLE'],
					'ITEMS' => $automatedSolutionSubItems,
				];
			}
		}

		if ($canEditAutomatedSolutions)
		{
			if (!empty($menuItems))
			{
				$menuItems[] = [
					'IS_DELIMITER' => true,
				];
			}

			$menuItems[] = [
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_CRM_DYNAMIC_AUTOMATED_SOLUTION_LIST'),
				'URL' => $router->getAutomatedSolutionListUrl(),
				'IS_NEW' => true,
			];
		}

		return $menuItems;
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
				'counter_id' => self::MENU_ITEMS_ID['rpa'],
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
				'menu_item_id' => self::MENU_ITEMS_ID['robots'],
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
			$supportedTypeId = $elementTypeId;
			if ($elementTypeId === \CCrmOwnerType::Contact || $elementTypeId === \CCrmOwnerType::Company)
			{
				$supportedTypeId = \CCrmOwnerType::Deal;
			}

			if (Crm\Automation\Factory::isSupported($supportedTypeId))
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
				'menu_item_id' => self::MENU_ITEMS_ID['scripts'],
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
			if (Crm\Automation\Factory::isSupported($elementTypeId))
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
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_SIGN_SIGN_TITLE_MSGVER_1'),
				'URL' => '/sign/#robots',
			],
		];

		if (Loader::includeModule('crm'))
		{
			$router = Crm\Service\Container::getInstance()->getRouter();
			$items[] = [
				'TEXT' => Loc::getMessage('AUTOMATION_SECTION_SIGN_CRM_TITLE_MSGVER_1'),
				'URL' => $router->getItemListUrlInCurrentView(\CCrmOwnerType::Deal) . '#robots',
			];
		}

		return [
			'TEXT' => Loc::getMessage('AUTOMATION_SECTION_SIGN_ITEM_TITLE_MSGVER_1'),
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
				'menu_item_id' => self::MENU_ITEMS_ID['ai'],
			],
		];
	}

	public static function getBIBuilder(): array
	{
		if (
			Loader::includeModule('biconnector')
			&& class_exists('\Bitrix\BIConnector\Superset\Scope\ScopeService')
		)
		{
			/** @see \Bitrix\BIConnector\Superset\Scope\MenuItem\MenuItemCreatorBizproc::getMenuItemData */
			return ScopeService::getInstance()->prepareScopeMenuItem(ScopeService::BIC_SCOPE_BIZPROC);
		}

		return [];
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
				'menu_item_id' => self::MENU_ITEMS_ID['onec'],
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
				'menu_item_id' => self::MENU_ITEMS_ID['lists'],
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
			if (!empty($item['available']))
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

		$counterId = self::getCounterCode();

		return [
			Loc::getMessage('AUTOMATION_SECTION_ROOT_ITEM_TITLE'),
			static::getPath(),
			$extraUrls,
			[
				'menu_item_id' => 'menu_automation',
				'top_menu_id' => 'top_menu_id_automation',
				'counter_id' => $counterId,
				'first_item_url' => $firstItemUrl,
			],
			'',
		];
	}

	private static function getCounterCode(): string
	{
		if (Loader::includeModule('bizproc') && class_exists('\Bitrix\Bizproc\Workflow\WorkflowUserCounters'))
		{
			return self::WORKFLOW_COUNTER_CODE;
		}

		return self::TASK_COUNTER_CODE;
	}
}
