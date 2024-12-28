<?php

namespace Bitrix\DiskMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile disk workspace (diskmobile)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/diskmobile/';
	}
}
