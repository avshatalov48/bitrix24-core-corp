<?php

namespace Bitrix\Intranet\Command;

use Bitrix\Bitrix24\Integration\Network\RegisterSettingsSynchronizer;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Security\Random;

class RegenerateSecretCommand
{
	public function execute()
	{
		if (!Loader::includeModule("bitrix24"))
		{
			throw new SystemException("bitrix24 module is not installed");
		}

		RegisterSettingsSynchronizer::setRegisterSettings([
			"REGISTER_SECRET" => Random::getString(8, true)
		]);
	}
}