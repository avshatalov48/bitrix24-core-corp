<?php

namespace Bitrix\Market\Integration\Messageservice;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\MessageService\Sender\SmsManager;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Messageservice
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'messageservice';
	private const TAG_MASSAGER_PROVIDER = 'message_provider';

	/**
	 * Updates tag of count Message Provider.
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function onGetMessageProvider(Event $event)
	{
		$tag = static::getCountMessageProviderTag();
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

	private static function getCountMessageProviderTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_MASSAGER_PROVIDER,
				'VALUE' => count(SmsManager::getSenders()),
			];
		}

		return $result;
	}

	/**
	 * Return all Message service tags.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getCountMessageProviderTag(),
		];
	}
}