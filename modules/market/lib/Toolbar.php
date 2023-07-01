<?php

namespace Bitrix\Market;

use Bitrix\Market\Subscription\Status;
use Bitrix\Rest\Marketplace\Client;
use CRestUtil;

class Toolbar
{
	public static function getInfo(): array
	{
		$result = [
			'CATEGORIES' => Categories::forceGet(),
			'FAV_NUMBERS' => AppFavoritesTable::getUserFavoritesCount(),
			'MENU_INFO' => Menu::getList(),
			'MARKET_SLIDER' => Status::getSlider(),
		];

		if (CRestUtil::isAdmin()) {
			$result['NUM_UPDATES'] = Client::getAvailableUpdateNum();
		}

		return $result;
	}
}