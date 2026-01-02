<?php

namespace Bitrix\ImMobile\Controller;

class Settings extends BaseController
{
	public function getAction(): array
	{
		return [
			'IS_BETA_AVAILABLE' => \Bitrix\ImMobile\Settings::isBetaAvailable(),
			'IS_CHAT_LOCAL_STORAGE_AVAILABLE' => \Bitrix\ImMobile\Settings::isChatLocalStorageAvailable(),
			'IS_MESSENGER_V2_ENABLED' => \Bitrix\ImMobile\Settings::isMessengerV2Enabled(),
		];
	}

	public function toggleMessengerV2Action(): array
	{
		return \Bitrix\ImMobile\Settings::toggleMessengerV2ForCurrentUser();
	}
}