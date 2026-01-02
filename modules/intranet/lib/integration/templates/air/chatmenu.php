<?php

namespace Bitrix\Intranet\Integration\Templates\Air;

class ChatMenu
{
	public static function getMenuItems(): array
	{
		if(!\Bitrix\Main\Loader::includeModule('im'))
		{
			return [];
		}

		$items = \Bitrix\Im\V2\Application\Navigation\Menu::getInstance()->getMenuItems();;

		return array_map(function($item) {
			$eventName = 'BX.Intranet.Bitrix24.ChatMenu:onSelect';
			$data = ['id' => $item['id']];
			if (!empty($item['entityId']))
			{
				$data['entityId'] = $item['entityId'];
				if ($item['id'] === 'market')
				{
					$item['id'] = $item['id'] . '_' . $item['entityId'];
				}
			}

			$jsData = "const data = " . json_encode($data) . "; data.event = event;";

			return [
				'ID' => $item['id'],
				'TEXT' => $item['text'],
				'COUNTER_ID' => $item['id'],
				'ON_CLICK' => $jsData . "BX.Event.EventEmitter.emit('" . $eventName . "', data)",
			];

		}, $items);
	}
}
