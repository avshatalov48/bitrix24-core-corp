<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal;

use Bitrix\CrmMobile\Controller\PrimaryAutoWiredEntity;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Entity\InitializeAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Entity\CreatePaymentAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Entity\OpenPaymentPayAction;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\CrmMobile\Controller\BaseJson;
use Bitrix\Main\Loader;

Loader::requireModule('crm');
Loader::requireModule('crmmobile');
Loader::requireModule('sale');
Loader::requireModule('salescenter');
Loader::requireModule('catalog');
Loader::requireModule('mobile');
Loader::requireModule('currency');
Loader::requireModule('seo');

class Entity extends BaseJson
{
	use PrimaryAutoWiredEntity;
	
	public function configureActions(): array
	{
		return [
			'initialize' => [
				'class' => InitializeAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'createPayment' => [
				'class' => CreatePaymentAction::class,
			],
			'openPaymentPay' => [
				'class' => OpenPaymentPayAction::class,
			],
		];
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}
}
