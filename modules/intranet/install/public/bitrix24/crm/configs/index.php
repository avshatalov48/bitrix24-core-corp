<?
/** @global CMain $APPLICATION */

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/configs/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));

$APPLICATION->includeComponent('bitrix:crm.control_panel', '',
	array(
		'ID' => 'CONFIG',
		'ACTIVE_ITEM_ID' => 'SETTINGS'
	),
	$component
);

$APPLICATION->includeComponent('bitrix:crm.configs', '', array('SHOW_TITLE' => 'N'), $component);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
