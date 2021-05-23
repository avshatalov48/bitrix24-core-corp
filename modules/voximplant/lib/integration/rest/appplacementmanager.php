<?php
namespace Bitrix\Voximplant\Integration\Rest;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest;

Loc::loadMessages(__FILE__);

class AppPlacementManager
{
	public static function getHandlerInfos($placement)
	{
		if(!Main\Loader::includeModule('rest'))
		{
			return [];
		}

		$results = [];
		foreach(Rest\PlacementTable::getHandlersList($placement) as $handler)
		{
			$info = [ 'ID' => (int)$handler['ID'], 'APP_ID' => (int)$handler['APP_ID'] ];

			$title = isset($handler['TITLE']) ? trim($handler['TITLE']) : '';
			$info['TITLE'] = $title !== '' ? $title : $handler['APP_NAME'];

			$groupName = isset($handler['GROUP_NAME']) ? trim($handler['GROUP_NAME']) : '';
			if($groupName === '')
			{
				$groupName = Loc::getMessage('VOXIMPLANT_APP_PLACEMENT_DEFAULT_GROUP');
			}
			if(!isset($results[$groupName]))
			{
				$results[$groupName] = [];
			}
			$results[$groupName][] = $info;
		}
		return $results;
	}
}