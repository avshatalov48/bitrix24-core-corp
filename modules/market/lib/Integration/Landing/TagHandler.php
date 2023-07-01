<?php

namespace Bitrix\Market\Integration\Landing;

use Bitrix\Landing\Internals\SiteTable;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Landing
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'landing';

	/**
	 * Updates tag of count landing.
	 *
	 * @param Event $event
	 */
	public static function onChangeLandingPublication(Event $event)
	{
		Manager::saveList(static::getLandingCount());

		return null;
	}

	/**
	 * Return all landing tags.
	 *
	 * @return null|array
	 */
	public static function list(): array
	{
		return static::getLandingCount();
	}

	private static function getLandingCount()
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$res = SiteTable::getList(
				[
					'select' => [
						'ID',
						'ACTIVE',
					],
					'filter' => [
						'CHECK_PERMISSIONS' => 'N',
						'=DELETED' => 'N',
					],
				]
			);
			$count = 0;
			$countPublic = 0;
			while ($item = $res->fetch())
			{
				$count++;
				if ($item['ACTIVE'] === 'Y')
				{
					$countPublic++;
				}
			}

			$result = [
				[
					'MODULE_ID' => static::MODULE_ID,
					'CODE' => 'landing_count',
					'VALUE' => $count,
				],
				[
					'MODULE_ID' => static::MODULE_ID,
					'CODE' => 'landing_count_public',
					'VALUE' => $countPublic,
				],
			];
		}

		return $result;
	}
}
