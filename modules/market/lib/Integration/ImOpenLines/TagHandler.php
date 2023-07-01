<?php

namespace Bitrix\Market\Integration\ImOpenLines;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\ImOpenLines
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'imopenlines';

	/**
	 * Updates tag of count lines.
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function onChangeConfig(Event $event)
	{
		$tag = static::getCountConfigTag();
		if ($tag['MODULE_ID'])
		{
			Manager::save(
				$tag['MODULE_ID'],
				$tag['CODE'],
				$tag['VALUE'],
			);
		}

		return true;
	}

	/**
	 * Return all OpenLines tags.
	 *=
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getCountConfigTag(),
		];
	}

	private static function getCountConfigTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => 'open_lines_count',
				'VALUE' => ConfigTable::getCount(
					[
						'=ACTIVE' => 'Y',
						'>STATISTIC.MESSAGE' => 0,
						'>STATISTIC.SESSION' => 0
					]
				),
			];
		}

		return $result;
	}
}
