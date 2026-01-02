<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\JsonController;

final class SettingsV2Feature extends JsonController
{
	public function configureActions(): array
	{
		return [
			'isEnabled' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function isEnabledAction(): bool
	{
		return (new \Bitrix\Mobile\Feature\SettingsV2Feature())->isEnabled();
	}
}
