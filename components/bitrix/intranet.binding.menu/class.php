<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Intranet\Binding;

class IntranetBindingMenuComponent extends \CBitrixComponent
{
	/**
	 * Check var in arParams. If no exists, create with default val.
	 * @param string|int $var Variable.
	 * @param mixed $default Default value.
	 * @return void
	 */
	protected function checkParam($var, $default): void
	{
		if (!isset($this->arParams[$var]))
		{
			$this->arParams[$var] = $default;
		}
		if (is_int($default))
		{
			$this->arParams[$var] = (int)$this->arParams[$var];
		}
		if (mb_substr($var, 0, 1) !== '~')
		{
			$this->checkParam('~' . $var, $default);
		}
	}

	/**
	 * Returns most frequency (by clicking) menu item.
	 * @param array $items Items array.
	 * @param string|null $menuId Frequency menu item id.
	 * @return array
	 */
	protected function getFrequencyMenuItem(array $items, ?string $menuId): array
	{
		if (!$items)
		{
			return [];
		}
		if (count($items) > 1)
		{
			// try find recursive needed menu id
			if ($menuId)
			{
				foreach ($items as $item)
				{
					if (isset($item['items']))
					{
						$innerItem = $this->getFrequencyMenuItem($item['items'], $menuId);
						if (isset($innerItem['id']) && $innerItem['id'] == $menuId)
						{
							return $innerItem;
						}
					}
					if (isset($item['id']) && $item['id'] == $menuId)
					{
						return $item;
					}
				}
			}
			// if not, try first available menu item
			foreach ($items as $item)
			{
				if (
					isset($item['id']) && $item['id'] &&
					(!isset($item['system']) || !$item['system'])
				)
				{
					return $item;
				}
			}
		}
		// else returns first element only
		else
		{
			$item = $items[0];
			if (!isset($item['system']) || !$item['system'])
			{
				return $item;
			}
		}

		return [];
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent(): void
	{
		$this->checkParam('SECTION_CODE', '');
		$this->checkParam('MENU_CODE', '');
		$this->checkParam('CONTEXT', []);

		$this->arParams['SECTION_CODE'] = mb_strtolower($this->arParams['SECTION_CODE']);
		$this->arParams['MENU_CODE'] = mb_strtolower($this->arParams['MENU_CODE']);

		$this->arResult['BINDING_ID'] = $this->arParams['SECTION_CODE'] . ':' . $this->arParams['MENU_CODE'];
		$this->arResult['SECTIONS'] = Binding\Menu::SECTIONS;
		$this->arResult['ITEMS'] = Binding\Menu::getMenuItems(
			$this->arParams['SECTION_CODE'],
			$this->arParams['MENU_CODE'],
			$this->arParams['CONTEXT']
			? ['context' => $this->arParams['CONTEXT']]
			: []
		);
		$this->arResult['FREQUENCY_MENU_ITEM'] = $this->getFrequencyMenuItem(
			$this->arResult['ITEMS'],
			Binding\Menu::getFrequencyMenuItemId(
				$this->arResult['BINDING_ID']
			)
		);

		if (!$this->arResult['ITEMS'])
		{
			return;
		}

		$this->includeComponentTemplate($this->arParams['SECTION_CODE']);
	}
}
