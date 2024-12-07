<?php

namespace Bitrix\IntranetMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile intranet workspace (intranetmobile)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/intranetmobile/';
	}
}
