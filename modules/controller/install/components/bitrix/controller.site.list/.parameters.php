<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("controller"))
	return;

$arSiteGroups = Array();
$rsSiteGroups = CControllerGroup::GetList(Array("ID" => "ASC"));
while($arSiteGroup = $rsSiteGroups->Fetch())
	$arSiteGroups[$arSiteGroup["ID"]] = $arSiteGroup["NAME"];

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"TITLE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSL_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => GetMessage("CP_BCSL_TITLE_DEFAULT"),
		),
		"GROUP" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSL_GROUP"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arSiteGroups,
			"DEFAULT" => 1,
		),
		"CACHE_TIME"  =>  Array("DEFAULT"=>3600),
	),
);
?>
