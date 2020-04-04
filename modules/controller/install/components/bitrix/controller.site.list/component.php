<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//Check if user have rights to see and login
if(!is_array($arParams["GROUP_PERMISSIONS"]))
	$arParams["GROUP_PERMISSIONS"] = array(1);

$bUSER_HAVE_ACCESS = false;

if(isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]))
{
	$MOD_RIGHT = $APPLICATION->GetGroupRight("controller");
	if($MOD_RIGHT >= "V")
	{
		$bUSER_HAVE_ACCESS = true;
	}
	else
	{
		$arRIGHTS = $APPLICATION->GetUserRoles("controller");
		if(in_array("L", $arRIGHTS))
			$bUSER_HAVE_ACCESS = true;
	}
}

if(!$bUSER_HAVE_ACCESS)
	return;

//Set menu title from parameter or from lang file
if(isset($arParams["TITLE"]))
{
	$arParams["TITLE"] = trim($arParams["TITLE"]);
	if(strlen($arParams["TITLE"]) <= 0)
		$arParams["TITLE"] = GetMessage("CC_BCSL_TITLE_DEFAULT");
}
else
{
	$arParams["TITLE"] = GetMessage("CC_BCSL_TITLE_DEFAULT");
}

//Component execution with cache support
if($this->StartResultCache())
{
	if(!CModule::IncludeModule("controller"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("CC_BCSL_MODULE_NOT_INSTALLED"));
		return;
	}

	$arResult["TITLE"] = $arParams["TITLE"];
	$arResult["ITEMS"] = array();
	$arResult["MENU_ITEMS"] = array();

	$arFilter = array(
		"=ACTIVE" => "Y",
		"=DISCONNECTED" => "N",
	);
	if(count($arParams["GROUP"]))
		$arFilter["=CONTROLLER_GROUP_ID"] = $arParams["GROUP"];

	$rsMembers = CControllerMember::GetList(Array("ID" => "ASC"), $arFilter);
	while($arMember = $rsMembers->GetNext())
	{
		$arResult["ITEMS"][] = $arMember;
		$arResult["MENU_ITEMS"][] = array(
			"ICONCLASS" => 'site-list-icon',
			"TEXT" => $arMember["NAME"],
			"ONCLICK" => 'window.location = \'/bitrix/admin/controller_goto.php?member='.$arMember["ID"].'&lang='.LANGUAGE_ID.'\';',
			"TITLE" => $arMember["URL"],
		);
	}

	$this->SetResultCacheKeys(array());
	$this->IncludeComponentTemplate();
}

$js = '/bitrix/js/main/utils.js';
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.$js.'?'.filemtime($_SERVER['DOCUMENT_ROOT'].$js).'"></script>');

$js = '/bitrix/js/main/popup_menu.js';
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.$js.'?'.filemtime($_SERVER['DOCUMENT_ROOT'].$js).'"></script>');

?>
