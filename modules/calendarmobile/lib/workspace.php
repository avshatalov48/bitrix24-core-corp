<?php

namespace Bitrix\CalendarMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile messenger workspace (calendarmobile)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/calendarmobile/';
	}
}
