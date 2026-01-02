<?php

namespace Bitrix\Rpa\Integration\Im;

use Bitrix\Main\Localization\Loc;

class NotifySchema
{
	public static function onGetNotifySchema()
	{
		return [
			'rpa' => [
				'NAME' => Loc::getMessage('RPA_NOTIFY_SCHEMA_BLOCK_TITLE'),
				'NOTIFY' => [
					'mention' => [
						'NAME' => Loc::getMessage('RPA_NOTIFY_SCHEMA_MENTION'),
						"MAIL" => "N",
						"PUSH" => "Y",
					],
				],
			],
		];
	}
}
