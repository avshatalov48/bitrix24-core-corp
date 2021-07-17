<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Crm;

class Deal extends Main\Engine\Controller
{
	public function configureActions()
	{
		return [
			'fetchPaymentDocuments' => [
				'class' => Crm\Controller\Action\Deal\FetchPaymentDocumentsAction::class,
				'+prefilters' => [new Authentication()]
			]
		];
	}
}
