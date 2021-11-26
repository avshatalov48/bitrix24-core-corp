<?php

namespace Bitrix\Crm\Agent\Recyclebin;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Settings\RecyclebinSettings;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

class RecyclebinAgent extends AgentBase
{
	private const ENTITY_LIMIT = 100;
	private const B24_DAYS_TTL = 30;
	private const SECONDS_IN_DAY = 86400; // 60 * 60 * 24
	private const TIME_LIMIT = 10; // 10 seconds by default

	/**
	 * @return bool
	 */
	public static function doRun(): bool
	{
		if (Loader::includeModule('recyclebin'))
		{
			$ttl = (
				ModuleManager::isModuleInstalled('bitrix24')
					? self::B24_DAYS_TTL
					: RecyclebinSettings::getCurrent()->getTtl()
			);

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
		$entityLimit = (int)Option::get('crm', 'recyclebin_agent_entity_limit', self::ENTITY_LIMIT);

		$list = RecyclebinTable::getList([
			'filter' => [
				'MODULE_ID' => 'crm',
				'<=TIMESTAMP' => DateTime::createFromTimestamp($timestamp),
			],
			'order' => ['TIMESTAMP' => 'ASC'],
			'limit' => ($entityLimit > 0 ? $entityLimit : self::ENTITY_LIMIT),
		]);

		$timeLimit = (int)Option::get('crm', 'recyclebin_agent_time_limit', self::TIME_LIMIT);
		$timeLimit = ($timeLimit > 0 ? $timeLimit : self::TIME_LIMIT);

		$start = time();
		foreach ($list as $item)
		{
			Recyclebin::remove($item['ID'], ['skipAdminRightsCheck' => true]);
			if (time() - $start > $timeLimit)
			{
				return;
			}
		}
	}
}
