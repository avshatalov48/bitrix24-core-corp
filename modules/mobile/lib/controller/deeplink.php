<?php
namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;

class Deeplink extends Controller
{
	public function getAction($intent, int $ttl = null): array
	{
		global $USER;

		$id = $USER->getId();
		if(intval($id) <= 0)
		{
			parent::addError(new Error('User is not authorized', 401));
			return [];
		}

		return ['link' => \Bitrix\Mobile\Deeplink::getAuthLink($intent, $id, $ttl)];
	}
}
