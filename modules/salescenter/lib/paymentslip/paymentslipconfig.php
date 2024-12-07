<?php

namespace Bitrix\Salescenter\PaymentSlip;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\MessageService\Sender\BaseConfigurable;
use Bitrix\MessageService\Sender\SmsManager;
use Bitrix\SalesCenter\Integration\CrmManager;

/**
 * Config of terminal slips sending. Must be accessible only from <b>PaymentSlipManager</b> class
 * @see PaymentSlipManager
 */
final class PaymentSlipConfig
{
	private const OPTION_SELECTED_SMS_SERVICE = 'PAYMENT_SLIP_SELECTED_SMS_SERVICE';
	private const OPTION_SENDING_ENABLED = 'PAYMENT_SLIP_SENDING_ENABLED';
	private const TERMINAL_PAYMENT_SYSTEMS_COLLAPSED = 'terminal_payment_systems_collapsed';

	public static function getConfigInstance(): self
	{
		static $config;

		$config ??= new self();

		return $config;
	}

	public function isNotificationsEnabled(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		return \Bitrix\Crm\Integration\NotificationsManager::isConnected();
	}

	public function isSendingEnabled(): bool
	{
		return Option::get('salescenter', self::OPTION_SENDING_ENABLED, 'Y') === 'Y';
	}

	public function setCollapsed(bool $status): void
	{
		\CUserOptions::SetOption('salescenter', self::TERMINAL_PAYMENT_SYSTEMS_COLLAPSED, $status ? 'Y' : 'N');
	}

	public function isCollapsed(): bool
	{
		return \CUserOptions::GetOption('salescenter', self::TERMINAL_PAYMENT_SYSTEMS_COLLAPSED, 'N') === 'Y';
	}

	public function getSelectedSmsServiceId(): ?string
	{
		if (!Loader::includeModule('messageservice'))
		{
			return null;
		}

		$selectedServiceId = Option::get('salescenter', self::OPTION_SELECTED_SMS_SERVICE, null);
		if (empty($selectedServiceId) || !$this->isSmsServiceActive($selectedServiceId))
		{
			/** @var BaseConfigurable|null $firstRegisteredSender */
			$firstRegisteredSender = current(CrmManager::getInstance()->getUsableSmsSendersList());

			if ($firstRegisteredSender)
			{
				Option::set('salescenter', self::OPTION_SELECTED_SMS_SERVICE, $firstRegisteredSender['id']);
				$selectedServiceId = $firstRegisteredSender['id'];
			}
		}

		return $selectedServiceId;
	}

	private function isSmsServiceActive(string $serviceId): bool
	{
		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		foreach (CrmManager::getInstance()->getUsableSmsSendersList() as $sender)
		{
			if ($sender['id'] === $serviceId)
			{
				return true;
			}
		}

		return false;
	}

	public function setSelectedServiceId(string $serviceId): bool
	{
		foreach ($this->getAvailableSmsServices() as $sender)
		{
			if ($sender['ID'] === $serviceId)
			{
				Option::set('salescenter', self::OPTION_SELECTED_SMS_SERVICE, $serviceId);

				return true;
			}
		}

		return false;
	}

	public function setEnablingSending(bool $isSendingEnabled): void
	{
		Option::set('salescenter', self::OPTION_SENDING_ENABLED, $isSendingEnabled ? 'Y' : 'N');
	}

	/**
	 * Returns array of SMS services that can use for sending payment slips
	 * @return array
	 */
	public function getAvailableSmsServices(): array
	{
		static $services;
		if (!Loader::includeModule('crm'))
		{
			$services = [];
		}

		if (!isset($services))
		{
			$services = [];
			$selectedServiceId = $this->getSelectedSmsServiceId();
			$hasSelected = false;
			$sendersList = CrmManager::getInstance()->getUsableSmsSendersList();
			foreach ($sendersList as $sender)
			{
				$services[] = [
					'ID' => $sender['id'],
					'NAME' => $sender['name'],
					'SELECTED' => $sender['id'] === $selectedServiceId,
				];

				$hasSelected = $hasSelected || ($sender['id'] === $selectedServiceId);
			}

			if (!$hasSelected && isset($services[0]))
			{
				$services[0]['SELECTED'] = true;
			}
		}

		return $services;
	}
}