<?php
namespace Bitrix\Mobile;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

class Deeplink
{
	private const domain = "https://bitrix24.page.link/";
	private const androidPackage = "com.bitrix24.android";
	private const iosBundleID = "com.bitrixsoft.cpmobile";
	private const iosID = "561683423";

	public static function getAuthLink($intent, int $userId = null)
	{
		$hash = Auth::getOneTimeAuthHash($userId);
		$request = Context::getCurrent()->getRequest();
		$server = Context::getCurrent()->getServer();
		$host = defined('BX24_HOST_NAME') ? BX24_HOST_NAME : $server->getHttpHost();
		$host = ($request->isHttps() ? 'https' : 'http').'://'.preg_replace("/:(443|80)$/", "", $host);
		$link = $host."/?intent=".urlencode("${intent};${hash}");
		return self::domain."?link=${link}&apn=".self::androidPackage."&isi=".self::iosID. "&ibi=".self::iosBundleID ;
	}

	public static function onOneTimeHashRemoved($userId, $hash) {
		if(Loader::includeModule('pull'))
		{
			\CPullStack::AddByUser($userId,
				array(
					'module_id' => 'mobile',
					'command' => 'onDeeplinkShouldRefresh',
					'params' => ['previous_hash' => $hash],
				)
			);
		}
	}
}
