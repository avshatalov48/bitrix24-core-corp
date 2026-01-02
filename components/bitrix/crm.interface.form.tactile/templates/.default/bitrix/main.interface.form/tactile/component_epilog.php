<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

CUtil::InitJSCore(array('window', 'ajax'));
$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/popup_menu.js');

\Bitrix\Main\UI\Extension::load('intranet.old-interface.form');

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/pubstyles.css');
if($arResult["OPTIONS"]["theme"] <> '')
	$APPLICATION->SetAdditionalCSS($templateFolder.'/themes/'.$arResult["OPTIONS"]["theme"].'/style.css');
?>
