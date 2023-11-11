<?php

namespace Bitrix\ImMobile;

class MessengerParams
{
	public static function get(): array
	{
		return [
			'IS_BETA_AVAILABLE' => Settings::isBetaAvailable(),
		];
	}
}
