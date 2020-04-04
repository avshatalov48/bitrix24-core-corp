<?php

namespace Bitrix\Disk\Integration;


use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class NotifySchema
{
	public static function onGetNotifySchema()
	{
		return array(
			"disk" => array(
				"files" => Array(
					"NAME" => Loc::getMessage('DISK_NOTIFY_SCHEMA_FILES_CATEGORY'),
					"SITE" => "Y",
					"MAIL" => "N",
					"XMPP" => "N",
					"PUSH" => "N",
					"DISABLED" => Array(
						IM_NOTIFY_FEATURE_XMPP,
						IM_NOTIFY_FEATURE_MAIL,
						IM_NOTIFY_FEATURE_PUSH,
					),
				),
			),
		);
	}
}