<?php

namespace Bitrix\Market\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Market\AppFavoritesTable;

class Favorites extends Controller
{
	public function addFavoriteAction($appCode): AjaxJson
	{
		AppFavoritesTable::addItem($appCode);

		return AjaxJson::createSuccess([
			'total' => count(AppFavoritesTable::getUserFavorites()),
			'currentValue' => 'Y',
		]);
	}

	public function rmFavoriteAction($appCode): AjaxJson
	{
		AppFavoritesTable::rmItem($appCode);

		return AjaxJson::createSuccess([
			'total' => count(AppFavoritesTable::getUserFavorites()),
			'currentValue' => 'N',
		]);
	}
}