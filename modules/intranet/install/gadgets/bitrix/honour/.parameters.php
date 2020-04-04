<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:intranet.structure.honour", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"LIST_URL"=> Array(
				"NAME" => GetMessage("GD_HONOUR_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/company/leaders.php",
			),
		),
		"USER_PARAMETERS"=> Array(
			"NUM_USERS"=>$arComponentProps["PARAMETERS"]["NUM_USERS"],
		),
	);

$arParameters["USER_PARAMETERS"]["NUM_USERS"]["DEFAULT"] = 5;
?>
