<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use \Bitrix\Mobile\Provider\SupportProvider;

class Support extends Controller
{
	public function configureActions(): array
	{
		return [
			'getBotId' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getBotIdAction()
	{
		$supportProvider = new SupportProvider();

		return [
			'botId' => $supportProvider->getBotId(),
		];
	}
}
