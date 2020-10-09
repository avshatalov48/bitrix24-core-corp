<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CTimeZone
{
	protected static $enabled = 1;
	protected static $useTimeZones = false;

	public static function Possible()
	{
		//since PHP 5.2
		return true;
	}

	public static function Enabled()
	{
		return (self::$enabled > 0 && self::OptionEnabled());
	}

	public static function OptionEnabled()
	{
		if(self::$useTimeZones === false)
		{
			self::$useTimeZones = COption::GetOptionString("main", "use_time_zones", "N");
		}
		return (self::$useTimeZones == "Y");
	}

	public static function Disable()
	{
		self::$enabled --;
	}

	public static function Enable()
	{
		self::$enabled ++;
	}

	private static function __tzsort($a, $b)
	{
		if($a['offset'] == $b['offset'])
			return strcmp($a['timezone_id'], $b['timezone_id']);
		return ($a['offset'] < $b['offset']? -1 : 1);
	}

	public static function GetZones()
	{
		IncludeModuleLangFile(__FILE__);

		$aTZ = array();
		static $aExcept = array("Etc/", "GMT", "UTC", "UCT", "HST", "PST", "MST", "CST", "EST", "CET", "MET", "WET", "EET", "PRC", "ROC", "ROK", "W-SU");
		foreach(DateTimeZone::listIdentifiers() as $tz)
		{
			foreach($aExcept as $ex)
				if(mb_strpos($tz, $ex) === 0)
					continue 2;
			try
			{
				$oTz = new DateTimeZone($tz);
				$aTZ[$tz] = array('timezone_id'=>$tz, 'offset'=>$oTz->getOffset(new DateTime("now", $oTz)));
			}
			catch(Exception $e){}
		}

		uasort($aTZ, array('CTimeZone', '__tzsort'));

		$aZones = array(""=>GetMessage("tz_local_time"));
		foreach($aTZ as $z)
			$aZones[$z['timezone_id']] = '(UTC'.($z['offset'] <> 0? ' '.($z['offset'] < 0? '-':'+').sprintf("%02d", ($h = floor(abs($z['offset'])/3600))).':'.sprintf("%02d", abs($z['offset'])/60 - $h*60) : '').') '.$z['timezone_id'];

		return $aZones;
	}

	public static function SetAutoCookie()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;

		$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
		if(self::IsAutoTimeZone(trim($USER->GetParam("AUTO_TIME_ZONE"))))
		{
			$cookieDate = (new \Bitrix\Main\Type\DateTime())->add("12M");
			$cookieDate->setDate((int)$cookieDate->format('Y'), (int)$cookieDate->format('m'), 1);
			$cookieDate->setTime(0,	0);

			$APPLICATION->AddHeadString(
				'<script type="text/javascript">var bxDate = new Date(); document.cookie="'.$cookie_prefix.'_TIME_ZONE="+bxDate.getTimezoneOffset()+"; path=/; expires='.$cookieDate->format("r").'"</script>', true
			);
		}
		elseif(isset($_COOKIE[$cookie_prefix."_TIME_ZONE"]))
		{
			unset($_COOKIE[$cookie_prefix."_TIME_ZONE"]);
			setcookie($cookie_prefix."_TIME_ZONE", "", time()-3600, "/");
		}
	}

	public static function IsAutoTimeZone($autoTimeZone)
	{
		if($autoTimeZone == "Y")
		{
			return true;
		}
		if($autoTimeZone == '')
		{
			static $defAutoZone = null;
			if($defAutoZone === null)
			{
				$defAutoZone = (COption::GetOptionString("main", "auto_time_zone", "N") == "Y");
			}
			return $defAutoZone;
		}
		return false;
	}

	public static function GetCookieValue()
	{
		static $cookie_prefix = null;
		if($cookie_prefix === null)
		{
			$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
		}

		if(isset($_COOKIE[$cookie_prefix."_TIME_ZONE"])	&& $_COOKIE[$cookie_prefix."_TIME_ZONE"] <> '')
		{
			return intval($_COOKIE[$cookie_prefix."_TIME_ZONE"]);
		}

		return null;
	}

	/**
	 * Emulates timezone got from JS cookie setter like in SetAutoCookie.
	 *
	 * @param int $timezoneOffset Time zone offset
	 */
	public static function SetCookieValue($timezoneOffset)
	{
		static $cookie_prefix = null;
		if($cookie_prefix === null)
		{
			$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
		}

		$_COOKIE[$cookie_prefix."_TIME_ZONE"] = $timezoneOffset;
	}

	/**
	 * @param int|null $USER_ID If USER_ID is set offset is taken from DB
	 * @param bool $forced If set, offset is calculated regardless enabling/disabling by functions Enable()/Disable().
	 * @return int
	 */
	public static function GetOffset($USER_ID = null, $forced = false)
	{
		global $USER;

		if($forced)
		{
			if(!self::OptionEnabled())
			{
				return 0;
			}
		}
		else
		{
			if(!self::Enabled())
			{
				return 0;
			}
		}

		try //possible DateTimeZone incorrect timezone
		{
			$localTime = new DateTime();
			$localOffset = $localTime->getOffset();
			$userOffset = $localOffset;

			$autoTimeZone = $userZone = '';
			$factOffset = 0;
			if($USER_ID !== null)
			{
				$dbUser = CUser::GetByID($USER_ID);
				if(($arUser = $dbUser->Fetch()))
				{
					$autoTimeZone = trim($arUser["AUTO_TIME_ZONE"]);
					$userZone = $arUser["TIME_ZONE"];
					$factOffset = intval($arUser["TIME_ZONE_OFFSET"]);
				}
			}
			elseif(is_object($USER))
			{
				$autoTimeZone = trim($USER->GetParam("AUTO_TIME_ZONE"));
				$userZone = $USER->GetParam("TIME_ZONE");
			}

			if($autoTimeZone == "N")
			{
				//manually set time zone
				$userTime = ($userZone <> ""? new DateTime(null, new DateTimeZone($userZone)) : $localTime);
				$userOffset = $userTime->getOffset();
			}
			else
			{
				if(self::IsAutoTimeZone($autoTimeZone))
				{
					if($USER_ID !== null)
					{
						//auto time zone from DB
						return $factOffset;
					}
					if(($cookie = self::GetCookieValue()) !== null)
					{
						//auto time zone from cookie
						$userOffset = -($cookie)*60;
					}
					elseif(is_object($USER))
					{
						//auto time zone from the session, set on Authorize
						return intval($USER->GetParam("TIME_ZONE_OFFSET"));
					}
				}
				else
				{
					//default server time zone
					$serverZone = COption::GetOptionString("main", "default_time_zone", "");
					$serverTime = ($serverZone <> ""? new DateTime(null, new DateTimeZone($serverZone)) : $localTime);
					$userOffset = $serverTime->getOffset();
				}
			}
		}
		catch(Exception $e)
		{
			return 0;
		}
		return $userOffset - $localOffset;
	}
}
