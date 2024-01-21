<?php

namespace Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\Before;

use Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\ActionProvider;
use Bitrix\CrmMobile\Integration\Sale\Payment\LocHelper;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

LocHelper::loadMessages();

class YandexCheckoutProvider implements ActionProvider
{
	public function provide(): ?array
	{
		$isRegistered = (bool)Option::get('sale', 'YANDEX_CHECKOUT_OAUTH_WEBHOOK_REGISTER', false);

		return [
			'done' => $isRegistered,
			'action' => 'sale.paysystem.entity.registerYookassaWebhook',
			'error' => Loc::getMessage('M_CRM_PSC_AP_BEFORE_CONNECT_TO_YOO_KASSA_WEBHOOK_ERROR'),
		];
	}
}
