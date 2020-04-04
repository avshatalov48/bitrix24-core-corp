<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
$sTplDir = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/")));

include($sTplDir."tab_section.php");
if (! isset($arSection))
	$arSection = null;

$ob =&$arParams['OBJECT'];
$bShowPermissions = false;
if ($ob->e_rights)
{
	if (isset($arSection['ID']) && (intval($arSection['ID'])>0))
		$bShowPermissions = $ob->GetPermission('SECTION', $arSection['ID'], 'section_rights_edit');
}

if ($bShowPermissions)
	include($sTplDir."tab_permissions.php");

if (!$arParams["FORM_ID"]) $arParams["FORM_ID"] = "section";
$APPLICATION->IncludeComponent(
    "bitrix:main.interface.form",
    "",
    array(
        "FORM_ID" => $arParams["FORM_ID"],
        "SHOW_FORM_TAG" => "N",
        "TABS" => $this->__component->arResult['TABS'],
        "DATA" => $this->__component->arResult['DATA'],
		"SHOW_SETTINGS" => false
    ),
    ($this->__component->__parent ? $this->__component->__parent : $component)
);
?>
