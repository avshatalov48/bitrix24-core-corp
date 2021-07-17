<?php


namespace Bitrix\Crm\MessageSender;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;

/**
 * Class SenderRepository
 * @package Bitrix\Crm\MessageSender
 * @internal
 */
final class SenderRepository
{
	/**
	 * @return ICanSendMessage[]
	 */
	public static function getPrioritizedList(): array
	{
		return [
			NotificationsManager::getSenderCode() => NotificationsManager::class,
			SmsManager::getSenderCode() => SmsManager::class,
		];
	}
}
