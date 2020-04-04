<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/*
 * PATH_TO_LOCATIONS_LIST
 * PATH_TO_LOCATIONS_EDIT
 * LOC_ID
 * LOC_ID_PAR_NAME
 */

$arParams['PATH_TO_LOCATIONS_LIST'] = CrmCheckPath('PATH_TO_LOCATIONS_LIST', $arParams['PATH_TO_LOCATIONS_LIST'], '');
$arParams['PATH_TO_LOCATIONS_EDIT'] = CrmCheckPath('PATH_TO_LOCATIONS_EDIT', $arParams['PATH_TO_LOCATIONS_EDIT'], '?loc_id=#loc_id#&edit');

define('CRM_LOC_NEW_COUNTRY', 0);
define('CRM_LOC_WITHOUT_COUNTRY', '');
define('CRM_LOC_NEW_REGION', 0);
define('CRM_LOC_WITHOUT_REGION', '');

$locID = isset($arParams['LOC_ID']) ? intval($arParams['LOC_ID']) : 0;

if($locID <= 0)
{
	$locIDParName = isset($arParams['LOC_ID_PAR_NAME']) ? intval($arParams['LOC_ID_PAR_NAME']) : 0;

	if($locIDParName <= 0)
		$locIDParName = 'loc_id';

	$locID = isset($_REQUEST[$locIDParName]) ? intval($_REQUEST[$locIDParName]) : 0;
}

$arLoc = array();

if($locID > 0)
{
	if(!($arLoc = CCrmLocations::GetByID($locID)))
	{
		ShowError(GetMessage('CRM_LOC_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus("404 Not Found");
		}
		return;
	}
}
$arResult['LOC_ID'] = $locID;
$arResult['LOC'] = $arLoc;

$arResult['FORM_ID'] = 'CRM_LOC_EDIT_FORM';
$arResult['GRID_ID'] = 'CRM_LOC_EDIT_GRID';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_LOCATIONS_LIST'],
	array()
);

$langCount = 0;
$arSysLangs = Array();
$arSysLangNames = Array();
$dbLang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
while ($arLang = $dbLang->Fetch())
{
	$arSysLangs[$langCount] = $arLang["LID"];
	$arSysLangNames[$langCount] = htmlspecialcharsEx($arLang["NAME"]);
	$langCount++;
}

$countLang = count($arSysLangs);
$arResult['SYS_LANGS'] = $arSysLangs;

