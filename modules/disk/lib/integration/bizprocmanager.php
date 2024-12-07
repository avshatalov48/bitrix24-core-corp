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

	public static function isAccessible(): bool
	{
		if (!static::isAvailable())
		{
			return false;
		}

		return (
			!class_exists(\Bitrix\Bizproc\Integration\Intranet\ToolsManager::class)
			|| \Bitrix\Bizproc\Integration\Intranet\ToolsManager::getInstance()->isBizprocAvailable()
		);
	}

	public static function getInaccessibilitySliderCode(): string
	{
		if (
			Loader::includeModule('bizproc')
			&& method_exists(
				\Bitrix\Bizproc\Integration\Intranet\ToolsManager::class,
				'getBizprocUnavailableSliderCode'
			)
		)
		{
			return \Bitrix\Bizproc\Integration\Intranet\ToolsManager::getInstance()->getBizprocUnavailableSliderCode();
		}

		return 'limit_automation_off';
	}
}
