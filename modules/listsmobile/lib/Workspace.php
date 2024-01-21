<?php

namespace Bitrix\ListsMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile lists workspace (lists)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/listsmobile/';
	}
}
