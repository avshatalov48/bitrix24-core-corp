<?php
namespace Bitrix\Mobile;

class Deeplink
{
	private const domain = "https://bitrix24.page.link/";
	private const androidPackage = "com.bitrix24.android";
	private const iosBundleID = "com.bitrixsoft.cpmobile";
	private const iosID = "561683423";

	public static function getAuthLink($intent)
	{
		$hash = Auth::getOneTimeAuthHash();
		$scheme = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https://' : 'http://');
		$link = $scheme.BX24_HOST_NAME."/?intent=".urlencode("${intent};${hash}");
		return self::domain."?link=${link}&apn=".self::androidPackage."&isi=".self::iosID. "&ibi=".self::iosBundleID ;
	}
}