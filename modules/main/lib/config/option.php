<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */
namespace Bitrix\Main\Config;

use Bitrix\Main;

class Option
{
	const CACHE_DIR = "b_option";

	protected static $options = array();

	/**
	 * Returns a value of an option.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $default The default value to return, if a value doesn't exist.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return string
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function get($moduleId, $name, $default = "", $siteId = false)
	{
		if ($moduleId == '')
			throw new Main\ArgumentNullException("moduleId");
		if ($name == '')
			throw new Main\ArgumentNullException("name");

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$siteKey = ($siteId == ""? "-" : $siteId);

		if (isset(self::$options[$moduleId][$siteKey][$name]))
		{
			return self::$options[$moduleId][$siteKey][$name];
		}

		if (isset(self::$options[$moduleId]["-"][$name]))
		{
			return self::$options[$moduleId]["-"][$name];
		}

		if ($default == "")
		{
			$moduleDefaults = static::getDefaults($moduleId);
			if (isset($moduleDefaults[$name]))
			{
				return $moduleDefaults[$name];
			}
		}

		return $default;
	}

	/**
	 * Returns the real value of an option as it's written in a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param bool|string $siteId The site ID.
	 * @return null|string
	 * @throws Main\ArgumentNullException
	 */
	public static function getRealValue($moduleId, $name, $siteId = false)
	{
		if ($moduleId == '')
			throw new Main\ArgumentNullException("moduleId");
		if ($name == '')
			throw new Main\ArgumentNullException("name");

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$siteKey = ($siteId == ""? "-" : $siteId);

		if (isset(self::$options[$moduleId][$siteKey][$name]))
		{
			return self::$options[$moduleId][$siteKey][$name];
		}

		return null;
	}

