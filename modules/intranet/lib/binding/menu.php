<?php
namespace Bitrix\Intranet\Binding;

use \Bitrix\Main\Event;
use \Bitrix\Main\EventResult;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Menu
{
	/**
	 * Allowed menu sections.
	 */
	const SECTIONS = [
		'knowledge' => 'knowledge',
		'script' => 'script',
		'marketplace' => 'marketplace',
		'other' => 'other'
	];

	/**
	 * User option code for logging opened menu items.
	 */
	const USER_OPTION_LOG_CODE = 'binding_menu';

	/**
	 * Placements codes for REST.
	 */
	const REST_PLACEMENT_MAP = [
		// crm_switcher
		'CRM_DEAL_LIST_TOOLBAR' => 'CRM_SWITCHER@DEAL',
		'CRM_LEAD_LIST_TOOLBAR' => 'CRM_SWITCHER@LEAD',
		'CRM_CONTACT_LIST_TOOLBAR' => 'CRM_SWITCHER@CONTACT',
		'CRM_COMPANY_LIST_TOOLBAR' => 'CRM_SWITCHER@COMPANY',
		'CRM_INVOICE_LIST_TOOLBAR' => 'CRM_SWITCHER@INVOICE',
		'CRM_QUOTE_LIST_TOOLBAR' => 'CRM_SWITCHER@QUOTE',
		'TASK_USER_LIST_TOOLBAR' => 'TASKS_SWITCHER@USER',
		'TASK_GROUP_LIST_TOOLBAR' => 'TASKS_SWITCHER@GROUP',
		// crm_detail
		'CRM_DEAL_DETAIL_TOOLBAR' => 'CRM_DETAIL@DEAL',
		'CRM_LEAD_DETAIL_TOOLBAR' => 'CRM_DETAIL@LEAD',
		'CRM_CONTACT_DETAIL_TOOLBAR' => 'CRM_DETAIL@CONTACT',
		'CRM_COMPANY_DETAIL_TOOLBAR' => 'CRM_DETAIL@COMPANY',
		'CRM_INVOICE_DETAIL_TOOLBAR' => 'CRM_DETAIL@INVOICE',
		'CRM_QUOTE_DETAIL_TOOLBAR' => 'CRM_DETAIL@QUOTE',
		// crm_documents
		'CRM_DEAL_DOCUMENTGENERATOR_BUTTON' => 'CRM_DOCUMENTS@DEAL',
		'CRM_LEAD_DOCUMENTGENERATOR_BUTTON' => 'CRM_DOCUMENTS@LEAD',
		'CRM_CONTACT_DOCUMENTGENERATOR_BUTTON' => 'CRM_DOCUMENTS@CONTACT',
		'CRM_COMPANY_DOCUMENTGENERATOR_BUTTON' => 'CRM_DOCUMENTS@COMPANY',
		'CRM_INVOICE_DOCUMENTGENERATOR_BUTTON' => 'CRM_DOCUMENTS@INVOICE',
		'CRM_QUOTE_DOCUMENTGENERATOR_BUTTON' => 'CRM_DOCUMENTS@QUOTE',
		// crm_timeline
		'CRM_DEAL_ACTIVITY_TIMELINE_MENU' => 'CRM_TIMELINE@DEAL',
		'CRM_LEAD_ACTIVITY_TIMELINE_MENU' => 'CRM_TIMELINE@LEAD',
		// automation
		'CRM_LEAD_ROBOT_DESIGNER_TOOLBAR' => 'BIZPROC_AUTOMATION@LEAD',
		'CRM_DEAL_ROBOT_DESIGNER_TOOLBAR' => 'BIZPROC_AUTOMATION@DEAL',
		'TASK_ROBOT_DESIGNER_TOOLBAR' => 'BIZPROC_AUTOMATION@TASK',
		// other
		'CRM_ANALYTICS_TOOLBAR' => 'CRM_ANALYTICS@CONFIG',
		'CRM_FUNNELS_TOOLBAR' => 'CRM_TUNNELS@DEAL',
		'SONET_GROUP_TOOLBAR' => 'SOCIALNETWORK@GROUP_NOTIFICATIONS',
		'USER_PROFILE_MENU' => 'TOP_PANEL@USER_MENU',
		'USER_PROFILE_TOOLBAR' => 'USER_DETAIL@TOP_MENU'
	];

	/**
	 * During building menu items will be set to true for specific links.
	 * @var bool
	 */
	protected static $needProvider = false;

	/**
	 * Gets maps of binding places.
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'top_panel' => [
				'items' => [
					'user_menu' => []
				]
			],
			'user_detail' => [
				'items' => [
					'top_menu' => []
				]
			],
			'crm_switcher' => [
				'items' => [
					'deal' => [],
					'lead' => [],
					'contact' => [],
					'company' => [],
					'invoice' => [],
					'quote' => []
				]
			],
			'crm_detail' => [
				'items' => [
					'deal' => [],
					'lead' => [],
					'contact' => [],
					'company' => [],
					'invoice' => [],
					'quote' => []
				]
			],
			'crm_timeline' => [
				'items' => [
					'deal' => [],
					'lead' => [],
					//'contact' => [],
					//'company' => [],
					//'invoice' => [],
					//'quote' => []
				]
			],
			'crm_documents' => [
				'items' => [
					'deal' => [],
					'lead' => [],
					'contact' => [],
					'company' => [],
					'invoice' => [],
					'quote' => []
				]
			],
			'crm_analytics' => [
				'items' => [
					'config' => []
				]
			],
			'crm_tunnels' => [
				'items' => [
					'deal' => []
				]
			],
			'bizproc_automation' => [
				'items' => [
					'lead' => [],
					'deal' => [],
					'task' => []
				]
			],
			'tasks_switcher' => [
				'items' => [
					'user' => [],
					'group' => []
				]
			],
			'socialnetwork' => [
				'items' => [
					'group_notifications' => []
				]
			]
		];
	}

	/**
	 * Returns current binding map for REST.
	 * @return array
	 */
	public static function getRestMap(): array
	{
		$map = [];
		foreach (self::REST_PLACEMENT_MAP as $key => $foo)
		{
			$map[$key] = [];
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
					'ID', 'APP_ID', 'PLACEMENT', 'TITLE',
					'APP_NAME' => 'REST_APP.APP_NAME', 'GROUP_NAME'
				],
				'filter' => [
					'=PLACEMENT' => array_keys(self::REST_PLACEMENT_MAP)
				],
				'order' => [
					'ID' => 'desc'
				]
			]);
			while ($row = $res->fetch())
			{
				if (!isset(self::REST_PLACEMENT_MAP[$row['PLACEMENT']]))
				{
					continue;
				}
				if (!trim($row['TITLE']))
				{
					$row['TITLE'] = $row['APP_NAME'];
				}
				[$bindingCode, $menuCode] = explode('@', self::REST_PLACEMENT_MAP[$row['PLACEMENT']]);
				$bindingCode = mb_strtolower($bindingCode);
				$menuCode = mb_strtolower($menuCode);
				if (isset($map[$bindingCode]['items'][$menuCode]))
				{
					self::$needProvider = true;
					$oneItem = [
						'id' => 'rest_' . $row['ID'],
						'system' => false,
						'text' => htmlspecialcharsbx($row['TITLE']),
						'sort' => 500,
						'sectionCode' => $menuSectionCode,
						'linkProvider' => 'marketplace',
						'params' => [
							'app_id' => $row['APP_ID'],
							'placement_id' => $row['ID'],
							'placement' => $row['PLACEMENT']
						]
					];
					if ($row['GROUP_NAME'])
					{
						$groupingKey = $row['PLACEMENT'] . '_' . $row['GROUP_NAME'];
						if (isset($groups[$groupingKey]))
						{
							$i = $groups[$groupingKey];
							$map[$bindingCode]['items'][$menuCode][$i]['items'][] = $oneItem;
						}
						else
						{
							$map[$bindingCode]['items'][$menuCode][] = [
								'text' => $row['GROUP_NAME'],
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
	 * @param string $sectionCode Section code.
	 * @param string $menuCode Item code.
	 * @param array $params Additional params:
	 * - context Query context, mixed
	 * - inline Inline mode, boolean, false by default
	 * @return array
	 */
	public static function getMenuItems($sectionCode, $menuCode, array $params = [])
	{
		static $bindings = [];

		$inline = (isset($params['inline']) && $params['inline'] === true);
		$context = (isset($params['context']) && is_array($params['context']))
					? $params['context'] : [];

		if (!$bindings)
		{
			$bindings = self::getBindings();
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
							'text' => Loc::getMessage('INTRANET_BIND_MENU_SECTION_'.mb_strtoupper($globalSectionCode)),
							'delimiter' => true
						];
					}
					$existItems = false;
					foreach ($bindings[$sectionCode]['items'][$menuCode] as $item)
					{
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
				$marketCode = mb_strtoupper($sectionCode.'@'.$menuCode);
				$placementMap = array_flip(self::REST_PLACEMENT_MAP);
				if (isset($placementMap[$marketCode]))
				{
					if ($returnItems)
					{
						$returnItems[] = [
							'delimiter' => true
						];
					}
					$returnItems[] = [
						'href' => '/marketplace/?placement=' . $placementMap[$marketCode],
						'text' => Loc::getMessage('INTRANET_BIND_MENU_APPS')
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
