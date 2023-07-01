<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\CrmMobile\AhaMoments\Factory;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;

class AhaMoment extends Controller
{
	public function configureActions(): array
	{
		return [
			'setViewed' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function setViewedAction(string $name): void
	{
		$ahaMomentsFactory = Factory::getInstance();

		$ahaMomentsFactory->getAhaInstance($name)->setViewed();
	}
}