	/**
	 * Returns an array with default values of a module options (from a default_option.php file).
	 *
	 * @param string $moduleId The module ID.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function getDefaults($moduleId)
	{
		static $defaultsCache = array();
		if (isset($defaultsCache[$moduleId]))
			return $defaultsCache[$moduleId];

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
			throw new Main\ArgumentOutOfRangeException("moduleId");

		$path = Main\Loader::getLocal("modules/".$moduleId."/default_option.php");
		if ($path === false)
			return $defaultsCache[$moduleId] = array();

		include($path);

		$varName = str_replace(".", "_", $moduleId)."_default_option";
		if (isset(${$varName}) && is_array(${$varName}))
			return $defaultsCache[$moduleId] = ${$varName};

		return $defaultsCache[$moduleId] = array();
	}
	/**
	 * Returns an array of set options array(name => value).
	 *
	 * @param string $moduleId The module ID.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	public static function getForModule($moduleId, $siteId = false)
	{
		if ($moduleId == '')
			throw new Main\ArgumentNullException("moduleId");

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$result = self::$options[$moduleId]["-"];

		if($siteId <> "" && !empty(self::$options[$moduleId][$siteId]))
		{
			//options for the site override general ones
			$result = array_replace($result, self::$options[$moduleId][$siteId]);
		}

		return $result;
	}

	protected static function load($moduleId)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = static::getCacheTtl();
		$loadFromDb = true;

		if ($cacheTtl !== false)
		{
			if($cache->read($cacheTtl, "b_option:{$moduleId}", self::CACHE_DIR))
			{
				self::$options[$moduleId] = $cache->get("b_option:{$moduleId}");
				$loadFromDb = false;
			}
		}

		if($loadFromDb)
		{
			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();

			self::$options[$moduleId] = ["-" => []];

			$query = "
				SELECT NAME, VALUE 
				FROM b_option 
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}' 
			";

			$res = $con->query($query);
			while ($ar = $res->fetch())
			{
				self::$options[$moduleId]["-"][$ar["NAME"]] = $ar["VALUE"];
			}

			try
			{
				//b_option_site possibly doesn't exist

				$query = "
					SELECT SITE_ID, NAME, VALUE 
					FROM b_option_site 
					WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}' 
				";

				$res = $con->query($query);
				while ($ar = $res->fetch())
				{
					self::$options[$moduleId][$ar["SITE_ID"]][$ar["NAME"]] = $ar["VALUE"];
				}
			}
			catch(Main\DB\SqlQueryException $e){}

			if($cacheTtl !== false)
			{
				$cache->set("b_option:{$moduleId}", self::$options[$moduleId]);
			}
		}

		/*ZDUyZmZMDNlNzUwZGQxZWFlMDQyNWU0NWFiMjllZTEyNTg4YWM=*/$GLOBALS['____1433572626']= array(base64_decode('ZXh'.'wbG9kZQ=='),base64_decode('cGFjaw'.'='.'='),base64_decode('b'.'WQ1'),base64_decode('Y2'.'9uc3R'.'hbnQ='),base64_decode('aGF'.'zaF9obWFj'),base64_decode('c3R'.'yY21w'),base64_decode('aXNfb2J'.'qZWN0'),base64_decode('Y2F'.'s'.'bF91c'.'2'.'V'.'yX2Z1bmM='),base64_decode('Y2'.'Fsb'.'F'.'91c2VyX2Z1bmM='),base64_decode('Y2Fsb'.'F91c'.'2VyX'.'2Z1b'.'mM='),base64_decode('Y2'.'FsbF'.'91c2V'.'yX'.'2Z1b'.'m'.'M'.'='),base64_decode('Y2FsbF91c2VyX2Z1bmM'.'='));if(!function_exists(__NAMESPACE__.'\\___1546909903')){function ___1546909903($_2087002148){static $_918413592= false; if($_918413592 == false) $_918413592=array('L'.'Q==','bWFpbg'.'==','bWFpbg='.'=',''.'LQ'.'==','bWFpbg==','flBB'.'UkFNX01BWF9VU0'.'VSUw'.'==','LQ==','b'.'WFpb'.'g'.'='.'=',''.'f'.'lBBUkFNX01B'.'W'.'F9VU0'.'V'.'SUw==',''.'L'.'g='.'=',''.'SCo=','Ym'.'l'.'0cml4','TElDR'.'U5'.'T'.'RV9LR'.'Vk'.'=','c2hhM'.'jU2','L'.'Q'.'==','b'.'WFpbg==',''.'flBBUkFNX01B'.'WF9VU0VS'.'Uw==','LQ==','bWF'.'pbg==','U'.'EF'.'SQU1fT'.'UF'.'YX'.'1VT'.'RVJT','VVNF'.'Ug='.'=','VV'.'NFUg'.'='.'=','V'.'VNFU'.'g'.'='.'=','SXNBdXRob3JpemVk','VVNFUg='.'=','SX'.'NBZG1pb'.'g==','QVBQ'.'T'.'E'.'l'.'DQVR'.'JT04=','Um'.'Vzd'.'G'.'FydEJ1ZmZlc'.'g==','TG9jY'.'WxS'.'Z'.'WRp'.'cmVjdA==',''.'L2xpY2Vuc2V'.'fcmVzd'.'H'.'J'.'pY3'.'Rpb24ucGh'.'w','LQ='.'=','bWF'.'pb'.'g==','flBBU'.'k'.'FN'.'X01'.'BWF9V'.'U0VSUw==','LQ'.'==',''.'bWFpbg==','UEFSQU1f'.'T'.'UFYX1VT'.'RVJT','X'.'EJpdHJpeFx'.'NYWluXENvbmZpZ1x'.'PcH'.'R'.'p'.'b246OnNl'.'dA==','bWF'.'pbg==','UEFSQU1fTUFY'.'X1VTRV'.'J'.'T');return base64_decode($_918413592[$_2087002148]);}};if(isset(self::$options[___1546909903(0)][___1546909903(1)]) && $moduleId === ___1546909903(2)){ if(isset(self::$options[___1546909903(3)][___1546909903(4)][___1546909903(5)])){ $_936730522= self::$options[___1546909903(6)][___1546909903(7)][___1546909903(8)]; list($_1279337020, $_1941023942)= $GLOBALS['____1433572626'][0](___1546909903(9), $_936730522); $_962785744= $GLOBALS['____1433572626'][1](___1546909903(10), $_1279337020); $_2040670174= ___1546909903(11).$GLOBALS['____1433572626'][2]($GLOBALS['____1433572626'][3](___1546909903(12))); $_2114301582= $GLOBALS['____1433572626'][4](___1546909903(13), $_1941023942, $_2040670174, true); self::$options[___1546909903(14)][___1546909903(15)][___1546909903(16)]= $_1941023942; self::$options[___1546909903(17)][___1546909903(18)][___1546909903(19)]= $_1941023942; if($GLOBALS['____1433572626'][5]($_2114301582, $_962785744) !==(181*2-362)){ if(isset($GLOBALS[___1546909903(20)]) && $GLOBALS['____1433572626'][6]($GLOBALS[___1546909903(21)]) && $GLOBALS['____1433572626'][7](array($GLOBALS[___1546909903(22)], ___1546909903(23))) &&!$GLOBALS['____1433572626'][8](array($GLOBALS[___1546909903(24)], ___1546909903(25)))){ $GLOBALS['____1433572626'][9](array($GLOBALS[___1546909903(26)], ___1546909903(27))); $GLOBALS['____1433572626'][10](___1546909903(28), ___1546909903(29), true);} return;}} else{ self::$options[___1546909903(30)][___1546909903(31)][___1546909903(32)]= round(0+4+4+4); self::$options[___1546909903(33)][___1546909903(34)][___1546909903(35)]= round(0+3+3+3+3); $GLOBALS['____1433572626'][11](___1546909903(36), ___1546909903(37), ___1546909903(38), round(0+6+6)); return;}}/**/
	}

	/**
	 * Sets an option value and saves it into a DB. After saving the OnAfterSetOption event is triggered.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $value The option value.
	 * @param string $siteId The site ID, if the option depends on a site.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function set($moduleId, $name, $value = "", $siteId = "")
	{
		if ($moduleId == '')
			throw new Main\ArgumentNullException("moduleId");
		if ($name == '')
			throw new Main\ArgumentNullException("name");

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$updateFields = [
			"VALUE" => $value,
		];

		if($siteId == "")
		{
			$insertFields = [
				"MODULE_ID" => $moduleId,
				"NAME" => $name,
				"VALUE" => $value,
			];

			$keyFields = ["MODULE_ID", "NAME"];

			$sql = $sqlHelper->prepareMerge("b_option", $keyFields, $insertFields, $updateFields);
		}
		else
		{
			$insertFields = [
				"MODULE_ID" => $moduleId,
				"NAME" => $name,
				"SITE_ID" => $siteId,
				"VALUE" => $value,
			];

			$keyFields = ["MODULE_ID", "NAME", "SITE_ID"];

			$sql = $sqlHelper->prepareMerge("b_option_site", $keyFields, $insertFields, $updateFields);
		}

		$con->queryExecute(current($sql));

		static::clearCache($moduleId);

		static::loadTriggers($moduleId);

		$event = new Main\Event(
			"main",
			"OnAfterSetOption_".$name,
			array("value" => $value)
		);
		$event->send();

		$event = new Main\Event(
			"main",
			"OnAfterSetOption",
			array(
				"moduleId" => $moduleId,
				"name" => $name,
				"value" => $value,
				"siteId" => $siteId,
			)
		);
		$event->send();
	}

	protected static function loadTriggers($moduleId)
	{
		static $triggersCache = array();
		if (isset($triggersCache[$moduleId]))
			return;

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
			throw new Main\ArgumentOutOfRangeException("moduleId");

		$triggersCache[$moduleId] = true;

		$path = Main\Loader::getLocal("modules/".$moduleId."/option_triggers.php");
		if ($path === false)
			return;

		include($path);
	}

	protected static function getCacheTtl()
	{
		static $cacheTtl = null;

		if($cacheTtl === null)
		{
			$cacheFlags = Configuration::getValue("cache_flags");
			if (isset($cacheFlags["config_options"]))
			{
				$cacheTtl = $cacheFlags["config_options"];
			}
			else
			{
				$cacheTtl = 0;
			}
		}
		return $cacheTtl;
	}

	/**
	 * Deletes options from a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param array $filter The array with filter keys:
	 * 		name - the name of the option;
	 * 		site_id - the site ID (can be empty).
	 * @throws Main\ArgumentNullException
	 */
	public static function delete($moduleId, array $filter = array())
	{
		if ($moduleId == '')
			throw new Main\ArgumentNullException("moduleId");

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$deleteForSites = true;
		$sqlWhere = $sqlWhereSite = "";

		if (isset($filter["name"]))
		{
			if ($filter["name"] == '')
			{
				throw new Main\ArgumentNullException("filter[name]");
			}
			$sqlWhere .= " AND NAME = '{$sqlHelper->forSql($filter["name"])}'";
		}
		if (isset($filter["site_id"]))
		{
			if($filter["site_id"] <> "")
			{
				$sqlWhereSite = " AND SITE_ID = '{$sqlHelper->forSql($filter["site_id"], 2)}'";
			}
			else
			{
				$deleteForSites = false;
			}
		}
		if($moduleId == 'main')
		{
			$sqlWhere .= "
				AND NAME NOT LIKE '~%' 
				AND NAME NOT IN ('crc_code', 'admin_passwordh', 'server_uniq_id','PARAM_MAX_SITES', 'PARAM_MAX_USERS') 
			";
		}
		else
		{
			$sqlWhere .= " AND NAME <> '~bsm_stop_date'";
		}

		if($sqlWhereSite == '')
		{
			$con->queryExecute("
				DELETE FROM b_option 
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}' 
					{$sqlWhere}
			");
		}

		if($deleteForSites)
		{
			$con->queryExecute("
				DELETE FROM b_option_site 
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}' 
					{$sqlWhere}
					{$sqlWhereSite}
			");
		}

		static::clearCache($moduleId);
	}

	protected static function clearCache($moduleId)
	{
		unset(self::$options[$moduleId]);

		if (static::getCacheTtl() !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option:{$moduleId}", self::CACHE_DIR);
		}
	}

	protected static function getDefaultSite()
	{
		static $defaultSite;

		if ($defaultSite === null)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
			{
				$defaultSite = $context->getSite();
			}
		}
		return $defaultSite;
	}
}
