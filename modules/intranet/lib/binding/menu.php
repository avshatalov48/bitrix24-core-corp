<?php
namespace Bitrix\Intranet\Binding;

use Bitrix\Intranet\Binding\Map\MapItem;
use Bitrix\Intranet\Binding\Map\MapSection;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Menu
 *
 * The main purpose of this class is to prepare params for placement/bindings button, which is often rendered
 * by intranet.binding.menu component. The button is rendered with BX.PopupMenu. Therefore, structure of arrays here is
 * highly influenced by structure of js-object that should be passed to BX.PopupMenu to render multi-layer menu.
 *
 * To put it simply, this class prepares associative arrays that should be converted to js-objects and passed to
 * BX.PopupMenu. And when an array with name 'items' is mentioned here, almost certainly it is js-object-like array from
 * BX.PopupMenu.
 *
 */
class Menu
{
	/**
	 * Allowed menu sections.
	 */
	public const SECTIONS = [
		'knowledge' => 'knowledge',
		'script' => 'script',
		'marketplace' => 'marketplace',
		'other' => 'other'
	];

	/**
	 * User option code for logging opened menu items.
	 */
	public const USER_OPTION_LOG_CODE = 'binding_menu';

	/**
	 * @deprecated Use the method instead
	 * @see Menu::getRestPlacementMap
	 */
	public const REST_PLACEMENT_MAP = [];

	/**
	 * During building menu items will be set to true for specific links.
	 * @var bool
	 */
	protected static $needProvider = false;

	/** @var Map */
	protected static $mapObject;

	protected static function getMapObject(): Map
	{
		if (is_null(static::$mapObject))
		{
			$hardCodedMap = static::getHardCodedMapObject();
			$mapFromEvent = static::getMapObjectFromEvent();

			static::$mapObject = $hardCodedMap->merge($mapFromEvent);
		}

		return static::$mapObject;
	}

