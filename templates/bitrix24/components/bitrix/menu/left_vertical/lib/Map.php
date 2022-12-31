<?php
namespace Bitrix\Intranet\LeftMenu;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\Date;

// Just code aggregation
class Map
{
	protected $toolItems = [];
	protected $mapItems = [];

	public function __construct(array $defaultItems)
	{
		$mapItems = [];
		$myToolsItems = [];

		foreach ($defaultItems as $itemIndex => $item)
		{
			if (isset($item['PERMISSION']) && $item['PERMISSION'] <= 'D')
			{
				continue;
			}

			if (isset($item['PARAMS'])
				&& isset($item['PARAMS']['my_tools_section'])
				&& $item['PARAMS']['my_tools_section'] === true
			)
			{
				$myToolsItems[] = array_merge($item, ['DEPTH_LEVEL' => 2]);
				//Skip empty root items
				if (
					$item['DEPTH_LEVEL'] !== 1 ||
					!isset($defaultItems[$itemIndex + 1]) ||
					$defaultItems[$itemIndex + 1]['DEPTH_LEVEL'] !== 1)
				{
					$mapItems[] = $item;
				}
			}
			else
			{
				$mapItems[] = $item;
			}
		}
		$this->toolItems = $myToolsItems;
		$this->mapItems = $mapItems;

	}

	public function getItems(): array
	{
		$result = [];
		if (!empty($this->toolItems))
		{
			$result = array_merge([[
				'TEXT' => Loc::getMessage('MENU_MY_WORKSPACE'),
				'LINK' => SITE_DIR,
				'SELECTED' => false,
				'PERMISSION' => 'X',
				'PARAMS' => array(
					'menu_item_item' => 'my_instruments'
				),
				'DEPTH_LEVEL' => 1,
				'IS_PARENT' => true,
				'ADDITIONAL_LINKS' => array()
			]], $this->toolItems);
		}
		return array_merge($result, $this->mapItems);
	}
}
