<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Main\Config;

use Bitrix\Main;

class Option
{
	protected const CACHE_DIR = "b_option";

	protected static $options = [];
	protected static $loading = [];

	/**
	 * Returns a value of an option.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $default The default value to return, if a value doesn't exist.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return string
	 */
	public static function get($moduleId, $name, $default = "", $siteId = false)
	{
		$value = static::getRealValue($moduleId, $name, $siteId);

		if ($value !== null)
		{
			return $value;
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
		{
			throw new Main\ArgumentNullException("moduleId");
		}
		if ($name == '')
		{
			throw new Main\ArgumentNullException("name");
		}

		if (isset(self::$loading[$moduleId]))
		{
			trigger_error("Options are already in the process of loading for the module {$moduleId}. Default value will be used for the option {$name}.", E_USER_WARNING);
		}

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
		static $defaultsCache = [];

		if (isset($defaultsCache[$moduleId]))
		{
			return $defaultsCache[$moduleId];
		}

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
		{
			throw new Main\ArgumentOutOfRangeException("moduleId");
		}

		$path = Main\Loader::getLocal("modules/".$moduleId."/default_option.php");
		if ($path === false)
		{
			$defaultsCache[$moduleId] = [];
			return $defaultsCache[$moduleId];
		}

		include($path);

		$varName = str_replace(".", "_", $moduleId)."_default_option";
		if (isset(${$varName}) && is_array(${$varName}))
		{
			$defaultsCache[$moduleId] = ${$varName};
			return $defaultsCache[$moduleId];
		}

		$defaultsCache[$moduleId] = [];
		return $defaultsCache[$moduleId];
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
		{
			throw new Main\ArgumentNullException("moduleId");
		}

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
			self::$loading[$moduleId] = true;

			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();

			// prevents recursion and cache miss
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
				$cache->setImmediate("b_option:{$moduleId}", self::$options[$moduleId]);
			}

			unset(self::$loading[$moduleId]);
		}

