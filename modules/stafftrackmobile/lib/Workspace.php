<?php

namespace Bitrix\StaffTrackMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile messenger workspace.
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/stafftrackmobile/';
	}
}