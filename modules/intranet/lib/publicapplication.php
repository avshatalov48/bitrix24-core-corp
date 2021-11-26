<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Intranet;

class PublicApplication extends \Bitrix\Main\Authentication\Application
{
	protected $validUrls = array(
		"/desktop_app/",
		"/online/",
		"/video/",
		"/docs/pub/",
		"/pub/",
		"/rest/",
		"/bitrix/services/main/ajax.php",
		"/bitrix/services/rest/index.php",
		"/bitrix/tools/sale_ps_success.php",
		"/bitrix/tools/sale_ps_fail.php",
		"/bitrix/tools/sale_ps_ajax.php",
		"/bitrix/tools/sale/paysystem/robokassa/redirect.php",
	);

	public static function OnApplicationsBuildList()
	{
		return array(
			"ID" => "public",
			"NAME" => "Public application",
			"DESCRIPTION" => "",
			"SORT" => 9000,
			"CLASS" => '\Bitrix\Intranet\PublicApplication',
			"VISIBLE" => false,
		);
	}

	public static function onApplicationScopeError(\Bitrix\Main\Event $event)
	{
		$applicationId = $event->getParameter('APPLICATION_ID');
		if ($applicationId == 'public')
		{
			global $USER;
			if ($USER->IsAuthorized())
			{
				$applicationUri = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getDecodedUri();
				if ($applicationUri == '/')
				{
					$USER->Logout();
					LocalRedirect('/');
				}
			}
		}
	}
}
