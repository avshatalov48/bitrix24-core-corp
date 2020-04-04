<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:intranet.structure.birthday.nearest", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"STRUCTURE_PAGE"=>$arComponentProps["PARAMETERS"]["STRUCTURE_PAGE"],
			"PM_URL"=>$arComponentProps["PARAMETERS"]["PM_URL"],
			"SHOW_YEAR"=>$arComponentProps["PARAMETERS"]["SHOW_YEAR"],
			"USER_PROPERTY"=>$arComponentProps["PARAMETERS"]["USER_PROPERTY"],
			"LIST_URL"=> Array(
				"NAME" => GetMessage("GD_BIRTHDAY_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/company/birthdays.php",
			),
		),
		"USER_PARAMETERS"=> Array(
			"NUM_USERS"=>$arComponentProps["PARAMETERS"]["NUM_USERS"],
		),
	);

$arDepartments = Array();
$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
if($arUserFields["UF_DEPARTMENT"]["SETTINGS"]["IBLOCK_ID"]>0)
{
	$dbRes = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$arUserFields["UF_DEPARTMENT"]["SETTINGS"]["IBLOCK_ID"], "GLOBAL_ACTIVE"=>"Y"));

	$arDepartments["-"] = GetMessage("GD_BIRTHDAY_P_ALL");
	while($arRes = $dbRes->GetNext())
		$arDepartments[$arRes["ID"]] = str_repeat(". ", $arRes["DEPTH_LEVEL"]).$arRes["NAME"];

	$arParameters["USER_PARAMETERS"]["DEPARTMENT"] = Array(
				"NAME" => GetMessage("GD_BIRTHDAY_P_DEP"),
				"TYPE" => "LIST",
				"VALUES" => $arDepartments,
				"MULTIPLE" => "N",
				"DEFAULT" => "",
			);
}

$arParameters["USER_PARAMETERS"]["NUM_USERS"]["DEFAULT"] = 5;
?>