	protected static function getHardCodedMapObject(): Map
	{
		return new Map([
			(new MapSection('user', 'top_panel'))
				->add(new MapItem('user_menu', 'USER_PROFILE_MENU'))
			,
			(new MapSection('user', 'user_detail'))
				->add(new MapItem('top_menu', 'USER_PROFILE_TOOLBAR')),
			(new MapSection('user_brief', 'top_panel'))
				->add(new MapItem('user_menu', 'USER_PROFILE_MENU')),
			(new MapSection('user_brief', 'user_detail'))
				->add(new MapItem('top_menu', 'USER_PROFILE_TOOLBAR')),
			(new MapSection('user_basic', 'top_panel'))
				->add(new MapItem('user_menu', 'USER_PROFILE_MENU')),
			(new MapSection('user_basic', 'user_detail'))
				->add(new MapItem('top_menu', 'USER_PROFILE_TOOLBAR')),
			(new MapSection('crm', 'crm_switcher'))
				->add(new MapItem('deal', 'CRM_DEAL_LIST_TOOLBAR'))
				->add(new MapItem('lead', 'CRM_LEAD_LIST_TOOLBAR'))
				->add(new MapItem('contact', 'CRM_CONTACT_LIST_TOOLBAR'))
				->add(new MapItem('company', 'CRM_COMPANY_LIST_TOOLBAR'))
				->add(new MapItem('invoice', 'CRM_INVOICE_LIST_TOOLBAR'))
				->add(new MapItem('quote', 'CRM_QUOTE_LIST_TOOLBAR'))
				->add(new MapItem('order', 'CRM_ORDER_LIST_TOOLBAR'))
			,
			(new MapSection('crm', 'crm_detail'))
				->add(new MapItem('deal', 'CRM_DEAL_DETAIL_TOOLBAR'))
				->add(new MapItem('lead', 'CRM_LEAD_DETAIL_TOOLBAR'))
				->add(new MapItem('contact', 'CRM_CONTACT_DETAIL_TOOLBAR'))
				->add(new MapItem('company', 'CRM_COMPANY_DETAIL_TOOLBAR'))
				->add(new MapItem('invoice', 'CRM_INVOICE_DETAIL_TOOLBAR'))
				->add(new MapItem('quote', 'CRM_QUOTE_DETAIL_TOOLBAR'))
			,
			(new MapSection('crm', 'crm_timeline'))
				->add(new MapItem('deal', 'CRM_DEAL_ACTIVITY_TIMELINE_MENU'))
				->add(new MapItem('lead', 'CRM_LEAD_ACTIVITY_TIMELINE_MENU'))
				->add(new MapItem('quote', 'CRM_QUOTE_ACTIVITY_TIMELINE_MENU'))
				// ->add(new MapItem('contact', 'CRM_CONTACT_ACTIVITY_TIMELINE_MENU'))
				// ->add(new MapItem('company', 'CRM_COMPANY_ACTIVITY_TIMELINE_MENU'))
				// ->add(new MapItem('invoice', 'CRM_INVOICE_ACTIVITY_TIMELINE_MENU'))
			,
			(new MapSection('crm', 'crm_documents'))
				->add(new MapItem('deal', 'CRM_DEAL_DOCUMENTGENERATOR_BUTTON'))
				->add(new MapItem('lead', 'CRM_LEAD_DOCUMENTGENERATOR_BUTTON'))
				->add(new MapItem('contact', 'CRM_CONTACT_DOCUMENTGENERATOR_BUTTON'))
				->add(new MapItem('company', 'CRM_COMPANY_DOCUMENTGENERATOR_BUTTON'))
				->add(new MapItem('invoice', 'CRM_INVOICE_DOCUMENTGENERATOR_BUTTON'))
				->add(new MapItem('quote', 'CRM_QUOTE_DOCUMENTGENERATOR_BUTTON'))
			,
			(new MapSection('crm', 'crm_analytics'))
				->add(new MapItem('config', 'CRM_ANALYTICS_TOOLBAR'))
			,
			(new MapSection('crm', 'crm_tunnels'))
				->add(new MapItem('deal', 'CRM_FUNNELS_TOOLBAR'))
			,
			(new MapSection('crm', 'bizproc_automation'))
				->add(new MapItem('lead', 'CRM_LEAD_ROBOT_DESIGNER_TOOLBAR'))
				->add(new MapItem('deal', 'CRM_DEAL_ROBOT_DESIGNER_TOOLBAR'))
			,
			(new MapSection('task', 'bizproc_automation'))
				->add(new MapItem('task', 'TASK_ROBOT_DESIGNER_TOOLBAR'))
			,
			(new MapSection('task', 'tasks_switcher'))
				->add(new MapItem('user', 'TASK_USER_LIST_TOOLBAR'))
				->add(new MapItem('group', 'TASK_GROUP_LIST_TOOLBAR'))
			,
			(new MapSection('sonet_group', 'socialnetwork'))
				->add(new MapItem('group_notifications', 'SONET_GROUP_TOOLBAR'))
			,
		]);
	}

	protected static function getMapObjectFromEvent(): Map
	{
		$event = new Event('intranet', 'onBuildBindingMap');
		$event->send();

		$mapFromEvent = new Map();
		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === EventResult::ERROR)
			{
				continue;
			}

			$resultParameters = $eventResult->getParameters();
			if (!is_array($resultParameters))
			{
				continue;
			}

			$receivedMap = $resultParameters['map'] ?? null;
			if (!($receivedMap instanceof Map))
			{
				continue;
			}

