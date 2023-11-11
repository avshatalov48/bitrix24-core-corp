<?php


namespace Bitrix\Crm\MessageSender;

use Bitrix\Crm\Integration\MailManager;
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
	 * Returns a list of senders that can be selected by users as a default client notification transport.
	 * The listed is sorted by priority. First usable sender should be selected.
	 *
	 * @return ICanSendMessage[]
	 */
	public static function getPrioritizedList(): array
	{
		return [
			NotificationsManager::getSenderCode() => NotificationsManager::class,
			SmsManager::getSenderCode() => SmsManager::class,
		];
	}

	/**
	 * Returns a list of all classes that implement ICanSendMessage interface. Some of them are not user-selectable as
	 * default notification transport and used only in limited scenarios.
	 *
	 * @return ICanSendMessage[]|string[]
	 */
	public static function getAllImplementationsList(): array
	{
		return [
			NotificationsManager::getSenderCode() => NotificationsManager::class,
			SmsManager::getSenderCode() => SmsManager::class,
			// not user-selectable. Implement support for mail in all scenarios first
			MailManager::getSenderCode() => MailManager::class,
		];
	}
}
