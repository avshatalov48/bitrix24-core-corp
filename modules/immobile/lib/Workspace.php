<?php

namespace Bitrix\ImMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile messenger workspace (im:)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/immobile/';
	}
	
	public static function getImmobileJNDevWorkspace()
	{
		return "/dev/immobile/imdevmobile/mobileapp";
	}
}
