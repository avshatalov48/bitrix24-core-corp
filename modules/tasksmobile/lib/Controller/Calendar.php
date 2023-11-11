<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;

class Calendar extends Controller
{
	public function configureActions(): array
	{
		return [
			'getSettings' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getSettingsAction(): array
	{
		return \Bitrix\Tasks\Util\Calendar::getSettings();
	}
}