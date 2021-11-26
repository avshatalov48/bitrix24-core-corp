<?php

namespace Bitrix\Mobile;

if (!\Bitrix\Main\Loader::includeModule('rest'))
{
	return;
}

class Rest extends \IRestService
{
	public static function onRestServiceBuildDescription()
	{
		$methods = [];
		$restClasses = [
			"Bitrix\Mobile\Rest\User",
			"Bitrix\Mobile\Rest\Config",
			"Bitrix\Mobile\Rest\Forms",
			"Bitrix\Mobile\Rest\Disk",
			"Bitrix\Mobile\Rest\Settings",
			"Bitrix\Mobile\Rest\Intranet",
			"Bitrix\Mobile\Rest\Tasks",
			"Bitrix\Mobile\Rest\Component",
		];

		array_walk($restClasses, function ($restClass) use (&$methods) {
			$restClassMethods = call_user_func([$restClass, "getMethods"]);
			if (count($restClassMethods) > 0)
			{
				$methods = array_merge($methods, $restClassMethods);
			}
		});

		return ["mobile" => $methods];
	}

}
