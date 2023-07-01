<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

class Option extends Base
{
	public function saveLatestSelectedProviderAction(string $provider): void
	{
		$paymentSmsProviderOptions = \CUserOptions::GetOption(
			'salescenter',
			'payment_sms_provider_options',
		);
		if (!is_array($paymentSmsProviderOptions))
		{
			return;
		}

		$paymentSmsProviderOptions['latest_selected_provider'] = $provider;
		\CUserOptions::SetOption(
			'salescenter',
			'payment_sms_provider_options',
			$paymentSmsProviderOptions
		);
	}

	public function saveIsNeedToSkipPaymentSystemsAction(bool $isNeedToSkipPaymentSystems): void
	{
		\CUserOptions::SetOption(
			'salescenter',
			'is_need_to_skip_payment_systems',
			$isNeedToSkipPaymentSystems
		);
	}
}