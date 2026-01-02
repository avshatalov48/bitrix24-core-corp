<?php

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\Intranet\Internal\Integration\Extranet\ExtranetService;
use Bitrix\Intranet\Settings\Tools\Automation;
use Bitrix\Intranet\Settings\Tools\BIConstructor;
use Bitrix\Intranet\Settings\Tools\Tasks;
use Bitrix\Intranet\Settings\Tools\TeamWork;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\BIConnector\Access;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class CollaborationSection
{
	private static ?array $allowedFeatures = null;
	private static ?TeamWork $teamWorkTools = null;
	private const CACHE_TTL = 604800;
	private const CACHE_PATH = '/bx/intranet/sections/collaboration2/';

	public static function getMenuData(): array
	{
		$result = [];
		$items = static::getItems();
		foreach ($items as $item)
		{
			$menuData = $item['menuData'] ?? [];
			$result[] = [
				$item['title'] ?? '',
				$item['url'] ?? '',
				$item['extraUrls'] ?? [],
				$menuData,
			];
		}

		return $result;
	}

	public static function getItems(): array
	{
		$items = static::getItemsInternal();

		$items = array_filter($items, static function ($item) {
			return $item['available'] ?? false;
		});

		$counters = \CUserCounter::GetValues(CurrentUser::get()->getId());
		self::setCounters($items, $counters);

		return $items;
	}

	private static function setCounters(&$items, $counters): void
	{
		foreach ($items as &$item)
		{
			if (isset($item['menuData']['counter_id']))
			{
				$item['menuData']['counter_num'] = $counters[$item['menuData']['counter_id']] ?? 0;
			}

			if (isset($item['COUNTER_ID']))
			{
				$item['COUNTER'] = $counters[$item['COUNTER_ID']] ?? 0;
			}

			if (isset($item['menuData']['sub_menu']) && is_array($item['menuData']['sub_menu']))
			{
				self::setCounters($item['menuData']['sub_menu'], $counters);
			}
		}
	}

	// This method is used to prepare menu items for the main.interface.buttons component.
	public static function getMenuItems(): array
	{
		$items = static::getItems();
		$result = [];

		foreach ($items as $item)
		{
			$menuData = $item['menuData'] ?? [];

			$result[] = [
				'ID' => $menuData['menu_item_id'] ?? $item['id'],
				'TEXT' => $item['title'],
				'URL' => $item['url'],
				'COUNTER_ID' => $menuData['counter_id'] ?? '',
				'COUNTER' => $menuData['counter_num'] ?? 0,
				'SUB_LINK' => $menuData['sub_link'] ?? '',
				'ITEMS' => empty($menuData['sub_menu']) ? [] : $menuData['sub_menu'],
				'ON_CLICK' => $item['onclick'] ?? '',
			];
		}

		return $result;
	}

	private static function getItemsInternal(): array
	{
		$userId = (int)CurrentUser::get()->getId();
		$cache = Cache::createInstance();
		$cacheId = md5($userId . LANGUAGE_ID . SITE_ID);
		$cachePath = static::CACHE_PATH . '/' . substr(md5($userId), 0, 2) . '/' . $userId . '/';

		if ($cache->initCache(static::CACHE_TTL, $cacheId, $cachePath))
		{
			$vars = $cache->getVars();
			if (is_array($vars['items']))
			{
				return $vars['items'];
			}
		}

		if (static::shouldShowNewStructure())
		{
			$items = [
				static::getMessenger(true),
				static::getTasks(),
				static::getBoards(),
				static::getNotifications(),
				static::getCalendar(true),
				static::getDisk(),
				static::getOnlineDocs(),
				static::getMail(),
				static::getBIConstructor(),
				static::getAutomation(),
				static::getFeed(),
				static::getWorkGroups(),
			];
		}
		else
		{
			$items = [
				static::getFeed(),
				static::getMessenger(),
				static::getCalendar(true),
				static::getOnlineDocs(),
				static::getBoards(),
				static::getDisk(),
				static::getMail(),
				static::getWorkGroups(),
			];
		}

		$cache->startDataCache();

		$taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
		$taggedCache->startTagCache($cachePath);
		$taggedCache->registerTag('bitrix24_left_menu');
		$taggedCache->endTagCache();

		$cache->endDataCache(['items' => $items]);

		return $items;
	}

	public static function shouldShowNewStructure(): bool
	{
		$shouldShowNewMenu = \Bitrix\Main\Config\Option::get('intranet', 'should_show_new_collaboration_menu', 'N') === 'Y';
		return $shouldShowNewMenu
			&& !(new ExtranetService())->isExtranet()
		;
	}

	public static function isFeatureEnabled(string $feature): bool
	{
		if (static::$allowedFeatures === null)
		{
			static::$allowedFeatures = [];
			if ($GLOBALS['USER']->isAuthorized() && Loader::includeModule('socialnetwork'))
			{
				$activeFeatures = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID());
				$socNetFeaturesSettings = \CSocNetAllowed::getAllowedFeatures();
				foreach (['tasks', 'files', 'photo', 'blog', 'calendar'] as $featureName)
				{
					static::$allowedFeatures[$featureName] = (
						array_key_exists($featureName, $socNetFeaturesSettings)
						&& array_key_exists('allowed', $socNetFeaturesSettings[$featureName])
						&& in_array(SONET_ENTITY_USER, $socNetFeaturesSettings[$featureName]['allowed'])
						&& is_array($activeFeatures)
						&& in_array($featureName, $activeFeatures)
					);
				}
			}
		}

		return static::$allowedFeatures[$feature] ?? false;
	}

	public static function isToolAvailable(string $toolId): bool
	{
		return ToolsManager::getInstance()->checkAvailabilityByToolId($toolId);
	}

	public static function isBitrix24Cloud(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	public static function isDiskEnabled(): bool
	{
		return
			ModuleManager::isModuleInstalled('disk')
			&& Option::get('disk', 'successfully_converted', false);
	}

	private static function getTitle(string $id): string
	{
		if (static::$teamWorkTools === null)
		{
			static::$teamWorkTools = new TeamWork();
		}

		return static::$teamWorkTools->getSubgroupNameById($id);
	}

	public static function getNotifications(): array
	{
		return [
			'id' => 'notification',
			'title' => self::getTitle('notification'),
			'available' => static::isToolAvailable('notification'),
			// for messenger
			'onclick' => "BX?.Messenger?.Public?.openNotifications();",
			'menuData' => [
				'menu_item_id' => 'menu_notifications',
				'onclick' => "BX?.Messenger?.Public?.openNotifications();",
				'counter_id' => 'notification',
			],
		];
	}

	public static function getAutomation()
	{
		$automation = new Automation();
		return [
			'id' => $automation->getId(),
			'title' => $automation->getName(),
			'available' => $automation->isAvailable(),
			'url' => $automation->getLeftMenuPath(),
			'menuData' => [
				'menu_item_id' => $automation->getMenuItemId(),
				'counter_id' => 'automation',
			],
		];

	}

	public static function getBIConstructor(): array
	{
		$biConstructor = new BIConstructor();
		return [
			'id' =>  $biConstructor->getId(),
			'title' => $biConstructor->getName(),
			'available' => $biConstructor->isAvailable()
				&& Loader::includeModule('biconnector')
				&& ToolsManager::getInstance()->checkAvailabilityByMenuId($biConstructor->getMenuItemId())
				&& Access\AccessController::getCurrent()->check(
					Access\ActionDictionary::ACTION_BIC_ACCESS
				),
			'url' => $biConstructor->getLeftMenuPath(),
			'menuData' => [
				'menu_item_id' =>  $biConstructor->getMenuItemId(),
				'counter_id' => 'report',
			],
		];
	}

	public static function getTasks()
	{
		$tasks = new Tasks();

		return [
			'id' => $tasks->getId(),
			'title' => $tasks->getName(),
			'available' => $tasks->isAvailable(),
			'url' => str_replace('#USER_ID#', CurrentUser::get()->getId(), $tasks->getLeftMenuPath()),
			'menuData' => [
				'menu_item_id' => $tasks->getMenuItemId(),
				'counter_id' => 'tasks_total',
			],
		];
	}

	public static function getFeed(): array
	{
		return [
			'id' => 'news',
			'title' => static::getTitle('news'),
			'available' => static::isToolAvailable('news'),
			'url' => SITE_DIR . 'stream/',
			'menuData' => [
				'menu_item_id' => 'menu_live_feed',
				'counter_id' => '**',
			],
			'extraUrls' => [
				SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/blog/',
			],
		];
	}

	public static function getMessenger(bool $includeSubMenu = false): array
	{
		$available = (
			static::isToolAvailable('instant_messenger')
			&& ModuleManager::isModuleInstalled('im')
			&& \CBXFeatures::isFeatureEnabled('WebMessenger')
		);

		$subMenu = [];

		if ($includeSubMenu)
		{
			foreach (ChatSection::getItems() as $item)
			{
				if ($item['id'] === 'notification') {
					continue;
				}
				if ($item['available'])
				{
					$menuData = $item['menuData'] ?? [];
					$subMenu[] = [
						'ID' => $menuData['menu_item_id'] ?? $item['id'],
						'TEXT' => $item['title'] ?? '',
						'URL' => $item['url'] ?? '',
						'ON_CLICK' => $item['onclick'] ?? '',
						'COUNTER' => $menuData['counter_num'] ?? '',
						'COUNTER_ID' => $menuData['counter_id'] ?? '',
					];
				}
			}
		}

		$menuData = [
			'menu_item_id' => 'menu_im_messenger',
			'counter_id' => static::shouldShowNewStructure() ? '' : 'im-message',
			'my_tools_section' => true,
			'can_be_first_item' => defined('AIR_SITE_TEMPLATE'),
		];

		if (static::shouldShowNewStructure()) {
			$menuData['sub_menu'] = $subMenu;
		}

		return [
			'id' => 'instant_messenger',
			'title' => static::getTitle('instant_messenger'),
			'available' => $available,
			'url' => SITE_DIR . 'online/',
			'menuData' => $menuData,
		];
	}

	public static function getCalendar(bool $includeSubMenu = false): array
	{
		$available = static::isToolAvailable('calendar') && ModuleManager::isModuleInstalled('calendar');
		if (!static::isBitrix24Cloud())
		{
			$available = (
				$available
				&& static::isFeatureEnabled('calendar')
				&& (\CBXFeatures::isFeatureEnabled('Calendar') || \CBXFeatures::isFeatureEnabled('CompanyCalendar'))
			);
		}

		$subMenu = [];
		if ($includeSubMenu)
		{
			foreach (CalendarSection::getItems() as $item)
			{
				if ($item['available'])
				{
					$menuData = $item['menuData'] ?? [];
					$subMenu[] = [
						'ID' => $menuData['menu_item_id'] ?? $item['id'],
						'TEXT' => $item['title'],
						'URL' => $item['url'],
						'COUNTER' => $menuData['counter_num'] ?? '',
						'COUNTER_ID' => $menuData['counter_id'] ?? '',
					];
				}
			}
		}

		$url = SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/calendar/';

		return [
			'id' => 'calendar',
			'title' => static::getTitle('calendar'),
			'available' => $available,
			'url' => $url,
			'menuData' => [
				'menu_item_id' => 'menu_calendar',
				// 'counter_id' => 'calendar',
				'sub_link' => $url . '?EVENT_ID=NEW',
				'sub_menu' => $subMenu,
			],
		];
	}

	public static function getOnlineDocs(): array
	{
		$available = (
			static::isToolAvailable('docs')
			&& static::isDiskEnabled()
			&& Option::get('disk', 'documents_enabled', 'N') === 'Y'
		);

		return [
			'id' => 'docs',
			'title' => static::getTitle('docs'),
			'available' => $available,
			'url' => SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/disk/documents/',
			'menuData' => [
				'menu_item_id' => 'menu_documents',
			],
		];
	}

	public static function getBoards(): array
	{
		$available = (
			static::isToolAvailable('boards')
			&& static::isDiskEnabled()
			&& Option::get('disk', 'boards_enabled', 'N') === 'Y'
		);

		return [
			'id' => 'boards',
			'title' => static::getTitle('boards'),
			'available' => $available,
			'url' => SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/disk/boards/',
			'menuData' => [
				'menu_item_id' => 'menu_boards',
			],
		];
	}

	public static function getDisk(): array
	{
		$available = static::isToolAvailable('disk');
		if (!static::isBitrix24Cloud())
		{
			$available = (
				ModuleManager::isModuleInstalled('disk')
				&& static::isFeatureEnabled('files')
				&& (\CBXFeatures::IsFeatureEnabled('PersonalFiles') || \CBXFeatures::IsFeatureEnabled('CommonDocuments'))
			);
		}

		$url = (
		static::isDiskEnabled()
			? SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/disk/path/'
			: SITE_DIR . 'company/personal/user/' . CurrentUser::get()->getId() . '/files/lib/'
		);

		return [
			'id' => 'disk',
			'title' => static::getTitle('disk'),
			'available' => $available,
			'url' => SITE_DIR . 'docs/',
			'menuData' => [
				'real_link' => $url,
				'menu_item_id' => 'menu_files',
			],
		];
	}

	public static function getMail(): array
	{
		$available = (
			static::isToolAvailable('mail')
			&& Loader::includeModule('intranet')
			&& \CIntranetUtils::isExternalMailAvailable()
		);

		return [
			'id' => 'mail',
			'title' => static::getTitle('mail'),
			'available' => $available,
			'url' => Option::get('intranet', 'path_mail_client', SITE_DIR . 'mail/'),
			'menuData' => [
				'counter_id' => 'mail_unseen',
				'menu_item_id' => 'menu_external_mail',
			],
		];
	}

	public static function getWorkGroups(): array
	{
		$available = (
			static::isToolAvailable('workgroups')
			&& ModuleManager::isModuleInstalled('socialnetwork')
			&& \CBXFeatures::IsFeatureEnabled('Workgroups')
		);

		return [
			'id' => 'workgroups',
			'title' => static::getTitle('workgroups'),
			'available' => $available,
			'url' => SITE_DIR . 'workgroups/',
			'menuData' => [
				'menu_item_id' => 'menu_all_groups',
			],
		];
	}
}
