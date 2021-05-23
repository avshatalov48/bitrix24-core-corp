<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("controller"))
	return;

$arUGroupsEx = Array();
$dbUGroups = CGroup::GetList($by = "c_sort", $order = "asc");
while($arUGroups = $dbUGroups -> Fetch())
{
	$arUGroupsEx[$arUGroups["ID"]] = $arUGroups["NAME"];
}

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"SITE_URL" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_SITE_URL"),
			"TYPE" => "STRING",
			"DEFAULT" => "={\$_REQUEST[\"site_url\"]}",
		),
		"COMMAND" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_COMMAND"),
			"TYPE" => "STRING",
			"DEFAULT" => "={\$_REQUEST[\"command\"]}",
		),
		"ACTION" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_ACTION"),
			"TYPE" => "STRING",
			"DEFAULT" => "={\$_REQUEST[\"action\"]}",
		),
		"SEPARATOR" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_SEPARATOR"),
			"TYPE" => "STRING",
			"DEFAULT" => ",",
		),
		"ACCESS_RESTRICTION" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_ACCESS_RESTRICTION"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"GROUP"=>GetMessage("CP_BCSC_BY_GROUP"),
				"IP"=>GetMessage("CP_BCSC_BY_IP"),
				"NONE"=>GetMessage("MAIN_NO"),
			),
			"DEFAULT" => array("GROUP"),
			"REFRESH" => "Y",
		),
		"IP_PERMISSIONS" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_IP_PERMISSIONS"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),
		"GROUP_PERMISSIONS" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCSC_GROUP_PERMISSIONS"),
			"TYPE" => "LIST",
			"VALUES" => $arUGroupsEx,
			"DEFAULT" => array(1),
			"MULTIPLE" => "Y",
		),
	),
);
if($arCurrentValues["ACCESS_RESTRICTION"]=="NONE")
{
	unset($arComponentParameters["PARAMETERS"]["GROUP_PERMISSIONS"]);
	unset($arComponentParameters["PARAMETERS"]["IP_PERMISSIONS"]);
}
elseif($arCurrentValues["ACCESS_RESTRICTION"]=="IP")
	unset($arComponentParameters["PARAMETERS"]["GROUP_PERMISSIONS"]);
else
	unset($arComponentParameters["PARAMETERS"]["IP_PERMISSIONS"]);
?>
