<?php

namespace Bitrix\Crm\Agent\Recyclebin;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

class RecyclebinAgent extends AgentBase
{
	private const
		ENTITY_LIMIT = 100,
		B24_DAYS_TTL = 30,
		SECONDS_IN_DAY = 86400; // 60 * 60 * 24

	/**
	 * @return bool
	 */
	public static function doRun(): bool
	{
		if (Loader::includeModule('recyclebin'))
		{
			if (ModuleManager::isModuleInstalled('bitrix24'))
			{
				$ttl = self::B24_DAYS_TTL;
			}
			else
			{
				$ttl = \Bitrix\Crm\Settings\RecyclebinSettings::getCurrent()->getTtl();
			}

			if ($ttl < 0)
			{
				return true;
			}

			self::removeExpiredEntities($ttl);
		}
		return true;
	}

	/**
	 * @param int $ttl
	 */
	private static function removeExpiredEntities(int $ttl): void
	{
		$timestamp = time() + \CTimeZone::getOffset() - self::SECONDS_IN_DAY * $ttl;
		$list = RecyclebinTable::getList([
			'filter' => [
				'MODULE_ID' => 'crm',
				'<=TIMESTAMP' => DateTime::createFromTimestamp($timestamp)
			],
			'order' => ['TIMESTAMP' => 'ASC'],
			'limit' => self::ENTITY_LIMIT
		]);
		foreach ($list as $item)
		{
			$entity = Recyclebin::remove($item['ID'], ['skipAdminRightsCheck' => true]);
		}
	}
}
