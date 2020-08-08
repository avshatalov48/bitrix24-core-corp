<?php
namespace Bitrix\Intranet\Integration;

use Bitrix\Main\Localization\Loc;

class Im
{
	public static function onGetNotifySchema()
	{
		return [
			"intranet" => [
				"security_otp" => [
					"NAME" => Loc::getMessage('INTRANET_NOTIFY_SCHEMA_SECURITY_OTP'),
					"SITE" => "N",
					"MAIL" => "N",
					"XMPP" => "N",
					"PUSH" => "Y",
					"DISABLED" => Array(
						IM_NOTIFY_FEATURE_XMPP,
						IM_NOTIFY_FEATURE_MAIL,
					),
				],
			],
		];
	}
}