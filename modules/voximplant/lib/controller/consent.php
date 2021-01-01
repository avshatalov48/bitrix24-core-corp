<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Controller;

class Consent extends Controller
{
	public function saveTOSConsentAction()
	{
		$server = Context::getCurrent()->getServer();
		$apiClient = new \CVoxImplantHttp();
		$result = $apiClient->saveTOSConsent($server->getRemoteAddr(), $server->getUserAgent());

		if(isset($result['error']))
		{
			$this->addError(new \Bitrix\Main\Error($result['error']['msg'], $result['error']['code']));
		}

		return null;
	}
}