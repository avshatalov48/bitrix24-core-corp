<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal;

use Bitrix\CrmMobile\Controller\Terminal\Actions\App\CreatePaymentAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\FindClient;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\GetPaymentAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\GetPaymentListAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\GetPaymentProductListAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\GetSearchDataAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\InitializeAction;
use Bitrix\CrmMobile\Controller\Terminal\Actions\App\InitiatePayAction;
use Bitrix\Intranet\ActionFilter\IntranetUser;
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

class App extends BaseController
{
	public function configureActions(): array
	{
		return [
			'initialize' => [
				'class' => InitializeAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadPayments' => [
				'class' => GetPaymentListAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'getSearchData' => [
				'class' => GetSearchDataAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'getPayment' => [
				'class' => GetPaymentAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'getPaymentProductList' => [
				'class' => GetPaymentProductListAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'createPayment' => [
				'class' => CreatePaymentAction::class,
			],
			'initiatePay' => [
				'class' => InitiatePayAction::class,
			],
			'findClient' => [
				'class' => FindClient::class,
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
			new IntranetUser(),
		];
	}
}
