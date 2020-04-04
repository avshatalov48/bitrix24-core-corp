<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

class MobileEventHandler
{
	const SECTION = 'MOBILE';

	const TITLE = 'INTRANET_USTAT_SECTION_MOBILE_NAME';

	public static function getTitle()
	{
		IncludeModuleLangFile(__FILE__);

		return GetMessage(static::TITLE);
	}

	public static function registerListeners()
	{
		RegisterModuleDependences("mobileapp", "OnMobileInit", "intranet", "\\".__CLASS__, "onMobileInitEvent");
	}

	public static function unregisterListeners()
	{
		UnRegisterModuleDependences("mobileapp", "OnMobileInit", "intranet", "\\".__CLASS__, "onMobileInitEvent");
	}

	public static function onMobileInitEvent()
	{
		UStat::incrementCounter(static::SECTION);
	}
}