$strError = "";

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$ID = isset($_POST['loc_id']) ? intval($_POST['loc_id']) : 0;

		if( $ID <= 0 && isset($_POST['ID']))
			$ID = intval($_POST['ID']) > 0 ? intval($_POST['ID']) : 0;

		$SORT = isset($_POST['SORT']) && intval($_POST['SORT']) > 0 ? intval($_POST['SORT']) : 100;

		$CHANGE_COUNTRY = isset($_POST['CHANGE_COUNTRY']) && $_POST['CHANGE_COUNTRY'] == "Y" ? 'Y' : 'N';
		$WITHOUT_CITY = isset($_POST['WITHOUT_CITY']) && $_POST['WITHOUT_CITY'] == "Y" ? 'Y' : 'N';

		$COUNTRY_ID = isset($_POST['COUNTRY_ID']) ? trim($_POST['COUNTRY_ID']) : '';

		if ($ID > 0 && $COUNTRY_ID <= 0 && $CHANGE_COUNTRY == "Y")
			$strError .= GetMessage("CRM_ERROR_SELECT_COUNTRY")."<br>";

		if (($COUNTRY_ID<=0 || $ID>0 && $COUNTRY_ID>0 && $CHANGE_COUNTRY=="Y") && $COUNTRY_ID != "")
		{
			$COUNTRY_NAME = isset($_POST['COUNTRY_NAME']) ? trim($_POST['COUNTRY_NAME']) : '';

			if (strlen($COUNTRY_NAME)<=0)
				$strError .= GetMessage("CRM_ERROR_COUNTRY_NAME")."<br>";
			/*
			for ($i = 0; $i<$countLang; $i++)
			{
				$langCountryName = "COUNTRY_NAME_".$arSysLangs[$i];
				$$langCountryName = isset($_POST[$langCountryName]) ? trim($_POST[$langCountryName]) : '';
				if (strlen($$langCountryName)<=0)
					$strError .= GetMessage("CRM_ERROR_COUNTRY_NAME_LANG")." [".$arSysLangs[$i]."] ".$arSysLangNames[$i].".<br>";
			}
			*/
		}

		if ($WITHOUT_CITY != "Y")
		{
			$CITY_NAME = isset($_POST['CITY_NAME']) ? trim($_POST['CITY_NAME']) : '';

			if (strlen($CITY_NAME) <= 0)
				$strError .= GetMessage("CRM_ERROR_CITY_NAME")."<br>";
			/*
			for ($i = 0; $i<$countLang; $i++)
			{
				$langCityName = "CITY_NAME_".$arSysLangs[$i];
				$$langCityName = isset($_POST[$langCityName]) ? trim($_POST[$langCityName]) : '';

				if (strlen($$langCityName)<=0)
					$strError .= GetMessage("CRM_ERROR_CITY_NAME_LANG")." [".$arSysLangs[$i]."] ".$arSysLangNames[$i].".<br>";
			}
			*/
		}

		//isset region
		if (isset($_POST["REGION_ID"]) && $_POST["REGION_ID"] != "")
		{
			$REGION_ID = trim($_POST['REGION_ID']);
			$REGION_NAME = isset($_POST['REGION_NAME']) ? trim($_POST['REGION_NAME']) : '';

			if (strlen($REGION_NAME) <= 0)
				$strError .= GetMessage("CRM_ERROR_REGION_NAME")."<br>";
			/*
			for ($i = 0; $i<$countLang; $i++)
			{
				$langRegionName = "REGION_NAME_".$arSysLangs[$i];
				$$langRegionName = isset($_POST[$langRegionName]) ? trim($_POST[$langRegionName]) : '';

				if (strlen($$langRegionName)<=0 && $REGION_ID == 0)
					$strError .= GetMessage("CRM_ERROR_REGION_NAME_LANG")." [".$arSysLangs[$i]."] ".$arSysLangNames[$i].".<br>";
			}
			*/
		}

		if (strlen($strError) <= 0)
		{
			$arFields = array(
				"SORT" => $SORT,
				"COUNTRY_ID" => $COUNTRY_ID,
				"CHANGE_COUNTRY" => (($CHANGE_COUNTRY == "Y") ? "Y" : "N"),
				"WITHOUT_CITY" => (($WITHOUT_CITY == "Y") ? "Y" : "N"),
				"REGION_ID" => $REGION_ID
				);

			if ($COUNTRY_ID<=0 || $ID>0 && $COUNTRY_ID>0 && $CHANGE_COUNTRY=="Y")
			{
				$arCountry = array(
					"NAME" => $COUNTRY_NAME,
					"SHORT_NAME" => isset($_POST['COUNTRY_SHORT_NAME']) ? $_POST['COUNTRY_SHORT_NAME'] : ''
					);

				for ($i = 0; $i<$countLang; $i++)
				{
					$arCountry[$arSysLangs[$i]] = array(
							"LID" => $arSysLangs[$i],
							"NAME" => $arCountry["NAME"], //isset($_POST["COUNTRY_NAME_".$arSysLangs[$i]]) ? $_POST["COUNTRY_NAME_".$arSysLangs[$i]] : '',
							"SHORT_NAME" => $arCountry["SHORT_NAME"] //isset($_POST["COUNTRY_SHORT_NAME_".$arSysLangs[$i]]) ? $_POST["COUNTRY_SHORT_NAME_".$arSysLangs[$i]] : ''
						);
				}

				$arFields["COUNTRY"] = $arCountry;
			}

			if ($WITHOUT_CITY!="Y")
			{
				$arCity = array(
					"NAME" => $CITY_NAME,
					"SHORT_NAME" => isset($_POST['CITY_SHORT_NAME']) ? $_POST['CITY_SHORT_NAME'] : ''
					);
				if ($REGION_ID > 0)
					$regionTmp = $REGION_ID;
				else
					$regionTmp = '';

				$arCity["REGION_ID"] = $regionTmp;

				for ($i = 0; $i<$countLang; $i++)
				{
					$arCity[$arSysLangs[$i]] = array(
							"LID" => $arSysLangs[$i],
							"NAME" => $arCity["NAME"], //isset($_POST["CITY_NAME_".$arSysLangs[$i]]) ? $_POST["CITY_NAME_".$arSysLangs[$i]] : '',
							"SHORT_NAME" => $arCity["SHORT_NAME"] //isset($_POST["CITY_SHORT_NAME_".$arSysLangs[$i]]) ? $_POST["CITY_SHORT_NAME_".$arSysLangs[$i]] : ''
						);
				}

				$arFields["CITY"] = $arCity;
			}

			//region
			if (isset($_POST["REGION_ID"]) && $_POST["REGION_ID"] != "")
			{
				$arRegion = array(
					"NAME" => isset($_POST['REGION_NAME']) ? $_POST['REGION_NAME'] : '',
					"SHORT_NAME" => isset($_POST['REGION_SHORT_NAME']) ? $_POST['REGION_SHORT_NAME'] : ''
					);

				for ($i = 0; $i<$countLang; $i++)
				{
					$arRegion[$arSysLangs[$i]] = array(
							"LID" => $arSysLangs[$i],
							"NAME" => $arRegion["NAME"], //isset($_POST["REGION_NAME_".$arSysLangs[$i]]) ? $_POST["REGION_NAME_".$arSysLangs[$i]] : '',
							"SHORT_NAME" => $arRegion["SHORT_NAME"] //isset($_POST["REGION_SHORT_NAME_".$arSysLangs[$i]]) ? $_POST["REGION_SHORT_NAME_".$arSysLangs[$i]] : ''
						);
				}

				$arFields["REGION"] = $arRegion;
			}

			$arFields["LOC_DEFAULT"] = "N";
			if (isset($_POST['LOC_DEFAULT']) && strlen($_POST['LOC_DEFAULT']) > 0)
				$arFields["LOC_DEFAULT"] = $_POST['LOC_DEFAULT'];

			if ($ID > 0)
			{
				$ID = CSaleLocation::Update($ID, $arFields);

				if (IntVal($ID) <= 0)
				{
					if ($ex = $APPLICATION->GetException())
						$strError .= $ex->GetString()."<br>";
					else
						$strError .= GetMessage("CRM_LOC_UPDATE_UNKNOWN_ERROR")."<br>";
				}
			}
			else
			{
				$ID = CSaleLocation::Add($arFields);
				if (IntVal($ID) <= 0 )
				{
					if ($ex = $APPLICATION->GetException())
						$strError = $ex->GetString()."<br>";
					else
						$strError .= GetMessage("CRM_LOC_ADD_UNKNOWN_ERROR")."<br>";
				}
			}

			if ($ID > 0 && strlen($strError) <= 0)
			{
				$arZipList = $_REQUEST["ZIP"];
				CSaleLocation::SetLocationZIP($ID, $arZipList);
			}

//			die();
			if(strlen($strError) <= 0)
			{
				LocalRedirect(
					isset($_POST['apply']) || strlen($strError) > 0
						? CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_LOCATIONS_EDIT'],
						array('loc_id' => $ID)
					)
						: CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_LOCATIONS_LIST'],
						array('loc_id' => $ID)
					)
				);
			}

		}

		if(strlen($strError) > 0)
			ShowError($strError);

	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$locID = isset($arParams['LOC_ID']) ? intval($arParams['LOC_ID']) : 0;
		$arLoc = $locID > 0 ? CCrmLocations::GetByID($locID) : null;
		if($arLoc)
		{

			if(!CSaleLocation::Delete($locID))
			{
				$errorMsg = '';

				if ($ex = $APPLICATION->GetException())
					$errorMsg = $ex->GetString();
				else
					$errorMsg = GetMessage('CRM_LOC_DELETE_UNKNOWN_ERROR');

				ShowError($errorMsg);
			}
			else
			{
				LocalRedirect(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_LOCATIONS_LIST'],
						array()
					)
				);
			}
		}
	}
}

