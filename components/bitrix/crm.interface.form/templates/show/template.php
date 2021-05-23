<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

$tactileFormID = isset($arParams['~TACTILE_FORM_ID']) ? $arParams['~TACTILE_FORM_ID'] : '';
if($tactileFormID !== '')
{
	$tactileTabs = $arParams['~TABS'];
	foreach($tactileTabs as &$tab)
	{
		if(!(isset($tab['fields']) && is_array($tab['fields'])))
		{
			continue;
		}

		foreach($tab['fields'] as $key => $field)
		{
			if(!(isset($field['isTactile']) && $field['isTactile']))
			{
				unset($tab['fields'][$key]);
			}
		}
		$tab['fields'] = array_values($tab['fields']);
	}
	unset($tab);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.form.tactile',
		'',
		array(
			'IS_NEW' => isset($arParams['~IS_NEW']) ? $arParams['~IS_NEW'] : 'Y',
			'MODE'=> 'VIEW',
			'TITLE' => isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '',
			'FORM_ID' => $tactileFormID,
			'DATA' => $arParams['~DATA'],
			'TABS' => $tactileTabs,
			'BUTTONS' => $arParams['~BUTTONS'],
			'FIELD_SETS' => isset($arParams['~FIELD_SETS']) ? $arParams['~FIELD_SETS'] : array(),
			'ENABLE_USER_FIELD_CREATION' => 'N',
			'ENABLE_SECTION_CREATION' => 'N',
			'SHOW_SETTINGS' => 'Y',
			'CUSTOM_FORM_SETTINGS_COMPONENT_PATH'=> isset($arParams['CUSTOM_FORM_SETTINGS_COMPONENT_PATH']) ?
				strval($arParams['CUSTOM_FORM_SETTINGS_COMPONENT_PATH']) : null,
			'ENABLE_IN_SHORT_LIST_OPTION' => isset($arParams['~ENABLE_IN_SHORT_LIST_OPTION']) ? $arParams['~ENABLE_IN_SHORT_LIST_OPTION'] : 'N'
		),
		$component, array('HIDE_ICONS' => 'Y')
	);
}

if (!isset($arParams['SHOW_TABS']) || $arParams['SHOW_TABS'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.form',
		'crm.view',
		array(
			'FORM_ID' => $arParams['~FORM_ID'],
			'THEME_GRID_ID' => $arParams['~GRID_ID'],
			'TABS' => $arParams['~TABS'],
			'TABS_EXT' => $arParams['~TABS_EXT'],
			'BUTTONS' => array('standard_buttons' =>  false),
			'DATA' => $arParams['~DATA'],
			'FIELD_LIMIT' => isset($arParams['~FIELD_LIMIT']) ? $arParams['~FIELD_LIMIT'] : 5,
			'SHOW_SETTINGS' => isset($arParams['~SHOW_SETTINGS']) ? $arParams['~SHOW_SETTINGS'] : 'Y',
			'CUSTOM_FORM_SETTINGS_COMPONENT_PATH'=> isset($arParams['CUSTOM_FORM_SETTINGS_COMPONENT_PATH']) ?
				strval($arParams['CUSTOM_FORM_SETTINGS_COMPONENT_PATH']) : null,
			'ENABLE_IN_SHORT_LIST_OPTION' => isset($arParams['~ENABLE_IN_SHORT_LIST_OPTION']) ? $arParams['~ENABLE_IN_SHORT_LIST_OPTION'] : 'N',
			'SHOW_FORM_TAG' => 'N'
		),
		$component, array('HIDE_ICONS' => 'Y')
	);
}
?>