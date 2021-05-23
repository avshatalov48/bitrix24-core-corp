<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arReturn = array();

if (!CModule::IncludeModule('crm'))
	$arReturn['ERROR'][] = GetMessage('CRM_LOC_EDT_MODULE_NOT_INSTALLED');

if (!CModule::IncludeModule('sale'))
	$arReturn['ERROR'][] = GetMessage('CRM_LOC_EDT_SALE_MODULE_NOT_INSTALLED');

$CrmPerms = new CCrmPerms($USER->GetID());
$bCrmReadPerm = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');

if($USER->IsAuthorized() && check_bitrix_sessid() && $bCrmReadPerm && !isset($arReturn['ERROR']))
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

	$langCount = 0;
	$arSysLangs = Array();
	$arSysLangNames = Array();
	$dbLang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));

	while ($arLang = $dbLang->Fetch())
	{
		$arSysLangs[$langCount] = $arLang["LID"];
		$arSysLangNames[$langCount] = htmlspecialcharsbx($arLang["NAME"]);
		$langCount++;
	}

	switch ($action)
	{
			case 'get_country_params':

				if($ID <= 0)
					break;

				$arCountry = CSaleLocation::GetCountryByID($ID);
				$arCountry['COUNTRY_NAME'] = $arCountry['NAME'];
				$arCountry['COUNTRY_SHORT_NAME'] = $arCountry['SHORT_NAME'];

				unset($arCountry['NAME']);
				unset($arCountry['SHORT_NAME']);
				$countLang = count($arSysLangs);
				for ($i = 0; $i<$countLang; $i++)
				{
					$arLngCountry = CSaleLocation::GetCountryLangByID($ID, $arSysLangs[$i]);
					$arCountry['COUNTRY_NAME_'.$arSysLangs[$i]] = $arLngCountry['NAME'];
					$arCountry['COUNTRY_SHORT_NAME_'.$arSysLangs[$i]] = $arLngCountry['SHORT_NAME_'];
				}

				$arReturn['COUNTRY'] = $arCountry;
				$arRegions = CCrmLocations::getRegionsNames($ID);

				$arReturn['COUNTRY']['REGIONS'] = array();

				foreach ($arRegions as $id => $region)
					$arReturn['COUNTRY']['REGIONS'][] = array($id, $region);

			break;

			case 'get_region_params':

				$arRegion = CSaleLocation::GetRegionByID($ID);
				$arRegion['REGION_NAME'] = $arRegion['NAME'];
				$arRegion['REGION_SHORT_NAME'] = $arRegion['SHORT_NAME'];

				unset($arRegion['NAME']);
				unset($arRegion['SHORT_NAME']);
				$countLang = count($arSysLangs);

				for ($i = 0; $i<$countLang; $i++)
				{
					$arLngCountry = CSaleLocation::GetRegionLangByID($ID, $arSysLangs[$i]);
					$arRegion['REGION_NAME_'.$arSysLangs[$i]] = $arLngCountry['NAME'];
					$arRegion['REGION_SHORT_NAME_'.$arSysLangs[$i]] = $arLngCountry['SHORT_NAME_'];
				}

				$arReturn['REGION'] = $arRegion;

			break;
	}
}
else
{
	$arReturn['ERROR'][] = GetMessage('CRM_LOC_EDT_ERROR_ACCESS_DENIED');
}

$arReturn = $APPLICATION->ConvertCharsetArray($arReturn, SITE_CHARSET, 'utf-8');
echo json_encode($arReturn);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>