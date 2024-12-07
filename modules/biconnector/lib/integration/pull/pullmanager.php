<?php

namespace Bitrix\BIConnector\Integration\Pull;

use Bitrix\Main\Loader;

class PullManager
{
	private function __construct(){}

	public static function getNotifyer(): Notifyer\NotifyerInterface
	{
		if (Loader::includeModule('pull'))
		{
			return new Notifyer\PullNotifyer();
		}

		return new Notifyer\NullNotifyer();
	}

	public static function onGetDependentModule()
	{
		return [
			'MODULE_ID' => "biconnector",
			'USE' => ["PUBLIC_SECTION"]
		];
	}
}