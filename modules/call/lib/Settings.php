<?php

namespace Bitrix\Call;

use Bitrix\Main\Config\Option;
use Bitrix\Im;

class Settings
{
	public static function getMobileOptions(): array
	{
		return [
			'useCustomTurnServer' => Option::get('im', 'turn_server_self') === 'Y',
			'turnServer' => Option::get('im', 'turn_server', ''),
			'turnServerLogin' => Option::get('im', 'turn_server_login', ''),
			'turnServerPassword' => Option::get('im', 'turn_server_password', ''),
			'callLogService' => Option::get('im', 'call_log_service', ''),
			'sfuServerEnabled' => Im\Call\Call::isCallServerEnabled(),
			'bitrixCallsEnabled' => Im\Call\Call::isBitrixCallEnabled(),
			'callBetaIosEnabled' => Im\Call\Call::isIosBetaEnabled(),
		];
	}

	public static function isConferenceChatEnabled(): bool
	{
		return (bool)Option::get("call", "conference_chat_enabled", true);
	}
}
