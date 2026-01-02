<?php

namespace Bitrix\Call\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Call\JwtCall;
use Bitrix\Call\Signaling;

class Settings extends Controller
{
	/**
	 * Generates a secret key for call JWT
	 * @restMethod call.Settings.registerKey
	 */
	public function registerKeyAction(): void
	{
		$result = JwtCall::registerPortal();
		if ($result->isSuccess())
		{
			Signaling::sendClearCallTokens();
		}
		else
		{
			$this->addError($result->getError());
		}
	}
}
