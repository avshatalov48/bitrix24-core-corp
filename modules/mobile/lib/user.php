<?php
namespace Bitrix\Mobile;

class User
{
	function __construct()
	{
	}

	public static function checkOnline($userId = false)
	{
		$maxDate = 120;

		$LastActivityDate = \CUserOptions::GetOption('mobile', 'lastActivityDate', 0, $userId);
		if (intval($LastActivityDate)+$maxDate+60 > time())
			return true;
		else
			return false;
	}

	public static function setOnline($userId = false, $cache = true)
	{
		global $USER;

		if (!$userId)
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		if ($cache && $userId == $USER->GetId())
		{
			if (isset($_SESSION['MOBILE_LAST_ONLINE_'.$userId]) && intval($_SESSION['MOBILE_LAST_ONLINE_'.$userId])+60 > time())
				return false;

			$_SESSION['MOBILE_LAST_ONLINE_'.$userId] = time();
		}

		$time = time();
		\CUserOptions::SetOption('mobile', 'lastActivityDate', $time, false, $userId);

		if (\CModule::IncludeModule("im"))
		{
			\CIMStatus::SetMobile($userId, true);
		}

		return true;
	}

	public static function setOffline($userId = false)
	{
		global $USER;

		if (!$userId)
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		\CUserOptions::DeleteOption('mobile', 'lastActivityDate', false, $userId);

		return false;
	}
}