$arResult['FIELDS'] = array();
/* LOCATION PARAMS SECTION*/
$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'loc_info',
	'name' => GetMessage('CRM_LOC_SECTION_MAIN'),
	'type' => 'section'
);

if(intval($arParams['LOC_ID']) > 0)
{
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'ID',
		'name' => GetMessage('CRM_LOC_FIELD_ID'),
		'value' => $locID,
		'type' =>  'label'
	);
}

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'SORT',
	'name' =>  GetMessage('CRM_LOC_FIELD_SORT'),
	'value' => $arLoc['SORT'],
	'required' => true,
	'type' =>  'text'
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'LOC_DEFAULT',
	'name' =>  GetMessage('CRM_LOC_FIELD_LOC_DEFAULT'),
	'value' => $arLoc['LOC_DEFAULT'] == 'Y',
	'type' =>  'checkbox'
);

/* COUNTRY SECTION*/
$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'loc_country',
	'name' => GetMessage('CRM_LOC_SECTION_COUNTRY'),
	'type' => 'section'
);

$arCountries[CRM_LOC_NEW_COUNTRY] = '< '.GetMessage('CRM_LOC_NEW_COUNTRY').' >';
$arCountries[CRM_LOC_WITHOUT_COUNTRY] = '< '.GetMessage('CRM_LOC_WITHOUT_COUNTRY').' >';
$arCountriesArr = CCrmLocations::getCountriesNames();
foreach ($arCountriesArr as $countyID => $country)
	$arCountries[$countyID] = $country;

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'COUNTRY_ID',
	'name' =>  GetMessage('CRM_LOC_FIELD_COUNTRY_ID'),
	'value' => intval($arLoc['COUNTRY_ID']) > 0 ? $arLoc['COUNTRY_ID'] : '',
	'type' =>  'list',
	'required' => true,
	'items' => $arCountries
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'CHANGE_COUNTRY',
	'name' =>  GetMessage('CRM_LOC_FIELD_CHANGE_COUNTRY'),
	'value' => 'N',
	'type' =>  'checkbox'
);


