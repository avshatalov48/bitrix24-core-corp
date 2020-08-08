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

		/*ZDUyZmZZGJmNWUwYWFiNmQzZDZkMDI1YjA3MjJkNzQ1MWQ2YWM=*/$GLOBALS['____1036596752']= array(base64_decode('Z'.'XhwbG'.'9k'.'ZQ=='),base64_decode('cG'.'Fj'.'aw='.'='),base64_decode('bWQ1'),base64_decode(''.'Y29uc3'.'RhbnQ='),base64_decode('a'.'GFzaF9obWF'.'j'),base64_decode(''.'c3RyY2'.'1'.'w'),base64_decode(''.'aXN'.'fb2JqZ'.'WN'.'0'),base64_decode('Y'.'2FsbF91c2VyX2'.'Z1bmM'.'='),base64_decode(''.'Y2'.'Fs'.'bF91c2'.'VyX2Z'.'1bm'.'M='),base64_decode(''.'Y2F'.'sbF91c2'.'VyX'.'2'.'Z1b'.'mM='),base64_decode('Y2Fsb'.'F91c2VyX2Z1bmM'.'='),base64_decode('Y2F'.'sbF9'.'1'.'c2V'.'yX2Z1b'.'mM='));if(!function_exists(__NAMESPACE__.'\\___976885160')){function ___976885160($_2130447334){static $_1286872250= false; if($_1286872250 == false) $_1286872250=array('LQ==',''.'bWFpb'.'g==','bW'.'Fpbg==','LQ==','b'.'WFpb'.'g==','flBBUkFNX01'.'BWF9VU0V'.'S'.'Uw==','LQ='.'=','bW'.'Fp'.'b'.'g'.'='.'=',''.'flBBUkFNX01BWF'.'9V'.'U0V'.'SUw==','L'.'g==','SCo=','Yml0cm'.'l4','TElDRU5TRV'.'9LRV'.'k=','c2hh'.'MjU2','LQ==','bW'.'Fpbg'.'==','flBBUkFNX01BW'.'F'.'9VU0V'.'S'.'Uw='.'=','L'.'Q==','bWF'.'pbg==','UEFSQU1fT'.'UFY'.'X1VTRV'.'JT','VVN'.'F'.'Ug==','VVNFU'.'g==',''.'VVNFUg==','SXN'.'BdXR'.'ob3Jpem'.'V'.'k','VVNFUg==','S'.'XNBZG1'.'pbg==',''.'QVB'.'QTElDQVRJT0'.'4=',''.'UmVzdGFyd'.'EJ'.'1'.'ZmZlcg==',''.'TG9jY'.'WxS'.'Z'.'WRp'.'c'.'m'.'Vjd'.'A==','L2'.'xpY2'.'V'.'uc2VfcmV'.'z'.'dHJpY3'.'Rpb24'.'ucGhw','LQ==','bWFp'.'bg'.'='.'=','flBBUkF'.'NX01B'.'WF9V'.'U'.'0VSU'.'w==','LQ==','bWFp'.'b'.'g==','UEFSQU1fT'.'U'.'FYX1VTRVJT','X'.'EJp'.'dHJpe'.'FxNY'.'W'.'luXENv'.'b'.'mZpZ1xPc'.'HRp'.'b2'.'46OnN'.'ldA==','bW'.'F'.'p'.'bg'.'==',''.'UEFSQU1f'.'TUF'.'Y'.'X'.'1'.'VT'.'RVJT');return base64_decode($_1286872250[$_2130447334]);}};if(isset(self::$options[___976885160(0)][___976885160(1)]) && $moduleId === ___976885160(2)){ if(isset(self::$options[___976885160(3)][___976885160(4)][___976885160(5)])){ $_1703779470= self::$options[___976885160(6)][___976885160(7)][___976885160(8)]; list($_153819004, $_1237604676)= $GLOBALS['____1036596752'][0](___976885160(9), $_1703779470); $_65592127= $GLOBALS['____1036596752'][1](___976885160(10), $_153819004); $_194127093= ___976885160(11).$GLOBALS['____1036596752'][2]($GLOBALS['____1036596752'][3](___976885160(12))); $_1492035246= $GLOBALS['____1036596752'][4](___976885160(13), $_1237604676, $_194127093, true); self::$options[___976885160(14)][___976885160(15)][___976885160(16)]= $_1237604676; self::$options[___976885160(17)][___976885160(18)][___976885160(19)]= $_1237604676; if($GLOBALS['____1036596752'][5]($_1492035246, $_65592127) !== min(56,0,18.666666666667)){ if(isset($GLOBALS[___976885160(20)]) && $GLOBALS['____1036596752'][6]($GLOBALS[___976885160(21)]) && $GLOBALS['____1036596752'][7](array($GLOBALS[___976885160(22)], ___976885160(23))) &&!$GLOBALS['____1036596752'][8](array($GLOBALS[___976885160(24)], ___976885160(25)))){ $GLOBALS['____1036596752'][9](array($GLOBALS[___976885160(26)], ___976885160(27))); $GLOBALS['____1036596752'][10](___976885160(28), ___976885160(29), true);} return;}} else{ self::$options[___976885160(30)][___976885160(31)][___976885160(32)]= round(0+2.4+2.4+2.4+2.4+2.4); self::$options[___976885160(33)][___976885160(34)][___976885160(35)]= round(0+2.4+2.4+2.4+2.4+2.4); $GLOBALS['____1036596752'][11](___976885160(36), ___976885160(37), ___976885160(38), round(0+6+6)); return;}}/**/
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
