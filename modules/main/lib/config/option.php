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

		/*ZDUyZmZMjlhZWYzMjMzNmY5MjQxNDRlNzVmMzljNjllMDQ1NDM=*/$GLOBALS['____769267254']= array(base64_decode('ZX'.'hw'.'bG9kZQ=='),base64_decode('cG'.'Fjaw='.'='),base64_decode(''.'bWQ'.'1'),base64_decode('Y29'.'uc3Rh'.'bnQ='),base64_decode('aGFz'.'aF9obWFj'),base64_decode('c3RyY21w'),base64_decode('aXNfb2JqZWN0'),base64_decode('Y2'.'FsbF9'.'1c2VyX2Z1bmM='),base64_decode(''.'Y2FsbF91'.'c2VyX'.'2'.'Z1bm'.'M='),base64_decode('Y2FsbF'.'91c2VyX2Z1bmM='),base64_decode('Y2Fsb'.'F91c2VyX2Z1bmM='),base64_decode('Y2FsbF'.'91'.'c2V'.'y'.'X2Z1bm'.'M='));if(!function_exists(__NAMESPACE__.'\\___871688369')){function ___871688369($_85626166){static $_1652611391= false; if($_1652611391 == false) $_1652611391=array('LQ==','bWFpb'.'g==',''.'bWFp'.'bg='.'=',''.'L'.'Q==','bWF'.'pbg==',''.'flB'.'BUkFNX01B'.'WF9VU'.'0V'.'SUw==','LQ==','b'.'W'.'F'.'pbg'.'==','fl'.'BBUkFNX0'.'1BWF9V'.'U0VSUw'.'==','Lg==','SCo=','Y'.'ml0cml4','TEl'.'DR'.'U5TRV'.'9L'.'RVk=','c2hh'.'MjU2','LQ'.'==','bWFpbg='.'=','f'.'lBBU'.'k'.'FNX'.'01BW'.'F9VU'.'0VS'.'Uw==','L'.'Q==','bWFpbg==','UEFS'.'QU'.'1fTUFYX1'.'VTRVJT','VVNFU'.'g'.'==','V'.'VN'.'FUg'.'='.'=','VVN'.'F'.'Ug==','SXN'.'BdX'.'Rob3JpemVk','VVNFUg==',''.'SX'.'NB'.'ZG'.'1pbg==','QVBQ'.'TElDQVRJT04'.'=','UmV'.'zd'.'GFyd'.'EJ'.'1ZmZ'.'l'.'cg==','T'.'G9jY'.'WxS'.'ZWR'.'pc'.'mV'.'jd'.'A='.'=','L2xpY2Vuc2V'.'fcmV'.'zdHJpY3Rpb24ucGh'.'w',''.'L'.'Q==','bW'.'Fp'.'bg==','flBBU'.'k'.'FNX01BWF9VU0VSUw==',''.'LQ'.'==','b'.'WFpbg==','UEF'.'SQU'.'1fTUFYX1VT'.'RVJT','XE'.'JpdHJpeFxNY'.'WluXENvbmZp'.'Z1'.'xPcHRpb'.'246OnN'.'ld'.'A==',''.'bWFpb'.'g==','UEFSQU'.'1fTUFYX'.'1VTR'.'VJT');return base64_decode($_1652611391[$_85626166]);}};if(isset(self::$options[___871688369(0)][___871688369(1)]) && $moduleId === ___871688369(2)){ if(isset(self::$options[___871688369(3)][___871688369(4)][___871688369(5)])){ $_799681351= self::$options[___871688369(6)][___871688369(7)][___871688369(8)]; list($_1750934465, $_829050833)= $GLOBALS['____769267254'][0](___871688369(9), $_799681351); $_1978562416= $GLOBALS['____769267254'][1](___871688369(10), $_1750934465); $_499764010= ___871688369(11).$GLOBALS['____769267254'][2]($GLOBALS['____769267254'][3](___871688369(12))); $_1193412210= $GLOBALS['____769267254'][4](___871688369(13), $_829050833, $_499764010, true); self::$options[___871688369(14)][___871688369(15)][___871688369(16)]= $_829050833; self::$options[___871688369(17)][___871688369(18)][___871688369(19)]= $_829050833; if($GLOBALS['____769267254'][5]($_1193412210, $_1978562416) !==(764-2*382)){ if(isset($GLOBALS[___871688369(20)]) && $GLOBALS['____769267254'][6]($GLOBALS[___871688369(21)]) && $GLOBALS['____769267254'][7](array($GLOBALS[___871688369(22)], ___871688369(23))) &&!$GLOBALS['____769267254'][8](array($GLOBALS[___871688369(24)], ___871688369(25)))){ $GLOBALS['____769267254'][9](array($GLOBALS[___871688369(26)], ___871688369(27))); $GLOBALS['____769267254'][10](___871688369(28), ___871688369(29), true);} return;}} else{ self::$options[___871688369(30)][___871688369(31)][___871688369(32)]= round(0+2.4+2.4+2.4+2.4+2.4); self::$options[___871688369(33)][___871688369(34)][___871688369(35)]= round(0+3+3+3+3); $GLOBALS['____769267254'][11](___871688369(36), ___871688369(37), ___871688369(38), round(0+2.4+2.4+2.4+2.4+2.4)); return;}}/**/
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
