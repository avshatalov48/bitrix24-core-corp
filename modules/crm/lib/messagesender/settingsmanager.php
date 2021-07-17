<?php


namespace Bitrix\Crm\MessageSender;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class SettingsManager
 * @package Bitrix\Crm\MessageSender
 * @internal
 */
final class SettingsManager
{
	public const SENDER_AUTO_SELECTION_SETTING_VALUE = 'auto';
	private const OPTION_NAME = '~CRM_SENDER';

	/**
	 * @return array
	 */
	public static function getSettingsList(): array
	{
		$result = [
			self::SENDER_AUTO_SELECTION_SETTING_VALUE => Loc::getMessage('CRM_INTEGRATION_MESSAGE_SENDER_AUTO_VALUE'),
		];

		$senders = SenderRepository::getPrioritizedList();
		foreach ($senders as $sender)
		{
			$senderCode = $sender::getSenderCode();

			$result[$senderCode] = Loc::getMessage(
				sprintf(
					'CRM_INTEGRATION_MESSAGE_SENDER_%s_VALUE',
					mb_strtoupper($senderCode)
				)
			);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getValue(): string
	{
		return Option::get('crm', self::OPTION_NAME, self::SENDER_AUTO_SELECTION_SETTING_VALUE);
	}

	/**
	 * @param string $value
	 */
	public static function setValue(string $value): void
	{
		Option::set('crm', self::OPTION_NAME, $value);
	}
}
