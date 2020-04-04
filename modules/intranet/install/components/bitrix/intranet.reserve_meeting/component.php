<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("intranet"))
{
	ShowError(GetMessage("INTERANET_MODULE_NOT_INSTALL"));
	return;
}

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat();
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

if (!array_key_exists("PATH_TO_USER", $arParams))
	$arParams["PATH_TO_USER"] = COption::GetOptionString('intranet', 'search_user_url', '/company/personal/user/#USER_ID#/');
if (!array_key_exists("PM_URL", $arParams))
	$arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (IsModuleInstalled("video") && !array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#USER_ID#/";

$arDefaultUrlTemplates404 = array(
	"index" => "index.php",
	"meeting" => "meeting/#meeting_id#/",
	"modify_meeting" => "meeting/#meeting_id#/modify/",
	"reserve_meeting" => "meeting/#meeting_id#/reserve/#item_id#/",
	"view_item" => "meeting/#meeting_id#/view/#item_id#/",
	"search" => "search/",
);

$arDefaultUrlTemplatesN404 = array();
$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();
$componentPage = "";
$arComponentVariables = array("meeting_id", "item_id", "page", "action");

if ($arParams["SEF_MODE"] == "Y")
{
	$arVariables = array();

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

	$componentPage = CComponentEngine::ParseComponentPath($arParams["SEF_FOLDER"], $arUrlTemplates, $arVariables);

	if (empty($componentPage))
		$componentPage = "index";

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
		$arResult["PATH_TO_".strToUpper($url)] = $arParams["SEF_FOLDER"].$value;
	$arResult["PATH_TO_MEETING_LIST"] = $arParams["SEF_FOLDER"].$arUrlTemplates["index"];
}
else
{
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
		$componentPage = $arVariables["page"];

	if (empty($componentPage))
		$componentPage = "index";
}

$arResult = Array_Merge(
	array(
		"SEF_MODE" => $arParams["SEF_MODE"],
		"SEF_FOLDER" => $arParams["SEF_FOLDER"],
		"VARIABLES" => $arVariables,
		"ALIASES" => $arParams["SEF_MODE"] == "Y"? array(): $arVariableAliases,
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_NAVCHAIN" => $arParams["SET_NAVCHAIN"],
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"USERGROUPS_MODIFY" => $arParams["USERGROUPS_MODIFY"],
		"USERGROUPS_RESERVE" => $arParams["USERGROUPS_RESERVE"],
		"USERGROUPS_CLEAR" => $arParams["USERGROUPS_CLEAR"],
		"WEEK_HOLIDAYS" => $arParams["WEEK_HOLIDAYS"],
	),
	$arResult
);

$this->IncludeComponentTemplate($componentPage);
?>