<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Intranet\Binding;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class IntranetBindingMenuComponent extends \CBitrixComponent
{
	/**
	 * Check var in arParams. If no exists, create with default val.
	 * @param string|int $var Variable.
	 * @param mixed $default Default value.
	 * @return void
	 */
	protected function checkParam($var, $default)
	{
		if (!isset($this->arParams[$var]))
		{
			$this->arParams[$var] = $default;
		}
		if (is_int($default))
		{
			$this->arParams[$var] = (int)$this->arParams[$var];
		}
		if (substr($var, 0, 1) !== '~')
		{
			$this->checkParam('~' . $var, $default);
		}
	}

	/**
	 * Separates additional items from general array.
	 * @param array &$items Data array.
	 * @return array Additional items.
	 */
	protected function separateAdditional(array &$items)
	{
		$separate = [];

		foreach ($items as $key => &$item)
		{
			if ($item['additional'])
			{
				$separate[] = $item;
				unset($items[$key]);
			}
		}
		unset($item);

		$items = array_values($items);

		return $separate;
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent()
	{
		$this->checkParam('SECTION_CODE', '');
		$this->checkParam('MENU_CODE', '');

		$this->arParams['SECTION_CODE'] = strtolower($this->arParams['SECTION_CODE']);
		$this->arParams['MENU_CODE'] = strtolower($this->arParams['MENU_CODE']);
		$this->arResult['DEFAULT_BUTTON_NAME'] = Loc::getMessage('INTRANET_CMP_BIND_MENU_BUTTON_NAME');

		$this->arResult['ITEMS'] = Binding\Menu::getMenuItems(
			$this->arParams['SECTION_CODE'],
			$this->arParams['MENU_CODE']
		);

		if (!$this->arResult['ITEMS'])
		{
			return;
		}

		$this->arResult['ADDITIONAL'] = $this->separateAdditional(
			$this->arResult['ITEMS']
		);

		$this->includeComponentTemplate($this->arParams['SECTION_CODE']);
	}
}
