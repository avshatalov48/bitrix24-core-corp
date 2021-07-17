<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Class MessageSender
 * @package Bitrix\Crm\Integration
 * @internal
 */
final class MessageSender
{
	private const SENDER_AUTO_SELECTION_SETTING_VALUE = 'auto';
	private const SENDER_BITRIX24_SETTING_VALUE = 'bitrix24';
	private const SENDER_SMS_PROVIDER_SETTING_VALUE = 'sms_provider';

	/**
	 * @param array $integrations
	 * @param array $options
	 * @return Result
	 */
	public static function send(array $integrations, array $options = []): Result
	{
		$commonOptions = $options['COMMON_OPTIONS'] ?? [];

		/** @var ICanSendMessage $senderIntegration */
		$senderIntegration = self::getSenderIntegrationClass();
		if (!isset($integrations[$senderIntegration]))
		{
			return (new Result())->addError(new Error('Unexpected integration'));
		}

		if (!$senderIntegration::canSendMessage())
		{
			return (new Result())->addError(new Error('Selected integration is not available'));
		}

		$result = $senderIntegration::sendMessage(
			$senderIntegration::makeMessageFields(
				$integrations[$senderIntegration],
				$commonOptions
			)
		);

		if ($result instanceof Result)
		{
			return $result;
		}

		return (new Result())->addError(new Error('Message has not been sent'));
	}

	/**
	 * @return string
	 */
	public static function getSenderSettingValue(): string
	{
		$result = Option::get('crm', '~CRM_SENDER', self::SENDER_AUTO_SELECTION_SETTING_VALUE);

		return in_array($result, self::getSenderSettingAvailableValues(), true)
			? $result
			: self::SENDER_AUTO_SELECTION_SETTING_VALUE;
	}

	/**
	 * @param string $value
	 * @throws SystemException
	 */
	public static function setSenderSettingValue(string $value): void
	{
		if (!in_array($value, self::getSenderSettingAvailableValues()))
		{
			throw new SystemException(sprintf('Unexpected setting value: %s', $value));
		}

		Option::set('crm', '~CRM_SENDER', $value);
	}

	/**
	 * @return bool
	 */
	public static function isSendingViaBitrix24(): bool
	{
		return self::getSenderIntegrationClass() === NotificationsManager::class;
	}

	/**
	 * @return string
	 */
	private static function getSenderIntegrationClass(): string
	{
		$settingValue = self::getSenderSettingValue();
		if ($settingValue === self::SENDER_BITRIX24_SETTING_VALUE)
		{
			return NotificationsManager::class;
		}

		if ($settingValue === self::SENDER_SMS_PROVIDER_SETTING_VALUE)
		{
			return SmsManager::class;
		}

		return (NotificationsManager::canUse() && NotificationsManager::canSendMessage())
			? NotificationsManager::class
			: SmsManager::class;
	}

	/**
	 * @return string[]
	 */
	private static function getSenderSettingAvailableValues(): array
	{
		return array_keys(self::getSenderAvailableSettings());
	}

	/**
	 * @return array
	 */
	public static function getSenderAvailableSettings(): array
	{
		return [
			self::SENDER_AUTO_SELECTION_SETTING_VALUE => Loc::getMessage('CRM_INTEGRATION_MESSAGE_SENDER_AUTO_VALUE'),
			self::SENDER_BITRIX24_SETTING_VALUE => Loc::getMessage('CRM_INTEGRATION_MESSAGE_SENDER_BITRIX24_VALUE'),
			self::SENDER_SMS_PROVIDER_SETTING_VALUE => Loc::getMessage('CRM_INTEGRATION_MESSAGE_SENDER_SMS_PROVIDER_VALUE'),
		];
	}
}
