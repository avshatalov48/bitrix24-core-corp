<?php

namespace Bitrix\Crm\Agent\Recyclebin;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

class RecyclebinAgent extends AgentBase
{
	private const
		ENTITY_LIMIT = 100,
		TTL = 2592000; //60 * 60 * 24 * 30;

	public static function doRun(): bool
	{
		if (Loader::includeModule('recyclebin'))
		{
			$days30 = time() + \CTimeZone::GetOffset() - self::TTL;
			$list = RecyclebinTable::getList([
				'filter' => [
					'MODULE_ID' => 'crm',
					'<=TIMESTAMP' => DateTime::createFromTimestamp($days30)
				],
				'order' => ['TIMESTAMP' => 'ASC'],
				'limit' => self::ENTITY_LIMIT
			]);
			foreach ($list as $item)
			{
				$entity = Recyclebin::remove($item['ID'], ['skipAdminRightsCheck' => true]);
			}
		}
		return true;
	}

}
