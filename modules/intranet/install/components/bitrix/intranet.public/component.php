<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!IsModuleInstalled("intranet"))
{
	ShowError(GetMessage("INTRANET_MODULE_NOT_INSTALL"));
	return;
}

$arDefaultUrlTemplates404 = array(
	"index" => "index.php",
	"post" => "post/#post_id#/",
	"task" => "task/#task_id#/",
);

$arDefaultUrlTemplatesN404 = array(
	"post" => "page=post&post_id=#post_id#",
	"task" => "page=task&task_id=#task_id#",
);
$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();
$componentPage = "";
$arComponentVariables = array("post_id", "task_id");

$arCustomPagesPath = array();

if ($arParams["SEF_MODE"] == "Y")
{
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, array());
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, array());

	$componentPage = CComponentEngine::ParseComponentPath($arParams["SEF_FOLDER"], $arUrlTemplates, $arVariables);

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
	{
		$componentPage = $arVariables["page"];
	}

	if (
		empty($componentPage)
		|| (!array_key_exists($componentPage, $arDefaultUrlTemplates404))
	)
	{
		$componentPage = "index";
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$arResult["PATH_TO_".strToUpper($url)] = $arParams["SEF_FOLDER"].$value;
	}

	if ($_REQUEST["auth"] == "Y")
	{
		$componentPage = "auth";
	}
}
else
{
	$componentPage = "index";
}

$arResult = array_merge(
	array(
		"SEF_MODE" => $arParams["SEF_MODE"],
		"SEF_FOLDER" => $arParams["SEF_FOLDER"],
		"VARIABLES" => $arVariables,
		"ALIASES" => $arParams["SEF_MODE"] == "Y" ? array() : $arVariableAliases,
		"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_TIME_LONG" => $arParams["CACHE_TIME_LONG"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"]
	),
	$arResult
);

$arParams["ERROR_MESSAGE"] = "";
$arParams["NOTE_MESSAGE"] = "";

CUtil::InitJSCore(array("window", "ajax"));
$this->IncludeComponentTemplate($componentPage, array_key_exists($componentPage, $arCustomPagesPath) ? $arCustomPagesPath[$componentPage] : "");
?>
