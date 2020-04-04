<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$dbRes = CIBlock::GetList(array(), array("TYPE" => "photos", "XML_ID" => "group_photogallery"));
if ($arRes = $dbRes->Fetch())
{
	$photoGroupIBlockID = $arRes["ID"];

	$arSiteID = array(WIZARD_SITE_ID);
	$rsSites = CIBlock::GetSite($photoGroupIBlockID);
	while($arSite = $rsSites->Fetch())
		$arSiteID[] = $arSite["SITE_ID"];

	$arIBlockFields = Array(
		"ACTIVE" => $arRes["ACTIVE"],
		"SITE_ID" => $arSiteID
	);
	$ib = new CIBlock;
	$res = $ib->Update($photoGroupIBlockID, $arIBlockFields);
}
?>
