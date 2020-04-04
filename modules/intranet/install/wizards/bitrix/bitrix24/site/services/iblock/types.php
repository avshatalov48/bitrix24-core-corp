<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$arTypes = Array(

	Array(
		"ID" => "structure",
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 20,
		"LANG" => Array(),
	),

	Array(
		"ID" => "services",
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 30,
		"LANG" => Array(),
	),

	Array(
		"ID" => "events",
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 40,
		"LANG" => Array(),
	),

	Array(
		"ID" => "photos",
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 60,
		"LANG" => Array(),
	),
/*	Array(
		"ID" => "lists",
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 70,
		"LANG" => Array(),
	),

	Array(
		"ID" => "lists_socnet",
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 80,
		"LANG" => Array(),
	),  */
);

$arLanguages = Array();
$rsLanguage = CLanguage::GetList($by, $order, array());
while($arLanguage = $rsLanguage->Fetch())
	$arLanguages[] = $arLanguage["LID"];

$iblockType = new CIBlockType;
foreach($arTypes as $arType)
{
	echo $arType["ID"].",";
	$dbType = CIBlockType::GetList(Array(),Array("=ID" => $arType["ID"]));
	if($dbType->Fetch())
		continue;

	foreach($arLanguages as $languageID)
	{
		WizardServices::IncludeServiceLang("type_names.php", $languageID);

		$code = strtoupper($arType["ID"]);
		$arType["LANG"][$languageID]["NAME"] = GetMessage($code."_TYPE_NAME");
		$arType["LANG"][$languageID]["ELEMENT_NAME"] = GetMessage($code."_ELEMENT_NAME");

		if ($arType["SECTIONS"] == "Y")
			$arType["LANG"][$languageID]["SECTION_NAME"] = GetMessage($code."_SECTION_NAME");
	}

	$iblockType->Add($arType);
}

?>