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

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

final class Site
{
	private static $cache = array();

	public static function getUserNameFormat($siteId = '')
	{
		return str_replace(array("#NOBR#","#/NOBR#"), array("",""), (string) \CSite::GetNameFormat(false, $siteId));
	}

	public static function getServerName($siteId = '')
	{
		return Option::get("main", "server_name");
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
			if(defined('SITE_ID'))
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
		if (empty(self::$cache['SITE']))
		{
			$extranetSiteId = (Loader::includeModule('extranet') ? \CExtranet::getExtranetSiteID() : false);
			$siteList = [
				'LIST' => [],
				'PAIR' => [
					'EXTRANET' => false,
					'INTRANET' => false,
				],
			];
			$res = \CSite::getList('sort', 'desc', ['ACTIVE' => 'Y']);
			while ($site = $res->Fetch())
			{
				$siteId = $site['ID'];
				$siteDir = $site['DIR'];
				$siteServerName = $site['SERVER_NAME'];

				$siteList['LIST'][$siteId] = [
					'SITE_ID' => $siteId,
					'DIR' => ($siteDir && trim($siteDir) !== '' ? $siteDir : '/'),
					'SERVER_NAME' => (
						$siteServerName && trim($siteServerName) !== ''
							? $siteServerName
							: Option::get('main', 'server_name', $_SERVER['HTTP_HOST'] ?? null)
					),
				];

				if ($siteId == $extranetSiteId)
				{
					$siteList['PAIR']['EXTRANET'] =& $siteList['LIST'][$siteId];
				}
				// type == intranet
				elseif (!(isset($siteList['PAIR']['INTRANET']) && $site['DEF'] !== 'Y'))
				{
					$siteList['PAIR']['INTRANET'] =& $siteList['LIST'][$siteId];
				}
			}

			self::$cache['SITE'] = $siteList;
		}

		return self::$cache['SITE'];
	}
}