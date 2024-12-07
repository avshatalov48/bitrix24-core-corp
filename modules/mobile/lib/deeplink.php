<?php
namespace Bitrix\Mobile;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

class Deeplink
{
	private const domain = "https://bitrix24.page.link/";

	public static function getAuthLink($intent, int $userId = null, int $ttl = null)
	{
		$hash = Auth::getOneTimeAuthHash($userId, $ttl);
		$request = Context::getCurrent()->getRequest();
		$server = Context::getCurrent()->getServer();
		$host = defined('BX24_HOST_NAME') ? BX24_HOST_NAME : $server->getHttpHost();
		$host = ($request->isHttps() ? 'https' : 'http').'://'.preg_replace("/:(443|80)$/", "", $host);
		$link = $host."/?intent=".urlencode("${intent};${hash}");
		$data = self::getAppsData();
		return self::domain."?link=${link}&apn=".$data['apn']."&isi=".$data['isi']. "&ibi=".$data['ibi'] ;
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

	private static function getAppsData(): array {
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?? 'en';
		$ruAppEnabled = \Bitrix\Main\Config\Option::get('mobile', 'ru_app_enable', 'N') == 'Y';
		$ruRegions = ['ru', 'kz', 'by'];
		if(!in_array($region, $ruRegions) || $ruAppEnabled === false)
		{
			return [
				"apn" =>  'com.bitrix24.android',
				"ibi" => 'com.bitrixsoft.cpmobile',
				"isi" => '561683423',
			];
		}
		else
		{
			return [
				"apn" =>  'ru.bitrix.bitrix24',
				"ibi" => 'ru.bitrix.bitrix24',
				"isi" => '6670570479',
			];
		}
	}
}
