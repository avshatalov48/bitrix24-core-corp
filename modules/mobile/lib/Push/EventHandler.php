<?php

namespace Bitrix\Mobile\Push;

class EventHandler
{
	private static array $aliases = [
		"com.alaio.app",
		"Bitrix24",
		"com.bitrixsoft.cpmobile",
		"com.bitrix24.android",
		"ru.bitrix.bitrix24",
	];

	private static string $mainAppId = "Bitrix24";

	public static function onPushTokenUniqueHashGet($userId, $appId): ?string
	{
		$appId = str_replace("_bxdev", "", $appId);
		if (in_array($appId, self::$aliases))
		{
			return md5($userId . self::$mainAppId);
		}

		return null;
	}
}