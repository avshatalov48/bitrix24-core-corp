<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\IntranetMobile;

class Settings extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'isBetaAvailable',
			'isBetaActive',
			'activateBeta',
			'deactivateBeta',
		];
	}

	public function isBetaAvailableAction(): bool
	{
		return IntranetMobile\Settings::getInstance()->isBetaAvailable();
	}

	public function isBetaActiveAction(): bool
	{
		return IntranetMobile\Settings::getInstance()->isBetaActive();
	}

	public function activateBetaAction(): void
	{
		IntranetMobile\Settings::getInstance()->activateBeta();
	}

	public function deactivateBetaAction(): void
	{
		IntranetMobile\Settings::getInstance()->deactivateBeta();
	}
}
