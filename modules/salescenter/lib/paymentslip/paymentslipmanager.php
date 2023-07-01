<?php

namespace Bitrix\Salescenter\PaymentSlip;

use Bitrix\Main\Loader;
use Bitrix\MessageService\Sender\BaseConfigurable;

/**
 * Manager of terminal payment slips
 */
final class PaymentSlipManager
{
	public static function getManager(): ?self
	{
		static $manager;
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		if (!isset($manager))
		{
			$manager = new self();
		}

		return $manager;
	}

	/**
	 * Get configuration of payment slip sending
	 * @return PaymentSlipConfig
	 */
	public function getConfig(): PaymentSlipConfig
	{
		return PaymentSlipConfig::getConfigInstance();
	}

	public function getConnectNotificationsLink(): ?array
	{
		$connectUrl = \Bitrix\Crm\Integration\NotificationsManager::getConnectUrl();
		if (is_string($connectUrl))
		{
			return [
				'type' => 'connect_link',
				'value' => $connectUrl,
			];
		}

		if (is_array($connectUrl))
		{
			return $connectUrl;
		}

		return null;
	}

	public function getConnectServiceLink(): ?string
	{
		$connectUrl = \Bitrix\Crm\Integration\SmsManager::getConnectUrl();
		if (isset($connectUrl))
		{
			return $connectUrl;
		}

		return null;
	}
}