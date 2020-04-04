<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:intranet.structure.informer.absent", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"LIST_URL"=> Array(
				"NAME" => GetMessage("GD_ABSENT_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/company/absence.php",
			),
		),
		"USER_PARAMETERS"=> Array(
			"NUM_USERS"=>$arComponentProps["PARAMETERS"]["NUM_USERS"],
		),
	);

$arParameters["USER_PARAMETERS"]["NUM_USERS"]["DEFAULT"] = 5;

$arDepartments = Array();
$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
if($arUserFields["UF_DEPARTMENT"]["SETTINGS"]["IBLOCK_ID"]>0)
{
	$dbRes = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$arUserFields["UF_DEPARTMENT"]["SETTINGS"]["IBLOCK_ID"], "GLOBAL_ACTIVE"=>"Y"));

	$arDepartments["-"] = GetMessage("GD_ABSENT_P_ALL");
	while($arRes = $dbRes->GetNext())
		$arDepartments[$arRes["ID"]] = str_repeat(". ", $arRes["DEPTH_LEVEL"]).$arRes["NAME"];

	$arParameters["USER_PARAMETERS"]["DEPARTMENT"] = Array(
				"NAME" => GetMessage("GD_ABSENT_P_DEP"),
				"TYPE" => "LIST",
				"VALUES" => $arDepartments,
				"MULTIPLE" => "N",
				"DEFAULT" => "",
			);
}

?>
