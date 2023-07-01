<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\StatefulList\BaseController;

Loader::requireModule('crm');
Loader::requireModule('crmmobile');
Loader::requireModule('sale');
Loader::requireModule('catalog');
Loader::requireModule('mobile');
Loader::requireModule('currency');
Loader::requireModule('seo');

class Terminal extends BaseController
{
	protected const PREFIX = 'crmmobile.Terminal';

	public function configureActions(): array
	{
		return [
			'initialize' => [
				'class' => Action\Terminal\InitializeAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadPayments' => [
				'class' => Action\Terminal\GetPaymentListAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'getPayment' => [
				'class' => Action\Terminal\GetPaymentAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'createPayment' => [
				'class' => Action\Terminal\CreatePaymentAction::class,
			],
			'initiatePay' => [
				'class' => Action\Terminal\InitiatePayAction::class,
			],
			'findClient' => [
				'class' => Action\Terminal\FindClient::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
		];
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}
}
