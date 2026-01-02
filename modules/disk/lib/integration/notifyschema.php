<?php

namespace Bitrix\Disk\Integration;

use Bitrix\Main\Localization\Loc;

class NotifySchema
{
	public static function onGetNotifySchema()
	{
		return [
			"disk" => [
				"NOTIFY" => [
					"files" => [
						"NAME" => Loc::getMessage('DISK_NOTIFY_SCHEMA_FILES_CATEGORY'),
						"SITE" => "Y",
						"MAIL" => "N",
						"XMPP" => "N",
						"PUSH" => "N",
						"DISABLED" => [
							IM_NOTIFY_FEATURE_XMPP,
							IM_NOTIFY_FEATURE_MAIL,
							IM_NOTIFY_FEATURE_PUSH,
						],
					],
					"deletion" => [
						"NAME" => Loc::getMessage('DISK_NOTIFY_SCHEMA_FILES_CATEGORY_DELETE'),
						"SITE" => "Y",
						"MAIL" => "N",
						"XMPP" => "N",
						"PUSH" => "N",
						"DISABLED" => [
							IM_NOTIFY_FEATURE_XMPP,
							IM_NOTIFY_FEATURE_MAIL,
							IM_NOTIFY_FEATURE_PUSH,
						],
					],
					"sharing_user_disconnect" => [
						"NAME" => Loc::getMessage('DISK_NOTIFY_SCHEMA_SHARING_USER_DISCONNECT'),
						"MAIL" => "N",
						"PUSH" => "Y",
					],
					"sharing_autoconnect" => [
						"NAME" => Loc::getMessage('DISK_NOTIFY_SCHEMA_SHARING_AUTOCONNECT'),
						"MAIL" => "N",
						"PUSH" => "Y",
					],
				],
			],
		];
	}
}