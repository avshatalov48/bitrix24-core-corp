<?php

namespace Bitrix\SignMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile  workspace.
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/signmobile/';
	}
}