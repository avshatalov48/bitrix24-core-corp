<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

if (intval($arParams["FIELDS"]["ENTITY_ID"]) > 0)
{
	try
	{
		$oFormat = new CCrmLiveFeedComponent(array(
			"FIELDS" => $arParams["~FIELDS"], 
			"PARAMS" => $arParams["~PARAMS"],
			"INVOICE" => $arParams["~INVOICE"]
		));
	}
	catch (Exception $e) 
	{
		return false;
	}

	$aFields = $oFormat->formatFields();

	$arResult["FORMAT"] = "table";
	$arResult["FIELDS_FORMATTED"] = array();

	if (!empty($aFields))
	{
		foreach($aFields as $arField)
		{
			$arResult["FIELDS_FORMATTED"][] = $oFormat->showField($arField);
		}
	}
}

$this->IncludeComponentTemplate();
?>