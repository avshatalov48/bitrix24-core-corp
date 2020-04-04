<?php
namespace Bitrix\Crm\Context;
use Bitrix\Main;
use Bitrix\Crm;

class GridContext
{
	/**
	 * Store grid filter in session data.
	 * @param string $gridID Grid ID.
	 * @param array $filter Filter settings.
	 */
	public static function setFilter($gridID, array $filter)
	{
		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}

		if(!isset($_SESSION['CRM_GRID_DATA'][$gridID]))
		{
			$_SESSION['CRM_GRID_DATA'][$gridID] = array();
		}

		$_SESSION['CRM_GRID_DATA'][$gridID]['FILTER'] = $filter;
	}
	/**
	 * Load grid filter settings from session data.
	 * @param string $gridID Grid ID.
	 * @return array|null
	 */
	public static function getFilter($gridID)
	{
		return isset($_SESSION['CRM_GRID_DATA'])
			&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
			&& isset($_SESSION['CRM_GRID_DATA'][$gridID]['FILTER'])
			? $_SESSION['CRM_GRID_DATA'][$gridID]['FILTER'] : null;
	}
	/**
	 * Store grid filter hash in session data.
	 * @param string $gridID Grid ID.
	 * @param string $filterHash Filter hash.
	 */
	public static function setFilterHash($gridID, $filterHash)
	{
		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}

		if(!isset($_SESSION['CRM_GRID_DATA'][$gridID]))
		{
			$_SESSION['CRM_GRID_DATA'][$gridID] = array();
		}

		$_SESSION['CRM_GRID_DATA'][$gridID]['FILTER_HASH'] = $filterHash;
	}
	/**
	 * Load grid filter hash from session data.
	 * @param string $gridID Grid ID.
	 * @return string
	 */
	public static function getFilterHash($gridID)
	{
		return isset($_SESSION['CRM_GRID_DATA'])
			&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
			&& isset($_SESSION['CRM_GRID_DATA'][$gridID]['FILTER_HASH'])
			? $_SESSION['CRM_GRID_DATA'][$gridID]['FILTER_HASH'] : '';
	}
	/**
	 * Prepare hash for grid filter settings.
	 * @param array $filter Filter settings.
	 * @return string
	 */
	public static function prepareFilterHash(array $filter)
	{
		unset($filter['GRID_FILTER_ID'], $filter['GRID_FILTER_APPLIED']);
		return md5(serialize($filter));
	}
}