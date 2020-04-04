<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_props',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE').' '.$arResult['TAX']['NAME'],
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_props']
);

if(isset($arResult['FIELDS']['tab_rateslist']))
{
	$arTabs[] = array(
		'id' => 'tab_rateslist',
		'name' => GetMessage('CRM_TAB_2'),
		'title' => GetMessage('CRM_TAB_2_TITLE').' '.$arResult['TAX']['NAME'],
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_rateslist']
	);
}

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
$formCustomHtml = '<input type="hidden" name="tax_id" value="'.$arResult['TAX_ID'].'"/>';
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'DATA' => $arResult['TAX'],
		'SHOW_SETTINGS' => 'Y',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>