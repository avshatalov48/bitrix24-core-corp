<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Engine;

class UrlManager extends Engine\Controller
{
	public function getBillingUrlAction()
	{
		$apiClient = new \CVoxImplantHttp();
		$result = $apiClient->getBillingUrl();

		if(isset($result['error']))
		{
			$this->addError(new \Bitrix\Main\Error($result['error']['msg'], $result['error']['code']));
			return null;
		}

		return [
			'billingUrl' => $result['billingUrl']
		];
	}
}