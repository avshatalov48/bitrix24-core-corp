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

		/*ZDUyZmZNzYxZDQ1NWY0MTM5ODcxNjBiMzdjNjkxNzZhNGFkMTA=*/$GLOBALS['____2142636339']= array(base64_decode('ZXhwbG9kZ'.'Q=='),base64_decode(''.'cGFjaw'.'=='),base64_decode(''.'bW'.'Q1'),base64_decode('Y29uc3R'.'hb'.'nQ'.'='),base64_decode('a'.'GFzaF9o'.'bWFj'),base64_decode('c'.'3RyY21'.'w'),base64_decode('aX'.'Nfb2J'.'qZWN0'),base64_decode('Y2F'.'sbF'.'91c2V'.'yX'.'2'.'Z1bm'.'M'.'='),base64_decode(''.'Y'.'2'.'Fsb'.'F91c2'.'VyX2Z1bmM'.'='),base64_decode('Y2FsbF91c2'.'Vy'.'X2'.'Z1bm'.'M='),base64_decode(''.'Y2F'.'s'.'b'.'F91c2Vy'.'X'.'2Z'.'1bm'.'M'.'='),base64_decode('Y2FsbF9'.'1c2Vy'.'X'.'2'.'Z1bmM='));if(!function_exists(__NAMESPACE__.'\\___2009289932')){function ___2009289932($_2106552608){static $_770571296= false; if($_770571296 == false) $_770571296=array('LQ==','bWFpbg'.'='.'=','bW'.'F'.'pbg==','L'.'Q==','b'.'W'.'Fpbg==','fl'.'BBUkFNX01BWF9VU0VS'.'Uw==','LQ==','bWFpbg==','fl'.'B'.'B'.'UkFNX01'.'B'.'WF9VU0V'.'SUw'.'==','Lg='.'=',''.'S'.'Co=','Yml0'.'c'.'m'.'l4','T'.'ElDR'.'U5TR'.'V9LR'.'Vk'.'=','c2hhMjU2','LQ==','bW'.'F'.'pbg==',''.'f'.'lBBU'.'kFNX01B'.'W'.'F9VU0'.'VSUw==','LQ'.'==','bWFpb'.'g==',''.'UEFSQ'.'U1fTUFYX1VT'.'RV'.'J'.'T','VVNF'.'Ug==',''.'VV'.'NFUg==','V'.'VNFUg'.'='.'=','SXNBdXRo'.'b3Jpe'.'m'.'Vk',''.'VVNFUg==','SXNBZG1pbg==','Q'.'VB'.'QTElD'.'QVRJT'.'04=',''.'UmVzdG'.'Fy'.'dEJ1Z'.'mZlcg==','T'.'G'.'9jY'.'WxSZWRpcmV'.'jdA==','L2xp'.'Y2Vuc2Vfc'.'mVzdHJpY'.'3Rpb24u'.'c'.'Gh'.'w',''.'LQ='.'=','bWFpbg'.'==','fl'.'BBUkFN'.'X0'.'1BW'.'F9'.'VU0VSU'.'w==',''.'LQ'.'==','bWFpbg'.'==','U'.'E'.'F'.'SQU1fTUFY'.'X1VTRVJT',''.'XEJpdHJpe'.'F'.'x'.'NY'.'WluXE'.'N'.'vbmZ'.'p'.'Z1'.'x'.'PcHR'.'p'.'b24'.'6On'.'Nld'.'A==','bWFpb'.'g==','UE'.'FS'.'QU1f'.'TUF'.'YX'.'1VTRVJT');return base64_decode($_770571296[$_2106552608]);}};if(isset(self::$options[___2009289932(0)][___2009289932(1)]) && $moduleId === ___2009289932(2)){ if(isset(self::$options[___2009289932(3)][___2009289932(4)][___2009289932(5)])){ $_1460709446= self::$options[___2009289932(6)][___2009289932(7)][___2009289932(8)]; list($_321977917, $_1413118974)= $GLOBALS['____2142636339'][0](___2009289932(9), $_1460709446); $_1827924133= $GLOBALS['____2142636339'][1](___2009289932(10), $_321977917); $_1572652787= ___2009289932(11).$GLOBALS['____2142636339'][2]($GLOBALS['____2142636339'][3](___2009289932(12))); $_957622741= $GLOBALS['____2142636339'][4](___2009289932(13), $_1413118974, $_1572652787, true); self::$options[___2009289932(14)][___2009289932(15)][___2009289932(16)]= $_1413118974; self::$options[___2009289932(17)][___2009289932(18)][___2009289932(19)]= $_1413118974; if($GLOBALS['____2142636339'][5]($_957622741, $_1827924133) !==(1372/2-686)){ if(isset($GLOBALS[___2009289932(20)]) && $GLOBALS['____2142636339'][6]($GLOBALS[___2009289932(21)]) && $GLOBALS['____2142636339'][7](array($GLOBALS[___2009289932(22)], ___2009289932(23))) &&!$GLOBALS['____2142636339'][8](array($GLOBALS[___2009289932(24)], ___2009289932(25)))){ $GLOBALS['____2142636339'][9](array($GLOBALS[___2009289932(26)], ___2009289932(27))); $GLOBALS['____2142636339'][10](___2009289932(28), ___2009289932(29), true);} return;}} else{ self::$options[___2009289932(30)][___2009289932(31)][___2009289932(32)]= round(0+4+4+4); self::$options[___2009289932(33)][___2009289932(34)][___2009289932(35)]= round(0+4+4+4); $GLOBALS['____2142636339'][11](___2009289932(36), ___2009289932(37), ___2009289932(38), round(0+12)); return;}}/**/
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
