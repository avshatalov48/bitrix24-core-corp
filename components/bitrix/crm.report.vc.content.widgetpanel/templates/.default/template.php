<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

CJSCore::RegisterExt('crm_common', [
	'js' => '/bitrix/js/crm/common.js',
	'css' => '/bitrix/js/crm/css/crm.css',
]);

CJSCore::Init(['crm_common']);

?>
<div id="report-widget-panel-container">
<?
$APPLICATION->IncludeComponent(
	'bitrix:crm.widget_panel',
	'',
	$arResult['WIDGET_PANEL_PARAMS']
);
?>
</div>
