<?php
namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Crm\Integration\Intranet\BindingMenu;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
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
				$groupName = Loc::getMessage('CRM_APP_PLACEMENT_DEFAULT_GROUP');
			}
			if(!isset($results[$groupName]))
			{
				$results[$groupName] = [];
			}
			$results[$groupName][] = $info;
		}
		return $results;
	}

	public static function deleteAllHandlersForType(int $entityTypeId): Result
	{
		if (!Main\Loader::includeModule('rest'))
		{
			return new Result();
		}

		$placementCodes = static::getAllPlacementCodesForType($entityTypeId);
		if (empty($placementCodes))
		{
			return new Result();
		}

		$placementsGetListResult = Rest\PlacementTable::getList([
			'select' => ['ID'],
			'filter' => [
				'@PLACEMENT' => $placementCodes,
			],
		]);

		$result = new Result();
		while ($entityObject = $placementsGetListResult->fetchObject())
		{
			$deleteResult = $entityObject->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	private static function getAllPlacementCodesForType(int $entityTypeId): array
	{
		$placementCodes = AppPlacement::getAllForType($entityTypeId);

		foreach (BindingMenu\SectionCode::getAll() as $mapSectionCode)
		{
			$placementCodes[] = BindingMenu\CodeBuilder::getRestPlacementCode($mapSectionCode, $entityTypeId);
		}

		return array_unique($placementCodes);
	}
}
