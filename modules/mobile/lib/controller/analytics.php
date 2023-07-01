<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;

class Analytics extends Controller
{
	public function configureActions(): array
	{
		return [
			'sendLabel' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function sendLabelAction(): void
	{
	}
}
