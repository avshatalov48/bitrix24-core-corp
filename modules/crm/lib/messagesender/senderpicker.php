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
		$senders = SenderRepository::getPrioritizedList();

		if ($settingValue === SettingsManager::SENDER_AUTO_SELECTION_SETTING_VALUE)
		{
			foreach ($senders as $sender)
			{
				if ($sender::isConnected())
				{
					return $sender;
				}
			}
		}
		else
		{
			foreach ($senders as $sender)
			{
				if ($sender::getSenderCode() === $settingValue)
				{
					return $sender;
				}
			}
		}

		return null;
	}
}
