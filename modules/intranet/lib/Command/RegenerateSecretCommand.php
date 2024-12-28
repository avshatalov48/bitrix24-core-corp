<?php

namespace Bitrix\Intranet\Command;

use Bitrix\Main\Loader;
use Bitrix\Socialservices\Network;
use Bitrix\Main\SystemException;
use Bitrix\Main\Security\Random;

class RegenerateSecretCommand
{
	public function execute()
	{
		if (!Loader::includeModule("socialservices"))
		{
			throw new SystemException("socialservices module is not installed");
		}

		Network::setRegisterSettings([
			"REGISTER_SECRET" => Random::getString(8, true)
		]);
	}
}