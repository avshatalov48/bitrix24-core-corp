<?php

namespace Bitrix\Crm\MessageSender;

/**
 * Class SenderPicker
 * @package Bitrix\Crm\MessageSender
 * @internal
 */
final class SenderPicker
{
	/**
	 * @return ICanSendMessage|null
	 */
	public static function getCurrentSender(): ?string
	{
		$settingValue = SettingsManager::getValue();

		if ($settingValue !== SettingsManager::SENDER_AUTO_SELECTION_SETTING_VALUE)
		{
			return self::getSenderByCode($settingValue);
		}

		$senders = SenderRepository::getPrioritizedList();
		foreach ($senders as $sender)
		{
			if ($sender::isConnected())
			{
				return $sender;
			}
		}

		return null;
	}

	/**
	 * @param string $code
	 * @return ICanSendMessage|null
	 */
	public static function getSenderByCode(string $code): ?string
	{
		$senders = SenderRepository::getPrioritizedList();

		foreach ($senders as $sender)
		{
			if ($sender::getSenderCode() === $code)
			{
				return $sender;
			}
		}

		return null;
	}
}
