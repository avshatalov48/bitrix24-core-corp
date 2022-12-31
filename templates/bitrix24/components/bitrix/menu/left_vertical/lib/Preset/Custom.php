<?php
namespace Bitrix\Intranet\LeftMenu\Preset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
use \Bitrix\Main;
use \Bitrix\Intranet\LeftMenu;

class Custom extends PresetAbstract
{
	const CODE = 'custom';

	public function getName(): string
	{
		return 'Custom';
	}

	public function getStructure(): array
	{
		$structure = unserialize(
			\COption::GetOptionString('intranet', 'left_menu_custom_preset_sort', '')
			, ['allowed_classes' => false]
		);
		return is_array($structure) ? ['shown' => $structure['show'], 'hidden' => $structure['hide']] : [];
	}

	public static function isAvailable(): bool
	{
		if (Main\Loader::includeModule('bitrix24'))
		{
			return \Bitrix\Bitrix24\Feature::isFeatureEnabled('intranet_menu_to_all');
		}
		return true;
	}

	public function getItems(): array
	{
		static $result;
		if ($result)
		{
			return $result;
		}
		$result = parent::getItems();
		$items = unserialize(
			\COption::GetOptionString('intranet', 'left_menu_custom_preset_items', '')
			, ['allowed_classes' => false]
		);
		$items = (is_array($items) ? $items : []);
		foreach ($items as $itemData)
		{
			$item = new LeftMenu\MenuItem\ItemAdminCustom(array_merge([
				'ID' => $itemData['ID'],
				'TEXT' => $itemData['TEXT'],
				'LINK' => $itemData['LINK'],
				'COUNTER_ID' => $itemData['COUNTER_ID'],
				'SUB_LINK' => $itemData['SUB_LINK'] ?? null,
				'NEW_PAGE' => $itemData['NEW_PAGE'] ?? null,
				'ADDITIONAL_LINKS' => $itemData['ADDITIONAL_LINKS'] ?? [],
			] , $itemData));
			$result[] = $item;
		}
		return $result;
	}
}