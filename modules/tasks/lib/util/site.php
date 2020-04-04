<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

final class Site
{
	private static $cache = array();

	public static function getUserNameFormat($siteId = '')
	{
		return str_replace(array("#NOBR#","#/NOBR#"), array("",""), (string) \CSite::GetNameFormat(false, $siteId));
	}

	public static function getServerName($siteId = '')
	{
		return \Bitrix\Main\Config\Option::get("main", "server_name");
	}

	/**
	 * Returns basic site data by id
	 *
	 * @param $id
	 * @return string[]|bool
	 *
	 */
	public static function get($id = '')
	{
		if(!$id)
		{
			if(defined(SITE_ID))
			{
				$id = SITE_ID;
			}
		}

		if(!$id)
		{
			return false;
		}

		$structure = static::getSiteStruct();

		return is_array($structure['LIST'][$id]) ? $structure['LIST'][$id] : false;
	}

	/**
	 * Returns two sites: intranet and extranet
	 */
	public static function getPair()
	{
		$structure = static::getSiteStruct();

		return $structure['PAIR'];
	}

	private static function getSiteStruct()
	{
		if(empty(self::$cache['SITE']))
		{
			if(\Bitrix\Main\Loader::includeModule("extranet"))
			{
				$extranetSiteId = \CExtranet::getExtranetSiteID();
			}
			else
			{
				$extranetSiteId = false;
			}

			$siteList = array(
				'LIST' => array(),
				'PAIR' => array(
					'EXTRANET' => false,
					'INTRANET' => false
				)
			);
			$res = \CSite::getList($by="sort", $order="desc", array("ACTIVE" => "Y"));
			while($site = $res->Fetch())
			{
				$siteList['LIST'][$site['ID']] = array(
					'SITE_ID' => $site['ID'],
					'DIR' => (strlen(trim($site["DIR"])) > 0 ? $site["DIR"] : "/"),
					'SERVER_NAME' => (strlen(trim($site["SERVER_NAME"])) > 0 ? $site["SERVER_NAME"] : \Bitrix\Main\Config\Option::get("main", "server_name", $_SERVER["HTTP_HOST"])),
				);

				if($site["ID"] == $extranetSiteId)
				{
					$siteList['PAIR']['EXTRANET'] =& $siteList['LIST'][$site['ID']];
				}
				else // type == intranet
				{
					if(!(isset($siteList['PAIR']['INTRANET']) && $site['DEF'] !== 'Y'))
					{
						$siteList['PAIR']['INTRANET'] =& $siteList['LIST'][$site['ID']];
					}
				}
			}

			self::$cache['SITE'] = $siteList;
		}

		return self::$cache['SITE'];
	}
}