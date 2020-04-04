<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

class ImEventHandler
{
	const SECTION = 'IM';

	const TITLE = 'INTRANET_USTAT_SECTION_IM_NAME';

	public static function getTitle()
	{
		IncludeModuleLangFile(__FILE__);

		return GetMessage(static::TITLE);
	}

	public static function registerListeners()
	{
		RegisterModuleDependences("im", "OnAfterMessagesAdd", "intranet", "\\".__CLASS__, "onAfterMessagesAddEvent");
		RegisterModuleDependences("im", "OnCallStart", "intranet", "\\".__CLASS__, "onCallStartEvent");
	}

	public static function unregisterListeners()
	{
		UnRegisterModuleDependences("im", "OnAfterMessagesAdd", "intranet", "\\".__CLASS__, "onAfterMessagesAddEvent");
		UnRegisterModuleDependences("im", "OnCallStart", "intranet", "\\".__CLASS__, "onCallStartEvent");
	}

	public static function onAfterMessagesAddEvent($ID, $arParams)
	{
		UStat::incrementCounter(static::SECTION, $arParams['FROM_USER_ID']);
	}

	public static function onCallStartEvent($arParams)
	{
		UStat::incrementCounter(static::SECTION, $arParams['USER_ID']);
	}
}