<?php

namespace Bitrix\Market\Integration\Voximplant;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Voximplant\SipTable;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Voximplant
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'voximplant';

	/**
	 * Updates tag of count sip.
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function onChangeSip(Event $event)
	{
		$tag = static::getCountSipTag();
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

	private static function getCountSipTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => 'sip_count',
				'VALUE' => SipTable::getCount(
					[
						'=REGISTRATION_STATUS_CODE' => 200,
					]
				),
			];
		}

		return $result;
	}

	/**
	 * Return all VoxImplants tags for.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getCountSipTag(),
		];
	}
}