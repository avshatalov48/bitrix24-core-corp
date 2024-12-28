<?php

namespace Bitrix\ImMobile\Controller;

class Settings extends BaseController
{
	public function getAction(): array
	{
		return [
			'IS_BETA_AVAILABLE' => \Bitrix\ImMobile\Settings::isBetaAvailable(),
			'IS_CHAT_LOCAL_STORAGE_AVAILABLE' => \Bitrix\ImMobile\Settings::isChatLocalStorageAvailable(),
		];
	}
}