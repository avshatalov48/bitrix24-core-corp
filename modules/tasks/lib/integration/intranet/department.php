<?
/**
 * Class implements all further interactions with "extranet" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Intranet;

use Bitrix\Main\Loader;

final class Department extends \Bitrix\Tasks\Integration\Intranet
{
	/**
	 * Returns a list of department IDs that are under $userId direction
	 *
	 * @param int $userId
	 * @param bool $recursive
	 * @return array
	 */
	public static function getSubordinateIds($userId = 0, $recursive = false)
	{
		$result = array();

		if(static::includeModule())
		{
			if(!$userId)
			{
				$userId = \Bitrix\Tasks\Util\User::getId();
			}
			if(!$userId)
			{
				return $result;
			}

			$result = \CIntranetUtils::getSubordinateDepartments($userId, $recursive);
		}

		return $result;
	}

	/**
	 * Returns a list of sub-department IDs for the department $id
	 *
	 * @param $id
	 * @param bool $direct
	 * @param bool $flat
	 * @return array
	 */
	public static function getSubIds($id, $direct = true, $flat = false)
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result;
		}

		if($direct)
		{
			$result = \CIntranetUtils::getSubDepartments($id);
		}
		else
		{
			$result = \CIntranetUtils::getDeparmentsTree($id, $flat);
		}

		if(!is_array($result))
		{
			$result = array();
		}

		return $result;
	}

	/**
	 * Returns basic data for department IDs passed
	 *
	 * @param array $departmentIds
	 * @return array
	 */
	public static function getData(array $departmentIds)
	{
		$result = array();

		if(!static::includeModule() || empty($departmentIds))
		{
			return $result; // no module = no departments
		}

		$res = static::getIBlockSections();
		$sections = static::replaceIBSField($res['SECTIONS']);
		if(!empty($sections))
		{
			foreach($sections as $item)
			{
				if(in_array($item['ID'], $departmentIds))
				{
					$item['HEAD'] = \CIntranetUtils::GetDepartmentManagerID($item['ID']);
					$result[$item['ID']] = $item;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the complete company structure
	 *
	 * @return array
	 */
	public static function getCompanyStructure()
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result; // no module = no departments
		}

		$result = static::getIBlockSections();
		return static::replaceIBSField($result['SECTIONS']);
	}

	public static function getMainDepartment()
	{
		$result = [];

		$companyStructure = static::getCompanyStructure();
		if (!empty($companyStructure))
		{
			reset($companyStructure);
			$result = current($companyStructure);
		}

		return $result;
	}

	private static function getIBlockSections(array $select = array())
	{
		$result = array("SECTIONS" => array(), "STRUCTURE" => array());

		if(!static::includeModule() || !Loader::includeModule('iblock') || !empty($ids))
		{
			return $result;
		}

		$iblockId = intval(\COption::getOptionInt('intranet', 'iblock_structure'));
		if(!$iblockId)
		{
			return $result;
		}

		$filter = array('IBLOCK_ID' => $iblockId);
		$select = array_merge($select, array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN'));

		$cache = new \CPHPCache();
		$cacheDir = '/tasks/subordinatedeps';

		$structure = array();
		$sections = array();

		if($cache->initCache(32100113, md5(serialize($filter)), $cacheDir))
		{
			$vars = $cache->getVars();
			$sections = $vars["SECTIONS"];
			$structure = $vars["STRUCTURE"];
		}
		elseif ($cache->startDataCache())
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->startTagCache($cacheDir);
			$CACHE_MANAGER->registerTag("iblock_id_{$iblockId}");

			$res = \CIBlockSection::getList(
				['left_margin' => 'asc'], // order as full expanded tree
				$filter,
				false, // don't count
				$select
			);
			while ($item = $res->fetch())
			{
				$id = $item['ID'];
				$iblockSectionID = (int)$item['IBLOCK_SECTION_ID'];

				if (!isset($structure[$iblockSectionID]))
				{
					$structure[$iblockSectionID] = [];
				}
				$structure[$iblockSectionID][] = $id;

				$sections[$id] = $item;
			}
			$CACHE_MANAGER->endTagCache();
			$cache->endDataCache(array("SECTIONS" => $sections, "STRUCTURE" => $structure));
		}

		$result['SECTIONS'] = $sections;
		$result['STRUCTURE'] = $structure;

		return $result;
	}

	private static function replaceIBSField(array $sections)
	{
		foreach($sections as $k => $v)
		{
			$sections[$k]['PARENT_ID'] = intval($sections[$k]['IBLOCK_SECTION_ID']);
			unset($sections[$k]['IBLOCK_SECTION_ID']);

			$sections[$k]['L'] = intval($sections[$k]['LEFT_MARGIN']);
			unset($sections[$k]['LEFT_MARGIN']);

			$sections[$k]['R'] = intval($sections[$k]['RIGHT_MARGIN']);
			unset($sections[$k]['RIGHT_MARGIN']);
		}

		return $sections;
	}

	public static function getFlatListTree($sep = '.', $sepMultiplier = 2)
	{
		if ((int)$sepMultiplier < 1)
		{
			$sepMultiplier = 1;
		}

		$list = [];

		$iblockId = intval(\COption::getOptionInt('intranet', 'iblock_structure'));
		$arFilter = Array("IBLOCK_ID" => $iblockId);
		//		if($ACTIVE_FILTER === "Y")
		//			$arFilter["GLOBAL_ACTIVE"] = "Y";

		$res = \CIBlockSection::GetList(
			Array("left_margin" => "asc"),
			$arFilter,
			false,
			array("ID", "DEPTH_LEVEL", "NAME")
		);

		while ($row = $res->Fetch())
		{
			$list[$row['ID']] = str_repeat($sep, ($row['DEPTH_LEVEL'] * $sepMultiplier) - $sepMultiplier).
								' '.
								$row['NAME'];
		}

		return $list;
	}

	public static function getFlatListTreeByDepartmentId($departmentId, $sep = '.', $sepMultiplier = 2)
	{
		if ((int)$sepMultiplier < 1)
		{
			$sepMultiplier = 1;
		}

		$iblockId = intval(\COption::getOptionInt('intranet', 'iblock_structure'));

		$res = \CIBlockSection::GetByID($departmentId);
		$section = $res->Fetch();

		$list = [];

		if (!$section)
		{
			return $list;
		}

		dd($section);

		$arFilter = Array("IBLOCK_ID" => $iblockId);
		//		if($ACTIVE_FILTER === "Y")
		//			$arFilter["GLOBAL_ACTIVE"] = "Y";

		$res = \CIBlockSection::GetList(
			Array("left_margin" => "asc"),
			$arFilter,
			false,
			array("ID", "DEPTH_LEVEL", "NAME")
		);

		while ($row = $res->Fetch())
		{
			$list[$row['ID']] = str_repeat($sep, ($row['DEPTH_LEVEL'] * $sepMultiplier) - $sepMultiplier).
								' '.
								$row['NAME'];
		}

		return $list;
	}
}