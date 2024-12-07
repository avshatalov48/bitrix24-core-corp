<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Controller\Action\MessageSender\ProvidersConfig;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Controller;

class MessageSender extends Controller
{
	public function configureActions(): array
	{
		return [
			'providersConfig' => [
				'class' => ProvidersConfig::class,
				'+prefilters' => [new Scope(Scope::AJAX)]
			]
		];
	}
}