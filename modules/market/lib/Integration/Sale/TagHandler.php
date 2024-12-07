<?php

namespace Bitrix\Market\Integration\Sale;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Sale
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'sale';
	private const TAG_PAYSYSTEM = 'paySystem_count';

	/**
	 * Updates tag of count Pay System.
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function onUpdatePaySystem(Event $event)
	{
		$oldFields = $event->getParameter('OLD_FIELDS');
		$newFields = $event->getParameter('NEW_FIELDS');
		if (
			!isset($oldFields['ACTIVE']) ||
			!isset($newFields['ACTIVE']) ||
			$oldFields['ACTIVE'] == $newFields['ACTIVE']
		) {
			return true;
		}

		$tag = static::getCountPaySystemTag();
		if ($tag['MODULE_ID'])
		{
			Manager::save(
				$tag['MODULE_ID'],
				$tag['CODE'],
				(string)$tag['VALUE']
			);
		}

		return true;
	}

	private static function getCountPaySystemTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_PAYSYSTEM,
				'VALUE' => PaySystemActionTable::getCount([
						'=ACTIVE' => 'Y'
					]
				),
			];
		}

		return $result;
	}

	/**
	 * Return all Pay system tags.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getCountPaySystemTag(),
		];
	}
}