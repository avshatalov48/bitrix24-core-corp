<?php

namespace Bitrix\TasksMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile tasks workspace (tasksmobile)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/tasksmobile/';
	}
}
