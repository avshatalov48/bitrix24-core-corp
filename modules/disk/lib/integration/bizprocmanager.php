<?php
namespace Bitrix\Disk\Integration;

use Bitrix\Main\Loader;

class BizProcManager
{
	/**
	 * Tells if module bizproc is available.
	 *
	 * @return bool
	 */
	public static function isAvailable()
	{
		return Loader::includeModule('bizproc') && \CBPRuntime::isFeatureEnabled();
	}
}