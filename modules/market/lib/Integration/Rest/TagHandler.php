<?php

namespace Bitrix\Market\Integration\Rest;


use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Rest\Marketplace\Client;
use Bitrix\Market\Tag\Manager;
use Bitrix\Main\Type\Date;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Rest
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'rest';

	/**
	 * Saves new subscription status.
	 *
	 * @param Event $event
	 * @return null|array
	 */
	public static function onChangeSubscription(Event $event): ?bool
	{
		$params = $event->getParameters();
		if ($params['value'])
		{
			$tagStatus = static::getSubscriptionStatusTag();
			if ($tagStatus['MODULE_ID'])
			{
				Manager::save(
					$tagStatus['MODULE_ID'],
					$tagStatus['CODE'],
					$tagStatus['VALUE'],
				);
			}

			$tagFinishTime = static::getSubscriptionFinishTimeTag();
			if ($tagFinishTime['MODULE_ID'])
			{
				Manager::save(
					$tagFinishTime['MODULE_ID'],
					$tagFinishTime['CODE'],
					(string)$tagFinishTime['VALUE'],
				);
			}
		}

		return true;
	}

	/**
	 * Return all REST tags.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getSubscriptionStatusTag(),
			static::getSubscriptionFinishTimeTag(),
		];
	}

	private static function getSubscriptionStatusTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => 'subscription_available',
				'VALUE' => Client::isSubscriptionAvailable() ? 'Y' : 'N',
			];
		}

		return $result;
	}

	private static function getSubscriptionFinishTimeTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$date = Client::getSubscriptionFinalDate();
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => 'subscription_finish_time',
				'VALUE' => ($date instanceof Date) ? $date->getTimestamp() : 0,
			];
		}

		return $result;
	}
}
