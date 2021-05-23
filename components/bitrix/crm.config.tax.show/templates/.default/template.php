<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_props',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_props']
);

$arTabs[] = array(
	'id' => 'tab_rateslist',
	'name' => GetMessage('CRM_TAB_2'),
	'title' => GetMessage('CRM_TAB_2_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_rateslist']
);

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'DATA' => $arResult['TAX'],
		'SHOW_SETTINGS' => 'Y',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

?>