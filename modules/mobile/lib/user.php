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
		$timestamp = 0;

		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			$status = \CIMStatus::GetStatus($userId);
			if ($status['MOBILE_LAST_DATE'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$timestamp = $status['MOBILE_LAST_DATE']->getTimestamp();
			}
		}
		else
		{
			$timestamp = \CUserOptions::GetOption('mobile', 'lastActivityDate', 0, $userId);
		}

		if (!$timestamp)
		{
			return false;
		}

		if (intval($timestamp)+$maxDate+60 > time())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function setOnline($userId = false, $cache = true)
	{
		global $USER;

		if (!$userId)
		{
			$userId = $USER->GetId();
		}

		$userId = intval($userId);
		if ($userId <= 0)
		{
			return false;
		}

		if ($cache && $userId == $USER->GetId())
		{
			if (isset($_SESSION['MOBILE_LAST_ONLINE_'.$userId]) && intval($_SESSION['MOBILE_LAST_ONLINE_'.$userId])+60 > time())
				return false;

			$_SESSION['MOBILE_LAST_ONLINE_'.$userId] = time();
		}

		$time = time();
		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			\CIMStatus::Set($userId, Array('MOBILE_LAST_DATE' => \Bitrix\Main\Type\DateTime::createFromTimestamp($time)));
		}
		else
		{
			\CUserOptions::SetOption('mobile', 'lastActivityDate', $time, false, $userId);
		}

		$mobileDevice = \Bitrix\Main\Context::getCurrent()->getRequest()->getCookieRaw('MOBILE_DEVICE');
		if ($mobileDevice)
		{
			$mobileDevice = mb_strtolower($mobileDevice);

			if ($mobileDevice === 'iphone' || $mobileDevice === 'ipad')
			{
				$lastTimestamp = (int)\CUserOptions::GetOption('mobile', 'iOsLastActivityDate', -1, $userId);
				if ($lastTimestamp+86400*30 < time())
				{
					\CUserOptions::SetOption('mobile', 'iOsLastActivityDate', $time, false, $userId);
				}
			}
			else if ($mobileDevice === 'android')
			{
				$lastTimestamp = (int)\CUserOptions::GetOption('mobile', 'AndroidLastActivityDate', -1, $userId);
				if ($lastTimestamp+86400*30 < time())
				{
					\CUserOptions::SetOption('mobile', 'AndroidLastActivityDate', $time, false, $userId);
				}
			}
		}

		return true;
	}

	public static function setOffline($userId = false)
	{
		global $USER;
		if (!$userId)
		{
			$userId = $USER->GetId();
		}

		$userId = intval($userId);
		if ($userId <= 0)
		{
			return false;
		}

		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			\CIMStatus::Set($userId, Array('MOBILE_LAST_DATE' => null));
		}
		else
		{
			\CUserOptions::DeleteOption('mobile', 'lastActivityDate', false, $userId);
		}

		return false;
	}
}