			$mapFromEvent = $mapFromEvent->merge($receivedMap);
		}

		return $mapFromEvent;
	}

	/**
	 * Gets maps of binding places.
	 * @return array
	 *
	 * Example of returned array structure:
	 * <pre><code>
	 * $result = [
	 * 	'crm_switcher' => [
	 * 		'items' => [
	 * 			'deal' => [],
	 * 			'lead' => [],
	 * 		],
	 * 	],
	 * 	'bizproc_automation' => [
	 * 		'items' => [
	 * 			'deal' => [],
	 * 			'lead' => [],
	 * 			// this item is from another scope, but from the section with the same code
	 * 			'task' => [],
	 * 		],
	 * 	],
	 * ];
	 * </code></pre>
	 */
	public static function getMap()
	{
		$result = [];
		foreach (static::getMapObject()->getSections() as $section)
		{
			$itemsInArrayForm = [];
			foreach ($section->getItems() as $item)
			{
				$itemsInArrayForm[$item->getCode()] = [];
			}

			$alreadyAddedItems = $result[$section->getCode()]['items'] ?? null;
			if (is_array($alreadyAddedItems))
			{
				$itemsInArrayForm = array_merge($alreadyAddedItems, $itemsInArrayForm);
			}

			$result[$section->getCode()] = [
				'items' => $itemsInArrayForm,
			];
		}

		return $result;
	}

	/**
	 * Returns a map where keys are rest placement codes and values - intranet binding menu codes
	 * @return array
	 *
	 * Example of returned array structure:
	 * <pre><code>
	 * $result = [
	 * 	'CRM_DEAL_LIST_TOOLBAR' => 'CRM_SWITCHER@DEAL',
	 * 	'CRM_DEAL_DETAIL_TOOLBAR' => 'CRM_DETAIL@DEAL',
	 * 	'USER_PROFILE_MENU' => 'TOP_PANEL@USER_MENU',
	 * ];
	 * </code></pre>
	 */
	public static function getRestPlacementMap(): array
	{
		$result = [];
		foreach (static::getMapObject()->getSections() as $section)
		{
			foreach ($section->getItems() as $item)
			{
				$result[static::getRestPlacementCode($section, $item)] = static::getBindingMenuCode($section, $item);
			}
		}

		return $result;
	}

	protected static function getRestPlacementCode(MapSection $section, MapItem $item): string
	{
		if (!is_null($item->getCustomRestPlacementCode()))
		{
			return $item->getCustomRestPlacementCode();
		}

		$scope = mb_strtoupper($section->getScope());
		$sectionCode = mb_strtoupper($section->getCode());
		$itemCode = mb_strtoupper($item->getCode());

		return "{$scope}_{$sectionCode}_{$itemCode}";
	}

	protected static function getBindingMenuCode(MapSection $section, MapItem $item): string
	{
		$sectionCode = mb_strtoupper($section->getCode());
		$itemCode = mb_strtoupper($item->getCode());

		return ($sectionCode . '@' . $itemCode);
	}

	/**
	 * Returns current binding map for REST. Contains rest placement codes divided in groups by their scope
	 * @return array
	 *
	 * Example of returned array structure:
	 * <pre><code>
	 * $result = [
	 * 	'crm' => [
	 * 		'CRM_DEAL_LIST_TOOLBAR' => [],
	 * 		'CRM_LEAD_LIST_TOOLBAR' => [],
	 * 		'CRM_DEAL_ROBOT_DESIGNER_TOOLBAR' => [],
	 * 		'CRM_LEAD_ROBOT_DESIGNER_TOOLBAR' => [],
	 * 	],
	 * 	'task' => [
	 * 		'TASK_ROBOT_DESIGNER_TOOLBAR' => [],
	 * 	],
	 * ];
	 * </code></pre>
	 */
	public static function getRestMap(): array
	{
		$map = [];

		foreach (static::getMapObject()->getSections() as $section)
		{
			$restPlacements = [];
			foreach ($section->getItems() as $item)
			{
				$restPlacements[static::getRestPlacementCode($section, $item)] = [];
			}

			$otherPlacementsInScope = $map[$section->getScope()] ?? null;
			if (is_array($otherPlacementsInScope))
			{
				$restPlacements = array_merge($otherPlacementsInScope, $restPlacements);
			}

			$map[$section->getScope()] = $restPlacements;
		}

		return $map;
	}

	/**
	 * Returns current user id, or 0 for guest.
	 * @return int
	 */
	protected static function getUserId(): int
	{
		return (int)$GLOBALS['USER']->getId();
	}

	/**
	 * Returns most frequency (by clicking) menu item id.
	 * @param string $bindingId Binding id (ex: 'section:item').
	 * @return string
	 */
	public static function getFrequencyMenuItemId($bindingId): ?string
	{
		$options = \CUserOptions::getOption('intranet', self::USER_OPTION_LOG_CODE);
		if (isset($options[$bindingId]) && is_array($options[$bindingId]))
		{
			$optionsFlip = array_flip($options[$bindingId]);
			return array_shift($optionsFlip);
		}

		return null;
	}

	/**
	 * Processing menu item click (logs frequency user clicks).
	 * @param string $bindingId Binding id (ex: 'section:item').
	 * @param string $menuItemId Menu item id.
	 * @return void
	 */
	public static function processMenuItemHit($bindingId, $menuItemId): void
	{
		if (self::getUserId() && is_string($bindingId) && is_string($menuItemId))
		{
			if(mb_strpos($bindingId, ':'))
			{
				[$sectionCode, $menuCode] = explode(':', $bindingId);
				$items = self::getMenuItems($sectionCode, $menuCode);
				if($items)
				{
					$itemsIds = self::getMenuItemsIds($items);
					$options = \CUserOptions::getOption('intranet', self::USER_OPTION_LOG_CODE);
					// increment use count by specific id
					if(!is_array($options))
					{
						$options = [];
					}
					if(!isset($options[$bindingId]))
					{
						$options[$bindingId] = [];
					}
					if(!isset($options[$bindingId][$menuItemId]))
					{
						$options[$bindingId][$menuItemId] = 0;
					}
					$options[$bindingId][$menuItemId] = (int)$options[$bindingId][$menuItemId];
					$options[$bindingId][$menuItemId]++;
					// validate, sort, save
					$options[$bindingId] = array_intersect_key($options[$bindingId], array_flip($itemsIds));
					asort($options[$bindingId], SORT_NUMERIC);
					$options[$bindingId] = array_reverse($options[$bindingId], true);
					\CUserOptions::setOption('intranet', self::USER_OPTION_LOG_CODE, $options);
				}
			}
		}
	}

	/**
	 * Validates raw data and returns items for menu.
	 *
	 * Items is the data structure that is used in multi-layer BX.PopupMenu
	 *
	 * @param array $items Raw data.
	 * @return array
	 */
	protected static function getValidItems($items)
	{
		$validData = [];

		if (!is_array($items))
		{
			return $validData;
		}

		// recursive validate
		foreach ($items as &$item)
		{
			if (isset($item['delimiter']))
			{
				$validData[] = [
					'delimiter' => true,
					'sort' => 100
				];
			}
			else if (
				isset($item['id']) &&
				is_string($item['id']) &&
				isset($item['text']) &&
				is_string($item['text']) &&
				(
					!isset($item['href'])
					||
					isset($item['href']) &&
					is_string($item['href'])
				)
			)
			{
				if (isset($item['items']))
				{
					$item['items'] = self::getValidItems($item['items']);
				}
				$item['sort'] = isset($item['sort'])
								? (int) $item['sort']
								: 100;
				$item['system'] = isset($item['system'])
								? (bool) $item['system']
								: false;
				$item['sectionCode'] = isset($item['sectionCode'])
								? (string) $item['sectionCode']
								: self::SECTIONS['other'];
				$validData[] = $item;
			}
		}
		unset($item);

		// sort items
		uasort($validData, function($a, $b)
		{
			if ($a['sort'] == $b['sort'])
			{
				return 0;
			}
			return ($a['sort'] > $b['sort']) ? 1 : -1;
		});

		return array_values($validData);
	}

	/**
	 * Validates raw data and returns items for bindings.
	 * @param array $bindings Row data.
	 * @return array
	 */
	protected static function getValidBindings($bindings)
	{
		static $map = [];
		$validData = [];

		if (!is_array($bindings))
		{
			return $validData;
		}

		if (!$map)
		{
			$map = self::getMap();
		}

		foreach ($bindings as $code => $binding)
		{
			if (isset($map[$code]))
			{
				$validData[$code] = [
					'include' => isset($binding['include'])
								? (array) $binding['include']
								: [],
					'exclude' => isset($binding['exclude'])
								? (array) $binding['exclude']
								: []
				];
			}
		}

		return $validData;
	}

	/**
	 * Builds and returns bindings by external handlers.
	 * @return array
	 */
	protected static function getBindings()
	{
		$map = self::getMap();

		// local bindings, from another modules
		$event = new Event('intranet', 'onBuildBindingMenu', []);
		$event->send();
		foreach ($event->getResults() as $result)
		{
			if ($result->getType() != EventResult::ERROR)
			{
				$places = $result->getParameters();
				if (is_array($places))
				{
					foreach ($places as $place)
					{
						// validate necessary keys
						if (
							!isset($place['items']) ||
							!isset($place['bindings'])
						)
						{
							continue;
						}
						$items = self::getValidItems($place['items']);
						$bindings = self::getValidBindings($place['bindings']);
						// fill map with items
						if ($items && $bindings)
						{
							foreach ($bindings as $bindingCode => $binding)
							{
								foreach ($map[$bindingCode]['items'] as $itemsCode => &$menu)
								{
									if (
										(
											!$binding['include'] ||
											in_array($itemsCode, $binding['include'])
										)
										&&
										(
											!in_array($itemsCode, $binding['exclude'])
										)
									)
									{
										$menu = array_merge($menu, $items);
									}
								}
								unset($menu);
							}
						}
					}
				}
			}
		}

		// rest bindings
		if (\Bitrix\Main\Loader::includeModule('rest'))
		{
			$menuSectionCode = self::SECTIONS['marketplace'];
			$groups = [];// array for group items position storage
			$res = \Bitrix\Rest\PlacementTable::getList([
				'select' => [
					'ID',
					'APP_ID',
					'PLACEMENT',
					'TITLE',
					'APP_NAME' => 'REST_APP.APP_NAME',
					'GROUP_NAME',
					'LANG_ALL',
				],
				'filter' => [
					'=PLACEMENT' => array_keys(static::getRestPlacementMap())
				],
				'order' => [
					'ID' => 'desc'
				]
			]);
			foreach ($res->fetchCollection() as $row)
			{
				if (!isset(static::getRestPlacementMap()[$row->getPlacement()]))
				{
					continue;
				}

				$placementLang = [];
				$placementLangAll = [];
				if (!is_null($row->getLangAll()))
				{
					foreach ($row->getLangAll() as $lang)
					{
						if (trim($lang->getTitle()))
						{
							$placementLangAll[$lang->getLanguageId()] = [
								'TITLE' => trim($lang->getTitle()),
								'GROUP_NAME' => $lang->getGroupName(),
							];
						}
					}
				}
				if (!empty($placementLangAll))
				{
					$langList = \Bitrix\Rest\Lang::listLanguage();
					foreach ($langList as $lang)
					{
						if ($placementLangAll[$lang])
						{
							$placementLang = $placementLangAll[$lang];
							break;
						}
					}
					if (!$placementLang['TITLE'])
					{
						$placementLang = reset($placementLangAll);
					}
				}
				elseif ($row->getRestApp())
				{
					$placementLang['TITLE'] = $row->getRestApp()->getAppName();
				}

				if (!trim($placementLang['TITLE']))
				{
					$placementLang['TITLE'] = \Bitrix\Rest\PlacementTable::getDefaultTitle($row->getId());
				}

				[$bindingCode, $menuCode] = explode('@', static::getRestPlacementMap()[$row->getPlacement()]);
				$bindingCode = mb_strtolower($bindingCode);
				$menuCode = mb_strtolower($menuCode);
				if (isset($map[$bindingCode]['items'][$menuCode]))
				{
					self::$needProvider = true;
					$oneItem = [
						'id' => 'rest_' . $row->getId(),
						'system' => false,
						'text' => htmlspecialcharsbx($placementLang['TITLE']),
						'sort' => 500,
						'sectionCode' => $menuSectionCode,
						'linkProvider' => 'marketplace',
						'params' => [
							'app_id' => $row->getAppId(),
							'placement_id' => $row->getId(),
							'placement' => $row->getPlacement(),
						]
					];
					if ($placementLang['GROUP_NAME'])
					{
						$groupingKey = $row->getPlacement() . '_' . $placementLang['GROUP_NAME'];
						if (isset($groups[$groupingKey]))
						{
							$i = $groups[$groupingKey];
							$map[$bindingCode]['items'][$menuCode][$i]['items'][] = $oneItem;
						}
						else
						{
							$map[$bindingCode]['items'][$menuCode][] = [
								'text' => $placementLang['GROUP_NAME'],
								'sort' => 500,
								'sectionCode' => $menuSectionCode,
								'items' => [
									$oneItem
								]
							];
							$groups[$groupingKey] = count($map[$bindingCode]['items'][$menuCode]) - 1;
						}
					}
					else
					{
						$map[$bindingCode]['items'][$menuCode][] = $oneItem;
					}
				}
			}
		}

		return $map;
	}

	/**
	 * Gets available menu items ids by code.
	 * @param array $items Items array (result of getMenuItems method).
	 * @return array
	 */
	protected static function getMenuItemsIds(array $items)
	{
		$ids = [];
		foreach ($items as $item)
		{
			if (isset($item['items']) && $item['items'])
			{
				$ids = array_merge(
					$ids,
					self::getMenuItemsIds($item['items'])
				);
			}
			else if ($item['id'])
			{
				$ids[] = $item['id'];
			}
		}

		return $ids;
	}

	/**
	 * Sets the context for the items.
	 * @param array $items Items array.
	 * @param array $context Context data.
	 * @return array
	 */
	protected static function setItemsProvider(array $items, array $context = []): array
	{
		foreach ($items as &$item)
		{
			if (isset($item['items']) && $item['items'])
			{
				$item['items'] = self::setItemsProvider(
					$item['items'], $context
				);
			}
			else if (isset($item['linkProvider']))
			{
				$item = LinkProvider::provide($item, $context);
			}
		}
		unset($item);

		return $items;
	}

	/**
	 * Gets available menu items by code.
	 *
	 * Item is an associative array that represents js-object that should be passed to BX.PopupMenu to render
	 * multi-layer menu.
	 * And items is an array of these arrays.
	 * Item could be recursive and contain other items to render a nested menu section.
	 *
	 * @param string $sectionCode Section code.
	 * @param string $menuCode Item code.
	 * @param array $params Additional params:
	 * - context Query context, mixed
	 * - inline Inline mode, boolean, false by default
	 * @return array[]
	 */
	public static function getMenuItems($sectionCode, $menuCode, array $params = [])
	{
		static $bindings = [];
		static $extLoaded = [];

		$inline = (isset($params['inline']) && $params['inline'] === true);
		$context = (isset($params['context']) && is_array($params['context']))
					? $params['context'] : [];

		if (!$bindings)
		{
			$cache = new \CPHPCache;
			$cacheManager = $GLOBALS['CACHE_MANAGER'];
			$cacheTag = 'intranet_menu_binding';
			$cacheKey = $cacheTag . '_' . LANGUAGE_ID;
			$cacheDir = '/intranet/menu_binding';
			if ($cache->initCache(8640000, $cacheKey, $cacheDir))
			{
				[$bindings, self::$needProvider] = $cache->getVars();
			}
			else
			{
				$cache->startDataCache();
				$cacheManager->startTagCache($cacheDir);
				$cacheManager->registerTag($cacheTag);
				$bindings = self::getBindings();
				$cacheManager->endTagCache();
				$cache->endDataCache([$bindings, self::$needProvider]);
			}
		}

		if (is_string($sectionCode) && is_string($menuCode))
		{
			$sectionCode = mb_strtolower($sectionCode);
			$menuCode = mb_strtolower($menuCode);
			if (isset($bindings[$sectionCode]['items'][$menuCode]))
			{
				// 'group' by sections
				$returnItems = [];
				$sections = self::SECTIONS;
				foreach ($sections as $globalSectionCode)
				{
					if ($globalSectionCode == $sections['other'])
					{
						if ($returnItems)
						{
							$returnItems[] = [
								'delimiter' => true
							];
						}
					}
					else
					{
						$returnItems[] = [
							'text' => Loc::getMessage('INTRANET_BIND_MENU_SECTION_' . mb_strtoupper($globalSectionCode)),
							'delimiter' => true
						];
					}
					$existItems = false;
					foreach ($bindings[$sectionCode]['items'][$menuCode] as $item)
					{
						if (isset($item['extension']))
						{
							if (!isset($extLoaded[$item['extension']]))
							{
								$extLoaded[$item['extension']] = true;
								\Bitrix\Main\UI\Extension::load(
									$item['extension']
								);
							}
						}
						if ($item['sectionCode'] == $globalSectionCode)
						{
							$existItems = true;
							$returnItems[] = $item;
						}
					}
					if (!$existItems)
					{
						array_pop($returnItems);
					}
				}
				// for rest items wee need rebuild for correct links
				if (self::$needProvider)
				{
					$returnItems = self::setItemsProvider($returnItems, $context);
				}
				// marketplace item
				$marketCode = mb_strtoupper($sectionCode . '@' . $menuCode);
				$placementMap = array_flip(static::getRestPlacementMap());
				if (isset($placementMap[$marketCode]))
				{
					if ($returnItems)
					{
						$returnItems[] = [
							'delimiter' => true
						];
					}
					$returnItems[] = [
						'href' => Marketplace::getMainDirectory() . '?placement=' . $placementMap[$marketCode],
						'text' => Loc::getMessage('INTRANET_BIND_MENU_APPS_2')
					];
				}
				if ($inline)
				{
					return [
						'text' => Loc::getMessage('INTRANET_BIND_MENU_TITLE'),
						'items' => $returnItems
					];
				}
				return $returnItems;
			}
		}

		return [];
	}
}
