<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>

<?


$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_CONVERT'),
	'title' => GetMessage('CRM_TAB_CONVERT_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_convert']
);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL']
		),
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'N',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>