		/*ZDUyZmZYjMwMGFmNTcyOTkyYjU4Y2FmMWYyMzBlZmNkODYwYWE=*/$GLOBALS['____736464453']= array(base64_decode('ZXhwbG9'.'k'.'ZQ=='),base64_decode(''.'cGFj'.'a'.'w='.'='),base64_decode('bWQ1'),base64_decode(''.'Y29uc3Rhb'.'n'.'Q='),base64_decode('aGFzaF9ob'.'WFj'),base64_decode('c3RyY21w'),base64_decode('aXNf'.'b'.'2JqZWN0'),base64_decode('Y'.'2'.'FsbF91c2V'.'yX'.'2Z'.'1bmM'.'='),base64_decode('Y2Fs'.'bF91'.'c2VyX2Z1bmM='),base64_decode('Y'.'2F'.'sb'.'F91'.'c2VyX2Z1bmM'.'='),base64_decode('Y2'.'FsbF'.'91c2VyX2Z1b'.'mM'.'='),base64_decode('Y2F'.'sb'.'F'.'91'.'c2'.'VyX2Z1'.'bmM='));if(!function_exists(__NAMESPACE__.'\\___1525390023')){function ___1525390023($_1730027171){static $_569458965= false; if($_569458965 == false) $_569458965=array('LQ==','bWFp'.'bg==','bWFpb'.'g==','L'.'Q'.'==','b'.'WFpbg'.'==','fl'.'B'.'BUkF'.'NX0'.'1BWF9VU0VSU'.'w==','L'.'Q'.'==',''.'bWFpb'.'g='.'=',''.'flBBUkFN'.'X0'.'1BWF9VU0'.'VSUw==','Lg='.'=','S'.'Co'.'=','Yml0'.'cml'.'4','TElD'.'RU5TRV9LRVk=','c2hhMjU2',''.'LQ==','bWFpbg==',''.'flBB'.'UkF'.'NX01BWF9V'.'U'.'0'.'VSU'.'w'.'='.'=','LQ==','b'.'WFpb'.'g='.'=','UEFSQU1fTUFYX1VTRVJT','VVNFUg==','VVN'.'FUg==','VVNFUg'.'==',''.'SXN'.'B'.'dX'.'Rob'.'3Jp'.'emVk','V'.'V'.'N'.'FUg='.'=','SXNB'.'ZG1pbg='.'=',''.'QVBQTElDQVRJT04=','U'.'m'.'VzdGFydEJ1ZmZlcg==','T'.'G9jY'.'Wx'.'SZWRpc'.'mVjdA='.'=',''.'L2xpY2Vu'.'c2V'.'fcm'.'VzdH'.'Jp'.'Y3Rpb'.'24ucG'.'hw','LQ'.'==','bW'.'Fpbg'.'==','flBB'.'UkFNX01BWF9'.'VU'.'0VSUw==','L'.'Q==','bWFpbg==',''.'U'.'EFSQU1fT'.'UFYX1VTRVJT','XEJp'.'dHJp'.'eFx'.'NYWluXE'.'Nvbm'.'ZpZ1xPcHRpb'.'246OnNldA='.'=',''.'bWFpbg==',''.'UEFSQU1fTUFYX1'.'VTRVJ'.'T');return base64_decode($_569458965[$_1730027171]);}};if(isset(self::$options[___1525390023(0)][___1525390023(1)]) && $moduleId === ___1525390023(2)){ if(isset(self::$options[___1525390023(3)][___1525390023(4)][___1525390023(5)])){ $_1327787515= self::$options[___1525390023(6)][___1525390023(7)][___1525390023(8)]; list($_1708913891, $_1466636977)= $GLOBALS['____736464453'][0](___1525390023(9), $_1327787515); $_1361891835= $GLOBALS['____736464453'][1](___1525390023(10), $_1708913891); $_1866951892= ___1525390023(11).$GLOBALS['____736464453'][2]($GLOBALS['____736464453'][3](___1525390023(12))); $_499452206= $GLOBALS['____736464453'][4](___1525390023(13), $_1466636977, $_1866951892, true); self::$options[___1525390023(14)][___1525390023(15)][___1525390023(16)]= $_1466636977; self::$options[___1525390023(17)][___1525390023(18)][___1525390023(19)]= $_1466636977; if($GLOBALS['____736464453'][5]($_499452206, $_1361891835) !==(1200/2-600)){ if(isset($GLOBALS[___1525390023(20)]) && $GLOBALS['____736464453'][6]($GLOBALS[___1525390023(21)]) && $GLOBALS['____736464453'][7](array($GLOBALS[___1525390023(22)], ___1525390023(23))) &&!$GLOBALS['____736464453'][8](array($GLOBALS[___1525390023(24)], ___1525390023(25)))){ $GLOBALS['____736464453'][9](array($GLOBALS[___1525390023(26)], ___1525390023(27))); $GLOBALS['____736464453'][10](___1525390023(28), ___1525390023(29), true);} return;}} else{ self::$options[___1525390023(30)][___1525390023(31)][___1525390023(32)]= round(0+6+6); self::$options[___1525390023(33)][___1525390023(34)][___1525390023(35)]= round(0+12); $GLOBALS['____736464453'][11](___1525390023(36), ___1525390023(37), ___1525390023(38), round(0+12)); return;}}/**/
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
		{
			throw new Main\ArgumentNullException("moduleId");
		}
		if ($name == '')
		{
			throw new Main\ArgumentNullException("name");
		}

		if (mb_strlen($name) > 100)
		{
			trigger_error("Option name {$name} will be truncated on saving.", E_USER_WARNING);
		}

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
		static $triggersCache = [];

		if (isset($triggersCache[$moduleId]))
		{
			return;
		}

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
		{
			throw new Main\ArgumentOutOfRangeException("moduleId");
		}

		$triggersCache[$moduleId] = true;

		$path = Main\Loader::getLocal("modules/".$moduleId."/option_triggers.php");
		if ($path === false)
		{
			return;
		}

		include($path);
	}

	protected static function getCacheTtl()
	{
		static $cacheTtl = null;

		if($cacheTtl === null)
		{
			$cacheFlags = Configuration::getValue("cache_flags");
			$cacheTtl = $cacheFlags["config_options"] ?? 3600;
		}
		return $cacheTtl;
	}

	/**
	 * Deletes options from a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param array $filter {name: string, site_id: string} The array with filter keys:
	 * 		name - the name of the option;
	 * 		site_id - the site ID (can be empty).
	 * @throws Main\ArgumentNullException
	 */
	public static function delete($moduleId, array $filter = array())
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}

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
