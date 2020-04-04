<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!IsModuleInstalled("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
elseif (!IsModuleInstalled("iblock")):
	ShowError(GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
	return 0;
endif;
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["PERMISSION"] = strToUpper(trim($arParams["PERMISSION"]));
	$arParams["BASE_URL"] = trim($arParams["BASE_URL"]);
/***************** STANDART ****************************************/
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "Y" ? "Y" : "N"); //Turn on by default
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/
$arBPHelp = array(
	'/services/help/bp_help.php',
	'/docs/bp_help.php',
);

$arResult['BPHELP'] = '';
foreach ($arBPHelp as $file)
{
	$fName = $_SERVER['DOCUMENT_ROOT'].$file;
	if (file_exists($fName))
	{
		$arResult['BPHELP'] = $file;
		break;
	}
}

if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_TITLE"));
}
if($arParams["SET_NAV_CHAIN"] == "Y")
{
    $APPLICATION->AddChainItem(GetMessage("WD_TITLE"));
}
if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());

$this->IncludeComponentTemplate();
?>
