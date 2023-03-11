<?php
namespace Bitrix\Intranet\UI\LeftMenu\Preset;

use \Bitrix\Main;
use \Bitrix\Intranet\UI\LeftMenu;

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
		return is_array($structure) ? [
			'shown' => isset($structure['show']) && is_array($structure['show']) ? $structure['show'] : [],
			'hidden' => isset($structure['hide']) && is_array($structure['hide']) ? $structure['hide'] : []
		] : [];
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
				'COUNTER_ID' => $itemData['COUNTER_ID'] ?? null,
				'SUB_LINK' => $itemData['SUB_LINK'] ?? null,
				'NEW_PAGE' => $itemData['NEW_PAGE'] ?? null,
				'ADDITIONAL_LINKS' => $itemData['ADDITIONAL_LINKS'] ?? [],
			] , $itemData));
			$result[] = $item;
		}
		return $result;
	}
}