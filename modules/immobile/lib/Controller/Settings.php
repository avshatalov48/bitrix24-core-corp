<?php

namespace Bitrix\ImMobile\Controller;

class Settings extends \Bitrix\Main\Engine\Controller
{
	public function isBetaAvailableAction(): bool
	{
		return \Bitrix\ImMobile\Settings::isBetaAvailable();
	}
}