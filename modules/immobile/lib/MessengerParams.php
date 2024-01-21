<?php

namespace Bitrix\ImMobile;

class MessengerParams
{
	public static function get(): array
	{
		return [
			'IS_BETA_AVAILABLE' => Settings::isBetaAvailable(),
			'IS_CHAT_M1_ENABLED' => Settings::isChatM1Enabled(),
			'IS_CHAT_LOCAL_STORAGE_AVAILABLE' => Settings::isChatLocalStorageAvailable(),
			'SHOULD_SHOW_CHAT_V2_UPDATE_HINT' => Settings::shouldShowChatV2UpdateHint(),
			'SMILE_LAST_UPDATE_DATE' => \CSmile::getLastUpdate()->format(\DateTime::ATOM),
		];
	}
}
