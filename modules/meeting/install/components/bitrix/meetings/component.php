<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("meeting"))
{
	ShowError(GetMessage("M_MODULE_NOT_INSTALLED"));
	return;
}

$arParams['USER_ID'] = $USER->GetID();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arDefaultUrlTemplates404 = array(
	"list" => "",
	"meeting" => "meeting/#MEETING_ID#/",
	"meeting_edit" => "meeting/#MEETING_ID#/edit/",
	"meeting_copy" => "meeting/#MEETING_ID#/copy/",
	"item" => "item/#ITEM_ID#/",
);

$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();

$arComponentVariables = array(
	"MEETING_ID",
	"ITEM_ID",
	"edit",
	"COPY",
);

$componentPage = "";

$arVariables = array();

if($arParams["SEF_MODE"] == "Y")
{
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams["SEF_FOLDER"],
		$arUrlTemplates,
		$arVariables
	);

	if(!$componentPage)
		$componentPage = "list";

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
	$arResult = array(
		"FOLDER" => $arParams["SEF_FOLDER"],
		"URL_TEMPLATES" => $arUrlTemplates,
		"VARIABLES" => $arVariables,
		"ALIASES" => $arVariableAliases
	);
}

if ($componentPage == 'meeting_edit')
{
	$componentPage = 'meeting';
	$arParams['EDIT'] = 'Y';
}
elseif ($componentPage == 'meeting_copy')
{
	$componentPage = 'meeting';
	$arParams['COPY'] = 'Y';
}

switch($componentPage)
{
	case 'meeting':
		$arParams['MEETING_ID'] = $arVariables['MEETING_ID'];
	break;
	case 'item':
		$arParams['ITEM_ID'] = $arVariables['ITEM_ID'];
	break;
}

$arParams["LIST_URL"] = CComponentEngine::MakePathFromTemplate($arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["list"], $arVariables);

$arParams["MEETING_URL"] = CComponentEngine::MakePathFromTemplate($arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["meeting"], $arVariables);
$arParams["MEETING_URL_TPL"] = $arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["meeting"];
$arParams["MEETING_ADD_URL"] = CComponentEngine::MakePathFromTemplate($arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["meeting_edit"], array('MEETING_ID' => 0));
$arParams["MEETING_EDIT_URL"] = CComponentEngine::MakePathFromTemplate($arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["meeting_edit"], $arVariables);
$arParams["MEETING_EDIT_URL_TPL"] = $arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["meeting_edit"];
$arParams["MEETING_COPY_URL"] = CComponentEngine::MakePathFromTemplate($arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["meeting_copy"], $arVariables);

$arParams["ITEM_URL"] = CComponentEngine::MakePathFromTemplate($arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["item"], $arVariables);

if ($componentPage != 'list' && $arParams['SET_NAVCHAIN'] !== 'N')
{
	\Bitrix\Main\Localization\Loc::loadLanguageFile(dirname(__FILE__)."/.description.php");
	$APPLICATION->AddChainItem(GetMessage('MEETINGS_NAME'), $arParams['LIST_URL']);
}

$this->IncludeComponentTemplate($componentPage);
?>