<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Site\Sections;

use Bitrix\Main\Loader;
use Bitrix\Intranet\Integration\Templates\Air\ChatMenu;

class ChatSection
{
	public static function getItems(): array
	{
		if (!Loader::includeModule('im'))
		{
			return [];
		}

		$items = ChatMenu::getMenuItems();

		return array_map(static function ($item) {
			return [
				'id' => $item['ID'],
				'title' => $item['TEXT'],
				'available' => true,
				'onclick' => $item['ON_CLICK'],
				'menuData' => [
					'menu_item_id' => $item['ID'],
					'counter_id' => $item['COUNTER_ID'],
				],
			];
		}, $items);
	}
}
