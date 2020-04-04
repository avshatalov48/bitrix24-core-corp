<?

IncludeModuleLangFile(__FILE__);

use Bitrix\Sale\Location;

class CCrmLocations
{
	private static $LOCATIONS = array();

	public static function GetAll($arSort = array())
	{
		if (!CModule::IncludeModule('sale'))
			return false;

		if(empty(self::$LOCATIONS))
		{
			$dbResultList = CSaleLocation::GetList($arSort);

			while ($arLoc = $dbResultList->Fetch())
				self::$LOCATIONS[$arLoc['ID']] = $arLoc;
		}

		return self::$LOCATIONS;
	}

	public static function CheckLocationExists($locID)
	{
		if(intval($locID) <= 0)
			return false;

		$res = Location\LocationTable::getById($locID)->fetch();
		return !!$res['ID'];
	}

	public static function GetByID($locID)
	{
		if(intval($locID) <= 0)
			return false;

		if(CSaleLocation::isLocationProMigrated())
			return CSaleLocation::GetByID($locID);

		$arLocs = self::GetAll();

		return isset($arLocs[$locID]) ? $arLocs[$locID] : false;
	}

	public static function getCountriesNames()
	{
		$arCNames = array();
		$dbCountList = CSaleLocation::GetCountryList(array("NAME"=>"ASC"), array(), LANGUAGE_ID);

		while ($arCountry = $dbCountList->Fetch())
			$arCNames[$arCountry["ID"]] = $arCountry["NAME_ORIG"]." [".$arCountry["NAME_LANG"]."]";

		return $arCNames;
	}

	public static function getRegionsNames($countryID = false)
	{
		$arFilterRegion = array();
		if ($countryID && intval($countryID) > 0)
			$arFilterRegion["COUNTRY_ID"] = $countryID;

		$arRNames = array();
		$dbRegList = CSaleLocation::GetRegionList(array("NAME"=>"ASC"), $arFilterRegion, LANGUAGE_ID);

		while ($arRegion = $dbRegList->Fetch())
			$arRNames[$arRegion["ID"]] = $arRegion["NAME_ORIG"]." [".$arRegion["NAME_LANG"]."]";

		return $arRNames;
	}

	public static function isLocationsCreated()
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			$res = Location\LocationTable::getList(array('select' => array('CNT')))->fetch();
			return $res['CNT'] > 0;
		}

		$dbResultList = CSaleLocation::GetList();

		if($dbResultList->Fetch())
			return true;

		return false;
	}

	public static function getLocationString($locID)
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			if(!strlen($locID))
				return '';

			if((string) $locID === (string) intval($locID))
				return \Bitrix\Sale\Location\Admin\LocationHelper::getLocationStringById($locID);
			else
				return \Bitrix\Sale\Location\Admin\LocationHelper::getLocationStringByCode($locID);
		}
		else
		{
			if(!is_int($locID))
			{
				$locID = (int)$locID;
			}

			if ($locID <= 0 || !(IsModuleInstalled('sale') && CModule::IncludeModule('sale')))
			{
				return '';
			}

			$entity = new CSaleLocation();
			return $entity->GetLocationString($locID);
		}
	}
}

?>