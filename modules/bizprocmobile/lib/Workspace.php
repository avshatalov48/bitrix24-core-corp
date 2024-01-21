<?php

namespace Bitrix\BizprocMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile bizproc workspace (bizproc:)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/bizprocmobile/';
	}
}
