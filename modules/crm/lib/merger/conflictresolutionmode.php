<?php
namespace Bitrix\Crm\Merger;

use Bitrix\Main\Localization\Loc;

class ConflictResolutionMode
{
	const UNDEFINED         = 0;
	const ASK_USER          = 1;
	const NEVER_OVERWRITE   = 2;
	const ALWAYS_OVERWRITE  = 3;
	const MANUAL = 4;

	const ASK_USER_NAME          = 'ASK_USER';
	const NEVER_OVERWRITE_NAME   = 'NEVER_OVERWRITE';
	const ALWAYS_OVERWRITE_NAME  = 'ALWAYS_OVERWRITE';
	const MANUAL_NAME = 'MANUAL';

	const FIRST = 1;
	const LAST  = 4;

	private static $captions = array();

	public static function isDefined($mode)
	{
		if(!is_int($mode))
		{
			$mode = (int)$mode;
		}

		return $mode >= self::FIRST && $mode <= self::LAST;
	}

	public static function getName($mode)
	{
		if(!is_int($mode))
		{
			$mode = (int)$mode;
		}

		switch($mode)
		{
			case self::ASK_USER:
				return self::ASK_USER_NAME;
			case self::NEVER_OVERWRITE:
				return self::NEVER_OVERWRITE_NAME;
			case self::ALWAYS_OVERWRITE:
				return self::ALWAYS_OVERWRITE_NAME;
			case self::MANUAL:
				return self::MANUAL;
			default:
				return '';
		}
	}

	public static function getCaptions()
	{
		if(!self::$captions[LANGUAGE_ID])
		{
			Loc::loadMessages(__FILE__);

			self::$captions[LANGUAGE_ID] = array(
				self::ASK_USER => Loc::getMessage('CRM_MERGE_CONFLICT_RESOLVE_ASK_USER'),
				self::NEVER_OVERWRITE => Loc::getMessage('CRM_MERGE_CONFLICT_RESOLVE_NEVER_OVERWRITE'),
				self::ALWAYS_OVERWRITE => Loc::getMessage('CRM_MERGE_CONFLICT_RESOLVE_ALWAYS_OVERWRITE'),
				self::MANUAL => Loc::getMessage('CRM_MERGE_CONFLICT_RESOLVE_MANUAL'),
			);
		}

		return self::$captions[LANGUAGE_ID];
	}

	public static function getCaption($mode)
	{
		$captions = self::getCaptions();
		return isset($captions[$mode]) ? $captions[$mode] : "[{$mode}]";
	}
}