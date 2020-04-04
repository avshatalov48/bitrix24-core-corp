<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

if (!CModule::IncludeModule('sale'))
	return;

global $USER;

$CCrmPerms = new CCrmPerms($USER->GetID());
if ($CCrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE, 'WRITE'))
	return;

CUtil::InitJSCore();

$lpEnabled = CSaleLocation::isLocationProEnabled();

if ($_SERVER['REQUEST_METHOD'] == 'POST') // process data from popup dialog
{
	if (check_bitrix_sessid())
	{
		$strError = '';

		if(isset($_REQUEST['RATE_PAGE']))
		{
			$arResult['RATE_PAGE'] = CHTTP::urlAddParams(
						$_REQUEST['RATE_PAGE'],
						array($_REQUEST['FORM_ID'].'_active_tab' => 'tab_rateslist')
			);
		}
		else
		{
			$arResult['RATE_PAGE'] = '';
		}

		$arFields = array();
		$ID = 0;

		if(isset($_POST['ID']))
			$arFields['ID'] = $ID = intval($_POST['ID']);

		if(isset($_POST['TAX_ID']))
			$arFields['TAX_ID'] = intval($_POST['TAX_ID']);

		$arFields['ACTIVE'] =  isset($_POST['ACTIVE']) && $_POST['ACTIVE'] == 'Y' ? 'Y' : 'N';

		if(isset($_POST['PERSON_TYPE_ID']))
			$arFields['PERSON_TYPE_ID'] = $_POST['PERSON_TYPE_ID'];

		if(isset($_POST['VALUE']))
			$arFields['VALUE'] = $_POST['VALUE'];

		$arFields['IS_IN_PRICE'] = isset($_POST['IS_IN_PRICE']) && $_POST['IS_IN_PRICE'] =='Y' ? 'Y' : 'N';

		if(isset($_POST['APPLY_ORDER']))
			$arFields['APPLY_ORDER'] = $_POST['APPLY_ORDER'];

		$arLocation = array();

		if($lpEnabled)
		{
			if(strlen($_REQUEST['LOCATION']['L']))
				$LOCATION1 = explode(':', $_REQUEST['LOCATION']['L']);

			if(strlen($_REQUEST['LOCATION']['G']))
				$LOCATION2 = explode(':', $_REQUEST['LOCATION']['G']);
		}
		else
		{
			$LOCATION1 = isset($_POST['LOCATION1']) ? $_POST['LOCATION1'] : array();
			$LOCATION2 = isset($_POST['LOCATION2']) ? $_POST['LOCATION2'] : array();
		}

		if (is_array($LOCATION1) && count($LOCATION1)>0)
		{
			$countLocation = count($LOCATION1);
			for ($i = 0; $i < $countLocation; $i++)
			{
				if ((string) $LOCATION1[$i] != '')
				{
					$arLocation[] = array(
						"LOCATION_ID" => $LOCATION1[$i],
						"LOCATION_TYPE" => "L"
						);
				}
			}
		}

		if (is_array($LOCATION2) && count($LOCATION2)>0)
		{
			$countLocation2 = count($LOCATION2);
			for ($i = 0; $i < $countLocation2; $i++)
			{
				if ((string) $LOCATION2[$i] != '')
				{
					$arLocation[] = array(
						"LOCATION_ID" => $LOCATION2[$i],
						"LOCATION_TYPE" => "G"
						);
				}
			}
		}

		if (!is_array($arLocation) || count($arLocation)<=0)
			$strError .= GetMessage("CRM_ERROR_NO_LOCATION")."<br>";

		$arFields['TAX_LOCATION'] = $arLocation;

		if (strlen($strError) <= 0)
		{
			if ($ID > 0)
			{
				if (!CSaleTaxRate::Update($ID, $arFields, array("EXPECT_LOCATION_CODES" => $lpEnabled)))
				{
					if ($ex = $GLOBALS['APPLICATION']->GetException())
						$strError .= $ex->GetString();
					else
						$strError .= GetMessage("CRM_ERROR_EDIT_TAX_RATE")."<br>";
				}
			}
			else
			{
				$ID = CSaleTaxRate::Add($arFields, array("EXPECT_LOCATION_CODES" => $lpEnabled));
				if ($ID <= 0)
				{
					if ($ex = $GLOBALS['APPLICATION']->GetException())
						$strError .= $ex->GetString();
					else
						$strError .= GetMessage("CRM_ERROR_ADD_TAX_RATE")."<br>";
				}
			}
		}

		$arResult['ERROR_MSG'] = $strError;
	}
}
else // fill popup dialog
{
	$arResult['ID'] = isset($arParams['ID']) ? intval($arParams['ID']) : 0;
	$arResult['TAX_ID'] = isset($arParams['TAX_ID']) ? intval($arParams['TAX_ID']) : 0;
	$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? strval($arParams['FORM_ID']) : '';

	$arTaxRate = array();

	if($arResult['ID'] > 0)
	{
		$dbTaxRate = CSaleTaxRate::GetList(array(), array("ID"=>$arResult['ID']));
		$arTaxRate = $dbTaxRate->Fetch();
	}

	$arResult['ACTIVE'] = (isset($arTaxRate['ACTIVE']) && $arTaxRate['ACTIVE'] == 'Y') || $arResult['ID'] <= 0 ? true : false;
	$arResult['PERSON_TYPE_ID'] = isset($arTaxRate['PERSON_TYPE_ID']) ? intval($arTaxRate['PERSON_TYPE_ID']) : 0;
	$arResult['VALUE'] = isset($arTaxRate['VALUE']) ? strval($arTaxRate['VALUE']) : '';
	$arResult['IS_IN_PRICE'] = (isset($arTaxRate['IS_IN_PRICE']) && $arTaxRate['IS_IN_PRICE'] == 'Y') ? true : false;
	$arResult['APPLY_ORDER'] = isset($arTaxRate['APPLY_ORDER']) ? intval($arTaxRate['APPLY_ORDER']) : 100;

	$arTax = CCrmTax::GetById($arResult['TAX_ID']);
	$arResult['TAX_NAME'] = htmlspecialcharsbx($arTax['NAME']);

	$arLOCATION1 = array();

	if($lpEnabled)
	{
		$arResult['LOCATION1'] = array();
		$arResult['LOCATION1_LIST'] = array();

		if(isset($_POST['LOCATION1']) || isset($_POST['LOCATION2']))
		{
			$arResult['LOCATION_QUERY'] = array(
				'L' => isset($LOCATION1) ? $LOCATION1 : array(),
				'G' => isset($LOCATION2) ? $LOCATION2 : array(),
			);
		}
		else
		{
			$arResult['LOCATION_QUERY'] = false;
		}
	}
	else
	{
		$db_location = CSaleTaxRate::GetLocationList(array(
										"TAX_RATE_ID" => $arResult['ID'],
										"LOCATION_TYPE" => "L"
		));
		while ($arLocation = $db_location->Fetch())
		{
			$arLOCATION1[] = $arLocation["LOCATION_ID"];
		}

		if(!is_array($arLOCATION1))
			$arLOCATION1 = array();

		$arResult['LOCATION1'] = $arLOCATION1;

		$arLocationsList = array();

		$dbLocList = CSaleLocation::GetList(array(
										"SORT"=>"ASC",
										"COUNTRY_NAME_LANG" => "ASC",
										"COUNTRY_NAME_ORIG" => "ASC",
										"REGION_NAME_LANG" => "ASC",
										"REGION_NAME_ORIG" => "ASC",
										"CITY_NAME_LANG"=>"ASC",
										"CITY_NAME_ORIG"=>"ASC",
										), array("LID" => LANGUAGE_ID)
		);

		while ($arLoc = $dbLocList->Fetch())
		{
			$arLocationsList[$arLoc['ID']] = array(); //$arLoc;

			if(in_array(intval($arLoc['ID']), $arResult['LOCATION1']))
				$arLocationsList[$arLoc['ID']]['SELECTED'] = true;
			else
				$arLocationsList[$arLoc['ID']]['SELECTED'] = false;

			$countryName = $arLoc["COUNTRY_NAME"] != null ? $arLoc["COUNTRY_NAME"] : $arLoc["COUNTRY_NAME_ORIG"];
			$regName = $arLoc["REGION_NAME"] != null ? $arLoc["REGION_NAME"] : $arLoc["REGION_NAME_ORIG"];
			$cityName = $arLoc["CITY_NAME"] != null ? $arLoc["CITY_NAME"] : $arLoc["CITY_NAME_ORIG"];

			$strLocation = $countryName;
			$strLocation .= $countryName && $regName ? ' - ' : '';
			$strLocation .= $regName;
			$strLocation .= ($regName || $countryName) && $cityName ? ' - ' : '';
			$strLocation .= $cityName;

			$arLocationsList[$arLoc['ID']]['STRING'] = $strLocation;
		}

		$arResult['LOCATION1_LIST'] = $arLocationsList;

	}

	/*
	$arLOCATION2 = array();

	$db_location = CSaleTaxRate::GetLocationList(array("TAXRATE_ID" => $arResult['ID'], "LOCATION_TYPE" => "G"));

	while ($arLocation = $db_location->Fetch())
	{
		$arLOCATION2[] = $arLocation["LOCATION_ID"];
	}

	if(!is_array($arLOCATION2))
		$arLOCATION2 = array();

	$arResult['LOCATION2'] = $arLOCATION2;

	$dbLocGrList = CSaleLocationGroup::GetList(array("NAME"=>"ASC"), array(), LANGUAGE_ID);

	while ($arLocGr = $dbLocGrList->Fetch())
	{
		$arLocGrList[$arLocGr['ID']] = $arLocGr;

		if(in_array(intval($arLocGr['ID']), $arResult['LOCATION2']))
			$arLocGrList[$arLocGr['ID']]['SELECTED'] = true;
		else
			$arLocGrList[$arLocGr['ID']]['SELECTED'] = false;
	}

	$arResult['LOCATION2_LIST'] = $arLocGrList;
	*/

	$arResult['PERSON_TYPES_LIST'] = CCrmPaySystem::getPersonTypesList(true);
}

$this->IncludeComponentTemplate();
?>
