<?php
namespace Bitrix\Intranet\Binding;

use \Bitrix\Main\Event;
use \Bitrix\Main\EventResult;

class Menu
{
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
			'tasks_switcher' => [
				'items' => [
					'user' => []
				]
			]
		];
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
			$item['additional'] = isset($item['additional']) && $item['additional'];
			if (isset($item['delimiter']))
			{
				$validData[] = [
					'delimiter' => true,
					'sort' => 100
				];
			}
			else if (
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
				$item['text'] = \htmlspecialcharsbx($item['text']);
				if (isset($item['items']))
				{
					$item['items'] = self::getValidItems($item['items']);
				}
				$item['sort'] = isset($item['sort'])
								? (int) $item['sort']
								: 100;
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

		return $map;
	}

	/**
	 * Gets available menu items by code.
	 * @param string $sectionCode Section code.
	 * @param string $menuCode Item code.
	 * @return array
	 */
	public static function getMenuItems($sectionCode, $menuCode)
	{
		static $bindings = [];

		if (!$bindings)
		{
			$bindings = self::getBindings();
		}

		if (is_string($sectionCode) && is_string($menuCode))
		{
			$sectionCode = strtolower($sectionCode);
			$menuCode = strtolower($menuCode);
			if (isset($bindings[$sectionCode]['items'][$menuCode]))
			{
				return $bindings[$sectionCode]['items'][$menuCode];
			}
		}

		return [];
	}
}
