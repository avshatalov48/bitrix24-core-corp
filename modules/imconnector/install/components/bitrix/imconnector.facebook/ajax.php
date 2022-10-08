<?php

use Bitrix\Seo\Catalog\Service;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ImConnectorFacebookAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function logoutAction(): void
	{
		if (\Bitrix\Main\Loader::includeModule('seo'))
		{
			$service = Service::getInstance();
			$service::getAuthAdapter($service::TYPE_FACEBOOK)->removeAuth();
		}
	}
}
