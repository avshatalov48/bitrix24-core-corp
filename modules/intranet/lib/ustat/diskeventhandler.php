<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

class DiskEventHandler
{
	const SECTION = 'DISK';

	const TITLE = 'INTRANET_USTAT_SECTION_DISK_NAME';

	public static function getTitle()
	{
		IncludeModuleLangFile(__FILE__);

		return GetMessage(static::TITLE);
	}

	public static function registerListeners()
	{
		RegisterModuleDependences("webdav", "OnAfterDiskFileAdd", "intranet", "\\".__CLASS__, "onAfterDiskFileAddEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFileUpdate", "intranet", "\\".__CLASS__, "onAfterDiskFileUpdateEvent");

		RegisterModuleDependences("webdav", "OnAfterDiskFolderAdd", "intranet", "\\".__CLASS__, "onAfterDiskFolderAddEvent");
		RegisterModuleDependences("webdav", "OnAfterDiskFolderUpdate", "intranet", "\\".__CLASS__, "onAfterDiskFolderUpdateEvent");

		RegisterModuleDependences("webdav", "OnAfterDiskFirstUsageByDay", "intranet", "\\".__CLASS__, "onAfterDiskFirstUsageByDayEvent");
	}

	public static function unregisterListeners()
	{
		UnRegisterModuleDependences("webdav", "OnAfterDiskFileAdd", "intranet", "\\".__CLASS__, "onAfterDiskFileAddEvent");
		UnRegisterModuleDependences("webdav", "OnAfterDiskFileUpdate", "intranet", "\\".__CLASS__, "onAfterDiskFileUpdateEvent");

		UnRegisterModuleDependences("webdav", "OnAfterDiskFolderAdd", "intranet", "\\".__CLASS__, "onAfterDiskFolderAddEvent");
		UnRegisterModuleDependences("webdav", "OnAfterDiskFolderUpdate", "intranet", "\\".__CLASS__, "onAfterDiskFolderUpdateEvent");

		UnRegisterModuleDependences("webdav", "OnAfterDiskFirstUsageByDay", "intranet", "\\".__CLASS__, "onAfterDiskFirstUsageByDayEvent");
	}

	public static function onAfterDiskFileAddEvent($ID, $fileData)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterDiskFileUpdateEvent($ID, $fileData)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterDiskFolderAddEvent($ID, $fileData)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterDiskFolderUpdateEvent($ID, $fileData)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterDiskFirstUsageByDayEvent()
	{
		UStat::incrementCounter(static::SECTION);
	}
}