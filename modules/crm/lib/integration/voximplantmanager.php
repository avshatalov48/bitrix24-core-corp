<?php

namespace Bitrix\Crm\Integration;
use Bitrix\Main\Loader;
use Bitrix\Voximplant\Transcript;

class VoxImplantManager
{
	public static function getCallInfo($callID)
	{
		if(!Loader::includeModule('voximplant'))
		{
			return null;
		}

		$info = \CVoxImplantHistory::getBriefDetails($callID);
		return is_array($info) ? $info : null;
	}

	public static function saveComment($callId, $comment)
	{
		if(!Loader::includeModule('voximplant'))
		{
			return null;
		}

		\CVoxImplantHistory::saveComment($callId, $comment);
	}
}