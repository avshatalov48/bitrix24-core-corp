<?php

namespace Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\Oauth;

use Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\ActionProvider;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\Checkout\Service;

class YandexCheckoutProvider implements ActionProvider
{
	private const YANDEXCHECKOUT_REGISTER_URL = 'https://yookassa.ru/joinups/?source=bitrix24';

	public function provide(): ?array
	{
		$authAdapter = Service::getAuthAdapter(Service::TYPE_YOOKASSA);
		$authAdapter->setParameters(['URL_PARAMETERS' => ['isMobileApp' => true]]);

		return [
			'done' => $authAdapter->hasAuth(),
			'title' => Loc::getMessage('M_CRM_PSC_AP_OAUTH_CONNECT_TO_YOO_KASSA_TITLE'),
			'text' => Loc::getMessage('M_CRM_PSC_AP_OAUTH_CONNECT_TO_YOO_KASSA_TEXT'),
			'items' => [
				[
					'id' => 'authorize',
					'type' => 'oauth',
					'title' => Loc::getMessage('M_CRM_PSC_AP_OAUTH_CONNECT_TO_YOO_KASSA_AUTHORIZE'),
					'params' => [
						'url' => $authAdapter->getAuthUrl(),
						'error' => Loc::getMessage('M_CRM_PSC_AP_OAUTH_CONNECT_TO_YOO_KASSA_AUTHORIZE_ERROR'),
					],
				],
				[
					'id' => 'register',
					'type' => 'externalLink',
					'title' => Loc::getMessage('M_CRM_PSC_AP_OAUTH_CONNECT_TO_YOO_KASSA_REGISTER'),
					'params' => [
						'url' => self::YANDEXCHECKOUT_REGISTER_URL,
					],
				],
			],
		];
	}
}
