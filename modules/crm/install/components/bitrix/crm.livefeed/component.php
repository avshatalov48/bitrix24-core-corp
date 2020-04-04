<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$arEventParams = array();
if (
	is_array($arParams["~FIELDS"])
	&& !empty($arParams["~FIELDS"]["~PARAMS"])
)
{
	$arEventParams = unserialize($arParams["~FIELDS"]["~PARAMS"]);
	if (!is_array($arEventParams))
	{
		$arEventParams = array();
	}
}

try
{
	$oFormat = new CCrmLiveFeedComponent(array(
		"FIELDS" => $arParams["~FIELDS"], 
		"PARAMS" => $arParams["~PARAMS"],
		"EVENT_PARAMS" => $arEventParams
	));
}
catch (Exception $e) 
{
	return false;
}

$aFields = $oFormat->formatFields();

if (in_array($arParams["FIELDS"]["EVENT_ID"], array("crm_company_message", "crm_contact_message", "crm_lead_message", "crm_deal_message")))
{
	$arResult["FORMAT"] = "div";
}
else
{
	$arResult["FORMAT"] = "table";
}

$arResult["FIELDS_FORMATTED"] = array();

$arUF = (!empty($arParams["~FIELDS"]["UF"]) ? $arParams["~FIELDS"]["UF"] : array());

if (!empty($aFields))
{
	foreach($aFields as $arField)
	{
		$arResult["FIELDS_FORMATTED"][] = $oFormat->showField($arField, $arUF);
	}
}

$this->IncludeComponentTemplate();
?>