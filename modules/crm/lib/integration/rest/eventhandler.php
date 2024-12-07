<?php

namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Crm\Timeline\Entity\Repository\RestAppLayoutBlocksRepository;
use Bitrix\Main\Loader;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\EO_App;

class EventHandler
{
	public static function onRestAppDelete(array $app): void
	{
		if (
			empty($app['APP_ID'])
			|| empty($app['CLEAN'])
			|| !Loader::includeModule('rest')
		)
		{
			return;
		}

		$restApp = self::getRestAppById((int)$app['APP_ID']);
		if ($restApp === null)
		{
			return;
		}

		self::deleteRestAppLayoutBlocks($restApp->getClientId());
	}

	private static function getRestAppById(int $id): EO_App|null
	{
		return AppTable::query()
			->setSelect(['*'])
			->where('ID', $id)
			->fetchObject()
		;
	}

	private static function deleteRestAppLayoutBlocks(string $clientId): void
	{
		(new RestAppLayoutBlocksRepository())->deleteByClientId($clientId);
	}
}
