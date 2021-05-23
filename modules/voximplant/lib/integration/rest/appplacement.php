<?php
namespace Bitrix\Voximplant\Integration\Rest;
class AppPlacement
{
	public const ANALYTICS_MENU = 'TELEPHONY_ANALYTICS_MENU';

	public static function getAll(): array
	{
		return [
			self::ANALYTICS_MENU,
		];
	}
}