$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'COUNTRY_NAME',
	'name' =>  GetMessage('CRM_LOC_FIELD_NAME'),
	'value' => htmlspecialcharsEx($arLoc['COUNTRY_NAME_ORIG']),
	'required' => true,
	'type' =>  'text'
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'COUNTRY_SHORT_NAME',
	'name' =>  GetMessage('CRM_LOC_FIELD_SHORT_NAME'),
	'value' => htmlspecialcharsEx($arLoc['COUNTRY_SHORT_NAME']),
	'type' =>  'text'
);

for ($i = 0; $i<$countLang; $i++)
{
	$arCountry = CSaleLocation::GetCountryLangByID($arLoc['COUNTRY_ID'], $arSysLangs[$i]);
/*
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'C_LANG_'.$arSysLangs[$i],
		'value' => '<b>['.$arSysLangs[$i].'] '.$arSysLangNames[$i].'</b>',
		'colspan' => true,
		'type' =>  'label'
	);
*/
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'COUNTRY_NAME_'.$arSysLangs[$i],
		'name' =>  GetMessage('CRM_LOC_FIELD_NAME'),
		'value' => htmlspecialcharsEx($arCountry["NAME"]),
		'required' => true,
		'type' =>  'text'
	);

	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'COUNTRY_SHORT_NAME_'.$arSysLangs[$i],
		'name' =>  GetMessage('CRM_LOC_FIELD_SHORT_NAME'),
		'value' => htmlspecialcharsEx($arCountry["SHORT_NAME"]),
		'type' =>  'text'
	);

}

/* REGION SECTION */
$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'loc_region',
	'name' => GetMessage('CRM_LOC_SECTION_REGION'),
	'type' => 'section'
);

$arRegionList[CRM_LOC_NEW_REGION] = '< '.GetMessage('CRM_LOC_NEW_REGION').' >';
$arRegionList[CRM_LOC_WITHOUT_REGION] = '< '.GetMessage('CRM_LOC_WITHOUT_REGION').' >';

$arRegionArr = CCrmLocations::getRegionsNames($arLoc['COUNTRY_ID']);

foreach ($arRegionArr as $regionID => $region)
	$arRegionList[$regionID] = $region;

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'REGION_ID',
	'name' =>  GetMessage('CRM_LOC_FIELD_REGION_ID'),
	'value' => intval($arLoc['REGION_ID']) > 0 ? $arLoc['REGION_ID'] : '',
	'type' =>  'list',
	'required' => true,
	'items' => $arRegionList
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'REGION_NAME',
	'name' =>  GetMessage('CRM_LOC_FIELD_NAME'),
	'value' => htmlspecialcharsEx($arLoc['REGION_NAME_ORIG']),
	'required' => true,
	'type' =>  'text'
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'REGION_SHORT_NAME',
	'name' =>  GetMessage('CRM_LOC_FIELD_SHORT_NAME'),
	'value' => htmlspecialcharsEx($arLoc['REGION_SHORT_NAME']),
	'type' =>  'text'
);

