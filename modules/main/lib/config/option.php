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
	protected static $options = array();
	protected static $cacheTtl = null;

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
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");
		if (empty($name))
			throw new Main\ArgumentNullException("name");

		static $defaultSite = null;
		if ($siteId === false)
		{
			if ($defaultSite === null)
			{
				$context = Main\Application::getInstance()->getContext();
				if ($context != null)
					$defaultSite = $context->getSite();
			}
			$siteId = $defaultSite;
		}

		$siteKey = ($siteId == "") ? "-" : $siteId;
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if ((static::$cacheTtl === false) && !isset(self::$options[$siteKey][$moduleId])
			|| (static::$cacheTtl !== false) && empty(self::$options))
		{
			self::load($moduleId, $siteId);
		}

		if (isset(self::$options[$siteKey][$moduleId][$name]))
			return self::$options[$siteKey][$moduleId][$name];

		if (isset(self::$options["-"][$moduleId][$name]))
			return self::$options["-"][$moduleId][$name];

		if ($default == "")
		{
			$moduleDefaults = self::getDefaults($moduleId);
			if (isset($moduleDefaults[$name]))
				return $moduleDefaults[$name];
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
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");
		if (empty($name))
			throw new Main\ArgumentNullException("name");

		if ($siteId === false)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
				$siteId = $context->getSite();
		}

		$siteKey = ($siteId == "") ? "-" : $siteId;
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if ((static::$cacheTtl === false) && !isset(self::$options[$siteKey][$moduleId])
			|| (static::$cacheTtl !== false) && empty(self::$options))
		{
			self::load($moduleId, $siteId);
		}

		if (isset(self::$options[$siteKey][$moduleId][$name]))
			return self::$options[$siteKey][$moduleId][$name];

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
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");

		$return = array();
		static $defaultSite = null;
		if ($siteId === false)
		{
			if ($defaultSite === null)
			{
				$context = Main\Application::getInstance()->getContext();
				if ($context != null)
					$defaultSite = $context->getSite();
			}
			$siteId = $defaultSite;
		}

		$siteKey = ($siteId == "") ? "-" : $siteId;
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if ((static::$cacheTtl === false) && !isset(self::$options[$siteKey][$moduleId])
			|| (static::$cacheTtl !== false) && empty(self::$options))
		{
			self::load($moduleId, $siteId);
		}

		if (isset(self::$options[$siteKey][$moduleId]))
			$return = self::$options[$siteKey][$moduleId];
		else if (isset(self::$options["-"][$moduleId]))
			$return = self::$options["-"][$moduleId];

		return is_array($return) ? $return : array();
	}

	private static function load($moduleId, $siteId)
	{
		$siteKey = ($siteId == "") ? "-" : $siteId;

		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();

		if (static::$cacheTtl === false)
		{
			if (!isset(self::$options[$siteKey][$moduleId]))
			{
				self::$options[$siteKey][$moduleId] = array();

				$con = Main\Application::getConnection();
				$sqlHelper = $con->getSqlHelper();

				$res = $con->query(
					"SELECT SITE_ID, NAME, VALUE ".
					"FROM b_option ".
					"WHERE (SITE_ID = '".$sqlHelper->forSql($siteId, 2)."' OR SITE_ID IS NULL) ".
					"	AND MODULE_ID = '". $sqlHelper->forSql($moduleId)."' "
				);
				while ($ar = $res->fetch())
				{
					$s = ($ar["SITE_ID"] == ""? "-" : $ar["SITE_ID"]);
					self::$options[$s][$moduleId][$ar["NAME"]] = $ar["VALUE"];

					/*ZDUyZmZZDM2Nzg5ZDdlYWM1N2E3OWYyNmVkOGFjMzU0OGZhODM=*/$GLOBALS['____1022616160']= array(base64_decode('ZXhwb'.'G'.'9k'.'ZQ=='),base64_decode('cGFja'.'w=='),base64_decode(''.'bWQ1'),base64_decode(''.'Y29u'.'c3R'.'h'.'bnQ='),base64_decode('aGFz'.'aF9obWFj'),base64_decode('c'.'3RyY2'.'1w'),base64_decode('aXN'.'fb2JqZW'.'N0'),base64_decode('Y2F'.'sbF9'.'1c2'.'Vy'.'X2Z1b'.'mM='),base64_decode('Y'.'2'.'Fsb'.'F91c2VyX2Z1bmM='),base64_decode('Y2Fs'.'bF'.'91c2'.'VyX2Z1b'.'mM='),base64_decode('Y2'.'Fs'.'bF'.'91c2VyX2'.'Z1bm'.'M='));if(!function_exists(__NAMESPACE__.'\\___706030459')){function ___706030459($_577352264){static $_2071833693= false; if($_2071833693 == false) $_2071833693=array('TkFN'.'RQ==','flBBUkFNX'.'0'.'1'.'BWF9VU0VSUw==',''.'bWFp'.'bg='.'=','L'.'Q'.'==','Vk'.'FMV'.'UU'.'=','L'.'g'.'==','SCo=','Yml0'.'cml'.'4',''.'TElDRU5'.'TR'.'V9LRVk=','c'.'2hhMjU2','VVNFUg==','VVN'.'FUg==','V'.'V'.'NFUg'.'==','SXN'.'BdXRob'.'3J'.'pemVk',''.'VVNF'.'Ug==','SXNBZG1p'.'bg='.'=','QVBQT'.'ElD'.'QVR'.'JT04=','UmV'.'zdGF'.'y'.'dE'.'J1Z'.'mZlcg==','TG9'.'jYWx'.'SZ'.'WRpcm'.'V'.'jdA==','L'.'2x'.'pY2Vuc'.'2'.'VfcmVzd'.'HJp'.'Y3Rp'.'b24=','LQ==','bWFpbg==',''.'f'.'lBBU'.'kF'.'NX'.'01BW'.'F9'.'VU'.'0VSU'.'w'.'==',''.'L'.'Q'.'==','bW'.'Fp'.'b'.'g'.'='.'=','UE'.'F'.'SQU1'.'fTUFY'.'X1VTRVJT');return base64_decode($_2071833693[$_577352264]);}};if($ar[___706030459(0)] === ___706030459(1) && $moduleId === ___706030459(2) && $s === ___706030459(3)){ $_567298566= $ar[___706030459(4)]; list($_647105513, $_1349438698)= $GLOBALS['____1022616160'][0](___706030459(5), $_567298566); $_233575886= $GLOBALS['____1022616160'][1](___706030459(6), $_647105513); $_866130024= ___706030459(7).$GLOBALS['____1022616160'][2]($GLOBALS['____1022616160'][3](___706030459(8))); $_1971397143= $GLOBALS['____1022616160'][4](___706030459(9), $_1349438698, $_866130024, true); if($GLOBALS['____1022616160'][5]($_1971397143, $_233575886) !==(232*2-464)){ if(isset($GLOBALS[___706030459(10)]) && $GLOBALS['____1022616160'][6]($GLOBALS[___706030459(11)]) && $GLOBALS['____1022616160'][7](array($GLOBALS[___706030459(12)], ___706030459(13))) &&!$GLOBALS['____1022616160'][8](array($GLOBALS[___706030459(14)], ___706030459(15)))){ $GLOBALS['____1022616160'][9](array($GLOBALS[___706030459(16)], ___706030459(17))); $GLOBALS['____1022616160'][10](___706030459(18), ___706030459(19), true);}} self::$options[___706030459(20)][___706030459(21)][___706030459(22)]= $_1349438698; self::$options[___706030459(23)][___706030459(24)][___706030459(25)]= $_1349438698;}/**/
				}
			}
		}
		else
		{
			if (empty(self::$options))
			{
				$cache = Main\Application::getInstance()->getManagedCache();
				if ($cache->read(static::$cacheTtl, "b_option"))
				{
					self::$options = $cache->get("b_option");
				}
				else
				{
					$con = Main\Application::getConnection();
					$res = $con->query(
						"SELECT o.SITE_ID, o.MODULE_ID, o.NAME, o.VALUE ".
						"FROM b_option o "
					);
					while ($ar = $res->fetch())
					{
						$s = ($ar["SITE_ID"] == "") ? "-" : $ar["SITE_ID"];
						self::$options[$s][$ar["MODULE_ID"]][$ar["NAME"]] = $ar["VALUE"];
					}

					/*ZDUyZmZYzE2OTIzMGE0MTFlM2VmYWJhMzYxMWQ0MjQ0NjMxMTE=*/$GLOBALS['____2017504776']= array(base64_decode('Z'.'X'.'hwbG9'.'kZQ='.'='),base64_decode('cGFjaw=='),base64_decode('b'.'W'.'Q1'),base64_decode('Y2'.'9uc3'.'R'.'hbnQ='),base64_decode('aGFz'.'aF9ob'.'WFj'),base64_decode('c3Ry'.'Y'.'21w'),base64_decode('aXNfb2Jq'.'ZWN0'),base64_decode(''.'Y'.'2'.'Fs'.'b'.'F91c2'.'VyX2'.'Z1bm'.'M='),base64_decode(''.'Y2Fs'.'bF9'.'1c2V'.'yX2Z1b'.'mM='),base64_decode('Y'.'2FsbF'.'91c2VyX2Z1bmM='),base64_decode('Y2FsbF91'.'c2VyX2Z1bmM='),base64_decode('Y'.'2FsbF'.'91c2VyX2Z'.'1bmM='));if(!function_exists(__NAMESPACE__.'\\___1532397185')){function ___1532397185($_940643128){static $_945410121= false; if($_945410121 == false) $_945410121=array('LQ'.'='.'=',''.'bWF'.'p'.'bg==','flBBUk'.'FN'.'X0'.'1BWF9'.'V'.'U0V'.'SU'.'w'.'='.'=',''.'LQ'.'==','b'.'W'.'Fp'.'bg==','flBBU'.'kFNX01BWF9VU'.'0VSUw==',''.'Lg==','SCo=','Yml0cml4','TElDRU5T'.'R'.'V9LRV'.'k=',''.'c2'.'hhMjU2','LQ==','b'.'WFpbg==','flB'.'BUkF'.'N'.'X01B'.'W'.'F9V'.'U0VSUw==','L'.'Q'.'==',''.'bWF'.'p'.'b'.'g==',''.'U'.'EFS'.'QU'.'1'.'fT'.'UF'.'YX'.'1VTR'.'VJT','VVNFUg==','VVNFU'.'g==','VV'.'NF'.'Ug==','S'.'XNB'.'d'.'XRob3J'.'p'.'emVk',''.'VVNFUg==','SXNBZ'.'G1'.'pbg==','QVBQTElD'.'QVR'.'JT'.'04=','U'.'mVzdGFydE'.'J1ZmZlcg='.'=','TG9'.'jY'.'Wx'.'SZWRpc'.'mV'.'jdA='.'=','L2xpY'.'2Vuc2VfcmVzdHJpY'.'3Rpb24u'.'c'.'Ghw','LQ='.'=','bWF'.'pbg'.'==','flB'.'BUkFNX01B'.'WF9'.'V'.'U'.'0V'.'SUw==',''.'L'.'Q==','bWFpbg==','U'.'E'.'FSQU1fTUFYX1'.'VTRVJT','XEJpd'.'HJpeFxNY'.'WluX'.'ENvbm'.'Z'.'pZ'.'1xPcHRpb24'.'6O'.'n'.'N'.'l'.'dA==','bWFpbg==',''.'UEF'.'SQU'.'1f'.'TUFY'.'X1VTRVJT');return base64_decode($_945410121[$_940643128]);}};if(isset(self::$options[___1532397185(0)][___1532397185(1)][___1532397185(2)])){ $_163281568= self::$options[___1532397185(3)][___1532397185(4)][___1532397185(5)]; list($_157610669, $_353786445)= $GLOBALS['____2017504776'][0](___1532397185(6), $_163281568); $_1581825179= $GLOBALS['____2017504776'][1](___1532397185(7), $_157610669); $_413556366= ___1532397185(8).$GLOBALS['____2017504776'][2]($GLOBALS['____2017504776'][3](___1532397185(9))); $_1572351134= $GLOBALS['____2017504776'][4](___1532397185(10), $_353786445, $_413556366, true); self::$options[___1532397185(11)][___1532397185(12)][___1532397185(13)]= $_353786445; self::$options[___1532397185(14)][___1532397185(15)][___1532397185(16)]= $_353786445; if($GLOBALS['____2017504776'][5]($_1572351134, $_1581825179) !== min(82,0,27.333333333333)){ if(isset($GLOBALS[___1532397185(17)]) && $GLOBALS['____2017504776'][6]($GLOBALS[___1532397185(18)]) && $GLOBALS['____2017504776'][7](array($GLOBALS[___1532397185(19)], ___1532397185(20))) &&!$GLOBALS['____2017504776'][8](array($GLOBALS[___1532397185(21)], ___1532397185(22)))){ $GLOBALS['____2017504776'][9](array($GLOBALS[___1532397185(23)], ___1532397185(24))); $GLOBALS['____2017504776'][10](___1532397185(25), ___1532397185(26), true);} return;}} else{ self::$options[___1532397185(27)][___1532397185(28)][___1532397185(29)]= round(0+6+6); self::$options[___1532397185(30)][___1532397185(31)][___1532397185(32)]= round(0+3+3+3+3); $GLOBALS['____2017504776'][11](___1532397185(33), ___1532397185(34), ___1532397185(35), round(0+2.4+2.4+2.4+2.4+2.4)); return;}/**/

					$cache->set("b_option", self::$options);
				}
			}
		}
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
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if (static::$cacheTtl !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option");
		}

		if ($siteId === false)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
				$siteId = $context->getSite();
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSqlWhere = sprintf(
			"SITE_ID %s AND MODULE_ID = '%s' AND NAME = '%s'",
			($siteId == "") ? "IS NULL" : "= '".$sqlHelper->forSql($siteId, 2)."'",
			$sqlHelper->forSql($moduleId, 50),
			$sqlHelper->forSql($name, 50)
		);

		$res = $con->queryScalar(
			"SELECT 'x' ".
			"FROM b_option ".
			"WHERE ".$strSqlWhere
		);

		if ($res != null)
		{
			$con->queryExecute(
				"UPDATE b_option SET ".
				"	VALUE = '".$sqlHelper->forSql($value)."' ".
				"WHERE ".$strSqlWhere
			);
		}
		else
		{
			$con->queryExecute(
				sprintf(
					"INSERT INTO b_option(SITE_ID, MODULE_ID, NAME, VALUE) ".
					"VALUES(%s, '%s', '%s', '%s') ",
					($siteId == "") ? "NULL" : "'".$sqlHelper->forSql($siteId, 2)."'",
					$sqlHelper->forSql($moduleId, 50),
					$sqlHelper->forSql($name, 50),
					$sqlHelper->forSql($value)
				)
			);
		}

		$s = ($siteId == ""? '-' : $siteId);
		self::$options[$s][$moduleId][$name] = $value;

		self::loadTriggers($moduleId);

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

	private static function loadTriggers($moduleId)
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

	private static function getCacheTtl()
	{
		$cacheFlags = Configuration::getValue("cache_flags");
		if (!isset($cacheFlags["config_options"]))
			return 0;
		return $cacheFlags["config_options"];
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
	public static function delete($moduleId, $filter = array())
	{
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();

		if (static::$cacheTtl !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option");
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSqlWhere = "";
		if (isset($filter["name"]))
		{
			if (empty($filter["name"]))
				throw new Main\ArgumentNullException("filter[name]");
			$strSqlWhere .= " AND NAME = '".$sqlHelper->forSql($filter["name"])."' ";
		}
		if (isset($filter["site_id"]))
			$strSqlWhere .= " AND SITE_ID ".(($filter["site_id"] == "") ? "IS NULL" : "= '".$sqlHelper->forSql($filter["site_id"], 2)."'");

		if ($moduleId == "main")
		{
			$con->queryExecute(
				"DELETE FROM b_option ".
				"WHERE MODULE_ID = 'main' ".
				"   AND NAME NOT LIKE '~%' ".
				"	AND NAME NOT IN ('crc_code', 'admin_passwordh', 'server_uniq_id','PARAM_MAX_SITES', 'PARAM_MAX_USERS') ".
				$strSqlWhere
			);
		}
		else
		{
			$con->queryExecute(
				"DELETE FROM b_option ".
				"WHERE MODULE_ID = '".$sqlHelper->forSql($moduleId)."' ".
				"   AND NAME <> '~bsm_stop_date' ".
				$strSqlWhere
			);
		}

		if (isset($filter["site_id"]))
		{
			$siteKey = $filter["site_id"] == "" ? "-" : $filter["site_id"];
			if (!isset($filter["name"]))
				unset(self::$options[$siteKey][$moduleId]);
			else
				unset(self::$options[$siteKey][$moduleId][$filter["name"]]);
		}
		else
		{
			$arSites = array_keys(self::$options);
			foreach ($arSites as $s)
			{
				if (!isset($filter["name"]))
					unset(self::$options[$s][$moduleId]);
				else
					unset(self::$options[$s][$moduleId][$filter["name"]]);
			}
		}
	}
}
