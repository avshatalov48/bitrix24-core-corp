<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

class SocnetEventHandler
{
	const SECTION = 'SOCNET';

	const TITLE = 'INTRANET_USTAT_SECTION_SOCNET_NAME';

	public static function getTitle()
	{
		IncludeModuleLangFile(__FILE__);

		return GetMessage(static::TITLE);
	}

	public static function registerListeners()
	{
		RegisterModuleDependences("blog", "OnPostAdd", "intranet", "\\".__CLASS__, "onPostAddEvent");
		RegisterModuleDependences("blog", "OnCommentAdd", "intranet", "\\".__CLASS__, "onCommentAddEvent");
	}

	public static function unregisterListeners()
	{
		UnRegisterModuleDependences("blog", "OnPostAdd", "intranet", "\\".__CLASS__, "onPostAddEvent");
		UnRegisterModuleDependences("blog", "OnCommentAdd", "intranet", "\\".__CLASS__, "onCommentAddEvent");
	}

	public static function onPostAddEvent($ID, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onCommentAddEvent($ID, $arFields)
	{
		if (!empty($arFields['UF_BLOG_COMMENT_FH']))
		{
			return false;
		}

		UStat::incrementCounter(static::SECTION);
	}
}