for ($i = 0; $i<$countLang; $i++)
{
	$arRegion = CSaleLocation::GetRegionLangByID($arLoc['REGION_ID'], $arSysLangs[$i]);
/*
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'R_LANG_'.$arSysLangs[$i],
		'value' => '<b>['.$arSysLangs[$i].'] '.$arSysLangNames[$i].'</b>',
		'colspan' => true,
		'type' =>  'label'
	);
*/
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'REGION_NAME_'.$arSysLangs[$i],
		'name' =>  GetMessage('CRM_LOC_FIELD_NAME'),
		'value' => htmlspecialcharsEx($arRegion["NAME"]),
		'required' => true,
		'type' =>  'text'
	);

	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'REGION_SHORT_NAME_'.$arSysLangs[$i],
		'name' =>  GetMessage('CRM_LOC_FIELD_SHORT_NAME'),
		'value' => htmlspecialcharsEx($arRegion["SHORT_NAME"]),
		'type' =>  'text'
	);

}

/* CITY SECTION */
$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'loc_city',
	'name' => GetMessage('CRM_LOC_SECTION_CITY'),
	'type' => 'section'
);

$arCity = CSaleLocation::GetCityByID($arLoc['CITY_ID']);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'WITHOUT_CITY',
	'name' =>  GetMessage('CRM_LOC_FIELD_WITHOUT_CITY'),
	'value' => intval($locID) > 0 && !is_null($arLoc['CITY_ID']) && $arCity ? 'N' : 'Y',
	'type' =>  'checkbox'
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'CITY_NAME',
	'name' =>  GetMessage('CRM_LOC_FIELD_NAME'),
	'value' => htmlspecialcharsEx($arLoc['CITY_NAME_ORIG']),
	'required' => true,
	'type' =>  'text'
);

$arResult['FIELDS']['tab_params'][] = array(
	'id' => 'CITY_SHORT_NAME',
	'name' =>  GetMessage('CRM_LOC_FIELD_SHORT_NAME'),
	'value' => htmlspecialcharsEx($arLoc['CITY_SHORT_NAME']),
	'type' =>  'text'
);

for ($i = 0; $i<$countLang; $i++)
{
	$arCity = CSaleLocation::GetCityLangByID($arLoc['CITY_ID'], $arSysLangs[$i]);
/*
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'CI_LANG_'.$arSysLangs[$i],
		'value' => '<b>['.$arSysLangs[$i].'] '.$arSysLangNames[$i].'</b>',
		'colspan' => true,
		'type' =>  'label'
	);
*/
	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'CITY_NAME_'.$arSysLangs[$i],
		'name' =>  GetMessage('CRM_LOC_FIELD_NAME'),
		'value' => htmlspecialcharsEx($arCity["NAME"]),
		'required' => true,
		'type' =>  'text'
	);

	$arResult['FIELDS']['tab_params'][] = array(
		'id' => 'CITY_SHORT_NAME_'.$arSysLangs[$i],
		'name' =>  GetMessage('CRM_LOC_FIELD_SHORT_NAME'),
		'value' => htmlspecialcharsEx($arCity["SHORT_NAME"]),
		'type' =>  'text'
	);

}


/*ZIP TAB*/
$arResult['FIELDS']['tab_zip'][] = array(
	'id' => 'loc_zip',
	'name' => GetMessage('CRM_LOC_SECTION_ZIP'),
	'type' => 'section'
);

$zipHtml = '<div id="zip_list">';
$arZipList = array();
$rsZipList = CSaleLocation::GetLocationZIP($locID);
while ($arZip = $rsZipList->Fetch())
	$arZipList[] = $arZip;

foreach ($arZipList as $key => $zip)
	$zipHtml .= '<input type="text" name="ZIP[]" value="'.htmlspecialcharsEx($zip["ZIP"]).'" size="10" /><span class="bx-crm-location-zip-delete" onclick="BX.crmLocationZip.delete(this);">'.GetMessage("CRM_DEL_ZIP").'</span><br />';

$zipHtml .= '<input type="text" name="ZIP[]" value="" size="10" /><br />
			</div>
			<button onClick="return BX.crmLocationZip.add();">'.GetMessage("CRM_ADD_ZIP").'</button>';

$arResult['FIELDS']['tab_zip'][] = array(
	'id' => 'ZIP_INPUTS',
	'name' =>  GetMessage('CRM_LOC_FIELD_LOC_ZIP'),
	'value' => $zipHtml,
	'type' =>  'custom'
);

$this->IncludeComponentTemplate();
?>