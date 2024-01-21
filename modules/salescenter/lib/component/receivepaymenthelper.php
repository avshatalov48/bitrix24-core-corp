<?php

namespace Bitrix\SalesCenter\Component;

use Bitrix\Crm\Activity\Provider\BaseMessage;
use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\MessageSender\SenderRepository;
use Bitrix\Crm\Order\Payment;
use Bitrix\Main\Loader;
use Bitrix\MessageService\Message;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;

class ReceivePaymentHelper
{
	public static function getSendersData(): ?array
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$senders = SenderRepository::getPrioritizedList();
		if (empty($senders))
		{
			return null;
		}

		$sendersData = [];
		foreach ($senders as $sender)
		{
			$senderData = [
				'code' => $sender::getSenderCode(),
				'isAvailable' => $sender::isAvailable(),
				'isConnected' => $sender::isConnected(),
				'connectUrl' => $sender::getConnectUrl(),
				'usageErrors' =>  $sender::getUsageErrors()
			];
			if ($sender::getSenderCode() === SmsManager::getSenderCode())
			{
				$senderData['smsSenders'] = self::getSmsSenderList();
			}

			$sendersData[] = $senderData;
		}

		return $sendersData;
	}

	public static function getSendingMethodDescByType(string $type, string $mode, ?Payment $payment = null): ?array
	{
		if ($type === 'sms')
		{
			$lastPaymentSms = null;
			$provider = null;
			$availableProviders = self::getAvailableSmsProviderIds();
			$defaultProvider = $availableProviders[0] ?? '';

			if ($payment && $mode === 'view')
			{
				$lastPaymentSmsParams = self::getLastPaymentSmsParams();
				if (is_array($lastPaymentSmsParams))
				{
					if (isset($lastPaymentSmsParams['SENDER_ID']))
					{
						$provider = $lastPaymentSmsParams['SENDER_ID'];
					}

					if (isset($lastPaymentSmsParams['MESSAGE_BODY']))
					{
						$lastPaymentSms = $lastPaymentSmsParams['MESSAGE_BODY'];
					}
				}
			}
			else
			{
				$userOptions = \CUserOptions::GetOption('salescenter', 'payment_sms_provider_options');
				if (is_array($userOptions) && isset($userOptions['latest_selected_provider']))
				{
					$provider = $userOptions['latest_selected_provider'];
				}
			}

			return [
				'provider' => in_array($provider, $availableProviders) ? $provider : $defaultProvider,
				'text' => $lastPaymentSms ?? CrmManager::getInstance()->getSmsTemplate(),
				'defaultText' => CrmManager::getInstance()->getDefaultSmsTemplate(),
				'defaultTextWrapped' => CrmManager::getInstance()->getDefaultWrappedSmsTemplate(),
				'sent' => (bool)$lastPaymentSms,
				'text_modes' => CrmManager::getInstance()->getAllSmsTemplates(),
			];
		}

		if ($type === 'chat')
		{
			return [
				'text' => ImOpenLinesManager::getInstance()->getImMessagePreview(),
				'text_modes' => ImOpenLinesManager::getInstance()->getAllImMessagePreviews(),
			];
		}

		return null;
	}

	private static function getLastPaymentSmsParams(?Payment $payment = null): ?array
	{
		if (!$payment || !Loader::includeModule('messageservice'))
		{
			return null;
		}

		$activityResult = \CCrmActivity::GetList(
			['ID' => 'DESC'],
			[
				'BINDINGS' => [
					[
						'OWNER_ID' => $payment->getId(),
						'OWNER_TYPE_ID' => \CCrmOwnerType::OrderPayment,
					]
				],
				'PROVIDER_ID' => Sms::getId(),
				'PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
			]
		);
		if (!$activityResult)
		{
			return null;
		}

		$activity = $activityResult->fetch();
		if (!$activity)
		{
			return null;
		}

		$message = Message::getFieldsById((int)$activity['ASSOCIATED_ENTITY_ID']);

		return is_array($message) ? $message : null;
	}

	private static function getAvailableSmsProviderIds(): array
	{
		$result = [];
		$list = self::getSmsSenderList();
		foreach ($list as $provider)
		{
			if (isset($provider['id']) && $provider['id'] !== '')
			{
				$result[] = (string)$provider['id'];
			}
		}
		return $result;
	}

	private static function getSmsSenderList(): array
	{
		$result = [];
		$restSender = null;

		$senderList = SmsManager::getSenderInfoList(true);
		foreach ($senderList as $sender)
		{
			if ($sender['canUse'])
			{
				if ($sender['id'] === 'rest')
				{
					$restSender = $sender;

					continue;
				}

				$result[] = $sender;
			}
		}

		if ($restSender !== null)
		{
			foreach ($restSender['fromList'] as $sender)
			{
				$result[] = $sender;
			}
		}

		return $result;
	}
}
